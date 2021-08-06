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
    public ?string $zoneHandle;

    public function init()
    {
        if ($this->withStructure === null) {
            $this->withStructure = true;
        }

        parent::init();
    }

    public function fragmentType($value): FragmentQuery
    {
        if (is_int($value)) {
            $this->fragmentTypeId = $value;
        }

        return $this;
    }

    public function zone($value): FragmentQuery
    {
        if (is_int($value)) {
            $this->zoneId = $value;
        }

        if (is_string($value)) {
            $this->zoneHandle = $value;
        }

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $fragmentsTableName = Craft::$app->db->schema->getRawTableName(Table::FRAGMENTS);
        $this->joinElementTable($fragmentsTableName);
        $this->query->select([
            sprintf('%s.uid', $fragmentsTableName),
            sprintf('%s.zoneId', $fragmentsTableName),
            sprintf('%s.fragmentTypeId', $fragmentsTableName),
        ]);

        if (!empty($this->fragmentTypeId)) {
            $this->subQuery->andWhere(Db::parseParam(sprintf('%s.fragmentTypeId', $fragmentsTableName), $this->fragmentTypeId));
        }

        if (!empty($this->zoneId)) {
            $this->subQuery->andWhere(Db::parseParam(sprintf('%s.zoneId', $fragmentsTableName), $this->zoneId));
        }

        if (!empty($this->zoneHandle)) {
            $zonesTableName = Craft::$app->db->schema->getRawTableName(Table::ZONES);
            $this->innerJoin($zonesTableName, sprintf('%s.id = %s.zoneId', $zonesTableName, $fragmentsTableName));
            $this->subQuery->andWhere(Db::parseParam(sprintf('%s.handle', $zonesTableName), $this->zoneHandle));
        }

        return parent::beforePrepare();
    }
}
