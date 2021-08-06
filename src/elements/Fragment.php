<?php

namespace thepixelage\fragments\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use craft\models\FieldLayout;
use craft\services\Structures;
use thepixelage\fragments\elements\db\FragmentQuery;
use thepixelage\fragments\models\FragmentType;
use thepixelage\fragments\models\Zone;
use thepixelage\fragments\Plugin;
use thepixelage\fragments\records\Fragment as FragmentRecord;
use yii\base\InvalidConfigException;
use yii\db\Exception;

/**
 *
 * @property-read null|int $sourceId
 * @property-read Zone $zone
 * @property-read FragmentType $fragmentType
 */
class Fragment extends Element
{
    public ?int $fragmentTypeId;
    public ?int $zoneId;

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
    public function getZone(): Zone
    {
        if ($this->zoneId === null) {
            throw new InvalidConfigException('Fragment is missing its zone ID');
        }

        $zone = Plugin::$plugin->zones->getZoneById($this->zoneId);

        if (!$zone) {
            throw new InvalidConfigException('Invalid fragment zone ID: ' . $this->zoneId);
        }

        return $zone;
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
        return sprintf(
            'fragments/fragments/%s/%s/%s',
            $this->getZone()->handle,
            $this->getFragmentType()->handle,
            $this->id);
    }

    /**
     * @throws InvalidConfigException
     */
    public function beforeSave(bool $isNew): bool
    {
        $this->structureId = $this->getZone()->structureId;

        return parent::beforeSave($isNew);
    }

    /**
     * @throws Exception
     * @throws \yii\base\Exception
     */
    public function afterSave(bool $isNew)
    {
        if (!$this->propagating) {
            if (!$isNew) {
                $record = FragmentRecord::findOne($this->id);

                if (!$record) {
                    throw new Exception('Invalid fragment ID: ' . $this->id);
                }
            } else {
                $record = new FragmentRecord();
                $record->id = (int)$this->id;
            }

            $record->zoneId = (int)$this->zoneId;
            $record->fragmentTypeId = (int)$this->fragmentTypeId;
            $record->save(false);

            if (!$this->duplicateOf) {
                $mode = $isNew ? Structures::MODE_INSERT : Structures::MODE_AUTO;
                Craft::$app->getStructures()->appendToRoot($this->structureId, $this, $mode);
            }
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
        $zones = Plugin::getInstance()->zones->getAllZones();

        return array_map(function ($zone) {
            return [
                'key' => 'type:' . $zone['uid'],
                'label' => Craft::t('site', $zone['name']),
                'data' => ['handle' => $zone['handle']],
                'criteria' => ['zoneId' => $zone['id']],
                'structureId' => $zone['structureId'],
                'structureEditable' => true,
            ];
        }, $zones);
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
