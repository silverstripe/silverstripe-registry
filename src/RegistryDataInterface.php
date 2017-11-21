<?php

namespace SilverStripe\Registry;

use FieldList;

interface RegistryDataInterface
{
    /**
     * A FieldList containing FormField objects that represent
     * the fields in the data search form.
     *
     * Example:
     *
     * <code>
     * public function getSearchFields()
     * {
     *     return new FieldList(
     *         new TextField('FirstName', 'First name'),
     *         new TextField('Surname', 'Surname')
     *     );
     * }
     * </code>
     *
     * @return FieldList
     */
    public function getSearchFields();
}
