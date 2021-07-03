<?php

return [
    'fragments/settings' => 'fragments/fragments/settings-index',
    'fragments/settings/types' => 'fragments/fragment-types/index',
    'fragments/settings/types/new' => 'fragments/fragment-types/edit',
    'fragments/settings/types/<typeId:\d+>' => 'fragments/fragment-types/edit',
    'fragments/settings/zones' => 'fragments/zones/index',
    'fragments/settings/zones/new' => 'fragments/zones/edit',
    'fragments/settings/zones/<zoneId:\d+>' => 'fragments/zones/edit',

    'fragments' => 'fragments/fragments/fragment-index',
    'fragments/<fragmentTypeHandle:{handle}>' => 'fragments/fragment-index',
];
