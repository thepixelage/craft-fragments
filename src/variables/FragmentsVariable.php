<?php

namespace thepixelage\fragments\variables;

use thepixelage\fragments\Plugin;
use thepixelage\fragments\services\FragmentTypes;
use thepixelage\fragments\services\Zones;

class FragmentsVariable
{
    public function getFragmentTypes(): FragmentTypes
    {
        return Plugin::getInstance()->fragmentTypes;
    }

    public function getZones(): Zones
    {
        return Plugin::getInstance()->zones;
    }
}
