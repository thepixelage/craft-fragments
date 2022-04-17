<?php

namespace thepixelage\fragments\conditions\request;

use craft\base\conditions\BaseConditionRule;

class RegexTextConditionRule extends BaseConditionRule
{

    protected function inputHtml(): string
    {
        return '<div>xx</div>';
    }

    public function getLabel(): string
    {
        return 'Regex';
    }
}
