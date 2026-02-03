# UpCloud Object Storage for Craft CMS

Seamlessly integrate UpCloud's high-performance Managed Object Storage with your Craft CMS projects. This plugin provides a native filesystem adapter that allows you to store and manage your Craft assets on UpCloud's S3-compatible object storage.

## Why UpCloud Object Storage?

- **🚀 High Performance**: Built on UpCloud's premium infrastructure with low-latency access
- **🌍 Global Availability**: Regions in Europe, US, and Asia-Pacific
- **💰 Cost-Effective**: Competitive pricing with no data transfer fees within UpCloud network
- **🔒 Secure**: Full support for private files with signed URLs
- **⚡ S3-Compatible**: Standard S3 API compatibility for seamless integration

## Features

- ✅ Full Craft CMS 4 & 5 support
- ✅ Public and private file uploads with ACL control
- ✅ Signed URLs for secure temporary access to private files
- ✅ Perfect for medical records, documents, and sensitive data
- ✅ Multipart uploads for large files
- ✅ Subfolder support within buckets
- ✅ Environment variable configuration
- ✅ Easy bucket selection with refresh functionality

## Use Cases

### Medical & Healthcare Applications
Store patient records, medical imaging, and sensitive documents with private access. Use signed URLs to provide temporary access without making files permanently public.

### E-commerce & Digital Assets
Manage product images, downloadable files, and user-generated content with high-performance delivery across multiple regions.

### Media & Publishing
Store and deliver large media files with UpCloud's fast infrastructure and global CDN integration.

## Quick Setup

1. Create an UpCloud Object Storage instance
2. Generate access keys
3. Install the plugin: `composer require dulce/upcloud`
4. Configure your filesystem in Settings → Filesystems
5. Start uploading!

## Private Files & Signed URLs

Perfect for applications requiring secure file access:

```twig
{# Display PDF with temporary signed URL #}
<iframe src="{{ asset.getUrl({
  ResponseContentDisposition: 'inline; filename="document.pdf"'
}) }}" width="100%" height="800px"></iframe>
```

Files remain private while allowing controlled, temporary access.

## Documentation

Full documentation available on [GitHub](https://github.com/mauriciodulce/craft-upcloud), including:
- Detailed setup instructions
- Bucket policy examples
- Environment variable configuration
- Private file handling
- Troubleshooting guide

## Support

Found a bug or need help? Open an issue on [GitHub](https://github.com/mauriciodulce/craft-upcloud/issues).

---

**Note**: This plugin requires an active UpCloud Object Storage instance. 

**New to UpCloud?** [Sign up here](https://signup.upcloud.com/?promo=5H4SQS) and get **$25 in free credits** to get started with UpCloud Object Storage!
