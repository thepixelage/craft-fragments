<?php

namespace thepixelage\fragments\gql\types\elements;

use thepixelage\fragments\gql\interfaces\elements\Fragment as FragmentInterface;
use craft\gql\types\elements\Element;

class Fragment extends Element
{
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            FragmentInterface::getType(),
        ];

        parent::__construct($config);
    }
}
