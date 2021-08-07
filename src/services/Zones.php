<?php

namespace thepixelage\fragments\services;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\errors\StructureNotFoundException;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use craft\models\Structure;
use craft\queue\jobs\ApplyNewPropagationMethod;
use craft\queue\jobs\ResaveElements;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\elements\Fragment;
use thepixelage\fragments\models\Zone;
use thepixelage\fragments\models\Zone_SiteSettings;
use thepixelage\fragments\records\Zone as ZoneRecord;
use thepixelage\fragments\records\Zone_SiteSettings as Zone_SiteSettingsRecord;
use Throwable;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;

/**
 *
 * @property-read array $allZones
 * @property-read int $zoneCount
 */
class Zones extends Component
{
    private ?MemoizableArray $zones = null;
    public ?bool $autoResaveFragments = true;

    public function getAllZones(): array
    {
        if ($this->zones === null) {
            $zones = [];

            $zoneRecords = ZoneRecord::find()
                ->orderBy(['name' => SORT_ASC])
                ->with('structure')
                ->all();

            foreach ($zoneRecords as $zoneRecord) {
                $zone = new Zone($zoneRecord->toArray([
                    'id',
                    'structureId',
                    'name',
                    'handle',
                    'uid',
                ]));
                $zone->settings = Json::decode($zoneRecord->settings);
                $zones[] = $zone;
            }

            $this->zones = new MemoizableArray($zones);
        }

        return $this->zones->all();
    }

    public function getZoneById($id): ?Zone
    {
        $result = $this->createZonesQuery()
            ->where(['id' => $id])
            ->one();

        if (!$result) {
            return null;
        }

        $settings = Json::decode($result['settings']);
        unset($result['settings']);

        $zone = new Zone($result);
        $zone->settings = $settings;

        return $zone;
    }

    public function getZoneByUid($uid): ?Zone
    {
        $result = $this->createZonesQuery()
            ->where(['uid' => $uid])
            ->one();

        if (!$result) {
            return null;
        }

        $settings = Json::decode($result['settings']);
        unset($result['settings']);

        $zone = new Zone($result);
        $zone->settings = $settings;

        return $zone;
    }

    public function getZoneByHandle($handle): ?Zone
    {
        $result = $this->createZonesQuery()
            ->where(['handle' => $handle])
            ->one();

        if (!$result) {
            return null;
        }

        $settings = Json::decode($result['settings']);
        unset($result['settings']);

        $zone = new Zone($result);
        $zone->settings = $settings;

        return $zone;
    }

    public function getZoneCount(): int
    {
        return $this->createZonesQuery()->count();
    }

    public function getZoneSiteSettings(int $zoneId): array
    {
        $siteSettings = (new Query())
            ->select([
                'zones_sites.id',
                'zones_sites.zoneId',
                'zones_sites.siteId',
                'zones_sites.enabledByDefault',
            ])
            ->from(['zones_sites' => Table::ZONES_SITES])
            ->innerJoin(['sites' => Table::SITES], '[[sites.id]] = [[zones_sites.siteId]]')
            ->where(['zones_sites.zoneId' => $zoneId])
            ->orderBy(['sites.sortOrder' => SORT_ASC])
            ->all();

        foreach ($siteSettings as $key => $value) {
            $siteSettings[$key] = new Zone_SiteSettings($value);
        }

        return $siteSettings;
    }

    /**
     * @throws NotSupportedException
     * @throws InvalidConfigException
     * @throws Exception
     * @throws ErrorException
     * @throws ServerErrorHttpException
     * @throws \Exception
     */
    public function saveZone(Zone $zone): bool
    {
        $isNew = empty($zone->id);

        if ($isNew) {
            $zone->uid = StringHelper::UUID();
        } else if (!$zone->uid) {
            $zone->uid = Db::uidById(Table::ZONES, $zone->id);
        }

        if (!$zone->validate()) {
            return false;
        }

        $path = "fragmentZones.$zone->uid";
        Craft::$app->projectConfig->set($path, $zone->getConfig());

        if ($isNew) {
            $zone->id = Db::idByUid(Table::ZONES, $zone->uid);
        }

        return true;
    }

    public function deleteZone(Zone $zone): bool
    {
        $path = "fragmentZones.$zone->uid";
        Craft::$app->projectConfig->remove($path);

        return true;
    }

    /**
     * @throws \yii\db\Exception
     * @throws StructureNotFoundException
     * @throws Throwable
     */
    public function handleChangedZone(ConfigEvent $event)
    {
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $zoneUid = $event->tokenMatches[0];
        $data = $event->newValue;

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $siteSettingData = $data['siteSettings'];

            // Basic data
            $zoneRecord = $this->getZoneRecord($zoneUid, true);
            $zoneRecord->uid = $zoneUid;
            $zoneRecord->name = $data['name'];
            $zoneRecord->handle = $data['handle'];
            $zoneRecord->enableVersioning = (bool)$data['enableVersioning'];
            $zoneRecord->propagationMethod = $data['propagationMethod'] ?? Zone::PROPAGATION_METHOD_ALL;
            $zoneRecord->settings = $data['settings'];

            $isNewZone = $zoneRecord->getIsNewRecord();
            $propagationMethodChanged = $zoneRecord->propagationMethod != $zoneRecord->getOldAttribute('propagationMethod');

            // Save the structure
            $structureData = $data['structure'];
            $structureUid = $structureData['uid'];
            $structure = Craft::$app->getStructures()->getStructureByUid($structureUid, true) ?? new Structure(['uid' => $structureUid]);
            $structure->maxLevels = 1;
            Craft::$app->getStructures()->saveStructure($structure);
            $zoneRecord->structureId = $structure->id;

            $resaveFragments = (
                $zoneRecord->handle !== $zoneRecord->getOldAttribute('handle') ||
                $propagationMethodChanged ||
                $zoneRecord->structureId != $zoneRecord->getOldAttribute('structureId')
            );

            if ($zoneRecord->dateDeleted) {
                $zoneRecord->restore();
                $resaveFragments = true;
            } else {
                $zoneRecord->save(false);
            }

            // Update the site settings
            // -----------------------------------------------------------------

            if (!$isNewZone) {
                // Get the old zone site settings
                $allOldSiteSettingsRecords = Zone_SiteSettingsRecord::find()
                    ->where(['zoneId' => $zoneRecord->id])
                    ->indexBy('siteId')
                    ->all();
            } else {
                $allOldSiteSettingsRecords = [];
            }

            $siteIdMap = Db::idsByUids(Table::SITES, array_keys($siteSettingData));
            $hasNewSite = false;

            foreach ($siteSettingData as $siteUid => $siteSettings) {
                $siteId = $siteIdMap[$siteUid];

                // Was this already selected?
                if (!$isNewZone && isset($allOldSiteSettingsRecords[$siteId])) {
                    $siteSettingsRecord = $allOldSiteSettingsRecords[$siteId];
                } else {
                    $siteSettingsRecord = new Zone_SiteSettingsRecord();
                    $siteSettingsRecord->zoneId = $zoneRecord->id;
                    $siteSettingsRecord->siteId = $siteId;
                    $resaveFragments = true;
                    $hasNewSite = true;
                }

                $siteSettingsRecord->enabledByDefault = $siteSettings['enabledByDefault'];
                $siteSettingsRecord->save(false);
            }

            if (!$isNewZone) {
                // Drop any sites that are no longer being used, as well as the associated fragment/element site
                // rows
                $affectedSiteUids = array_keys($siteSettingData);

                foreach ($allOldSiteSettingsRecords as $siteId => $siteSettingsRecord) {
                    $siteUid = array_search($siteId, $siteIdMap, false);
                    if (!in_array($siteUid, $affectedSiteUids, false)) {
                        $siteSettingsRecord->delete();
                        $resaveFragments = true;
                    }
                }
            }

            // Finally, deal with the existing fragments...
            // -----------------------------------------------------------------

            if (!$isNewZone && $resaveFragments) {
                // If the propagation method just changed, we definitely need to update entries for that
                if ($propagationMethodChanged) {
                    Queue::push(new ApplyNewPropagationMethod([
                        'description' => Craft::t('app', 'Applying new propagation method to {zone} entries', [
                            'zone' => $zoneRecord->name,
                        ]),
                        'elementType' => Fragment::class,
                        'criteria' => [
                            'zoneId' => $zoneRecord->id,
                            'structureId' => $zoneRecord->structureId,
                        ],
                    ]));
                } else if ($this->autoResaveFragments) {
                    Queue::push(new ResaveElements([
                        'description' => Craft::t('app', 'Resaving {zone} entries', [
                            'zone' => $zoneRecord->name,
                        ]),
                        'elementType' => Fragment::class,
                        'criteria' => [
                            'zoneId' => $zoneRecord->id,
                            'siteId' => array_values($siteIdMap),
                            'unique' => true,
                            'status' => null,
                        ],
                        'updateSearchIndex' => $hasNewSite,
                    ]));
                }
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * @throws \yii\db\Exception
     */
    public function handleDeletedZone(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $zone = $this->getZoneByUid($uid);
        if (!$zone) {
            return;
        }

        Craft::$app->db->createCommand()
            ->delete(Table::ZONES, ['id' => $zone->id])
            ->execute();
    }

    private function createZonesQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'structureId',
                'propagationMethod',
                'settings',
                'uid'
            ])
            ->from([Table::ZONES]);
    }

    private function getZoneRecord(string $uid, bool $withTrashed = false): ZoneRecord
    {
        $query = $withTrashed ? ZoneRecord::findWithTrashed() : ZoneRecord::find();
        $query->andWhere(['uid' => $uid]);

        return $query->one() ?? new ZoneRecord();
    }
}
