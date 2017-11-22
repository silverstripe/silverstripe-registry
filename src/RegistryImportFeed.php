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
        $files = ArrayList::create();

        $path = REGISTRY_IMPORT_PATH . '/' . $this->sanitiseClassName($this->modelClass);
        if (file_exists($path)) {
            $registryPage = DataObject::get_one(
                RegistryPage::class,
                ['DataClass' => $this->modelClass]
            );

            if ($registryPage && $registryPage->exists()) {
                foreach (array_diff(scandir($path), array('.', '..')) as $file) {
                    $files->push(RegistryImportFeedEntry::create(
                        $file,
                        '',
                        filemtime($path . '/' . $file),
                        REGISTRY_IMPORT_URL . '/' . $this->sanitiseClassName($this->modelClass) . '/' . $file
                    ));
                }
            }
        }

        return RSSFeed::create(
            $files,
            'registry-feed/latest/' . $this->sanitiseClassName($this->modelClass),
            singleton($this->modelClass)->singular_name() . ' data import history'
        );
    }

    /**
     * See {@link \SilverStripe\Admin\ModelAdmin::sanitiseClassName}
     *
     * @param  string $class
     * @return string
     */
    protected function sanitiseClassName($class)
    {
        return str_replace('\\', '-', $class);
    }
}
