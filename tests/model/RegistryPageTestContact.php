<?php
class RegistryPageTestContact extends DataObject implements RegistryDataInterface {

	private static $db = array(
		'FirstName' => 'Varchar(50)',
		'Surname' => 'Varchar(50)'
	);

	private static $summary_fields = array(
		'FirstName' => 'First name',
		'Surname' => 'Surname'
	);

	public function getSearchFields() {
		return new FieldList(
			new TextField('FirstName', 'First name'),
			new TextField('Surname', 'Surname')
		);
	}

}
