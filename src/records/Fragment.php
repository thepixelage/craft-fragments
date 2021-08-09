<?php

namespace thepixelage\fragments\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use thepixelage\fragments\db\Table;
use yii\db\ActiveQueryInterface;

/**
 *
 * @property int $id ID
 * @property int $zoneId Zone ID
 * @property string $settings Settings
 * @property Element $element Element
 * @property Zone $zone Zone
 * @property-read ActiveQueryInterface $fragmentType
 * @property int $fragmentTypeId
 */
class Fragment extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::FRAGMENTS;
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getFragmentType(): ActiveQueryInterface
    {
        return $this->hasOne(FragmentType::class, ['id' => 'fragmentTypeId']);
    }

    public function getZone(): ActiveQueryInterface
    {
        return $this->hasOne(Zone::class, ['id' => 'zoneId']);
    }
}
