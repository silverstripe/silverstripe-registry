<?php
class RegistryPageTest extends SapphireTest {

	public static $fixture_file = array(
		'fixtures/RegistryPageTestContact.yml',
		'fixtures/RegistryPageTest.yml'
	);

	protected $extraDataObjects = array(
		'RegistryPageTestContact',
		'RegistryPageTestSubclass'
	);

	public function testPageLengthDefault() {
		$page = $this->objFromFixture('RegistryPage', 'contact-registrypage');
		$this->assertEquals(RegistryPage::$page_length_default, $page->getPageLength());
	}

	public function testPageLengthFieldOverridesDefault() {
		$page = $this->objFromFixture('RegistryPage', 'contact-registrypage-with-length');
		$this->assertEquals(20, $page->getPageLength());
	}

	public function testDataClass() {
		$page = $this->objFromFixture('RegistryPage', 'contact-registrypage');
		$this->assertEquals('RegistryPageTestContact', $page->getDataClass());
	}

	public function testDataSingleton() {
		$page = $this->objFromFixture('RegistryPage', 'contact-registrypage');
		$this->assertInstanceOf('RegistryPageTestContact', $page->getDataSingleton());
	}

	public function testTemplateList() {
		$page = $this->objFromFixture('RegistryPage', 'contact-registrypage');
		$controller = new RegistryPage_Controller($page);

		$this->assertEquals(
			array(
				'RegistryPage_RegistryPageTestContact',
				'RegistryPage',
				'Page',
				'ContentController',
				'Controller'
			),
			$controller->getTemplateList('index')
		);

		$this->assertEquals(
			array(
				'RegistryPage_RegistryPageTestContact_show',
				'RegistryPage_show',
				'Page_show',
				'ContentController_show',
				'RegistryPage_RegistryPageTestContact',
				'RegistryPage',
				'Page',
				'ContentController',
				'Controller'
			),
			$controller->getTemplateList('show')
		);
	}

	public function testTemplateListSubclass() {
		$page = $this->objFromFixture('RegistryPageTestSubclass', 'registrypage-subclass');
		$controller = new RegistryPage_Controller($page);

		$this->assertEquals(
			array(
				'RegistryPageTestSubclass_RegistryPageTestContact',
				'RegistryPage_RegistryPageTestContact',
				'RegistryPage',
				'Page',
				'ContentController',
				'Controller'
			),
			$controller->getTemplateList('index')
		);

		$this->assertEquals(
			array(
				'RegistryPageTestSubclass_RegistryPageTestContact_show',
				'RegistryPage_RegistryPageTestContact_show',
				'RegistryPage_show',
				'Page_show',
				'ContentController_show',
				'RegistryPageTestSubclass_RegistryPageTestContact',
				'RegistryPage_RegistryPageTestContact',
				'RegistryPage',
				'Page',
				'ContentController',
				'Controller'
			),
			$controller->getTemplateList('show')
		);
	}

}

