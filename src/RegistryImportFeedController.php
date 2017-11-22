<?php

namespace SilverStripe\Registry;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Registry\RegistryDataInterface;

class RegistryImportFeedController extends Controller
{
    private static $allowed_actions = [
        'latest',
    ];

    private static $url_handlers = [
        '$Action/$ModelClass' => 'handleAction',
    ];

    /**
     * Get an RSS feed of the latest data imports that were made for this registry model. This will only
     * return a valid result for classes that exist and implement the {@link RegistryDataInterface} interface.
     *
     * @param HTTPRequest $request
     * @return DBHTMLText
     */
    public function latest($request)
    {
        $feed = RegistryImportFeed::create();
        $modelClass = $this->unsanitiseClassName($request->param('ModelClass'));

        if (!class_exists($modelClass) || !(singleton($modelClass) instanceof RegistryDataInterface)) {
            return $this->httpError(404);
        }

        $feed->setModelClass($modelClass);
        return $feed->getLatest()->outputToBrowser();
    }

    /**
     * See {@link \SilverStripe\Admin\ModelAdmin::unsanitiseClassName}
     */
    protected function unsanitiseClassName($class)
    {
        return str_replace('-', '\\', $class);
    }
}
