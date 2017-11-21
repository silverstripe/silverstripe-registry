<?php

namespace SilverStripe\Registry\Tests;

use SapphireTest;
use RegistryPage;
use RegistryPageTestContact;
use RegistryPageTestSubclass;

class RegistryPageTest extends SapphireTest
{
    protected static $fixture_file = [
        'fixtures/RegistryPageTestContact.yml',
        'fixtures/RegistryPageTest.yml',
    ];

    protected static $extra_dataobjects = [
        RegistryPageTestContact::class,
        RegistryPageTestSubclass::class,
    ];

    public function testPageLengthDefault()
    {
        $page = $this->objFromFixture(RegistryPage::class, 'contact-registrypage');
        $this->assertEquals(RegistryPage::$page_length_default, $page->getPageLength());
    }

    public function testPageLengthFieldOverridesDefault()
    {
        $page = $this->objFromFixture(RegistryPage::class, 'contact-registrypage-with-length');
        $this->assertEquals(20, $page->getPageLength());
    }

    public function testDataClass()
    {
        $page = $this->objFromFixture(RegistryPage::class, 'contact-registrypage');
        $this->assertEquals(RegistryPageTestContact::class, $page->getDataClass());
    }

    public function testDataSingleton()
    {
        $page = $this->objFromFixture(RegistryPage::class, 'contact-registrypage');
        $this->assertInstanceOf(RegistryPageTestContact::class, $page->getDataSingleton());
    }
}
