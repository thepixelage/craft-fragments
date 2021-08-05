<?php

namespace thepixelage\fragments\elements;

use craft\base\Element;
use craft\models\FieldLayout;
use thepixelage\fragments\models\FragmentType;
use thepixelage\fragments\Plugin;
use yii\base\InvalidConfigException;

/**
 *
 * @property-read FragmentType $fragmentType
 */
class Fragment extends Element
{
    public string $fragmentTypeId;

    /**
     * @throws InvalidConfigException
     */
    public function getFieldLayout(): ?FieldLayout
    {
        return parent::getFieldLayout() ?? $this->getFragmentType()->getFieldLayout();
    }

    /**
     * @throws InvalidConfigException
     */
    public function getFragmentType(): FragmentType
    {
        if ($this->fragmentTypeId === null) {
            throw new InvalidConfigException('Fragment is missing its fragment type ID');
        }

        $type = Plugin::$plugin->fragmentTypes->getFragmentTypeById($this->fragmentTypeId);

        if (!$type) {
            throw new InvalidConfigException('Invalid fragment fragment type ID: ' . $this->fragmentTypeId);
        }

        return $type;
    }
}
