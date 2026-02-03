<?php

declare(strict_types=1);
/**
 * UpCloud Object Storage for Craft CMS
 * 
 * Forked from craftcms/aws-s3
 * @author Mauricio Dulce
 * @copyright Copyright (c) Mauricio Dulce
 * @license MIT
 */

namespace dulce\upcloud;

use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Aws\Handler\Guzzle\GuzzleHandler;
use Aws\S3\Exception\S3Exception;
use Aws\Sts\StsClient;
use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\flysystem\base\FlysystemFs;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets;
use craft\helpers\DateTimeHelper;
use DateTime;
use InvalidArgumentException;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Visibility;

/**
 * Class Fs
 *
 * @property mixed $settingsHtml
 * @property string $rootUrl
 * @author Mauricio Dulce
 * @since 1.0
 */
class Fs extends FlysystemFs
{
    // Constants
    // =========================================================================

    public const STORAGE_STANDARD = 'STANDARD';
    public const STORAGE_REDUCED_REDUNDANCY = 'REDUCED_REDUNDANCY';
    public const STORAGE_STANDARD_IA = 'STANDARD_IA';

    /**
     * Cache key to use for caching purposes
     */
    public const CACHE_KEY_PREFIX = 'upcloud.';

    /**
     * Cache duration for access token
     */
    public const CACHE_DURATION_SECONDS = 3600;

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'UpCloud Object Storage';
    }

    // Properties
    // =========================================================================

    /**
     * @var string UpCloud Object Storage endpoint (e.g., https://yourendpoint.upcloudobjects.com)
     */
    public string $endpoint = '';

    /**
     * @var string Subfolder to use
     */
    public string $subfolder = '';

    /**
     * @var string Access Key ID
     */
    public string $keyId = '';

    /**
     * @var string Secret Access Key
     */
    public string $secret = '';

    /**
     * @var string Bucket selection mode ('choose' or 'manual')
     */
    public string $bucketSelectionMode = 'choose';

    /**
     * @var string Bucket to use
     */
    public string $bucket = '';

    /**
     * @var string Region (usually us-east-1 for UpCloud)
     */
    public string $region = 'us-east-1';

    /**
     * @var string Cache expiration period.
     */
    public string $expires = '';

    /**
     * @var bool Set ACL for Uploads
     */
    public bool $makeUploadsPublic = true;

    /**
     * @var string S3 storage class to use.
     * @deprecated in 1.1.1
     */
    public string $storageClass = '';

    /**
     * @var bool Whether the specified sub folder should be added to the root URL
     */
    public bool $addSubfolderToRootUrl = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        if (isset($config['manualBucket'])) {
            if (isset($config['bucketSelectionMode']) && $config['bucketSelectionMode'] === 'manual') {
                $config['bucket'] = ArrayHelper::remove($config, 'manualBucket');
                $config['region'] = ArrayHelper::remove($config, 'manualRegion');
            } else {
                unset($config['manualBucket'], $config['manualRegion']);
            }
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['parser'] = [
            'class' => EnvAttributeParserBehavior::class,
            'attributes' => [
                'endpoint',
                'keyId',
                'secret',
                'bucket',
                'region',
                'subfolder',
            ],
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['endpoint', 'bucket'], 'required'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('upcloud/fsSettings', [
            'fs' => $this,
            'periods' => array_merge(['' => ''], Assets::periodList()),
        ]);
    }

    /**
     * Get the bucket list using the specified credentials and endpoint.
     *
     * @param string|null $keyId The key ID
     * @param string|null $secret The key secret
     * @param string|null $endpoint The UpCloud endpoint
     * @return array
     * @throws InvalidArgumentException
     */
    public static function loadBucketList(?string $keyId, ?string $secret, ?string $endpoint): array
    {
        if (empty($endpoint)) {
            throw new InvalidArgumentException('UpCloud endpoint is required');
        }

        $config = self::buildConfigArray($keyId, $secret, 'us-east-1', false, $endpoint);

        $client = static::client($config);

        $objects = $client->listBuckets();

        if (empty($objects['Buckets'])) {
            return [];
        }

        $buckets = $objects['Buckets'];
        $bucketList = [];

        // Parse endpoint to get the base domain
        $parsedEndpoint = parse_url($endpoint);
        $endpointHost = $parsedEndpoint['host'] ?? $endpoint;

        foreach ($buckets as $bucket) {
            try {
                // For UpCloud, use virtual-hosted-style URLs
                // Format: https://bucket-name.endpoint.upcloudobjects.com/
                $urlPrefix = 'https://' . $bucket['Name'] . '.' . $endpointHost . '/';

                $bucketList[] = [
                    'bucket' => $bucket['Name'],
                    'urlPrefix' => $urlPrefix,
                    'region' => 'us-east-1', // UpCloud doesn't use AWS regions
                ];
            } catch (S3Exception $exception) {
                // If a bucket cannot be accessed by the current policy, move along
                continue;
            }
        }

        return $bucketList;
    }

    /**
     * @inheritdoc
     */
    public function getRootUrl(): ?string
    {
        $rootUrl = parent::getRootUrl();

        if ($rootUrl) {
            $rootUrl .= $this->_getRootUrlPath();
        }

        return $rootUrl;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return AwsS3V3Adapter
     */
    protected function createAdapter(): FilesystemAdapter
    {
        $client = static::client($this->_getConfigArray(), $this->_getCredentials());
        $options = [

            // This is the S3 default for all objects, but explicitly
            // sending the header allows for bucket policies that require it.
            // @see https://github.com/craftcms/aws-s3/pull/172
            'ServerSideEncryption' => 'AES256',
        ];

        return new AwsS3V3Adapter(
            $client,
            App::parseEnv($this->bucket),
            $this->_subfolder(),
            new PortableVisibilityConverter($this->visibility()),
            null,
            $options,
            false,
        );
    }

    /**
     * Get the Amazon S3 client.
     *
     * @param array $config client config
     * @param array $credentials credentials to use when generating a new token
     * @return S3Client
     */
    protected static function client(array $config = [], array $credentials = []): S3Client
    {
        if (!empty($config['credentials']) && $config['credentials'] instanceof Credentials) {
            $config['generateNewConfig'] = static function() use ($credentials) {
                $args = [
                    $credentials['keyId'],
                    $credentials['secret'],
                    $credentials['region'],
                    true,
                ];
                return call_user_func_array(self::class . '::buildConfigArray', $args);
            };
        }

        return new S3Client($config);
    }

    /**
     * @inheritdoc
     */
    protected function addFileMetadataToConfig(array $config): array
    {
        if (!empty($this->expires) && DateTimeHelper::isValidIntervalString($this->expires)) {
            $expires = new DateTime();
            $now = new DateTime();
            $expires->modify('+' . $this->expires);
            $diff = (int)$expires->format('U') - (int)$now->format('U');
            $config['CacheControl'] = 'max-age=' . $diff;
        }

        return parent::addFileMetadataToConfig($config);
    }

    /**
     * @inheritdoc
     */
    public function invalidateCdnPath(string $path): bool
    {
        // UpCloud Object Storage doesn't have CDN invalidation
        // This method is kept for compatibility but does nothing
        return true;
    }

    /**
     * Build the config array based on a keyID and secret
     *
     * @param ?string $keyId The key ID
     * @param ?string $secret The key secret
     * @param ?string $region The region to user
     * @param bool $refreshToken If true will always refresh token
     * @param ?string $endpoint Custom S3-compatible endpoint (e.g., for UpCloud)
     * @return array
     */
    public static function buildConfigArray(?string $keyId = null, ?string $secret = null, ?string $region = null, bool $refreshToken = false, ?string $endpoint = null): array
    {
        $config = [
            'region' => $region ?: 'us-east-1',
            'version' => 'latest',
        ];

        // Add custom endpoint if provided (for S3-compatible services like UpCloud)
        if (!empty($endpoint)) {
            $config['endpoint'] = $endpoint;
            $config['use_path_style_endpoint'] = true;
        }

        $client = Craft::createGuzzleClient();
        $config['http_handler'] = new GuzzleHandler($client);

        /** @noinspection MissingOrEmptyGroupStatementInspection */
        if (empty($keyId) || empty($secret)) {
            // Check for predefined access
            if (App::env('AWS_WEB_IDENTITY_TOKEN_FILE') && App::env('AWS_ROLE_ARN')) {
                // Check if anything is defined for a web identity provider (see: https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials_provider.html#assume-role-with-web-identity-provider)
                $provider = CredentialProvider::assumeRoleWithWebIdentityCredentialProvider();
                $provider = CredentialProvider::memoize($provider);
                $config['credentials'] = $provider;
            }
            // Check if running on ECS
            if (App::env('AWS_CONTAINER_CREDENTIALS_RELATIVE_URI')) {
                // Check if anything is defined for an ecsCredentials provider
                $provider = CredentialProvider::ecsCredentials();
                $provider = CredentialProvider::memoize($provider);
                $config['credentials'] = $provider;
            }
            // If that didn't happen, assume we're running on EC2 and we have an IAM role assigned so no action required.
        } else {
            $tokenKey = static::CACHE_KEY_PREFIX . md5($keyId . $secret);
            $credentials = new Credentials($keyId, $secret);

            if (Craft::$app->cache->exists($tokenKey) && !$refreshToken) {
                $cached = Craft::$app->cache->get($tokenKey);
                $credentials->unserialize($cached);
            } else {
                $config['credentials'] = $credentials;
                
                // Use STS endpoint for UpCloud if custom endpoint is provided
                $stsConfig = $config;
                if (!empty($endpoint)) {
                    // Replace the S3 endpoint with STS endpoint for UpCloud
                    $stsConfig['endpoint'] = str_replace(
                        ['upcloudobjects.com', ':443'],
                        ['upcloudobjects.com:4443/sts', ''],
                        $endpoint
                    );
                }
                
                $stsClient = new StsClient($stsConfig);
                $result = $stsClient->getSessionToken(['DurationSeconds' => static::CACHE_DURATION_SECONDS]);
                $credentials = $stsClient->createCredentials($result);
                $cacheDuration = $credentials->getExpiration() - time();
                $cacheDuration = $cacheDuration > 0 ? $cacheDuration : static::CACHE_DURATION_SECONDS;
                Craft::$app->cache->set($tokenKey, $credentials->serialize(), $cacheDuration);
            }

            // TODO Add support for different credential supply methods
            $config['credentials'] = $credentials;
        }

        return $config;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the parsed subfolder path
     *
     * @return string
     */
    private function _subfolder(): string
    {
        if ($this->subfolder && ($subfolder = rtrim(Craft::parseEnv($this->subfolder), '/')) !== '') {
            return $subfolder . '/';
        }

        return '';
    }

    /**
     * Returns the root path for URLs
     *
     * @return string
     */
    private function _getRootUrlPath(): string
    {
        if ($this->addSubfolderToRootUrl) {
            return $this->_subfolder();
        }
        return '';
    }

    /**
     * Get the config array for UpCloud S3 Clients.
     *
     * @return array
     */
    private function _getConfigArray(): array
    {
        $credentials = $this->_getCredentials();

        return self::buildConfigArray(
            $credentials['keyId'],
            $credentials['secret'],
            $credentials['region'],
            false,
            $credentials['endpoint']
        );
    }

    /**
     * Return the credentials as an array
     *
     * @return array
     */
    private function _getCredentials(): array
    {
        return [
            'keyId' => Craft::parseEnv($this->keyId),
            'secret' => Craft::parseEnv($this->secret),
            'region' => Craft::parseEnv($this->region) ?: 'us-east-1',
            'endpoint' => Craft::parseEnv($this->endpoint),
        ];
    }

    /**
     * Returns the visibility setting for the Fs.
     *
     * @return string
     */
    protected function visibility(): string
    {
        return $this->makeUploadsPublic ? Visibility::PUBLIC : Visibility::PRIVATE;
    }
}
