<?php
class RegistryImportFeed {

	protected $modelClass;

	public function setModelClass($class) {
		$this->modelClass = $class;
	}

	public function getLatest() {
		$files = new ArrayList();

		$path = REGISTRY_IMPORT_PATH . '/' . $this->modelClass;
		if(file_exists($path)) {
			$registryPage = DataObject::get_one('RegistryPage', sprintf('"DataClass" = \'%s\'', $this->modelClass));
			if(($registryPage && $registryPage->exists())) {
				foreach(array_diff(scandir($path), array('.', '..')) as $file) {
					$files->push(new RegistryImportFeed_Entry(
						$file,
						'',
						filemtime($path . '/' . $file),
						REGISTRY_IMPORT_URL . '/' . $this->modelClass . '/' . $file
					));
				}
			}
		}

		return new RSSFeed(
			$files,
			'registry-feed/latest/' . $this->modelClass,
			singleton($this->modelClass)->singular_name() . ' data import history'
		);
	}

}
class RegistryImportFeed_Entry extends ViewableData {

	protected $title, $description, $date, $link;

	public function __construct($title, $description, $date, $link) {
		$this->title = $title;
		$this->description = $description;
		$this->date = $date;
		$this->link = $link;
	}

	public static $casting = array(
		'Date' => 'SS_Datetime'
	);

	public function Link() {
		return $this->link;
	}

	public function Description() {
		return $this->description;
	}

	public function Title() {
		return $this->title;
	}

	public function Date() {
		return $this->date;
	}

}
class RegistryImportFeed_Controller extends Controller {

	private static $allowed_actions = array(
		'latest'
	);

	public static $url_handlers = array(
		'$Action/$ModelClass' => 'handleAction',
	);

	public function latest($request) {
		$feed = new RegistryImportFeed();
		$feed->setModelClass($request->param('ModelClass'));
		return $feed->getLatest()->outputToBrowser();
	}

}
