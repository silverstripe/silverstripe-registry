<?php

namespace SilverStripe\Registry\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Registry\RegistryPage;
use SilverStripe\Registry\Tests\Stub\RegistryPageTestContact;
use SilverStripe\Registry\Tests\Stub\RegistryPageTestSubclass;

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
