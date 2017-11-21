<?php

namespace SilverStripe\Registry\Tests\Stub;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Registry\RegistryDataInterface;

class RegistryPageTestContact extends DataObject implements RegistryDataInterface, TestOnly
{
    private static $table_name = 'RegistryPageTestContact';

    private static $db = [
        'FirstName' => 'Varchar(50)',
        'Surname' => 'Varchar(50)',
    ];

    private static $summary_fields = [
        'FirstName' => 'First name',
        'Surname' => 'Surname',
    ];

    public function getSearchFields()
    {
        return new FieldList(
            new TextField('FirstName', 'First name'),
            new TextField('Surname', 'Surname')
        );
    }
}
