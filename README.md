# UpCloud Object Storage for Craft CMS

This plugin provides [UpCloud Managed Object Storage](https://upcloud.com/products/object-storage/) integration for [Craft CMS](https://craftcms.com/).

**Note:** This is a fork of the [craftcms/aws-s3](https://github.com/craftcms/aws-s3) plugin, specifically adapted for UpCloud Object Storage.

## Requirements

This plugin requires Craft CMS 4.0.0+ or 5.0.0+.

## Installation

You can install this plugin with Composer.

### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require dulce/upcloud

# tell Craft to install the plugin
./craft plugin/install upcloud
```

## Setup

To create a new UpCloud Object Storage filesystem to use with your volumes, visit **Settings** → **Filesystems**, and press **New filesystem**. Select "UpCloud Object Storage" for the **Filesystem Type** setting and configure as needed.

### Getting Your UpCloud Credentials

1. Log in to your [UpCloud Control Panel](https://hub.upcloud.com/)
2. Navigate to **Object Storage** in the sidebar
3. Create a new Object Storage instance or select an existing one
4. Note your **Endpoint URL** (e.g., `https://abc123.upcloudobjects.com`)
5. Create a user for your storage instance
6. Generate **Access Keys** for that user
7. Save the **Access Key ID** and **Secret Access Key** (the secret is only shown once!)

### Configuration Fields

- **UpCloud Endpoint** (required): Your UpCloud Object Storage endpoint URL
  - Example: `https://abc123.upcloudobjects.com`
  - You can find this in your UpCloud Control Panel under your Object Storage instance
  
- **Access Key ID** (required): Your UpCloud Object Storage access key
  
- **Secret Access Key** (required): Your UpCloud Object Storage secret key
  
- **Bucket**: Select from available buckets or enter manually
  - Use the "Refresh" button to load buckets from your UpCloud account
  - Or choose "Manual" to enter bucket name directly
  
- **Subfolder** (optional): Specify a subfolder within the bucket to use as the filesystem root
  
- **Region**: Usually `us-east-1` (UpCloud uses this for S3 API compatibility)

> 💡 **Tip:** All configuration values support environment variables. See [Environmental Configuration](https://craftcms.com/docs/4.x/config/#environmental-configuration) in the Craft docs to learn more.

### Example Configuration

```php
// config/upcloud.php
return [
    'endpoint' => getenv('UPCLOUD_ENDPOINT'),
    'keyId' => getenv('UPCLOUD_ACCESS_KEY'),
    'secret' => getenv('UPCLOUD_SECRET_KEY'),
    'bucket' => getenv('UPCLOUD_BUCKET'),
    'region' => 'us-east-1',
];
```

### Environment Variables (.env)

```env
UPCLOUD_ENDPOINT="https://abc123.upcloudobjects.com"
UPCLOUD_ACCESS_KEY="your-access-key-id"
UPCLOUD_SECRET_KEY="your-secret-access-key"
UPCLOUD_BUCKET="your-bucket-name"
```

## UpCloud Object Storage Features

UpCloud Managed Object Storage is fully S3-compatible and supports:

- ✅ Standard bucket operations (create, delete, list)
- ✅ Object operations (upload, download, delete, copy)
- ✅ Multipart uploads for large files
- ✅ Access Control Lists (ACLs)
- ✅ Bucket policies
- ✅ Object versioning
- ✅ Lifecycle management
- ✅ CORS configuration
- ✅ Presigned URLs
- ✅ Virtual-hosted-style bucket URLs

### Available Regions

UpCloud Object Storage is available in the following regions:

- **Europe:**
  - `europe-1` (Helsinki, Finland)
  - `europe-2` (Frankfurt, Germany)
  - `europe-3` (Stockholm, Sweden)
  
- **United States:**
  - `us-1` (Chicago, Illinois)
  
- **Asia-Pacific:**
  - `apac-1` (Singapore)

## Differences from AWS S3

This plugin is specifically designed for UpCloud and has the following differences from the AWS S3 plugin:

- ✅ **Endpoint Required**: You must specify your UpCloud endpoint
- ✅ **Simplified Configuration**: No CloudFront or AWS-specific features
- ❌ **No CloudFront**: UpCloud doesn't use CloudFront for CDN
- ❌ **No Rekognition**: Automatic focal point detection is not available
- ❌ **No IAM Roles**: UpCloud uses access keys for authentication

## Troubleshooting

### Buckets not loading

Make sure you:
1. Have entered the correct endpoint URL (including `https://`)
2. Have entered valid access key credentials
3. Have created at least one bucket in your UpCloud Object Storage instance
4. The user has permission to list buckets

### Upload errors

Check that:
1. The bucket name is correct
2. The access key has write permissions
3. The bucket exists in your UpCloud Object Storage instance
4. Your endpoint URL is correct

### URL generation issues

UpCloud uses virtual-hosted-style URLs in the format:
```
https://[bucket-name].[endpoint-host]/[object-key]
```

Make sure your Base URL is configured correctly in the filesystem settings.

## Resources

- [UpCloud Object Storage Documentation](https://upcloud.com/docs/products/managed-object-storage/)
- [UpCloud S3 Compatibility](https://upcloud.com/docs/products/managed-object-storage/s3-standard-compatibility/)
- [Getting Started with UpCloud Object Storage](https://upcloud.com/docs/guides/get-started-managed-object-storage/)
- [Craft CMS Filesystem Documentation](https://craftcms.com/docs/4.x/assets.html)

## License

This plugin is licensed under the MIT License. See [LICENSE.md](LICENSE.md) for details.

## Credits

- **Author**: Mauricio Dulce
- **Based on**: [craftcms/aws-s3](https://github.com/craftcms/aws-s3) by Pixel & Tonic
- **UpCloud**: [upcloud.com](https://upcloud.com)

## Support

For issues and questions about this plugin, please open an issue on [GitHub](https://github.com/dulce/craft-aws-s3/issues).

For UpCloud Object Storage support, contact [UpCloud Support](https://upcloud.com/support/).
