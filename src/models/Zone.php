<?php

namespace thepixelage\fragments\models;

use Craft;
use craft\base\Model;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\Plugin;
use thepixelage\fragments\records\Zone as ZoneRecord;

/**
 *
 * @property-read int[] $siteIds
 */
class Zone extends Model
{
    const PROPAGATION_METHOD_NONE = 'none';
    const PROPAGATION_METHOD_SITE_GROUP = 'siteGroup';
    const PROPAGATION_METHOD_LANGUAGE = 'language';
    const PROPAGATION_METHOD_ALL = 'all';
    const PROPAGATION_METHOD_CUSTOM = 'custom';

    public ?int $id = null;
    public ?string $name = null;
    public ?string $handle = null;
    public ?string $uid = null;
    public ?int $structureId = null;
    public ?int $maxLevels = 1;
    public ?bool $enableVersioning = true;
    public ?string $propagationMethod = self::PROPAGATION_METHOD_ALL;
    public ?bool $propagateEntries = true;

    /** @var Zone_SiteSettings[]|null */
    private ?array $siteSettings = null;

    public function validateSiteSettings()
    {
        if ($this->id) {
            $currentSiteIds = (new Query())
                ->select(['siteId'])
                ->from([Table::ZONES_SITES])
                ->where(['zoneId' => $this->id])
                ->column();

            if (empty(array_intersect($currentSiteIds, array_keys($this->getSiteSettings())))) {
                $this->addError('siteSettings', Craft::t('app', 'At least one currently-enabled site must remain enabled.'));
            }
        }

        foreach ($this->getSiteSettings() as $i => $siteSettings) {
            if (!$siteSettings->validate()) {
                $this->addModelErrors($siteSettings, "siteSettings[{$i}]");
            }
        }
    }

    public function getSiteSettings(): array
    {
        if ($this->siteSettings !== null) {
            return $this->siteSettings;
        }

        if (!$this->id) {
            return [];
        }

        // Set them with setSiteSettings() so they get indexed by site ID and setSection() gets called on them
        $this->setSiteSettings(Plugin::getInstance()->zones->getZoneSiteSettings($this->id));

        return $this->siteSettings;
    }

    public function setSiteSettings(array $siteSettings)
    {
        $this->siteSettings = ArrayHelper::index($siteSettings, 'siteId');

        foreach ($this->siteSettings as $settings) {
            $settings->setZone($this);
        }
    }

    public function getSiteIds(): array
    {
        return array_keys($this->getSiteSettings());
    }

    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'structure' => [
                'uid' => $this->structureId ? Db::uidById(Table::STRUCTURES, $this->structureId) : StringHelper::UUID(),
                'maxLevels' => (int)$this->maxLevels ?: null,
            ],
            'enableVersioning' => (bool)$this->enableVersioning,
            'propagationMethod' => $this->propagationMethod,
            'siteSettings' => [],
        ];

        foreach ($this->getSiteSettings() as $siteId => $siteSettings) {
            $siteUid = Db::uidById(Table::SITES, $siteId);
            $config['siteSettings'][$siteUid] = [
                'enabledByDefault' => (bool)$siteSettings['enabledByDefault'],
            ];
        }

        return $config;
    }

    public function addSiteSettingsErrors(array $errors, int $siteId)
    {
        foreach ($errors as $attribute => $siteErrors) {
            $key = $attribute . '-' . $siteId;
            foreach ($siteErrors as $error) {
                $this->addError($key, $error);
            }
        }
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'structureId', 'maxLevels'], 'number', 'integerOnly' => true];
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];
        $rules[] = [['name', 'handle'], UniqueValidator::class, 'targetClass' => ZoneRecord::class];
        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];

        return $rules;
    }
}
