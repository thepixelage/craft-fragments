<?php

namespace thepixelage\fragments\services;

use craft\base\Component;
use thepixelage\fragments\Plugin;

class Fragments extends Component
{
    public function hasTypesAndZonesSetup(): bool
    {
        return (
            Plugin::getInstance()->zones->getZoneCount() > 0 &&
            Plugin::getInstance()->fragmentTypes->getFragmentTypeCount() > 0
        );
    }
}
