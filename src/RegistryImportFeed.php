<?php

namespace SilverStripe\Registry;

use SilverStripe\Assets\Storage\GeneratedAssetHandler;
use SilverStripe\Control\RSS\RSSFeed;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBDatetime;

class RegistryImportFeed
{
    use Configurable;
    use Injectable;

    /**
     * The path format to store imported record files in (inside the assets directory)
     *
     * @config
     * @var string
     */
    private static $storage_path = '_imports/{model}';

    /**
     * The filename to use for storing imported record files. Used by RegistryImportFeedController to save files to.
     *
     * @config
     * @var string
     */
    private static $storage_filename = 'import-{date}.csv';

    protected $modelClass;

    /**
     * The class used to manipulate imported feed files on the filesystem
     *
     * @var GeneratedAssetHandler
     */
    protected $assetHandler;

    /**
     * The "assets" folder name
     *
     * @var string
     */
    protected $assetsDir;

    public function setModelClass($class)
    {
        $this->modelClass = $class;
        return $this;
    }

    public function getLatest()
    {
        $registryPage = RegistryPage::get()->filter(['DataClass' => $this->modelClass])->first();
        if ($registryPage && $registryPage->exists()) {
            $files = $this->getImportFiles();
        } else {
            // Always return an empty list of the model isn't associated to any RegistryPages
            $files = ArrayList::create();
        }

        return RSSFeed::create(
            $files,
            'registry-feed/latest/' . $this->sanitiseClassName($this->modelClass),
            singleton($this->modelClass)->singular_name() . ' data import history'
        );
    }

    /**
     * Set the handler used to manipulate the filesystem, and add the ListFiles plugin from Flysystem to inspect
     * the contents of a directory
     *
     * @param GeneratedAssetHandler $handler
     * @return $this
     */
    public function setAssetHandler(GeneratedAssetHandler $handler)
    {
        $this->assetHandler = $handler;

        return $this;
    }

    /**
     * Get the handler used to manipulate the filesystem
     *
     * @return GeneratedAssetHandler
     */
    public function getAssetHandler()
    {
        return $this->assetHandler;
    }

    /**
     * Get the path that import files will be stored for this model
     *
     * @param string $modelClass If null, the current model class will be used
     * @return string
     */
    public function getStoragePath($modelClass = null)
    {
        $sanitisedClassName = $this->sanitiseClassName($modelClass ?: $this->modelClass);
        return str_replace('{model}', $sanitisedClassName ?? '', $this->config()->get('storage_path') ?? '');
    }

    /**
     * Loop import files in the storage path and push them into an {@link ArrayList}
     *
     * @return ArrayList<RegistryImportFeedEntry>
     */
    public function getImportFiles()
    {
        $path = $this->getStoragePath();
        $importFiles = $this->getAssetHandler()->getFilesystem()->listContents($path)->toArray();

        $files = ArrayList::create();

        foreach ($importFiles as $importFile) {
            $files->push(RegistryImportFeedEntry::create(
                basename($importFile->path()),
                '',
                DBDatetime::create()->setValue($importFile->lastModified())->Format(DBDatetime::ISO_DATETIME),
                $this->getAssetsDir() . '/' . $importFile->path()
            ));
        }

        return $files;
    }

    /**
     * Returns a relatively unique filename to storage imported data feeds as
     *
     * @return string
     */
    public function getImportFilename()
    {
        // Note: CLDR date format see DBDatetime
        $datetime = DBDatetime::now()->Format('y-MM-dd-HHmmss');
        return str_replace('{date}', $datetime ?? '', $this->config()->get('storage_filename') ?? '');
    }

    /**
     * Set the assets directory name
     *
     * @param string $assetsDir
     * @return $this
     */
    public function setAssetsDir($assetsDir)
    {
        $this->assetsDir = $assetsDir;
        return $this;
    }

    /**
     * Get the assets directory name
     *
     * @return string
     */
    public function getAssetsDir()
    {
        return $this->assetsDir;
    }

    /**
     * See {@link \SilverStripe\Admin\ModelAdmin::sanitiseClassName}
     *
     * @param  string $class
     * @return string
     */
    protected function sanitiseClassName($class)
    {
        return str_replace('\\', '-', $class ?? '');
    }
}
