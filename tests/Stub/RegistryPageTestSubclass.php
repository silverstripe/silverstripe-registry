<?php

namespace SilverStripe\Registry\Tests\Stub;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Registry\RegistryPage;
use SilverStripe\Registry\RegistryPageController;

class RegistryPageTestSubclass extends RegistryPage implements TestOnly
{
    private static $table_name = 'RegistryPageTestSubclass';
}
