<?php

namespace thepixelage\fragments\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use thepixelage\fragments\db\Table;

class FragmentQuery extends ElementQuery
{
    public int $typeId;

    protected function beforePrepare(): bool
    {
        $tableName = Craft::$app->db->schema->getRawTableName(Table::FRAGMENTS);
        $this->joinElementTable($tableName);
        $this->query->select([
            sprintf('%s.uid', $tableName),
            sprintf('%s.typeId', $tableName),
        ]);

        return parent::beforePrepare();
    }
}
