<?php

namespace SilverStripe\Registry\Tests;

use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Registry\Tests\Stub\RegistryPageTestContact;

class RegistryImportFeedControllerTest extends FunctionalTest
{
    protected static $fixture_file = 'fixtures/RegistryPageTestContact.yml';

    protected static $extra_dataobjects = [
        RegistryPageTestContact::class,
    ];

    public function testNonExistentClassInLatestFeedReturnsNotFound()
    {
        $result = $this->get('registry-feed/latest/Non-Existent-Class-Name-Here-Monkey');

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testClassNotImplementingRegistryInterfaceReturnsNotFound()
    {
        $result = $this->get('registry-feed/latest/Page');

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testValidRegistryClassReturnsXmlFeed()
    {
        $result = $this->get('registry-feed/latest/SilverStripe-Registry-Tests-Stub-RegistryPageTestContact');

        $this->assertEquals(200, $result->getStatusCode());
    }
}
