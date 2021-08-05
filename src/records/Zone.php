<?php

namespace thepixelage\fragments\records;

use craft\db\ActiveRecord;
use thepixelage\fragments\db\Table;

class Zone extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::ZONES;
    }
}
