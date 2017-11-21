<?php

namespace SilverStripe\Registry;

use SilverStripe\Control\RSS\RSSFeed;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;

class RegistryImportFeed
{
    use Injectable;

    protected $modelClass;

    public function setModelClass($class)
    {
        $this->modelClass = $class;
        return $this;
    }

    public function getLatest()
    {
        $files = new ArrayList();

        $path = REGISTRY_IMPORT_PATH . '/' . $this->modelClass;
        if (file_exists($path)) {
            $registryPage = DataObject::get_one(RegistryPage::class, sprintf('"DataClass" = \'%s\'', $this->modelClass));
            if (($registryPage && $registryPage->exists())) {
                foreach (array_diff(scandir($path), array('.', '..')) as $file) {
                    $files->push(new RegistryImportFeedEntry(
                        $file,
                        '',
                        filemtime($path . '/' . $file),
                        REGISTRY_IMPORT_URL . '/' . $this->modelClass . '/' . $file
                    ));
                }
            }
        }

        return new RSSFeed(
            $files,
            'registry-feed/latest/' . $this->modelClass,
            singleton($this->modelClass)->singular_name() . ' data import history'
        );
    }
}
