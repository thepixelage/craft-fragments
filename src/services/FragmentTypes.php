<?php

namespace thepixelage\fragments\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use thepixelage\fragments\models\FragmentType;
use thepixelage\fragments\records\FragmentType as FragmentTypeRecord;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

/**
 *
 * @property-read array $allFragmentTypes
 * @property-read int $fragmentTypeCount
 */
class FragmentTypes extends Component
{
    public function getAllFragmentTypes(): array
    {
        return $this->createFragmentTypesQuery()->all();
    }

    public function getFragmentTypeById($id): ?FragmentType
    {
        $result = $this->createFragmentTypesQuery()
            ->where(['id' => $id])
            ->one();

        return $result ? new FragmentType($result) : null;
    }

    public function getFragmentTypeByUid($uid): ?FragmentType
    {
        $result = $this->createFragmentTypesQuery()
            ->where(['uid' => $uid])
            ->one();

        return $result ? new FragmentType($result) : null;
    }

    public function getFragmentTypeCount(): int
    {
        return $this->createFragmentTypesQuery()->count();
    }

    /**
     * @throws NotSupportedException
     * @throws InvalidConfigException
     * @throws Exception
     * @throws ErrorException
     * @throws ServerErrorHttpException
     * @throws \Exception
     */
    public function saveFragmentType(FragmentType $fragmentType): bool
    {
        $isNew = empty($fragmentType->id);

        if ($isNew) {
            $fragmentType->uid = StringHelper::UUID();
        } else if (!$fragmentType->uid) {
            $fragmentType->uid = Db::uidById(FragmentTypeRecord::tableName(), $fragmentType->id);
        }

        if (!$fragmentType->validate()) {
            return false;
        }

        $path = "fragmentTypes.$fragmentType->uid";
        Craft::$app->projectConfig->set($path, [
            'name' => $fragmentType->name,
            'handle' => $fragmentType->handle,
        ]);

        if ($isNew) {
            $fragmentType->id = Db::idByUid(FragmentTypeRecord::tableName(), $fragmentType->uid);
        }

        return true;
    }

    public function deleteFragmentType(FragmentType $fragmentType): bool
    {
        $path = "fragmentTypes.$fragmentType->uid";
        Craft::$app->projectConfig->remove($path);

        return true;
    }

    /**
     * @throws \yii\db\Exception
     */
    public function handleChangedFragmentType(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];

        $id = (new Query())
            ->select(['id'])
            ->from(FragmentTypeRecord::tableName())
            ->where(['uid' => $uid])
            ->scalar();

        $isNew = empty($id);

        if ($isNew) {
            Craft::$app->db->createCommand()
                ->insert(FragmentTypeRecord::tableName(), [
                    'uid' => $uid,
                    'name' => $event->newValue['name'],
                    'handle' => $event->newValue['handle'],
                ])
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update(FragmentTypeRecord::tableName(), [
                    'name' => $event->newValue['name'],
                    'handle' => $event->newValue['handle'],
                ], ['id' => $id])
                ->execute();
        }
    }

    /**
     * @throws \yii\db\Exception
     */
    public function handleDeletedFragmentType(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $fragmentType = $this->getFragmentTypeByUid($uid);
        if (!$fragmentType) {
            return;
        }

        Craft::$app->db->createCommand()
            ->delete(FragmentTypeRecord::tableName(), ['id' => $fragmentType->id])
            ->execute();
    }

    private function createFragmentTypesQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'uid'
            ])
            ->from([FragmentTypeRecord::tableName()]);
    }
}
