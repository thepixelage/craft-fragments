<?php

namespace thepixelage\fragments\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\elements\Entry;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use Exception;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\models\FragmentType;
use thepixelage\fragments\records\FragmentType as FragmentTypeRecord;
use Throwable;

class FragmentTypes extends Component
{
    public function getAllFragmentTypes(): array
    {
        $results = $this->_createFragmentTypesQuery()
            ->orderBy(['name' => SORT_ASC])
            ->all();

        foreach ($results as $key => $result) {
            $results[$key] = new FragmentType($result);
        }

        return $results;
    }

    public function getFragmentTypeById($id)
    {
        $result = $this->_createFragmentTypesQuery()
            ->where(['id' => $id])
            ->one();

        return $result ? new FragmentType($result) : null;
    }

    public function getFragmentTypeByUid($uid)
    {
        $result = $this->_createFragmentTypesQuery()
            ->where(['uid' => $uid])
            ->one();

        return $result ? new FragmentType($result) : null;
    }

    /**
     * @throws Exception
     */
    public function saveFragmentType(FragmentType $fragmentType): bool
    {
        $isNew = empty($fragmentType->id);

        if ($isNew) {
            $fragmentType->uid = StringHelper::UUID();
        } else if (!$fragmentType->uid) {
            $fragmentType->uid = Db::uidById(Table::FRAGMENTTYPES, $fragmentType->id);
        }

        if (!$fragmentType->validate()) {
            return false;
        }

        $config = [
            'name' => $fragmentType->name,
            'handle' => $fragmentType->handle,
        ];

        if (
            ($fieldLayout = $fragmentType->getFieldLayout()) &&
            ($fieldLayoutConfig = $fieldLayout->getConfig())
        ) {
            if (!$fieldLayout->uid) {
                $fieldLayout->uid = $fieldLayout->id ? Db::uidById(\craft\db\Table::FIELDLAYOUTS, $fieldLayout->id) : StringHelper::UUID();
            }
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        $path = "fragmentTypes.$fragmentType->uid";
        Craft::$app->projectConfig->set($path, $config);

        if ($isNew) {
            $fragmentType->id = Db::idByUid(Table::FRAGMENTTYPES, $fragmentType->uid);
        }

        return true;
    }

    public function deleteFragmentType(FragmentType $fragmentType): bool
    {
        $path = "fragmentTypes.$fragmentType->uid";
        Craft::$app->projectConfig->remove($path);

        return true;
    }

    public function deleteFragmentTypeById($id): bool
    {
        $fragmentType = $this->getFragmentTypeById($id);

        if (!$fragmentType) {
            return false;
        }

        return $this->deleteFragmentType($fragmentType);
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function handleChangedFragmentType(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $typeRecord = $this->_getFragmentTypeRecord($uid);
            $typeRecord->name = $data['name'];
            $typeRecord->handle = $data['handle'];
            $typeRecord->uid = $uid;

            if (!empty($data['fieldLayouts'])) {
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $typeRecord->fieldLayoutId;
                $layout->type = Entry::class;
                $layout->uid = key($data['fieldLayouts']);
                Craft::$app->getFields()->saveLayout($layout);
                $typeRecord->fieldLayoutId = $layout->id;
            } else if ($typeRecord->fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($typeRecord->fieldLayoutId);
                $typeRecord->fieldLayoutId = null;
            }

            $typeRecord->save(false);
            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * @throws \yii\db\Exception
     */
    public function handleDeletedFragmentType(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $zone = $this->getFragmentTypeByUid($uid);
        if (!$zone) {
            return;
        }

        Craft::$app->db->createCommand()
            ->delete(Table::FRAGMENTTYPES, ['id' => $zone->id])
            ->execute();
    }

    private function _createFragmentTypesQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'uid',
                'fieldLayoutId',
            ])
            ->from([Table::FRAGMENTTYPES]);
    }

    private function _getFragmentTypeRecord(string $uid): FragmentTypeRecord
    {
        $query = FragmentTypeRecord::find()->andWhere(['uid' => $uid]);
        return $query->one() ?? new FragmentTypeRecord();
    }
}
