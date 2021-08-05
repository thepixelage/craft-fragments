<?php

namespace thepixelage\fragments\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use craft\models\FieldLayout;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\elements\db\FragmentQuery;
use thepixelage\fragments\models\FragmentType;
use thepixelage\fragments\Plugin;
use yii\base\InvalidConfigException;
use yii\db\Exception;

/**
 *
 * @property-read null|int $sourceId
 * @property-read FragmentType $fragmentType
 */
class Fragment extends Element
{
    public ?int $fragmentTypeId;

    public static function displayName(): string
    {
        return Craft::t('app', "Fragment");
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('app', "Fragments");
    }

    public static function find(): ElementQueryInterface
    {
        return new FragmentQuery(static::class);
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public function getIsEditable(): bool
    {
        return true;
    }

    /**
     * @throws InvalidConfigException
     */
    public function getFieldLayout(): ?FieldLayout
    {
        return parent::getFieldLayout() ?? $this->getFragmentType()->getFieldLayout();
    }

    /**
     * @throws InvalidConfigException
     */
    public function getFragmentType(): FragmentType
    {
        if ($this->fragmentTypeId === null) {
            throw new InvalidConfigException('Fragment is missing its fragment type ID');
        }

        $type = Plugin::$plugin->fragmentTypes->getFragmentTypeById($this->fragmentTypeId);

        if (!$type) {
            throw new InvalidConfigException('Invalid fragment fragment type ID: ' . $this->fragmentTypeId);
        }

        return $type;
    }

    /**
     * @throws InvalidConfigException
     */
    public function getCpEditUrl(): string
    {
        return sprintf('fragments/fragments/%s/%s', $this->getFragmentType()->handle, $this->id);
    }

    /**
     * @throws Exception
     */
    public function afterSave(bool $isNew)
    {
        if ($isNew) {
            Craft::$app->db->createCommand()
                ->insert(Table::FRAGMENTS, [
                    'id' => $this->id,
                    'fragmentTypeId' => $this->fragmentTypeId,
                ])
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update(Table::FRAGMENTS, [
                    'fragmentTypeId' => $this->fragmentTypeId,
                ], ['id' => $this->id])
                ->execute();
        }

        parent::afterSave($isNew);
    }

    protected static function defineActions(string $source = null): array
    {
        return [
            SetStatus::class,
            Delete::class,
        ];
    }

    protected static function defineSources(string $context = null): array
    {
        $fragmentTypes = Plugin::getInstance()->fragmentTypes->getAllFragmentTypes();

        return array_map(function ($fragmentType) {
            return [
                'key' => 'type:' . $fragmentType['uid'],
                'label' => Craft::t('site', $fragmentType['name']),
                'data' => ['handle' => $fragmentType['handle']],
                'criteria' => ['fragmentTypeId' => $fragmentType['id']],
            ];
        }, $fragmentTypes);
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', "Title"),
            'slug' => Craft::t('app', "Slug"),
            [
                'label' => Craft::t('app', "Date Updated"),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', "ID"),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', "Title")],
            'slug' => ['label' => Craft::t('app', "Slug")],
            'id' => ['label' => Craft::t('app', "ID")],
            'uid' => ['label' => Craft::t('app', "UID")],
            'dateCreated' => ['label' => Craft::t('app', "Date Created")],
            'dateUpdated' => ['label' => Craft::t('app', "Date Updated")],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'slug'
        ];
    }
}
