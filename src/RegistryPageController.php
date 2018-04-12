<?php

namespace SilverStripe\Registry;

use PageController;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTP;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Registry\Exception\RegistryException;
use SilverStripe\View\ArrayData;
use SilverStripe\View\ViewableData;

class RegistryPageController extends PageController
{
    private static $allowed_actions = [
        'RegistryFilterForm',
        'show',
        'export',
    ];

    /**
     * Whether to output headers when sending the export file. This can be disabled for example in unit tests.
     *
     * @config
     * @var bool
     */
    private static $output_headers = true;

    /**
     * Get all search query vars, compiled into a query string for a URL.
     * This will escape all the variables to avoid XSS.
     *
     * @return string
     */
    public function AllQueryVars()
    {
        return Convert::raw2xml(http_build_query($this->queryVars()));
    }

    /**
     * Get all search query vars except Sort and Dir, compiled into a query link.
     * This will escape all the variables to avoid XSS.
     *
     * @return string
     */
    public function QueryLink()
    {
        $vars = $this->queryVars();
        unset($vars['Sort']);
        unset($vars['Dir']);

        return Convert::raw2xml($this->Link('RegistryFilterForm') . '?' . http_build_query($vars));
    }

    public function Sort()
    {
        return isset($_GET['Sort']) ? $_GET['Sort'] : '';
    }

    /**
     * Return the opposite direction from the currently sorted column's direction.
     * @return string
     */
    public function OppositeDirection()
    {
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

    public function RegistryFilterForm()
    {
        $singleton = $this->dataRecord->getDataSingleton();
        if (!$singleton) {
            return;
        }

        $fields = $singleton->getSearchFields();

        // Add the sort information.
        $vars = $this->getRequest()->getVars();
        $fields->merge(FieldList::create(
            HiddenField::create('Sort', 'Sort', (!$vars || empty($vars['Sort'])) ? 'ID' : $vars['Sort']),
            HiddenField::create('Dir', 'Dir', (!$vars || empty($vars['Dir'])) ? 'ASC' : $vars['Dir'])
        ));

        $actions = FieldList::create(
            FormAction::create('doRegistryFilter')->setTitle('Filter')->addExtraClass('btn btn-primary primary'),
            FormAction::create('doRegistryFilterReset')->setTitle('Clear')->addExtraClass('btn')
        );

        $form = Form::create($this, 'RegistryFilterForm', $fields, $actions);
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
     * @param HTTPRequest
     * @return array
     */
    public function doRegistryFilter($data, $form, $request)
    {
        // Basic parameters
        $parameters = [
            'start' => 0,
            'Sort' => 'ID',
            'Dir' => 'ASC',
        ];

        // Data record-specific parameters
        $singleton = $this->dataRecord->getDataSingleton();
        if ($singleton) {
            $fields = $singleton->getSearchFields();
            if ($fields) {
                foreach ($fields as $field) {
                    $parameters[$field->Name] = '';
                }
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

    public function doRegistryFilterReset($data, $form, $request)
    {
        // Link back to this page with no relevant parameters.
        $this->redirect($this->AbsoluteLink());
    }

    public function RegistryEntries($paginated = true)
    {
        $variables = $this->request->getVars();
        $singleton = $this->dataRecord->getDataSingleton();

        // Pagination
        $start = isset($variables['start']) ? (int)$variables['start'] : 0;

        // Ordering
        $sort = isset($variables['Sort']) && $variables['Sort'] ? Convert::raw2sql($variables['Sort']) : 'ID';
        if ($this->canSortBy($sort)) {
            $sort = 'ID';
        }
        $direction = (!empty($variables['Dir']) && in_array($variables['Dir'], ['ASC', 'DESC']))
            ? $variables['Dir']
            : 'ASC';
        $orderby = ["\"{$sort}\"" => $direction];

        // Filtering
        $where = [];
        if ($singleton) {
            foreach ($singleton->getSearchFields() as $field) {
                if (!empty($variables[$field->getName()])) {
                    $where[] = sprintf(
                        '"%s" LIKE \'%%%s%%\'',
                        $field->getName(),
                        Convert::raw2sql($variables[$field->getName()])
                    );
                }
            }
        }

        return $this->queryList($where, $orderby, $start, $this->dataRecord->getPageLength(), $paginated);
    }

    /**
     * Loosely check if the record can be sorted by a property
     * @param  string $property
     * @return boolean
     */
    public function canSortBy($property)
    {
        $canSort = false;
        $singleton = $this->dataRecord->getDataSingleton();

        if ($singleton) {
            $properties = explode('.', $property);

            $relationClass = $singleton->getRelationClass($properties[0]);
            if ($relationClass) {
                if (count($properties) <= 2 && singleton($relationClass)->hasDatabaseField($properties[1])) {
                    $canSort = true;
                }
            } elseif ($singleton instanceof DataObject) {
                if ($singleton->hasDatabaseField($property)) {
                    $canSort = true;
                }
            }
        }

        return $canSort;
    }

    /**
     * Format a set of columns, used for headings and row data
     * @param  ViewabledData $result The row context
     * @return ArrayList
     */
    public function Columns($result = null)
    {
        $singleton = $this->dataRecord->getDataSingleton();
        $columns = $singleton->summaryFields();
        $list = ArrayList::create();

        foreach ($columns as $name => $title) {
            // Check for unwanted parameters
            if (preg_match('/[()]/', $name)) {
                throw new RegistryException(_t(
                    'SilverStripe\\Registry\\RegistryPageController.UNWANTEDCOLUMNPARAMETERS',
                    "Columns do not accept parameters"
                ));
            }

            // Get dot deliniated properties
            $properties = explode('.', $name);

            // Increment properties for value
            $context = $result;
            foreach ($properties as $property) {
                if ($context instanceof ViewableData) {
                    $context = $context->obj($property);
                }
            }

            // Check for link
            $link = null;
            $useLink = $singleton->config()->get('use_link');
            if ($useLink !== false) {
                if ($result && $result->hasMethod('link')) {
                    $link = $result->Link();
                }
            }

            // Format column
            $list->push(ArrayData::create([
                'Name' => $name,
                'Title' => $title,
                'Link' => $link,
                'Value' => isset($context) ? $context : null,
                'CanSort' => $this->canSortBy($name)
            ]));
        }
        return $list;
    }

    /**
     * Exports out all the data for the current search results.
     * Sends the data to the browser as a CSV file.
     */
    public function export($request)
    {
        $dataClass = $this->dataRecord->getDataClass();
        $resultColumns = $this->dataRecord->getDataSingleton()->fieldLabels();

        // Used for the browser, not stored on the server
        $filepath = sprintf('export-%s.csv', date('Y-m-dHis'));

        // Allocates up to 1M of memory storage to write to, then will fail over to a temporary file on the filesystem
        $handle = fopen('php://temp/maxmemory:' . (1024 * 1024), 'w');

        $cols = array_keys($resultColumns);

        // put the headers in the first row
        fputcsv($handle, $cols);

        // put the data in the rows after
        foreach ($this->RegistryEntries(false) as $result) {
            $item = [];
            foreach ($cols as $col) {
                $item[] = $result->$col;
            }
            fputcsv($handle, $item);
        }

        rewind($handle);

        // if the headers can't be sent (i.e. running a unit test, or something)
        // just return the file path so the user can manually download the csv
        if (!headers_sent() && $this->config()->get('output_headers')) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $filepath);
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . fstat($handle)['size']);
            ob_clean();
            flush();

            echo stream_get_contents($handle);

            fclose($handle);
        } else {
            $contents = stream_get_contents($handle);
            fclose($handle);

            return $contents;
        }
    }

    public function show($request)
    {
        // If Id is not numeric, then return an error page
        if (!is_numeric($request->param('ID'))) {
            return $this->httpError(404);
        }

        $entry = DataObject::get_by_id($this->DataClass, $request->param('ID'));

        if (!($entry && $entry->exists())) {
            return $this->httpError(404);
        }

        return $this->customise([
            'Entry' => $entry
        ]);
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
    protected function queryList(array $where, array $orderby, $start, $pageLength, $paged = true)
    {
        $dataClass = $this->dataRecord->getDataClass();
        if (!$dataClass) {
            return PaginatedList::create(ArrayList::create());
        }

        $tableName = DataObject::getSchema()->tableName($dataClass);

        $summarisedModel = $this->dataRecord->getDataSingleton();
        $resultColumns = $summarisedModel->summaryFields();

        // Utilise DataObject::$searchable_fields
        $resultDBOnlyColumns = [];
        $fields = $summarisedModel->config()->get('searchable_fields');
        foreach ($fields as $field) {
            $resultDBOnlyColumns[$field] = $field;
        }

        $resultDBOnlyColumns['ID'] = 'ID';
        $results = ArrayList::create();

        $query = SQLSelect::create();
        $query
            ->setSelect($this->escapeSelect(array_keys($resultDBOnlyColumns)))
            ->setFrom('"' . $tableName . '"');
        $query->addWhere($where);
        $query->addOrderBy($orderby);
        $query->setConnective('AND');

        if ($paged) {
            $query->setLimit($pageLength, $start);
        }

        foreach ($query->execute() as $record) {
            $result = Injector::inst()->create($dataClass, $record);
            // we attach Columns here so the template can loop through them on each result
            $result->Columns = $this->Columns($result);
            $results->push($result);
        }

        if ($paged) {
            $list = PaginatedList::create($results);
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
     * Safely escape a list of "select" candidates for a query
     *
     * @param array $names List of select fields
     * @return array List of names, with each name double quoted
     */
    protected function escapeSelect($names)
    {
        return array_map(
            function ($var) {
                return "\"{$var}\"";
            },
            $names
        );
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
    protected function queryVars()
    {
        $resultColumns = $this->dataRecord->getDataSingleton()->getSearchFields();
        $columns = [];
        foreach ($resultColumns as $field) {
            $columns[$field->getName()] = '';
        }

        $arr = array_merge(
            $columns,
            [
                'action_doRegistryFilter' => 'Filter',
                'Sort' => '',
                'Dir' => ''
            ]
        );

        foreach ($arr as $key => $val) {
            if (isset($_GET[$key])) {
                $arr[$key] = $_GET[$key];
            }
        }

        return $arr;
    }

    public function getTemplateList($action)
    {
        // Add action-specific templates for inheritance chain
        $templates = [];
        $parentClass = get_class($this);
        if ($action && $action !== 'index') {
            $parentClass = get_class($this);
            while ($parentClass !== Controller::class) {
                $templates[] = strtok($parentClass, '_') . '_' . $action;
                $parentClass = get_parent_class($parentClass);
            }
        }
        // Add controller templates for inheritance chain
        $parentClass = get_class($this);
        while ($parentClass !== Controller::class) {
            $templates[] = strtok($parentClass, '_');
            $parentClass = get_parent_class($parentClass);
        }

        $templates[] = Controller::class;

        // remove duplicates
        $templates = array_unique($templates);

        $actionlessTemplates = [];

        if ($action && $action !== 'index') {
            array_unshift($templates, $this->DataClass . '_RegistryPage_' . $action);
        }
        array_unshift($actionlessTemplates, $this->DataClass . '_RegistryPage');

        $parentClass = get_class($this->dataRecord);
        while ($parentClass !== RegistryPage::class) {
            if ($action && $action != 'index') {
                array_unshift($templates, $parentClass . '_' . $action);
            }
            array_unshift($actionlessTemplates, $parentClass);

            $parentClass = get_parent_class($parentClass);
        }

        $index = 0;
        while ($index < count($templates) && $templates[$index] !== RegistryPage::class) {
            $index++;
        }

        return array_merge(array_slice($templates, 0, $index), $actionlessTemplates, array_slice($templates, $index));
    }

    /**
     * Sanitise a PHP class name for display in URLs etc
     *
     * @return string
     */
    public function getClassNameForUrl($className)
    {
        return str_replace('\\', '-', $className);
    }
}
