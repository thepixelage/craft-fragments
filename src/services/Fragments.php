<?php

namespace thepixelage\fragments\services;

use craft\base\Component;
use craft\elements\Entry;
use craft\elements\User;
use thepixelage\fragments\elements\Fragment;
use thepixelage\fragments\Plugin;
use yii\base\InvalidConfigException;

class Fragments extends Component
{
    public function hasTypesAndZonesSetup(): bool
    {
        return (
            Plugin::getInstance()->zones->getZoneCount() > 0 &&
            Plugin::getInstance()->fragmentTypes->getFragmentTypeCount() > 0
        );
    }

    /**
     * @throws InvalidConfigException
     */
    public function matchConditions(Fragment $fragment, Entry $entry = null, User $user = null): bool
    {
        if ($entryCondition = $fragment->getEntryCondition()) {
            if (count($entryCondition->getConditionRules()) > 0) {
                if (!$entry) {
                    return false;
                }

                foreach ($entryCondition->getConditionRules() as $rule) {
                    if (!$rule->matchElement($entry)) {
                        return false;
                    }
                }
            }
        }

        if ($userCondition = $fragment->getUserCondition()) {
            if (count($userCondition->getConditionRules()) > 0) {
                if (!$user) {
                    return false;
                }

                foreach ($userCondition->getConditionRules() as $rule) {
                    if (!$rule->matchElement($user)) {
                        return false;
                    }
                }
            }

        }

        return true;
    }
}
