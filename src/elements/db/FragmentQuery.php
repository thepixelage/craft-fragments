<?php

namespace thepixelage\fragments\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use thepixelage\fragments\db\Table;

class FragmentQuery extends ElementQuery
{
    public int $fragmentTypeId;

    public function fragmentTypeId($value): FragmentQuery
    {
        $this->fragmentTypeId = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $tableName = Craft::$app->db->schema->getRawTableName(Table::FRAGMENTS);
        $this->joinElementTable($tableName);
        $this->query->select([
            sprintf('%s.uid', $tableName),
            sprintf('%s.fragmentTypeId', $tableName),
        ]);

        if (!empty($this->fragmentTypeId)) {
            $this->subQuery->andWhere(Db::parseParam(sprintf('%s.fragmentTypeId', $tableName), $this->fragmentTypeId));
        }

        return parent::beforePrepare();
    }
}
