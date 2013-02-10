# Registry module

## Introduction

This module allows a website user to search, find details for, and export search results
for a particular type of data.

## Requirements

 * SilverStripe 3.0.2+
 * MySQL 5.1+ or SQL Server 2008+ database

## Known issues

PostgreSQL databases might have problems with searches, as queries done using `LIKE` are case sensitive.

## Installation

Copy the registry directory into your SilverStripe project, then append dev/build?flush=all
to the website URL in your browser. e.g. http://mysite.com/dev/build?flush=all

## Instructions

To use the registry module, you need to define the data you wish to store and then set up the pages
to search through the data.

### Defining the data

Each registry is a list of a single type of DataObject. These DataObject definitions must implement
the `RegistryDataInterface` and the `getSearchFields` abstract function.

Here's an example of what a staff member definition might look like:

	class StaffMember extends DataObject implements RegistryDataInterface {
		public static $db = array(
			'Name' => 'Varchar(255)',
			'PhoneNumber' => 'Varchar(50)'
		);
		
		public function getSearchFields() {
			return new FieldList(
				new TextField('Name'),
				new TextField('PhoneNumber')
			);
		}
	}

Run /dev/build and now the Registry tab will appear in the CMS. From here you can use this tab to manage
your registry data. All DataObject classes that implement `RegistryDataInterface` will appear in here.

### Viewing the data

Create a new page of the "Registry Page" type. In the Content tab, find the "Data class" drop down
and set it to the DataObject that you just created, in this case "Staff Member".

Save and Publish the page and view it in the front end.

### Overriding the template

While the default template does its best to be functional and easy-to-style, it's quite likely that
you'll need to change the templates. You can do so by placing the templates `RegistryPage.ss` and
`RegistryPage_show.ss` in your themes templates/Layout folder. You can base these off the files found
in registry/templates/Layout.

As a further layer of customisation, you can create templates that will be only used when viewing
specific registries. So if you wanted to create a template that would only be used to view the
StaffMember registry, you would create `RegistryPage_StaffMember.ss` and `RegistryPage_StaffMember_show.ss`

