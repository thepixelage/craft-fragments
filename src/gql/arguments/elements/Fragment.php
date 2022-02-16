<?php

namespace thepixelage\fragments\gql\arguments\elements;

use craft\gql\base\ElementArguments;
use GraphQL\Type\Definition\Type;

class Fragment extends ElementArguments
{
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'type' => [
                'name' => 'type',
                'type' => Type::string(),
                'description' => 'Narrows the query results to only fragments of this type.'
            ],
            'zone' => [
                'name' => 'zone',
                'type' => Type::string(),
                'description' => 'Narrows the query results to only fragments in this zone.'
            ],
            'currentUrl' => [
                'name' => 'currentUrl',
                'type' => Type::string(),
                'description' => 'Current page URL to match against visibility rules, if any.'
            ],
        ]);
    }
}
