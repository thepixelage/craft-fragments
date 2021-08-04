<?php

namespace thepixelage\fragments\variables;

use thepixelage\fragments\Plugin;
use thepixelage\fragments\services\FragmentTypes;
use thepixelage\fragments\services\Zones;

class FragmentsVariable
{
    public function getTypes(): FragmentTypes
    {
        return Plugin::getInstance()->types;
    }

    public function getZones(): Zones
    {
        return Plugin::getInstance()->zones;
    }
}
