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
            'entryUri' => [
                'name' => 'entryUri',
                'type' => Type::string(),
                'description' => 'Current page URI to match against visibility rules, if any. Empty string or single "/" will match `__home__`.'
            ],
            'userId' => [
                'name' => 'userId',
                'type' => Type::int(),
                'description' => 'Current user ID to match against visibility rules, if any.'
            ],
            'requestProps' => [
                'name' => 'requestProps',
                'type' => Type::string(),
                'description' => 'Current request to match against visibility rules, if any.'
            ],
        ]);
    }
}
