<?php

namespace thepixelage\fragments\fields;

use Craft;
use craft\fields\BaseRelationField;
use craft\models\GqlSchema;
use craft\services\Gql as GqlService;
use GraphQL\Type\Definition\Type;
use thepixelage\fragments\elements\Fragment;
use thepixelage\fragments\gql\arguments\elements\Fragment as FragmentArguments;
use thepixelage\fragments\gql\interfaces\elements\Fragment as FragmentInterface;
use thepixelage\fragments\gql\resolvers\elements\Fragment as FragmentResolver;
use thepixelage\fragments\helpers\Gql as GqlHelper;

/**
 *
 * @property-read array $contentGqlType
 */
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

    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return GqlHelper::canQueryFragments();
    }

    public function getContentGqlType(): array
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(FragmentInterface::getType()),
            'args' => FragmentArguments::getArguments(),
            'resolve' => FragmentResolver::class . '::resolve',
            'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
    }
}
