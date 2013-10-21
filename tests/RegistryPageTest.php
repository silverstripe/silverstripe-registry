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

}

