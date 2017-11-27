<?php

namespace SilverStripe\Registry;

use SilverStripe\View\ViewableData;

class RegistryImportFeedEntry extends ViewableData
{
    protected $title;
    protected $description;
    protected $date;
    protected $link;

    private static $casting = [
        'Date' => 'DBDatetime',
    ];

    public function __construct($title, $description, $date, $link)
    {
        $this->title = $title;
        $this->description = $description;
        $this->date = $date;
        $this->link = $link;
    }

    public function Link()
    {
        return 'assets/' . $this->link;
    }

    public function Description()
    {
        return $this->description;
    }

    public function Title()
    {
        return $this->title;
    }

    public function Date()
    {
        return $this->date;
    }
}
