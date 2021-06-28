<?php

namespace thepixelage\fragments\models;

use craft\base\SavableComponent;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\Entry;
use craft\models\FieldLayout;
use yii\base\InvalidConfigException;

/**
 * @mixin FieldLayoutBehavior
 */

class FragmentType extends SavableComponent
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

    /**
     * @var int|null Field layout ID
     */
    public $fieldLayoutId;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['fieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => Entry::class,
        ];

        return $behaviors;
    }

    /**
     * @throws InvalidConfigException
     */
    public function getFieldLayout(): FieldLayout
    {
        /* @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('fieldLayout');

        return $behavior->getFieldLayout();
    }
}
