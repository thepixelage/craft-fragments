<?php

namespace thepixelage\fragments\conditions;

use craft\base\ElementInterface;
use craft\elements\conditions\ElementCondition;
use thepixelage\fragments\conditions\request\ReferrerConditionRule;
use thepixelage\fragments\conditions\request\RemoteHostConditionRule;
use thepixelage\fragments\conditions\request\RemoteIpConditionRule;
use thepixelage\fragments\conditions\request\UrlConditionRule;
use thepixelage\fragments\conditions\request\UserAgentConditionRule;

class FragmentRequestCondition extends ElementCondition
{
    protected function conditionRuleTypes(): array
    {
        return [
            ReferrerConditionRule::class,
            RemoteHostConditionRule::class,
            RemoteIpConditionRule::class,
            UrlConditionRule::class,
            UserAgentConditionRule::class,
        ];
    }

    public function matchElement(ElementInterface $element = null): bool
    {
        return parent::matchElement($element);
    }
}
