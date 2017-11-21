<?php

namespace SilverStripe\Registry;

use Page;
use ClassInfo;
use DropdownField;
use NumericField;
use DataList;
use DBDatetime;
use Controller;
use DataObject;
use SSViewer;
use ArrayData;
use ArrayList;

class RegistryPage extends Page
{
    private static $description = 'Shows large series of data in a filterable, searchable, and paginated list';

    private static $db = array(
        'DataClass' => 'Varchar(100)',
        'PageLength' => 'Int'
    );

    public static $page_length_default = 10;

    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['DataClass'] = _t('RegistryPage.DataClassFieldLabel', "Data Class");
        $labels['PageLength'] = _t('RegistryPage.PageLengthFieldLabel', "Results page length");

        return $labels;
    }

    public function getDataClasses()
    {
        $map = array();
        foreach (ClassInfo::implementorsOf(RegistryDataInterface::class) as $class) {
            $map[$class] = singleton($class)->singular_name();
        }
        return $map;
    }

    public function getDataClass()
    {
        return $this->getField('DataClass');
    }

    public function getDataSingleton()
    {
        $class = $this->getDataClass();
        if (!$class) {
            return null;
        }
        return singleton($this->getDataClass());
    }

    public function getPageLength()
    {
        $length = $this->getField('PageLength');
        return $length ? $length : self::$page_length_default;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $classDropdown = new DropdownField('DataClass', $this->fieldLabel('DataClass'), $this->getDataClasses());
        $classDropdown->setEmptyString(_t('RegistryPage.SelectDropdownDefault', 'Select one'));
        $fields->addFieldToTab('Root.Main', $classDropdown, 'Content');
        $fields->addFieldToTab('Root.Main', new NumericField('PageLength', $this->fieldLabel('PageLength')), 'Content');
        return $fields;
    }

    public function LastUpdated()
    {
        $elements = new DataList($this->dataClass);
        $lastUpdated = new DBDatetime('LastUpdated');
        $lastUpdated->setValue($elements->max('LastEdited'));
        return $lastUpdated;
    }

    /**
     * Modified version of Breadcrumbs, to cater for viewing items.
     */
    public function Breadcrumbs($maxDepth = 20, $unlinked = false, $stopAtPageType = false, $showHidden = false)
    {
        $page = $this;
        $pages = array();

        while (
            $page
            && (!$maxDepth || count($pages) < $maxDepth)
            && (!$stopAtPageType || $page->ClassName != $stopAtPageType)
        ) {
            if ($showHidden || $page->ShowInMenus || ($page->ID == $this->ID)) {
                $pages[] = $page;
            }

            $page = $page->Parent;
        }

        // Add on the item we're currently showing.
        $controller = Controller::curr();
        if ($controller) {
            $request = $controller->getRequest();
            if ($request->param('Action') == 'show') {
                $id = $request->param('ID');
                if ($id) {
                    $object = DataObject::get_by_id($this->getDataClass(), $id);
                    array_unshift($pages, $object);
                }
            }
        }

        $template = new SSViewer('BreadcrumbsTemplate');

        return $template->process($this->customise(new ArrayData(array(
            'Pages' => new ArrayList(array_reverse($pages))
        ))));
    }
}
