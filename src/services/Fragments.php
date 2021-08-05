<?php

namespace thepixelage\fragments\services;

use Craft;
use craft\base\Component;
use thepixelage\fragments\elements\Fragment;

class Fragments extends Component
{
    public function getFragmentById(int $fragmentId, int $siteId = null): ?Fragment
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($fragmentId, Fragment::class, $siteId);
    }
}
