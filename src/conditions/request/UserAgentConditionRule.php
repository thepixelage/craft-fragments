<?php

namespace thepixelage\fragments\conditions\request;

use Craft;
use craft\base\conditions\BaseTextConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

class UserAgentConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{

    public function getLabel(): string
    {
        return Craft::t('app', 'User Agent');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['userAgent'];
    }

    /**
     * @throws NotSupportedException
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        throw new NotSupportedException('Browser user agent condition rule does not support element queries.');
    }

    /**
     * @throws InvalidConfigException
     */
    public function matchElement(ElementInterface $element = null): bool
    {
        $request = Craft::$app->getRequest();

        return $this->matchValue($request->userAgent);
    }
}
