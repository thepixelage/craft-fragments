<?php

namespace thepixelage\fragments\models;

use craft\base\Model;

class Zone extends Model
{
    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    /**
     * @var string UID
     */
    public $uid;
}
