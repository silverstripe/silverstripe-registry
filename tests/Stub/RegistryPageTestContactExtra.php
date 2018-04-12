<?php

namespace SilverStripe\Registry\Tests\Stub;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use Silverstripe\Control\Controller;
use SilverStripe\Registry\RegistryDataInterface;
use SilverStripe\Registry\Tests\Stub\RegistryPageTestPage;

class RegistryPageTestContactExtra extends DataObject implements RegistryDataInterface, TestOnly
{
    private static $table_name = 'RegistryPageTestContactExtra';

    private static $use_link = true;

    private static $db = [
        'FirstName' => 'Varchar(50)',
        'Surname' => 'Varchar(50)',
    ];

    private static $has_one = [
        'RegistryPage' => RegistryPageTestPage::class
    ];

    private static $summary_fields = [
        'FirstName' => 'First name',
        'Surname' => 'Surname',
        'RegistryPage.Title' => 'Registry Page',
        'StaticReference' => 'Other'
    ];

    private static $searchable_fields = [
        'FirstName',
        'Surname',
        'RegistryPageID'
    ];

    public function getSearchFields()
    {
        return new FieldList(
            new TextField('FirstName', 'First name'),
            new TextField('Surname', 'Surname'),
            new TextField('RegistryPageID', 'Registry Page')
        );
    }

    public function getStaticReference()
    {
        return 'Static Reference';
    }

    public function Link($action = null)
    {
        $page = RegistryPageTestPage::get()->filter('DataClass', RegistryPageTestContactExtra::class)->First();
        return Controller::join_links($page->Link(), $action, $this->ID);
    }
}
