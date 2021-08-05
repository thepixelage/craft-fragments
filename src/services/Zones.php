<?php

namespace thepixelage\fragments\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\models\Zone;
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
    public function getAllZones(): array
    {
        return $this->createZonesQuery()->all();
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

    public function deleteZoneById($id): bool
    {
        $zone = $this->getZoneById($id);

        if (!$zone) {
            return false;
        }

        return $this->deleteZone($zone);
    }

    /**
     * @throws \yii\db\Exception
     */
    public function handleChangedZone(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];

        $id = (new Query())
            ->select(['id'])
            ->from(Table::ZONES)
            ->where(['uid' => $uid])
            ->scalar();

        $isNew = empty($id);

        if ($isNew) {
            Craft::$app->db->createCommand()
                ->insert(Table::ZONES, [
                    'uid' => $uid,
                    'name' => $event->newValue['name'],
                    'handle' => $event->newValue['handle'],
                ])
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update(Table::ZONES, [
                    'name' => $event->newValue['name'],
                    'handle' => $event->newValue['handle'],
                ], ['id' => $id])
                ->execute();
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
                'uid'
            ])
            ->from([Table::ZONES]);
    }
}
