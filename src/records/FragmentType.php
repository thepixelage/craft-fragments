<?php

namespace thepixelage\fragments\records;

use craft\db\ActiveRecord;
use craft\records\FieldLayout;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\elements\Fragment;
use yii\db\ActiveQueryInterface;

/**
 *
 * @property-read ActiveQueryInterface $fieldLayout
 * @property int $id ID
 * @property int $fieldLayoutId Field layout ID
 * @property string $name Name
 * @property-read ActiveQueryInterface $fragments
 * @property string $handle Handle
 */
class FragmentType extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::FRAGMENTTYPES;
    }

    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }

    public function getFragments(): ActiveQueryInterface
    {
        return $this->hasMany(Fragment::class, ['fragmentTypeId' => 'id']);
    }
}
