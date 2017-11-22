<?php

namespace SilverStripe\Registry;

use Page;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ArrayData;

class RegistryPage extends Page
{
    private static $description = 'Shows large series of data in a filterable, searchable, and paginated list';

    private static $db = [
        'DataClass' => 'Varchar(100)',
        'PageLength' => 'Int',
    ];

    /**
     * The default length of a page of registry entries
     *
     * @config
     * @var integer
     */
    private static $page_length_default = 10;

    public function fieldLabels($includerelations = true)
    {
        $labels = parent::fieldLabels($includerelations);
        $labels['DataClass'] = _t(__CLASS__ . '.DataClassFieldLabel', "Data Class");
        $labels['PageLength'] = _t(__CLASS__ . '.PageLengthFieldLabel', "Results page length");

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
        return $length ?: $this->config()->get('page_length_default');
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $classDropdown = DropdownField::create('DataClass', $this->fieldLabel('DataClass'), $this->getDataClasses());
        $classDropdown->setEmptyString(_t(__CLASS__ . '.SelectDropdownDefault', 'Select one'));
        $fields->addFieldToTab('Root.Main', $classDropdown, 'Content');
        $fields->addFieldToTab(
            'Root.Main',
            NumericField::create('PageLength', $this->fieldLabel('PageLength')),
            'Content'
        );
        return $fields;
    }

    public function LastUpdated()
    {
        $elements = DataList::create($this->dataClass);
        $lastUpdated = DBDatetime::create('LastUpdated');
        $lastUpdated->setValue($elements->max('LastEdited'));
        return $lastUpdated;
    }

    /**
     * Modified version of Breadcrumbs, to cater for viewing items.
     */
    public function Breadcrumbs(
        $maxDepth = 20,
        $unlinked = false,
        $stopAtPageType = false,
        $showHidden = false,
        $delimiter = '&raquo;'
    ) {
        $page = $this;
        $pages = [];

        while ($page
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

        $template = SSViewer::create('BreadcrumbsTemplate');

        return $template->process($this->customise(ArrayData::create([
            'Pages' => ArrayList::create(array_reverse($pages))
        ])));
    }
}
