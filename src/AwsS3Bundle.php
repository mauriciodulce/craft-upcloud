<?php
/**
 * UpCloud Object Storage for Craft CMS
 * 
 * Forked from craftcms/aws-s3
 * @author Mauricio Dulce
 * @copyright Copyright (c) Mauricio Dulce
 * @license MIT
 */

namespace dulce\upcloud;

use craft\web\assets\cp\CpAsset;
use yii\web\AssetBundle;

/**
 * Asset bundle for UpCloud Object Storage
 */
class AwsS3Bundle extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@dulce/upcloud/resources';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'js/editVolume.js',
    ];
}
