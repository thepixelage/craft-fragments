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
    public ?string $fragmentTypeHandle;
    public ?int $zoneId;
    public ?string $zoneHandle;

    public function init()
    {
        if ($this->withStructure === null) {
            $this->withStructure = true;
        }

        parent::init();
    }

    public function type($value): FragmentQuery
    {
        if (is_int($value)) {
            $this->fragmentTypeId = $value;
        }

        if (is_string($value)) {
            $this->fragmentTypeHandle = $value;
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
            sprintf('%s.fragmentTypeId', $fragmentsTableName),
        ]);

        if (!empty($this->fragmentTypeId)) {
            $this->subQuery->andWhere(Db::parseParam(sprintf('%s.fragmentTypeId', $fragmentsTableName), $this->fragmentTypeId));
        }

        if (!empty($this->fragmentTypeHandle)) {
            $fragmentTypesTableName = Craft::$app->db->schema->getRawTableName(Table::FRAGMENTTYPES);
            $this->subQuery
                ->innerJoin($fragmentTypesTableName, sprintf('%s.id = %s.fragmentTypeId', $fragmentTypesTableName, $fragmentsTableName))
                ->andWhere(Db::parseParam(sprintf('%s.handle', $fragmentTypesTableName), $this->fragmentTypeHandle));
        }

        if (!empty($this->zoneId)) {
            $this->subQuery->andWhere(Db::parseParam(sprintf('%s.zoneId', $fragmentsTableName), $this->zoneId));
        }

        if (!empty($this->zoneHandle)) {
            $zonesTableName = Craft::$app->db->schema->getRawTableName(Table::ZONES);
            $this->subQuery
                ->innerJoin($zonesTableName, sprintf('%s.id = %s.zoneId', $zonesTableName, $fragmentsTableName))
                ->andWhere(Db::parseParam(sprintf('%s.handle', $zonesTableName), $this->zoneHandle));
        }

        return parent::beforePrepare();
    }
}
