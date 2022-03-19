<?php

namespace thepixelage\fragments\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\events\DefineHtmlEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Json;
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
 * @property-read string $gqlTypeName
 * @property-read FragmentType $fragmentType
 */
class Fragment extends Element
{
    public ?int $fragmentTypeId;
    public ?int $zoneId;
    public ?array $settings = [];

    public function __construct($config = [])
    {
        if (isset($config['settings'])) {
            $config['settings'] = Json::decode($config['settings']);
        } else {
            $config['settings'] = [
                'visibility' => [
                    'ruletype' => '',
                    'rules' => [],
                ],
            ];
        }

        if (!is_array($config['settings']['visibility']['rules'])) {
            $config['settings']['visibility']['rules'] = [];
        }

        parent::__construct($config);
    }

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

    public function getCrumbs(): array
    {
        $zone = $this->getZone();

        return [
            [
                'label' => Craft::t('app', 'Fragments'),
                'url' => UrlHelper::url('fragments'),
            ],
            [
                'label' => Craft::t('site', $zone->name),
                'url' => UrlHelper::url('fragments/fragments/' . $zone->handle),
            ],
        ];
    }

    /**
     * @throws InvalidConfigException
     */
    public function getFieldLayout(): ?FieldLayout
    {
        return parent::getFieldLayout() ?? $this->getFragmentType()->getFieldLayout();
    }

    public function getSidebarHtml(bool $static): string
    {
        $components = [];

        $metaFieldsHtml = $this->metaFieldsHtml($static);
        if ($metaFieldsHtml !== '') {
            $components[] = Html::tag('div', $metaFieldsHtml, ['class' => 'meta']);
        }

        if (!$static && static::hasStatuses()) {
            // Is this a multi-site element?
            $components[] = $this->statusFieldHtml();
        }

        if ($this->hasRevisions() && !$this->getIsRevision()) {
            $components[] = $this->notesFieldHtml();
        }

        if ($this->id) {
            $components[] = Cp::metadataHtml($this->getMetadata());
        }

        // Fire a defineSidebarHtml event
        $event = new DefineHtmlEvent([
            'html' => implode("\n", $components),
        ]);
        $this->trigger(self::EVENT_DEFINE_SIDEBAR_HTML, $event);
        return $event->html;
    }

    protected function metaFieldsHtml(bool $static): string
    {
        return implode('', [
            $this->slugFieldHtml($static),
            parent::metaFieldsHtml($static),
        ]);
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
    public function getType(): FragmentType
    {
        return $this->getFragmentType();
    }

    /**
     * @throws InvalidConfigException
     */
    public function getCpEditUrl(): ?string
    {
        $zone = $this->getZone();
        $fragmentType = $this->getFragmentType();

        // The slug *might* not be set if this is a Draft and they've deleted it for whatever reason
        $craft37 = version_compare(Craft::$app->getVersion(), '3.7', '>=');
        if ($craft37) {
            $sourceId = $this->getCanonicalId();
        } else {
            $sourceId = $this->getSourceId();
        }
        $path = 'fragments/fragments/' . $zone->handle . '/' . $fragmentType->handle . '/' . $sourceId;

        if ($this->slug) {
            $path .= '-' . $this->slug;
        }

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
    public function beforeSave(bool $isNew): bool
    {
        $this->structureId = $this->getZone()->structureId;

        return parent::beforeSave($isNew);
    }

    /**
     * @throws Exception
     * @throws \yii\base\Exception
     */
    public function afterSave(bool $isNew): void
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
            $record->settings = Json::encode($this->settings);
            $record->save(false);

            if (!$this->duplicateOf && $isNew) {
                Craft::$app->getStructures()->appendToRoot($this->structureId, $this, Structures::MODE_INSERT);
            }
        }

        parent::afterSave($isNew);
    }

    /**
     * @throws \yii\base\Exception
     */
    public function afterRestore(): void
    {
        $zone = $this->getZone();
        Craft::$app->getStructures()->appendToRoot($zone->structureId, $this);

        parent::afterRestore();
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [
            SetStatus::class,
            Delete::class,
        ];

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('fragments', "Fragments restored."),
            'partialSuccessMessage' => Craft::t('fragments', "Some fragments restored."),
            'failMessage' => Craft::t('fragments', "Fragments not restored."),
        ]);

        return $actions;
    }

    public function canView(User $user): bool
    {
        return true;
    }

    /**
     * @throws InvalidConfigException
     */
    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }

        $zone = $this->getZone();

        return $user->can("editFragments:$zone->uid");
    }

    /**
     * @throws InvalidConfigException
     */
    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        $zone = $this->getZone();

        return $user->can("deleteFragments:$zone->uid");
    }

    /**
     * @throws InvalidConfigException
     */
    protected static function defineSources(string $context = null): array
    {
        $zones = Plugin::getInstance()->zones->getAllZones();
        if ($context === 'index') {
            $editable = true;
        } else {
            $editable = false;
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
            $source['structureEditable'] = Craft::$app->getRequest()->getIsConsoleRequest() || Craft::$app->getUser()->checkPermission("editFragments:$zone->uid");

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

    public static function gqlTypeNameByContext(mixed $context): string
    {
        /** @var FragmentType $context */
        return $context->handle . '_Fragment';
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlScopesByContext(mixed $context): array
    {
        /** @var FragmentType $context */
        return ['fragmenttypes.' . $context->uid];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public static function gqlMutationNameByContext(mixed $context): string
    {
        /** @var FragmentType $context */
        return 'save_' . $context->handle . '_Fragment';
    }

    /**
     * @throws InvalidConfigException
     */
    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this->getFragmentType());
    }
}
