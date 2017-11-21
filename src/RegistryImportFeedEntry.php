<?php

namespace SilverStripe\Registry;

use ViewableData;

class RegistryImportFeedEntry extends ViewableData
{
    protected $title;
    protected $description;
    protected $date;
    protected $link;

    public function __construct($title, $description, $date, $link)
    {
        $this->title = $title;
        $this->description = $description;
        $this->date = $date;
        $this->link = $link;
    }

    public static $casting = array(
        'Date' => 'DBDatetime'
    );

    public function Link()
    {
        return $this->link;
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
