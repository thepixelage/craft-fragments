<?php

namespace thepixelage\fragments\behaviors;

use Craft;
use thepixelage\fragments\elements\db\FragmentQuery;
use thepixelage\fragments\elements\Fragment;
use yii\base\Behavior;

class CraftVariableBehavior extends Behavior
{
    public function fragments($criteria = null): FragmentQuery
    {
        $query = Fragment::find();
        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }
}
