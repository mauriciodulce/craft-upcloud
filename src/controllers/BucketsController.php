<?php

namespace dulce\upcloud\controllers;

use Craft;
use dulce\upcloud\Fs;
use craft\helpers\App;
use craft\web\Controller as BaseController;
use yii\web\Response;

/**
 * This controller provides functionality to load data from UpCloud.
 *
 * @author Mauricio Dulce
 * @since 1.0
 */
class BucketsController extends BaseController
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        $this->defaultAction = 'load-bucket-data';
    }

    /**
     * Load bucket data for specified credentials and endpoint.
     *
     * @return Response
     */
    public function actionLoadBucketData(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $keyId = App::parseEnv($request->getRequiredBodyParam('keyId'));
        $secret = App::parseEnv($request->getRequiredBodyParam('secret'));
        $endpoint = App::parseEnv($request->getRequiredBodyParam('endpoint'));

        try {
            return $this->asJson([
                'buckets' => Fs::loadBucketList($keyId, $secret, $endpoint),
            ]);
        } catch (\Throwable $e) {
            return $this->asFailure($e->getMessage());
        }
    }
}
