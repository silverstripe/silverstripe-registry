<?php

namespace SilverStripe\Registry;

use SilverStripe\Control\Controller;

class RegistryImportFeedController extends Controller
{
    private static $allowed_actions = [
        'latest',
    ];

    private static $url_handlers = [
        '$Action/$ModelClass' => 'handleAction',
    ];

    public function latest($request)
    {
        $feed = RegistryImportFeed::create();
        $feed->setModelClass($request->param('ModelClass'));
        return $feed->getLatest()->outputToBrowser();
    }
}
