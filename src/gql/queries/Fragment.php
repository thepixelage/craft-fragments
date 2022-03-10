<?php

namespace thepixelage\fragments\gql\queries;

use craft\gql\base\Query;
use GraphQL\Type\Definition\Type;
use thepixelage\fragments\helpers\Gql as GqlHelper;
use thepixelage\fragments\gql\interfaces\elements\Fragment as FragmentInterface;
use thepixelage\fragments\gql\arguments\elements\Fragment as FragmentArguments;
use thepixelage\fragments\gql\resolvers\elements\Fragment as FragmentResolver;

class Fragment extends Query
{
    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryFragments()) {
            return [];
        }

        return [
            'fragments' => [
                'type' => Type::listOf(FragmentInterface::getType()),
                'args' => FragmentArguments::getArguments(),
                'resolve' => FragmentResolver::class . '::resolve',
                'description' => 'This query is used to query for fragments.'
            ],
        ];
    }
}
