<?php

namespace thepixelage\fragments\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use thepixelage\fragments\db\Table;

/**
 * Class FragmentQuery
 *
 * @package thepixelage\fragments\elements\db
 */
class FragmentQuery extends ElementQuery
{
    public ?int $fragmentTypeId;
    public ?int $zoneId;

    public function init()
    {
        if ($this->withStructure === null) {
            $this->withStructure = true;
        }

        parent::init();
    }

    public function fragmentTypeId($value): FragmentQuery
    {
        $this->fragmentTypeId = $value;

        return $this;
    }

    public function zoneId($value): FragmentQuery
    {
        $this->zoneId = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $tableName = Craft::$app->db->schema->getRawTableName(Table::FRAGMENTS);
        $this->joinElementTable($tableName);
        $this->query->select([
            sprintf('%s.uid', $tableName),
            sprintf('%s.zoneId', $tableName),
            sprintf('%s.fragmentTypeId', $tableName),
        ]);

        if (!empty($this->fragmentTypeId)) {
            $this->subQuery->andWhere(Db::parseParam(sprintf('%s.fragmentTypeId', $tableName), $this->fragmentTypeId));
        }

        if (!empty($this->zoneId)) {
            $this->subQuery->andWhere(Db::parseParam(sprintf('%s.zoneId', $tableName), $this->zoneId));
        }

        return parent::beforePrepare();
    }
}
