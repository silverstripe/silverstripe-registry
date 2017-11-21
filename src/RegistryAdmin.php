<?php

namespace SilverStripe\Registry;

use ModelAdmin;
use ClassInfo;

class RegistryAdmin extends ModelAdmin
{
    private static $url_segment = 'registry';

    // Hide the registry section completely if we have no registries to manage.
    public function canView($member = null)
    {
        $managedModels = $this->getManagedModels();
        if (count($managedModels) == 0) {
            return false;
        }

        return parent::canView($member);
    }

    public function getManagedModels()
    {
        $models = ClassInfo::implementorsOf(RegistryDataInterface::class);

        foreach ($models as $k => $v) {
            if (is_numeric($k)) {
                $models[$v] = array('title' => singleton($v)->i18n_singular_name());
                unset($models[$k]);
            }
        }

        return $models;
    }

    public function getExportFields()
    {
        $fields = array();
        foreach (singleton($this->modelClass)->db() as $field => $spec) {
            $fields[$field] = $field;
        }
        return $fields;
    }

    public function getCsvImportsPath()
    {
        $base = REGISTRY_IMPORT_PATH;
        if (!file_exists($base)) {
            mkdir($base);
        }

        $path = sprintf('%s/%s', $base, $this->modelClass);
        if (!file_exists($path)) {
            mkdir($path);
        }

        return $path;
    }

    public function import($data, $form, $request)
    {
        if (!$this->showImportForm || (is_array($this->showImportForm) && !in_array($this->modelClass, $this->showImportForm))) {
            return false;
        }

        $importers = $this->getModelImporters();
        $loader = $importers[$this->modelClass];

        // File wasn't properly uploaded, show a reminder to the user
        if (empty($_FILES['_CsvFile']['tmp_name'])
            || file_get_contents($_FILES['_CsvFile']['tmp_name']) == ''
        ) {
            $form->sessionMessage(_t('ModelAdmin.NOCSVFILE', 'Please browse for a CSV file to import'), 'good');
            $this->redirectBack();
            return false;
        }

        if (!empty($data['EmptyBeforeImport']) && $data['EmptyBeforeImport']) { //clear database before import
            $loader->deleteExistingRecords = true;
        }

        $results = $loader->load($_FILES['_CsvFile']['tmp_name']);

        // copy the uploaded file into the export path
        copy($_FILES['_CsvFile']['tmp_name'], sprintf('%s/import-%s.csv', $this->getCsvImportsPath(), date('Y-m-dHis')));

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
