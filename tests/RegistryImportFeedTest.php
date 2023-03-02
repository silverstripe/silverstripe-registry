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

    public function testGetImportFiles()
    {
        $importFeed = RegistryImportFeed::create();
        $importFeed->getAssetHandler()
            ->setContent(
                $importFeed->getStoragePath() . "import-2023-01-01.csv",
                'File contents'
            );
        $importFeed->getAssetHandler()
            ->setContent(
                $importFeed->getStoragePath() . "import-2023-02-02.csv",
                'File contents 3'
            );

        $items = $importFeed->getImportFiles()->items;
        $this->assertEquals(2, count($items));
        $this->assertSame('assets/_imports/import-2023-01-01.csv', $items[0]->link);
        $this->assertSame('assets/_imports/import-2023-02-02.csv', $items[1]->link);

        $importFeed->getAssetHandler()->removeContent($importFeed->getStoragePath());
    }
}
