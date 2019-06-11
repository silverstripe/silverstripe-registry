<?php

namespace SilverStripe\Registry\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Registry\RegistryPage;
use SilverStripe\Registry\RegistryPageController;
use SilverStripe\Registry\Tests\Stub\RegistryPageTestContact;

class RegistryPageControllerTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures/RegistryPageTest.yml';

    protected static $extra_dataobjects = [
        RegistryPageTestContact::class,
    ];

    public function testCanSortByReturnsFalseWithNoDataRecord()
    {
        $dataRecord = $this->createMock(RegistryPage::class);
        $dataRecord->expects($this->once())->method('getDataSingleton')->willReturn(null);

        $controller = new RegistryPageController($dataRecord);
        $this->assertFalse($controller->canSortBy('Title'));
    }

    public function testCanSortByDataRecordField()
    {
        /** @var RegistryPage $dataRecord */
        $dataRecord = $this->objFromFixture(RegistryPage::class, 'contact-registrypage');
        $controller = new RegistryPageController($dataRecord);

        $this->assertTrue($controller->canSortBy('FirstName'));
        $this->assertTrue($controller->canSortBy('Surname'));
        $this->assertFalse($controller->canSortBy('DateOfBirth'));
        $this->assertFalse($controller->canSortBy('Email'));
    }
}
