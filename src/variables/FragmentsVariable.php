<?php

namespace thepixelage\fragments\variables;

use craft\web\twig\variables\CraftVariable;
use thepixelage\fragments\Plugin;

class FragmentsVariable extends CraftVariable
{
    public function getAllZones()
    {
        return Plugin::$plugin->zones->getAllZones();
    }

    public function getAllFragmentTypes()
    {
        return Plugin::$plugin->fragmentTypes->getAllFragmentTypes();
    }
}
