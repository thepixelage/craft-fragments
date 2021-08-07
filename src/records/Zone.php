<?php

namespace thepixelage\fragments\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\records\Structure;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\elements\Fragment;
use thepixelage\fragments\models\Zone_SiteSettings;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id ID
 * @property int $structureId Structure ID
 * @property string $name Name
 * @property string $handle Handle
 * @property Structure $structure Structure
 * @property Fragment[] $fragments Categories
 * @property bool $enableVersioning Enable versioning
 * @property string $propagationMethod Propagation method
 * @property Zone_SiteSettings[] $siteSettings Site settings
 * @property string $settings Settings
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

    public function getSiteSettings(): ActiveQueryInterface
    {
        return $this->hasMany(Zone_SiteSettings::class, ['zoneId' => 'id']);
    }
}
