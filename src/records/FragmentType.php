<?php

namespace thepixelage\fragments\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\records\FieldLayout;
use thepixelage\fragments\db\Table;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id ID
 * @property int $fieldLayoutId Field layout ID
 * @property string $name Name
 * @property string $handle Handle
 * @property FieldLayout $fieldLayout Field layout
 */
class FragmentType extends ActiveRecord
{
    use SoftDeleteTrait;

    public static function tableName(): string
    {
        return Table::FRAGMENTTYPES;
    }

    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }
}
