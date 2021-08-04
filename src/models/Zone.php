<?php

namespace thepixelage\fragments\models;

use craft\base\Model;

class Zone extends Model
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $handle = null;
    public ?string $uid = null;

    public function rules(): array
    {
        return [
            [['name', 'handle'], 'required']
        ];
    }
}
