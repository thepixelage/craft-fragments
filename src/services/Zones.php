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
use craft\helpers\StringHelper;
use craft\models\Structure;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\models\Zone;
use thepixelage\fragments\records\Zone as ZoneRecord;
use Throwable;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

/**
 *
 * @property-read array $allZones
 * @property-read int $zoneCount
 */
class Zones extends Component
{
    private ?MemoizableArray $zones = null;

    public function getAllZones(): array
    {
        if ($this->zones === null) {
            $zones = [];

            $zoneRecords = ZoneRecord::find()
                ->orderBy(['name' => SORT_ASC])
                ->with('structure')
                ->all();

            foreach ($zoneRecords as $zoneRecord) {
                $zones[] = new Zone($zoneRecord->toArray([
                    'id',
                    'structureId',
                    'name',
                    'handle',
                    'uid',
                ]));
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

        return $result ? new Zone($result) : null;
    }

    public function getZoneByUid($uid): ?Zone
    {
        $result = $this->createZonesQuery()
            ->where(['uid' => $uid])
            ->one();

        return $result ? new Zone($result) : null;
    }

    public function getZoneByHandle($handle): ?Zone
    {
        $result = $this->createZonesQuery()
            ->where(['handle' => $handle])
            ->one();

        return $result ? new Zone($result) : null;
    }

    public function getZoneCount(): int
    {
        return $this->createZonesQuery()->count();
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
        Craft::$app->projectConfig->set($path, [
            'name' => $zone->name,
            'handle' => $zone->handle,
            'structure' => [
                'uid' => $zone->structureId ? Db::uidById(Table::STRUCTURES, $zone->structureId) : StringHelper::UUID(),
                'maxLevels' => (int)$zone->maxLevels ?: null,
            ],
        ]);

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
        $zoneUid = $event->tokenMatches[0];
        $data = $event->newValue;

        ProjectConfigHelper::ensureAllFieldsProcessed();

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $structureData = $data['structure'];
            $structureUid = $structureData['uid'];

            // Basic data
            $zoneRecord = $this->getZoneRecord($zoneUid, true);

            // Structure
            $structuresService = Craft::$app->getStructures();
            $structure = $structuresService->getStructureByUid($structureUid, true) ?? new Structure(['uid' => $structureUid]);
            $structure->maxLevels = $structureData['maxLevels'];
            $structuresService->saveStructure($structure);

            $zoneRecord->structureId = $structure->id;
            $zoneRecord->uid = $zoneUid;
            $zoneRecord->name = $data['name'];
            $zoneRecord->handle = $data['handle'];

            $wasTrashed = (bool)$zoneRecord->dateDeleted;
            if ($wasTrashed) {
                $zoneRecord->restore();
            } else {
                $zoneRecord->save(false);
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
