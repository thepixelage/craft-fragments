<?php

namespace thepixelage\fragments\records;

use craft\db\ActiveRecord;
use craft\records\Site;
use thepixelage\fragments\db\Table;
use yii\db\ActiveQueryInterface;

/**
 *
 * @property-read ActiveQueryInterface $site
 * @property-read ActiveQueryInterface $section
 */
class Zone_SiteSettings extends ActiveRecord
{
    public static function tableName(): string
    {
        return Table::ZONES_SITES;
    }

    public function getSection(): ActiveQueryInterface
    {
        return $this->hasOne(Zone::class, ['id' => 'zoneId']);
    }

    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
