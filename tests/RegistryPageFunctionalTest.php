<?php

namespace SilverStripe\Registry\Tests;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Registry\Tests\Stub\RegistryPageTestContact;
use SilverStripe\Registry\Tests\Stub\RegistryPageTestContactExtra;
use SilverStripe\Registry\Tests\Stub\RegistryPageTestPage;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

class RegistryPageFunctionalTest extends FunctionalTest
{
    protected static $fixture_file = [
        'fixtures/RegistryPageFunctionalTest.yml',
        'fixtures/RegistryPageTestContact.yml'
    ];

    protected static $extra_dataobjects = [
        RegistryPageTestContact::class,
        RegistryPageTestContactExtra::class,
        RegistryPageTestPage::class
    ];

    public function testUseLink()
    {
        // Page with links
        $page = $this->objFromFixture(RegistryPageTestPage::class, 'contact-registrypage-extra');
        $page->publishRecursive();

        $response = $this->get($page->Link());
        $parser = new CSSContentParser($response->getBody());

        $cells = $parser->getBySelector('table.results tbody tr td');

        $this->assertStringContainsString('/contact-search-extra/', (string)$cells[0]->a->attributes()->href[0]);
    }

    public function testFilteredSearchResults()
    {
        $page = $this->objFromFixture(RegistryPageTestPage::class, 'contact-registrypage');
        $page->publishRecursive();
        $uri = Controller::join_links(
            $page->RelativeLink('RegistryFilterForm'),
            '?' .
            http_build_query(array(
                'FirstName' => 'Alexander',
                'action_doRegistryFilter' => 'Filter'
            ))
        );
        $response = $this->get($uri);

        $parser = new CSSContentParser($response->getBody());
        $rows = $parser->getBySelector('table.results tbody tr');

        $cells = $rows[0]->td;

        $this->assertCount(1, $rows);
        $this->assertEquals('Alexander', trim((string)$cells[0]));
        $this->assertEquals('Bernie', trim((string)$cells[1]));
    }

    public function testFilteredByRelationSearchResults()
    {
        $page = $this->objFromFixture(RegistryPageTestPage::class, 'contact-registrypage-extra');
        $page->publishRecursive();
        $uri = Controller::join_links(
            $page->RelativeLink('RegistryFilterForm'),
            '?' . http_build_query(array(
                'RegistryPage.Title' => $page->Title,
                'action_doRegistryFilter' => 'Filter'
            ))
        );

        $response = $this->get($uri);

        $parser = new CSSContentParser($response->getBody());

        $rows = $parser->getBySelector('table.results tbody tr');
        $cells = $rows[0]->td;

        $this->assertCount(1, $rows);
        $this->assertEquals('Jimmy', trim((string)$cells[0]->a[0]));
        $this->assertEquals('Sherson', trim((string)$cells[1]->a[0]));
    }

    /**
     * Check that RegistryPageController can filter for ExactMatches (ID) for relationships.
     *
     * @throws \Exception
     */
    public function testFilteredByRelationIDSearchResults()
    {
        $page = $this->objFromFixture(RegistryPageTestPage::class, 'contact-registrypage-extra');
        $page->publishRecursive();
        $uri = Controller::join_links(
            $page->RelativeLink('RegistryFilterForm'),
            '?' . http_build_query(array(
                'RegistryPage.ID' => $page->ID,
                'action_doRegistryFilter' => 'Filter',
            ))
        );

        // If this is wrong then the configuration system is broken.
        $this->assertCount(4, $page->getDataSingleton()->config()->get('searchable_fields'));

        $response = $this->get($uri);

        $parser = new CSSContentParser($response->getBody());

        $rows = $parser->getBySelector('table.results tbody tr');

        // there should only be one user with that ID from our YML
        $this->assertCount(1, $rows);
        $cells = $rows[0]->td;

        $this->assertCount(1, $rows);
        $this->assertEquals('Jimmy', trim((string)$cells[0]->a));
        $this->assertEquals('Sherson', trim((string)$cells[1]->a));
    }

    public function testUserCustomSummaryField()
    {
        $page = $this->objFromFixture(RegistryPageTestPage::class, 'contact-registrypage-extra');
        $page->publishRecursive();
        $response = $this->get($page->Link());
        $parser = new CSSContentParser($response->getBody());

        $cells = $parser->getBySelector('table.results tbody tr td');

        $this->assertStringContainsString(
            $page->getDataSingleton()->getStaticReference(),
            trim((string)$cells[4]->a[0])
        );
    }

    public function testSearchResultsLimitAndStart()
    {
        $page = $this->objFromFixture(RegistryPageTestPage::class, 'contact-registrypage-limit');
        $page->publishRecursive();
        $uri = Controller::join_links(
            $page->RelativeLink('RegistryFilterForm'),
            '?' . http_build_query(array(
                'Sort' => 'FirstName',
                'Dir' => 'DESC',
                'action_doRegistryFilter' => 'Filter'
            ))
        );

        $response = $this->get($uri);

        $parser = new CSSContentParser($response->getBody());
        $rows = $parser->getBySelector('table.results tbody tr');
        $anchors = $parser->getBySelector('ul.pageNumbers li a');

        $this->assertCount(3, $rows, 'Limited to 3 search results');
        $this->assertCount(4, $anchors, '4 paging anchors, including next');

        $this->assertStringContainsString('Sort=FirstName', (string)$anchors[0]['href']);
        $this->assertStringContainsString('Dir=DESC', (string)$anchors[0]['href']);

        $this->assertStringContainsString('start=0', (string)$anchors[0]['href']);
        $this->assertStringContainsString('start=3', (string)$anchors[1]['href']);
        $this->assertStringContainsString('start=6', (string)$anchors[2]['href']);
    }

    public function testGetParamsPopulatesSearchForm()
    {
        $page = $this->objFromFixture(RegistryPageTestPage::class, 'contact-registrypage');
        $page->publishRecursive();
        $uri = Controller::join_links(
            $page->RelativeLink('RegistryFilterForm'),
            '?' . http_build_query(array(
                'FirstName' => 'Alexander',
                'Sort' => 'FirstName',
                'Dir' => 'DESC',
                'action_doRegistryFilter' => 'Filter'
            ))
        );
        $response = $this->get($uri);

        $parser = new CSSContentParser($response->getBody());
        $firstNameField = $parser->getBySelector('#Form_RegistryFilterForm_FirstName');
        $sortField = $parser->getBySelector('#Form_RegistryFilterForm_Sort');
        $dirField = $parser->getBySelector('#Form_RegistryFilterForm_Dir');

        $this->assertEquals('Alexander', (string)$firstNameField[0]['value']);
        $this->assertEquals('FirstName', (string)$sortField[0]['value']);
        $this->assertEquals('DESC', (string)$dirField[0]['value']);
    }

    public function testQueryLinks()
    {
        $page = $this->objFromFixture(RegistryPageTestPage::class, 'contact-registrypage');
        $page->publishRecursive();
        $uri = Controller::join_links(
            $page->RelativeLink('RegistryFilterForm'),
            '?' . http_build_query(array(
                'FirstName' => 'Alexander',
                'action_doRegistryFilter' => 'Filter'
            ))
        );
        $response = $this->get($uri);

        $parser = new CSSContentParser($response->getBody());
        $rows = $parser->getBySelector('table.results thead tr');
        $anchors = $rows[0]->th->a;

        $this->assertStringContainsString('FirstName=Alexander', (string)$anchors[0]['href']);
        $this->assertStringContainsString('Surname=', (string)$anchors[0]['href']);
        $this->assertStringContainsString('Sort=FirstName', (string)$anchors[0]['href']);
        $this->assertStringContainsString('Dir=ASC', (string)$anchors[0]['href']);
        $this->assertStringContainsString('action_doRegistryFilter=Filter', (string)$anchors[0]['href']);
    }

    public function testShowExistingRecord()
    {
        $record = $this->objFromFixture(RegistryPageTestContact::class, 'alexander');
        $page = $this->objFromFixture(RegistryPageTestPage::class, 'contact-registrypage');
        $page->publishRecursive();
        $response = $this->get(Controller::join_links($page->RelativeLink(), 'show', $record->ID));

        $this->assertStringContainsString('Alexander Bernie', $response->getBody());
    }

    public function testPageNotFoundNonExistantRecord()
    {
        $page = $this->objFromFixture(RegistryPageTestPage::class, 'contact-registrypage');
        $response = $this->get(Controller::join_links($page->RelativeLink(), 'show', '123456'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testColumnName()
    {
        $page = $this->objFromFixture(RegistryPageTestPage::class, 'contact-registrypage');
        $page->publishRecursive();
        $uri = Controller::join_links(
            $page->RelativeLink('RegistryFilterForm'),
            '?' . http_build_query(array(
                'action_doRegistryFilter' => 'Filter'
            ))
        );
        $response = $this->get($uri);

        $parser = new CSSContentParser($response->getBody());
        $rows = $parser->getBySelector('table.results thead tr');
        $anchors = $rows[0]->th->a;

        $this->assertEquals('First name', trim((string)$anchors[0]));
    }

    public function testSortableColumns()
    {
        $page = $this->objFromFixture(RegistryPageTestPage::class, 'contact-registrypage-extra');
        $page->publishRecursive();
        $response = $this->get($page->Link());
        $parser = new CSSContentParser($response->getBody());
        $columns = $parser->getBySelector('table.results thead tr th');

        $this->assertNotEmpty($columns[0]->a);
        $this->assertNotEmpty($columns[1]->a);
        $this->assertNotEmpty($columns[2]->a);
        $this->assertNotEmpty($columns[3]->a);
        $this->assertEquals('Other', trim((string)$columns[4]));
    }

    public function testExportLink()
    {
        $page = $this->objFromFixture(RegistryPageTestPage::class, 'contact-registrypage');
        $page->publishRecursive();
        $uri = Controller::join_links(
            $page->RelativeLink('RegistryFilterForm'),
            '?' . http_build_query(array(
                'FirstName' => 'Alexander',
                'Sort' => 'FirstName',
                'Dir' => 'DESC',
                'action_doRegistryFilter' => 'Filter'
            ))
        );
        $response = $this->get($uri);

        $parser = new CSSContentParser($response->getBody());
        $anchor = $parser->getBySelector('a.export');

        $this->assertStringContainsString('export?', (string)$anchor[0]['href']);
        $this->assertStringContainsString('FirstName=Alexander', (string)$anchor[0]['href']);
        $this->assertStringContainsString('Surname=', (string)$anchor[0]['href']);
        $this->assertStringContainsString('Sort=FirstName', (string)$anchor[0]['href']);
        $this->assertStringContainsString('Dir=DESC', (string)$anchor[0]['href']);
        $this->assertStringContainsString('action_doRegistryFilter=Filter', (string)$anchor[0]['href']);
    }
}
