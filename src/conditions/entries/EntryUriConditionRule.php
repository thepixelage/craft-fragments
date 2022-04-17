<?php

namespace thepixelage\fragments\conditions\entries;

use Craft;
use craft\base\conditions\BaseTextConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use yii\base\NotSupportedException;

class EntryUriConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    protected const OPERATOR_REGEX_MATCH = '//';
    protected const OPERATOR_REGEX_NOTMATCH = '!//';

    public function getLabel(): string
    {
        return Craft::t('app', 'URI');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['entryUri'];
    }

    /**
     * @throws NotSupportedException
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        throw new NotSupportedException('Entry URI condition rule does not support element queries.');
    }

    /**
     */
    public function matchElement(ElementInterface $element = null): bool
    {
        return $this->matchValue($element->uri);
    }

    protected function matchValue(mixed $value): bool
    {
        $match = preg_match("/$this->value/", $value);

        return ($this->operator == self::OPERATOR_REGEX_NOTMATCH) ? !$match : $match;
    }

    protected function operators(): array
    {
        return array_merge(parent::operators(), [self::OPERATOR_REGEX_MATCH, self::OPERATOR_REGEX_NOTMATCH]);
    }

    protected function operatorLabel(string $operator): string
    {
        return match ($operator) {
            self::OPERATOR_REGEX_MATCH => Craft::t('app', 'matches (regex)'),
            self::OPERATOR_REGEX_NOTMATCH => Craft::t('app', 'does not match (regex)'),
            default => parent::operatorLabel($operator),
        };
    }
}
