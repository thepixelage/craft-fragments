<?php

namespace thepixelage\fragments\conditions;

use craft\elements\conditions\entries\EntryCondition;

class FragmentEntryCondition extends EntryCondition
{
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            EntryUriConditionRule::class,
        ]);
    }
}
