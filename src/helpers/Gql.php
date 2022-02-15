<?php

namespace thepixelage\fragments\helpers;

class Gql extends \craft\helpers\Gql
{
    public static function canQueryFragments(): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema();

        return isset($allowedEntities['fragmenttypes']);
    }
}
