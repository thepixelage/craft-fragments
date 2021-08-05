<?php

namespace thepixelage\fragments\models;

use craft\base\Model;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use thepixelage\fragments\records\Zone as ZoneRecord;

class Zone extends Model
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $handle = null;
    public ?string $uid = null;

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];
        $rules[] = [['name', 'handle'], UniqueValidator::class, 'targetClass' => ZoneRecord::class];
        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];

        return $rules;
    }
}
