<?php

namespace thepixelage\fragments\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\models\Site;
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
 * @property-read bool $isDeletable
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

    public static function hasStatuses(): bool
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
        $zone = $this->getZone();
        $fragmentType = $this->getFragmentType();

        // The slug *might* not be set if this is a Draft and they've deleted it for whatever reason
        $path = 'fragments/fragments/' . $zone->handle . '/' . $fragmentType->handle . '/' . $this->getSourceId();

        $params = [];
        if (Craft::$app->getIsMultiSite()) {
            $params['site'] = $this->getSite()->handle;
        }

        return UrlHelper::cpUrl($path, $params);
    }

    /**
     * @throws InvalidConfigException
     */
    public function getSupportedSites(): array
    {
        $zone = $this->getZone();
        /* @var Site[] $allSites */
        $allSites = ArrayHelper::index(Craft::$app->getSites()->getAllSites(), 'id');
        $sites = [];

        // If the zone is leaving it up to entries to decide which sites to be propagated to,
        // figure out which sites the entry is currently saved in
        if (
            ($this->duplicateOf->id ?? $this->id) &&
            $zone->propagationMethod === Zone::PROPAGATION_METHOD_CUSTOM
        ) {
            if ($this->id) {
                $currentSites = static::find()
                    ->anyStatus()
                    ->id($this->id)
                    ->siteId('*')
                    ->select('elements_sites.siteId')
                    ->drafts($this->getIsDraft())
                    ->revisions($this->getIsRevision())
                    ->column();
            } else {
                $currentSites = [];
            }

            // If this is being duplicated from another element (e.g. a draft), include any sites the source element is saved to as well
            if (!empty($this->duplicateOf->id)) {
                array_push($currentSites, ...static::find()
                    ->anyStatus()
                    ->id($this->duplicateOf->id)
                    ->siteId('*')
                    ->select('elements_sites.siteId')
                    ->drafts($this->duplicateOf->getIsDraft())
                    ->revisions($this->duplicateOf->getIsRevision())
                    ->column()
                );
            }

            $currentSites = array_flip($currentSites);
        }

        foreach ($zone->getSiteSettings() as $siteSettings) {
            switch ($zone->propagationMethod) {
                case Zone::PROPAGATION_METHOD_NONE:
                    $include = $siteSettings->siteId == $this->siteId;
                    $propagate = true;
                    break;
                case Zone::PROPAGATION_METHOD_SITE_GROUP:
                    $include = $allSites[$siteSettings->siteId]->groupId == $allSites[$this->siteId]->groupId;
                    $propagate = true;
                    break;
                case Zone::PROPAGATION_METHOD_LANGUAGE:
                    $include = $allSites[$siteSettings->siteId]->language == $allSites[$this->siteId]->language;
                    $propagate = true;
                    break;
                case Zone::PROPAGATION_METHOD_CUSTOM:
                    $include = true;
                    // Only actually propagate to this site if it's the current site, or the entry has been assigned
                    // a status for this site, or the entry already exists for this site
                    $propagate = (
                        $siteSettings->siteId == $this->siteId ||
                        $this->getEnabledForSite($siteSettings->siteId) !== null ||
                        isset($currentSites[$siteSettings->siteId])
                    );
                    break;
                default:
                    $include = $propagate = true;
                    break;
            }

            if ($include) {
                $sites[] = [
                    'siteId' => $siteSettings->siteId,
                    'propagate' => $propagate,
                    'enabledByDefault' => $siteSettings->enabledByDefault,
                ];
            }
        }

        return $sites;
    }

    /**
     * @throws InvalidConfigException
     */
    public function getIsDeletable(): bool
    {
        $zone = $this->getZone();
        $userSession = Craft::$app->getUser();
        return $userSession->checkPermission("deleteFragments:$zone->uid");
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
        if ($context === 'index') {
            $zones = Plugin::getInstance()->zones->getAllZones();
            $editable = true;
        } else {
            $zones = Plugin::getInstance()->zones->getAllZones();
            $editable = false;
        }

        $zoneIds = [];

        foreach ($zones as $zone) {
            $zoneIds[] = $zone->id;
        }

        $sources = [];

        foreach ($zones as $zone) {
            /* @var Zone $zone */
            $source = [
                'key' => 'zone:' . $zone->uid,
                'label' => Craft::t('site', $zone->name),
                'sites' => $zone->getSiteIds(),
                'data' => [
                    'handle' => $zone->handle,
                ],
                'criteria' => [
                    'zoneId' => $zone->id,
                    'editable' => $editable,
                ],
            ];

            $source['defaultSort'] = ['structure', 'asc'];
            $source['structureId'] = $zone->structureId;
            $source['structureEditable'] = Craft::$app->getUser()->checkPermission('editFragments:' . $zone->uid);

            $sources[] = $source;
        }

        return $sources;
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
            'fragmentTypeId' => ['label' => Craft::t('app', "Type")],
            'dateCreated' => ['label' => Craft::t('app', "Date Created")],
            'dateUpdated' => ['label' => Craft::t('app', "Date Updated")],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'fragmentTypeId',
            'slug'
        ];
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        if ($attribute === 'fragmentTypeId') {
            return $this->getFragmentType()->name;
        }

        return parent::tableAttributeHtml($attribute);
    }
}
