<?php

namespace SilverStripe\Registry\Tests\Stub;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Registry\RegistryPage;

class RegistryPageTestPage extends RegistryPage implements TestOnly
{
    private static $table_name = 'RegistryPageTestPage';
}
