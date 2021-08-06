<?php

namespace thepixelage\fragments\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\records\Structure;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\elements\Fragment;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id ID
 * @property int $structureId Structure ID
 * @property string $name Name
 * @property string $handle Handle
 * @property Structure $structure Structure
 * @property Fragment[] $fragments Categories
 */
class Zone extends ActiveRecord
{
    use SoftDeleteTrait;

    public static function tableName(): string
    {
        return Table::ZONES;
    }

    public function getStructure(): ActiveQueryInterface
    {
        return $this->hasOne(Structure::class, ['id' => 'structureId']);
    }

    public function getFragments(): ActiveQueryInterface
    {
        return $this->hasMany(Fragment::class, ['zoneId' => 'id']);
    }
}
