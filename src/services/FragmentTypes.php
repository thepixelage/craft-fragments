<?php

namespace thepixelage\fragments\services;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\CategoryGroup;
use craft\models\FieldLayout;
use craft\records\CategoryGroup as CategoryGroupRecord;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\elements\Fragment;
use thepixelage\fragments\models\FragmentType;
use thepixelage\fragments\records\FragmentType as FragmentTypeRecord;
use Throwable;
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
    /**
     * @var MemoizableArray<FragmentType>|null
     * @see _types()
     */
    private $_types;

    public function getAllFragmentTypes(): array
    {
        return $this->_types()->all();
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

    public function getFragmentTypeByHandle($handle): ?FragmentType
    {
        $result = $this->createFragmentTypesQuery()
            ->where(['handle' => $handle])
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
                $fieldLayout->uid = $fieldLayout->id ? Db::uidById(Table::FIELDLAYOUTS, $fieldLayout->id) : StringHelper::UUID();
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

    /**
     * @throws \yii\db\Exception|Exception
     * @throws Throwable
     */
    public function handleChangedFragmentType(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $record = $this->getFragmentTypeRecord($uid);
            $record->name = $data['name'];
            $record->handle = $data['handle'];
            $record->uid = $uid;

            if (!empty($data['fieldLayouts'])) {
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $record->fieldLayoutId;
                $layout->type = Fragment::class;
                $layout->uid = key($data['fieldLayouts']);
                Craft::$app->getFields()->saveLayout($layout);
                $record->fieldLayoutId = $layout->id;
            } else if ($record->fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($record->fieldLayoutId);
                $record->fieldLayoutId = null;
            }

            $record->save(false);
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
        $fragmentType = $this->getFragmentTypeByUid($uid);
        if (!$fragmentType) {
            return;
        }

        Craft::$app->db->createCommand()
            ->delete(Table::FRAGMENTTYPES, ['id' => $fragmentType->id])
            ->execute();
    }

    private function createFragmentTypesQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'handle',
                'uid',
                'fieldLayoutId',
            ])
            ->from([Table::FRAGMENTTYPES])
            ->orderBy('name asc');
    }

    private function getFragmentTypeRecord(string $uid): FragmentTypeRecord
    {
        $query = FragmentTypeRecord::find()->andWhere(['uid' => $uid]);

        return $query->one() ?? new FragmentTypeRecord();
    }

    private function _types(): MemoizableArray
    {
        if ($this->_types === null) {
            $types = [];

            /** @var FragmentTypeRecord[] $typeRecords */
            $typeRecords = FragmentTypeRecord::find()
                ->orderBy(['name' => SORT_ASC])
                ->all();

            foreach ($typeRecords as $typeRecord) {
                $types[] = $this->_createFragmentTypeFromRecord($typeRecord);
            }

            $this->_types = new MemoizableArray($types);
        }

        return $this->_types;
    }

    private function _createFragmentTypeFromRecord(FragmentTypeRecord $typeRecord = null)
    {
        if (!$typeRecord) {
            return null;
        }

        $type = new FragmentType($typeRecord->toArray([
            'id',
            'structureId',
            'fieldLayoutId',
            'name',
            'handle',
            'defaultPlacement',
            'uid',
        ]));

        return $type;
    }
}
