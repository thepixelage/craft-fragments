<?php

namespace thepixelage\fragments\fields;

use Craft;
use craft\fields\BaseRelationField;
use thepixelage\fragments\elements\Fragment;

class Fragments extends BaseRelationField
{
    public static function displayName(): string
    {
        return Craft::t('fragments', "Fragments");
    }

    protected static function elementType(): string
    {
        return Fragment::class;
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('fragments', "Add a fragment");
    }
}
