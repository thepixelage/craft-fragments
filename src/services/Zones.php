<?php

namespace thepixelage\fragments\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\errors\MissingComponentException;
use craft\events\ConfigEvent;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use Exception;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\models\Zone;
use yii\base\InvalidConfigException;

class Zones extends Component
{
    public function getAllZones(): array
    {
        $results = $this->_createZonesQuery()
            ->orderBy(['name' => SORT_ASC])
            ->all();

        foreach ($results as $key => $result) {
            $results[$key] = new Zone($result);
        }

        return $results;
    }

    public function getZoneById($id)
    {
        $result = $this->_createZonesQuery()
            ->where(['id' => $id])
            ->one();

        return $result ? new Zone($result) : null;
    }

    public function getZoneByUid($uid)
    {
        $result = $this->_createZonesQuery()
            ->where(['uid' => $uid])
            ->one();

        return $result ? new Zone($result) : null;
    }

    /**
     * @throws InvalidConfigException
     */
    public function createZone($config): Zone
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        if (!empty($config['id']) && empty($config['uid']) && is_numeric($config['id'])) {
            $uid = Db::uidById(\craft\db\Table::FIELDS, $config['id']);
            $config['uid'] = $uid;
        }

        try {
            $zone = ComponentHelper::createComponent($config, Zone::class);
        } catch (MissingComponentException $e) {
            $config['errorMessage'] = $e->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $zone = new Zone($config);
        }

        return $zone;
    }

    /**
     * @throws Exception
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
     * @throws Exception
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

    private function _createZonesQuery(): Query
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
