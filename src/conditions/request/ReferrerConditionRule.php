<?php

namespace thepixelage\fragments\conditions\request;

use Craft;
use craft\base\conditions\BaseTextConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use stdClass;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

class ReferrerConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('app', 'Referrer');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['referrer'];
    }

    /**
     * @throws NotSupportedException
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        throw new NotSupportedException('Referrer condition rule does not support element queries.');
    }

    /**
     * @throws InvalidConfigException
     */
    public function matchElement(ElementInterface $element = null, stdClass $request = null): bool
    {
        $request = $request ?: Craft::$app->getRequest();
        if (!isset($request->referrer)) {
            return false;
        }

        $this->value = strtolower($this->value);
        $referrer = strtolower($request->referrer);

        return $this->matchValue($referrer);
    }
}
