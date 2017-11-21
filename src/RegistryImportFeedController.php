<?php

namespace SilverStripe\Registry;

use Controller;

class RegistryImportFeedController extends Controller
{
    private static $allowed_actions = array(
        'latest'
    );

    public static $url_handlers = array(
        '$Action/$ModelClass' => 'handleAction',
    );

    public function latest($request)
    {
        $feed = new RegistryImportFeed();
        $feed->setModelClass($request->param('ModelClass'));
        return $feed->getLatest()->outputToBrowser();
    }
}
