<?php

namespace thepixelage\fragments\models;

use craft\base\Model;
use thepixelage\fragments\Plugin;
use yii\base\InvalidConfigException;

class Zone_SiteSettings extends Model
{
    public ?int $id;
    public ?int $zoneId;
    public ?int $siteId;
    public ?bool $enabledByDefault = true;
    private ?Zone $zone;

    /**
     * @throws InvalidConfigException
     */
    public function getZone(): Zone
    {
        if ($this->zone !== null) {
            return $this->zone;
        }

        if (!$this->zoneId) {
            throw new InvalidConfigException('Zone site settings model is missing its zone ID');
        }

        if (($this->zone = Plugin::getInstance()->zones->getZoneById($this->zoneId)) === null) {
            throw new InvalidConfigException('Invalid zone ID: ' . $this->zoneId);
        }

        return $this->zone;
    }

    public function setZone(Zone $zone)
    {
        $this->zone = $zone;
    }
}
