<?php
class RegistryPage extends Page {

	private static $description = 'Shows large series of data in a filterable, searchable, and paginated list';

	private static $db = array(
		'DataClass' => 'Varchar(100)',
		'PageLength' => 'Int'
	);

	public static $page_length_default = 10;

	public function fieldLabels($includerelations = true) {
		$labels = parent::fieldLabels($includerelations);
		$labels['DataClass'] = _t('RegistryPage.DataClassFieldLabel', "Data Class");
		$labels['PageLength'] = _t('RegistryPage.PageLengthFieldLabel', "Results page length");
		
		return $labels;
	}

	public function getDataClasses() {
		$map = array();
		foreach(ClassInfo::implementorsOf('RegistryDataInterface') as $class) {
			$map[$class] = singleton($class)->singular_name();
		}
		return $map;
	}

	public function getDataClass() {
		return $this->getField('DataClass');
	}

	public function getDataSingleton() {
		$class = $this->getDataClass();
		if (!$class) {
			return NULL;
		}
		return singleton($this->getDataClass());
	}

	public function getPageLength() {
		$length = $this->getField('PageLength');
		return $length ? $length : self::$page_length_default;
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$classDropdown = new DropdownField('DataClass', $this->fieldLabel('DataClass'), $this->getDataClasses());
		$classDropdown->setEmptyString(_t('RegistryPage.SelectDropdownDefault','Select one'));
		$fields->addFieldToTab('Root.Main', $classDropdown, 'Content');
		$fields->addFieldToTab('Root.Main', new NumericField('PageLength', $this->fieldLabel('PageLength')), 'Content');
		return $fields;
	}

	public function LastUpdated() {
		$elements = new DataList($this->dataClass);
		$lastUpdated = new SS_Datetime('LastUpdated');
		$lastUpdated->setValue($elements->max('LastEdited'));
		return $lastUpdated;
	}

	/**
	 * Modified version of Breadcrumbs, to cater for viewing items.
	 */
	public function Breadcrumbs($maxDepth = 20, $unlinked = false, $stopAtPageType = false, $showHidden = false) {
		$page = $this;
		$pages = array();
		
		while(
			$page  
 			&& (!$maxDepth || count($pages) < $maxDepth) 
 			&& (!$stopAtPageType || $page->ClassName != $stopAtPageType)
 		) {
			if($showHidden || $page->ShowInMenus || ($page->ID == $this->ID)) { 
				$pages[] = $page;
			}
			
			$page = $page->Parent;
		}

		// Add on the item we're currently showing.
		$controller = Controller::curr();
		if ($controller) {
			$request = $controller->getRequest();
			if ($request->param('Action') == 'show') {
				$id = $request->param('ID');
				if ($id) {
					$object = DataObject::get_by_id($this->getDataClass(), $id);
					array_unshift($pages, $object);
				}
			}
		}
		
		$template = new SSViewer('BreadcrumbsTemplate');
		
		return $template->process($this->customise(new ArrayData(array(
			'Pages' => new ArrayList(array_reverse($pages))
		))));
	}
}
class RegistryPage_Controller extends Page_Controller {

	private static $allowed_actions = array(
		'RegistryFilterForm',
		'show',
		'export'
	);

	/**
	 * Get all search query vars, compiled into a query string for a URL.
	 * This will escape all the variables to avoid XSS.
	 *
	 * @return string
	 */
	public function AllQueryVars() {
		return Convert::raw2xml(http_build_query($this->_queryVars()));
	}

	/**
	 * Get all search query vars except Sort and Dir, compiled into a query link.
	 * This will escape all the variables to avoid XSS.
	 *
	 * @return string
	 */
	public function QueryLink() {
		$vars = $this->_queryVars();
		unset($vars['Sort']);
		unset($vars['Dir']);

		return Convert::raw2xml($this->Link('RegistryFilterForm') . '?' . http_build_query($vars));
	}

	public function Sort() {
		return isset($_GET['Sort']) ? $_GET['Sort'] : '';
	}

	/**
	 * Return the opposite direction from the currently sorted column's direction.
	 * @return string
	 */
	public function OppositeDirection() {
		// If direction is set, then just reverse it.
		$direction = $this->request->getVar('Dir');
		if ($direction) {
			if ($direction == 'ASC') {
				return 'DESC';
			}
			return 'ASC';
		}

		// If the sort column is set, then we're sorting by ASC (default is omitted)
		if ($this->request->getVar('Sort')) {
			return 'DESC';
		}

		// Otherwise we're not sorting at all so default to ASC.
		return 'ASC';
	}

	public function RegistryFilterForm() {
		$singleton = $this->dataRecord->getDataSingleton();
		if (!$singleton) {
			return;
		}

		$fields = $singleton->getSearchFields();

		// Add the sort information.
		$vars = $this->getRequest()->getVars();
		$fields->merge(new FieldList(
			new HiddenField('Sort', 'Sort', (!$vars || empty($vars['Sort'])) ? 'ID' : $vars['Sort']),
			new HiddenField('Dir', 'Dir', (!$vars || empty($vars['Dir'])) ? 'ASC' : $vars['Dir'])
		));

		$actions = new FieldList(
			FormAction::create('doRegistryFilter')->setTitle('Filter')->addExtraClass('btn btn-primary primary'),
			FormAction::create('doRegistryFilterReset')->setTitle('Clear')->addExtraClass('btn')
		);

		$form = new Form($this, 'RegistryFilterForm', $fields, $actions);
		$form->loadDataFrom($this->request->getVars());
		$form->disableSecurityToken();
		$form->setFormMethod('get');

		return $form;
	}

	/**
	 * Build up search filters from user's search criteria and hand off
	 * to the {@link query()} method to search against the database.
	 *
	 * @param array $data Form request data
	 * @param Form Form object for submitted form
	 * @param SS_HTTPRequest
	 * @return array
	 */
	public function doRegistryFilter($data, $form, $request) {
		// Basic parameters
		$parameters = array(
			'start' => 0,
			'Sort' => 'ID',
			'Dir' => 'ASC'
		);

		// Data record-specific parameters
		$singleton = $this->dataRecord->getDataSingleton();
		if ($singleton) {
			$fields = $singleton->getSearchFields();
			if ($fields) foreach ($fields as $field) {
				$parameters[$field->Name] = '';
			}
		}

		// Read them from the request
		foreach ($parameters as $key => $default) {
			$value = $this->request->getVar($key);
			if (!$value || $value == $default) {
				unset($parameters[$key]);
			} else {
				$parameters[$key] = $value;
			}
		}

		// Link back to this page with the relevant parameters.
		$link = $this->AbsoluteLink();
		foreach ($parameters as $key => $value) {
			$link = HTTP::setGetVar($key, $value, $link, '&');
		}
		$this->redirect($link);
	}

	public function doRegistryFilterReset($data, $form, $request) {
		// Link back to this page with no relevant parameters.
		$this->redirect($this->AbsoluteLink());
	}

	public function RegistryEntries($paginated = true) {
		$variables = $this->request->getVars();
		$singleton = $this->dataRecord->getDataSingleton();

		// Pagination
		$start = isset($variables['start']) ? (int)$variables['start'] : 0;

		// Ordering
		$sort = isset($variables['Sort']) && $variables['Sort'] ? Convert::raw2sql($variables['Sort']) : 'ID';
		if (!$singleton->hasDatabaseField($sort)) {
			$sort = 'ID';
		}
		$direction = (!empty($variables['Dir']) && in_array($variables['Dir'], array('ASC', 'DESC'))) ? $variables['Dir'] : 'ASC';
		$orderby = array($sort => $direction);
		
		// Filtering
		$where = array();
		if ($singleton) foreach($singleton->getSearchFields() as $field) {
			if(!empty($variables[$field->getName()])) {
				$where[] = sprintf('"%s" LIKE \'%%%s%%\'', $field->getName(), Convert::raw2sql($variables[$field->getName()]));
			}
		}

		return $this->queryList($where, $orderby, $start, $this->dataRecord->getPageLength(), $paginated);
	}

	public function Columns($result = null) {
		$columns = $this->dataRecord->getDataSingleton()->summaryFields();
		$list = new ArrayList();
		foreach($columns as $name => $title) {
			$list->push(new ArrayData(array(
				'Name' => $name,
				'Title' => $title,
				'Link' => (($result && $result->hasMethod('Link')) ? $result->Link() : ''),
				'Value' => ($result ? $result->obj($name) : '')
			)));
		}
		return $list;
	}

	/**
	 * Exports out all the data for the current search results.
	 * Sends the data to the browser as a CSV file.
	 */
	public function export($request) {
		$dataClass = $this->dataRecord->getDataClass();
		$resultColumns = $this->dataRecord->getDataSingleton()->fieldLabels();

		if (!file_exists(REGISTRY_EXPORT_PATH)) {
			mkdir(REGISTRY_EXPORT_PATH);
		}
		$base = REGISTRY_EXPORT_PATH . '/' . $dataClass;
		if(!file_exists($base)) {
			mkdir($base);
		}

		$filepath = sprintf('%s/export-%s.csv', $base, date('Y-m-dHis'));
		$file = fopen($filepath, 'w');

		$cols = array_keys($resultColumns);
		// put the headers in the first row
		fputcsv($file, $cols, ',', '"');

		// put the data in the rows after
		foreach($this->RegistryEntries(false) as $result) {
			$item = array();
			foreach($cols as $col) {
				$item[] = $result->$col;
			}
			fputcsv($file, $item, ',', '"');
		}

		fclose($file);

		// if the headers can't be sent (i.e. running a unit test, or something)
		// just return the file path so the user can manually download the csv
		if(!headers_sent() && !SapphireTest::is_running_test()) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . basename($filepath));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($filepath));
			ob_clean();
			flush();
			readfile($filepath);

			unlink($filePath);
		} else {
			$contents = file_get_contents($filepath);
			unlink($filePath);
			return $contents;
		}
	}

	public function show($request) {
		$data = DataObject::get_by_id($this->DataClass, $request->param('ID'));
		if(!($data && $data->exists())) {
			return $this->httpError(404);
		}

		return $this->customise($data)->renderWith($this->getTemplateList('show'));
	}

	/**
	 * Perform a search against the data table.
	 *
	 * @param array $where Array of strings to add into the WHERE clause
	 * @param array $orderby Array of column as key, to direction as value to add into the ORDER BY clause
	 * @param string|int $start Record to start at (for paging)
	 * @param string|int $pageLength Number of results per page (for paging)
	 * @param boolean $paged Paged results or not?
	 * @return ArrayList|PaginatedList
	 */
	protected function queryList($where = array(), $orderby = array(), $start, $pageLength, $paged = true) {
		$dataClass = $this->dataRecord->getDataClass();
		if (!$dataClass) {
			return new PaginatedList(new ArrayList());
		}

		$resultColumns = $this->dataRecord->getDataSingleton()->summaryFields();
		$resultColumns['ID'] = 'ID';
		$results = new ArrayList();

		$query = new SQLQuery();
		$query->setSelect(array_keys($resultColumns))->setFrom($dataClass);
		$query->addWhere($where);
		$query->addOrderBy($orderby);
		$query->setConnective('AND');

		if($paged) {
			$query->setLimit($pageLength, $start);
		}

		foreach($query->execute() as $record) {
			$result = new $dataClass($record);
			$result->Columns = $this->Columns($result); // we attach Columns here so the template can loop through them on each result
			$results->push($result);
		}

		if($paged) {
			$list = new PaginatedList($results);
			$list->setPageStart($start);
			$list->setPageLength($pageLength);
			$list->setTotalItems($query->unlimitedRowCount());
			$list->setLimitItems(false);
		} else {
			$list = $results;
		}

		return $list;
	}

	/**
	 * Compiles all available GET variables for the result
	 * columns into an array. Used internally, not to be
	 * used directly with the templates or outside classes.
	 *
	 * This will NOT escape values to avoid XSS.
	 *
	 * @return array
	 */
	protected function _queryVars() {
		$resultColumns = $this->dataRecord->getDataSingleton()->getSearchFields();
		$columns = array();
		foreach($resultColumns as $field) {
			$columns[$field->getName()] = '';
		}

		$arr = array_merge(
			$columns,
			array(
				'action_doRegistryFilter' => 'Filter',
				'Sort' => '',
				'Dir' => ''
			)
		);

		foreach($arr as $key => $val) {
			if(isset($_GET[$key])) $arr[$key] = $_GET[$key];
		}

		return $arr;
	}

	public function getTemplateList($action) {
		// Add action-specific templates for inheritance chain
		$templates = array();
		$parentClass = $this->class;
		if($action && $action != 'index') {
			$parentClass = $this->class;
			while($parentClass != "Controller") {
				$templates[] = strtok($parentClass,'_') . '_' . $action;
				$parentClass = get_parent_class($parentClass);
			}
		}
		// Add controller templates for inheritance chain
		$parentClass = $this->class;
		while($parentClass != "Controller") {
			$templates[] = strtok($parentClass,'_');
			$parentClass = get_parent_class($parentClass);
		}

		$templates[] = 'Controller';

		// remove duplicates
		$templates = array_unique($templates);

		$actionlessTemplates = array();

		if($action && $action != 'index') {
			array_unshift($templates, 'RegistryPage_' . $this->DataClass . '_' . $action);
		}
		array_unshift($actionlessTemplates, 'RegistryPage_' . $this->DataClass);

		$parentClass = get_class($this->dataRecord);
		while($parentClass != 'RegistryPage') {
			if($action && $action != 'index') {
				array_unshift($templates, $parentClass . '_' . $this->DataClass . '_' . $action);
			}
			array_unshift($actionlessTemplates, $parentClass . '_' . $this->DataClass);

			$parentClass = get_parent_class($parentClass);
		}

		$index = 0;
		while ($index < count($templates) && $templates[$index] != 'RegistryPage') {
			$index++;
		}

		return array_merge(array_slice($templates, 0, $index), $actionlessTemplates, array_slice($templates, $index));
	}

}
