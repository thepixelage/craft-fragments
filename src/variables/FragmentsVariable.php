<?php

namespace thepixelage\fragments\variables;

use Craft;
use thepixelage\fragments\elements\db\FragmentQuery;
use thepixelage\fragments\elements\Fragment;
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

    public function fragments($criteria = null): FragmentQuery
    {
        $query = Fragment::find();
        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }
}
