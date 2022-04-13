<?php

namespace SilverStripe\Registry;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;

class RegistryAdmin extends ModelAdmin
{
    private static $url_segment = 'registry';

    private static $menu_title = 'Registry';

    /**
     * Hide the registry section completely if we have no registries to manage.
     *
     * {@inheritDoc}
     */
    public function canView($member = null)
    {
        $managedModels = $this->getManagedModels();
        if (count($managedModels ?? []) == 0) {
            return false;
        }

        return parent::canView($member);
    }

    public function getManagedModels()
    {
        $models = ClassInfo::implementorsOf(RegistryDataInterface::class);

        foreach ($models as $alias => $className) {
            $models[$className] = [
                'title' => singleton($className)->i18n_singular_name(),
            ];
            unset($models[$alias]);
        }

        return $models;
    }

    public function getExportFields()
    {
        $fields = [];
        foreach (singleton($this->modelClass)->summaryFields() as $field => $spec) {
            $fields[$field] = $field;
        }
        return $fields;
    }

    /**
     * Gets a unique filename to use for importing the uploaded CSV data
     *
     * @return string
     */
    public function getCsvImportFilename()
    {
        $feed = RegistryImportFeed::singleton();

        return sprintf('%s/%s', $feed->getStoragePath($this->modelClass), $feed->getImportFilename());
    }

    public function import($data, $form, $request)
    {
        if (!$this->showImportForm
            || (is_array($this->showImportForm) && !in_array($this->modelClass, $this->showImportForm ?? []))
        ) {
            return false;
        }

        $importers = $this->getModelImporters();
        $loader = $importers[$this->modelClass];

        $fileContents = !empty($data['_CsvFile']['tmp_name']) ? file_get_contents($data['_CsvFile']['tmp_name']) : '';
        // File wasn't properly uploaded, show a reminder to the user
        if (!$fileContents) {
            $form->sessionMessage(
                _t('SilverStripe\\Admin\\ModelAdmin.NOCSVFILE', 'Please browse for a CSV file to import'),
                'bad'
            );
            $this->redirectBack();
            return false;
        }

        if (!empty($data['EmptyBeforeImport']) && $data['EmptyBeforeImport']) { //clear database before import
            $loader->deleteExistingRecords = true;
        }

        $results = $loader->load($data['_CsvFile']['tmp_name']);

        // copy the uploaded file into the export path
        RegistryImportFeed::singleton()
            ->getAssetHandler()
            ->setContent($this->getCsvImportFilename(), $fileContents);

        $message = '';
        if ($results->CreatedCount()) {
            $message .= _t(
                'SilverStripe\\Admin\\ModelAdmin.IMPORTEDRECORDS',
                "Imported {count} records.",
                ['count' => $results->CreatedCount()]
            );
        }
        if ($results->UpdatedCount()) {
            $message .= _t(
                'SilverStripe\\Admin\\ModelAdmin.UPDATEDRECORDS',
                "Updated {count} records.",
                ['count' => $results->UpdatedCount()]
            );
        }
        if ($results->DeletedCount()) {
            $message .= _t(
                'SilverStripe\\Admin\\ModelAdmin.DELETEDRECORDS',
                "Deleted {count} records.",
                ['count' => $results->DeletedCount()]
            );
        }
        if (!$results->CreatedCount() && !$results->UpdatedCount()) {
            $message .= _t('SilverStripe\\Admin\\ModelAdmin.NOIMPORT', "Nothing to import");
        }

        $form->sessionMessage($message, 'good');
        $this->redirectBack();
    }
}
