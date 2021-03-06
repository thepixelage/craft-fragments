<?php

namespace thepixelage\fragments\gql\types\generators;

use Craft;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql as GqlHelper;
use thepixelage\fragments\elements\Fragment as FragmentElement;
use thepixelage\fragments\gql\interfaces\elements\Fragment as FragmentInterface;
use thepixelage\fragments\gql\types\elements\Fragment;
use thepixelage\fragments\Plugin;

class FragmentType extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
    {
        $fragmentTypes = Plugin::$plugin->fragmentTypes->getAllFragmentTypes();
        $gqlTypes = [];

        foreach ($fragmentTypes as $fragmentType) {
            $requiredContexts = FragmentElement::gqlScopesByContext($fragmentType);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts)) {
                continue;
            }

            $type = static::generateType($fragmentType);
            $gqlTypes[$type->name] = $type;
        }

        return $gqlTypes;
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): mixed
    {
        /** @var \thepixelage\fragments\models\FragmentType $context */
        $typeName = FragmentElement::gqlTypeNameByContext($context);
        $contentFieldGqlTypes = self::getContentFields($context);

        $fragmentFields = Craft::$app->getGql()->prepareFieldDefinitions(
            array_merge(
                FragmentInterface::getFieldDefinitions(),
                $contentFieldGqlTypes
            ),
            $typeName
        );

        return GqlEntityRegistry::getEntity($typeName) ?:
            GqlEntityRegistry::createEntity(
                $typeName,
                new Fragment([
                    'name' => $typeName,
                    'fields' => function() use ($fragmentFields) {
                        return $fragmentFields;
                    },
                ])
            );
    }
}
