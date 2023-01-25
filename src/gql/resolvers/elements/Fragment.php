<?php

namespace thepixelage\fragments\gql\resolvers\elements;

use craft\gql\base\ElementResolver;
use thepixelage\fragments\elements\Fragment as FragmentElement;
use thepixelage\fragments\helpers\Gql as GqlHelper;

class Fragment extends ElementResolver
{
    public static function prepareQuery(mixed $source, array $arguments, $fieldName = null): mixed
    {
        if ($source === null) {
            // If this is the beginning of a resolver chain, start fresh
            $query = FragmentElement::find();
        } else {
            // If not, get the prepared element query
            $query = $source->$fieldName;
        }

        // Return the query if it’s preloaded
        if (is_array($query)) {
            return $query;
        }

        if (isset($arguments['limit'])) {
            $arguments['queryLimit'] = $arguments['limit'];
            unset($arguments['limit']);
        }

        foreach ($arguments as $key => $value) {
            if (method_exists($query, $key) || property_exists($query, $key)) {
                $query->$key($value);
            }
        }

        // Don’t return anything that’s not allowed
        if (!GqlHelper::canQueryFragments()) {
            return [];
        }

        return $query;
    }
}
