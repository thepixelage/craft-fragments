<?php

return [
    'fragments' => 'fragments/fragments/plugin-index',
    'fragments/fragments' => 'fragments/fragments/index',
    'fragments/fragments/<zone:{handle}>' => 'fragments/fragments/index',
    'fragments/fragments/<zone:{handle}>/<type:{handle}>/new' => 'fragments/fragments/edit',
    'fragments/fragments/<zone:{handle}>/<type:{handle}>/new/<site:{handle}>' => 'fragments/fragments/edit',
    'fragments/fragments/<zone:{handle}>/<type:{handle}>/<fragmentId:\d+>' => 'fragments/fragments/edit',
    'fragments/fragments/<zone:{handle}>/<type:{handle}>/<fragmentId:\d+>/<site:{handle}>' => 'fragments/fragments/edit',
    'fragments/settings/fragmenttypes' => 'fragments/fragment-types/index',
    'fragments/settings/fragmenttypes/new' => 'fragments/fragment-types/edit',
    'fragments/settings/fragmenttypes/<fragmentTypeId:\d+>' => 'fragments/fragment-types/edit',
    'fragments/settings/zones' => 'fragments/zones/index',
    'fragments/settings/zones/new' => 'fragments/zones/edit',
    'fragments/settings/zones/<zoneId:\d+>' => 'fragments/zones/edit',
];
