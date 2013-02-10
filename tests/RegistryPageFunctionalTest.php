<?php
class RegistryPageFunctionalTest extends FunctionalTest {

	public static $fixture_file = array(
		'fixtures/RegistryPageTestContact.yml',
		'fixtures/RegistryPageFunctionalTest.yml'
	);

	protected $extraDataObjects = array(
		'RegistryPageTestPage',
		'RegistryPageTestContact'
	);

	public static $use_draft_site = true;

	public function testFilteredSearchResults() {
		$page = $this->objFromFixture('RegistryPageTestPage', 'contact-registrypage');
		$response = $this->get($page->AbsoluteLink('Form') . '?' . http_build_query(array(
			'FirstName' => 'Alexander',
			'action_search' => 'Search'
		)));

		$parser = new CSSContentParser($response->getBody());
		$rows = $parser->getBySelector('table.results tbody tr');
		$cells = $rows[0]->td;

		$this->assertEquals(1, count($rows));
		$this->assertEquals('Alexander', (string) $cells[0]);
		$this->assertEquals('Bernie', (string) $cells[1]);
	}

	public function testSearchResultsLimitAndStart() {
		$page = $this->objFromFixture('RegistryPageTestPage', 'contact-registrypage-limit');
		$response = $this->get($page->AbsoluteLink('Form') . '?' . http_build_query(array(
			'Sort' => 'FirstName',
			'Dir' => 'DESC',
			'action_search' => 'Search'
		)));

		$parser = new CSSContentParser($response->getBody());
		$rows = $parser->getBySelector('table.results tbody tr');
		$anchors = $parser->getBySelector('ul.pageNumbers li a');

		$this->assertEquals(3, count($rows), 'Limited to 3 search results');
		$this->assertEquals(4, count($anchors), '4 paging anchors, including next');

		$this->assertContains('Form?', (string) $anchors[0]['href']);
		$this->assertContains('Sort=FirstName', (string) $anchors[0]['href']);
		$this->assertContains('Dir=DESC', (string) $anchors[0]['href']);

		$this->assertContains('start=0', (string) $anchors[0]['href']);
		$this->assertContains('start=3', (string) $anchors[1]['href']);
		$this->assertContains('start=6', (string) $anchors[2]['href']);
	}

	public function testGetParamsPopulatesSearchForm() {
		$page = $this->objFromFixture('RegistryPageTestPage', 'contact-registrypage');
		$response = $this->get($page->AbsoluteLink('Form') . '?' . http_build_query(array(
			'FirstName' => 'Alexander',
			'Sort' => 'FirstName',
			'Dir' => 'DESC',
			'action_search' => 'Search'
		)));

		$parser = new CSSContentParser($response->getBody());
		$firstNameField = $parser->getBySelector('#Form_Form_FirstName');
		$sortField = $parser->getBySelector('#Form_Form_Sort');
		$dirField = $parser->getBySelector('#Form_Form_Dir');

		$this->assertEquals('Alexander', (string) $firstNameField[0]['value']);
		$this->assertEquals('FirstName', (string) $sortField[0]['value']);
		$this->assertEquals('DESC', (string) $dirField[0]['value']);
	}

	public function testQueryLinks() {
		$page = $this->objFromFixture('RegistryPageTestPage', 'contact-registrypage');
		$response = $this->get($page->AbsoluteLink('Form') . '?' . http_build_query(array(
			'FirstName' => 'Alexander',
			'action_search' => 'Search'
		)));

		$parser = new CSSContentParser($response->getBody());
		$rows = $parser->getBySelector('table.results thead tr');
		$anchors = $rows[0]->th->a;

		$this->assertContains('FirstName=Alexander', (string) $anchors[0]['href']);
		$this->assertContains('Surname=', (string) $anchors[0]['href']);
		$this->assertContains('Sort=FirstName', (string) $anchors[0]['href']);
		$this->assertContains('Dir=ASC', (string) $anchors[0]['href']);
		$this->assertContains('action_search=Search', (string) $anchors[0]['href']);
	}

	public function testShowExistingRecord() {
		$record = $this->objFromFixture('RegistryPageTestContact', 'alexander');
		$page = $this->objFromFixture('RegistryPageTestPage', 'contact-registrypage');
		$response = $this->get(Controller::join_links($page->AbsoluteLink(), 'show', $record->ID));

		$this->assertContains('Alexander Bernie', $response->getBody());
	}

	public function testPageNotFoundNonExistantRecord() {
		$page = $this->objFromFixture('RegistryPageTestPage', 'contact-registrypage');
		$response = $this->get(Controller::join_links($page->AbsoluteLink(), 'show', '123456'));
		$this->assertEquals(404, $response->getStatusCode());
	}

	public function testColumnName() {
		$page = $this->objFromFixture('RegistryPageTestPage', 'contact-registrypage');
		$response = $this->get($page->AbsoluteLink('Form') . '?' . http_build_query(array(
			'action_search' => 'Search'
		)));

		$parser = new CSSContentParser($response->getBody());
		$rows = $parser->getBySelector('table.results thead tr');
		$anchors = $rows[0]->th->a;

		$this->assertEquals('First Name', (string) $anchors[0]);
	}

	public function testExportLink() {
		$page = $this->objFromFixture('RegistryPageTestPage', 'contact-registrypage');
		$response = $this->get($page->AbsoluteLink('Form') . '?' . http_build_query(array(
			'FirstName' => 'Alexander',
			'Sort' => 'FirstName',
			'Dir' => 'DESC',
			'action_search' => 'Search'
		)));

		$parser = new CSSContentParser($response->getBody());
		$anchor = $parser->getBySelector('a.export');

		$this->assertContains('export?', (string) $anchor[0]['href']);
		$this->assertContains('FirstName=Alexander', (string) $anchor[0]['href']);
		$this->assertContains('Surname=', (string) $anchor[0]['href']);
		$this->assertContains('Sort=FirstName', (string) $anchor[0]['href']);
		$this->assertContains('Dir=DESC', (string) $anchor[0]['href']);
		$this->assertContains('action_search=Search', (string) $anchor[0]['href']);
	}

}

