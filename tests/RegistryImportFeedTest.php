<?php

namespace SilverStripe\Registry\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Registry\RegistryImportFeed;

class RegistryImportFeedTest extends SapphireTest
{
    public function testGetStoragePath()
    {
        $importFeed = RegistryImportFeed::create();
        $this->assertSame('_imports/Foo-Bar-ModelName', $importFeed->getStoragePath('Foo\Bar\ModelName'));
    }

    public function testGetImportFilename()
    {
        DBDatetime::set_mock_now('2017-01-01 12:30:45');

        $importFeed = RegistryImportFeed::create();
        $this->assertStringContainsString('import-2017-01-01', $importFeed->getImportFilename());
    }
}
