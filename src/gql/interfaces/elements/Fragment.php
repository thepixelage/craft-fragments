<?php

namespace thepixelage\fragments\gql\interfaces\elements;

use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use craft\gql\TypeManager;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use thepixelage\fragments\gql\types\generators\FragmentType as FragmentTypeGenerator;

class Fragment extends Element
{
    public static function getName(): string
    {
        return 'FragmentInterface';
    }

    public static function getTypeGenerator(): string
    {
        return FragmentTypeGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all fragments.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        FragmentTypeGenerator::generateTypes();

        return $type;
    }

//    public static function getFieldDefinitions(): array
//    {
//        return TypeManager::prepareFieldDefinitions(array_merge(
//            parent::getFieldDefinitions(),
//            [
//                'settings' => [
//                    'name' => 'settings',
//                    'type' => Type::listOf(Type::string()),
//                    'description' => 'Whether the fragment is approved.'
//                ],
//            ]
//        ), self::getName());
//    }
}
