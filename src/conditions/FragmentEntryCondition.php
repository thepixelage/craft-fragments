<?php

namespace thepixelage\fragments\conditions;

use craft\elements\conditions\entries\EntryCondition;
use thepixelage\fragments\conditions\entries\EntryUriConditionRule;

class FragmentEntryCondition extends EntryCondition
{
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            EntryUriConditionRule::class,
        ]);
    }
}
