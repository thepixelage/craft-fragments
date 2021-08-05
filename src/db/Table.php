<?php

namespace thepixelage\fragments\db;

abstract class Table extends \craft\db\Table
{
    const FRAGMENTS = '{{%fragments}}';
    const FRAGMENTS_ZONES = '{{%fragments_zones}}';
    const FRAGMENTTYPES = '{{%fragmenttypes}}';
    const ZONES = '{{%fragmentzones}}';
}
