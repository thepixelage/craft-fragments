<?php

namespace thepixelage\fragments\db;

abstract class Table extends \craft\db\Table
{
    const FRAGMENTS = '{{%fragments}}';
    const FRAGMENTTYPES = '{{%fragmenttypes}}';
    const ZONES = '{{%fragmentzones}}';
    const ZONES_SITES = '{{%fragmentzones_sites}}';
}
