<?php

namespace thepixelage\fragments\variables;

use thepixelage\fragments\Plugin;
use thepixelage\fragments\services\Fragments;
use thepixelage\fragments\services\FragmentTypes;
use thepixelage\fragments\services\Zones;

class FragmentsVariable
{
    public function getFragments(): Fragments
    {
        return Plugin::getInstance()->fragments;
    }

    public function getFragmentTypes(): FragmentTypes
    {
        return Plugin::getInstance()->fragmentTypes;
    }

    public function getZones(): Zones
    {
        return Plugin::getInstance()->zones;
    }
}
