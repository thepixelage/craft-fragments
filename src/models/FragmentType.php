<?php

namespace thepixelage\fragments\models;

use craft\base\Model;

class FragmentType extends Model
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $handle = null;
    public ?string $uid = null;
}
