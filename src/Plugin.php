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

use craft\base\Element;
use craft\elements\Asset;
use craft\events\ModelEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\ReplaceAssetEvent;
use craft\services\Assets;
use craft\services\Fs as FsService;
use yii\base\Event;

/**
 * Plugin represents the UpCloud Object Storage filesystem.
 *
 * @author Mauricio Dulce
 */
class Plugin extends \craft\base\Plugin
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public string $schemaVersion = '2.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        Event::on(FsService::class, FsService::EVENT_REGISTER_FILESYSTEM_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Fs::class;
        });

        Event::on(
            Assets::class,
            Assets::EVENT_BEFORE_REPLACE_ASSET,
            function(ReplaceAssetEvent $event) {
                $asset = $event->asset;
                $fs = $asset->getVolume()->getFs();

                if (!$fs instanceof Fs) {
                    return;
                }

                $oldFilename = $asset->getFilename();
                $newFilename = $event->filename;

                // when replacing asset with another one with the same filename, invalidate the cdn path for the original file too
                if ($oldFilename === $newFilename) {
                    $fs->invalidateCdnPath($asset->getPath());
                }
            }
        );
    }
}
