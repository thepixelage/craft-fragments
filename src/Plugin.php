<?php

namespace thepixelage\fragments;

class Plugin extends \craft\base\Plugin
{
    public static $plugin;

    public $schemaVersion = '1.0.0';
    public $hasCpSettings = true;
    public $hasCpSection = false;

    public function init()
    {
        parent::init();
        self::$plugin = $this;
    }
}
