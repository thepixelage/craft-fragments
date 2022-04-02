<?php

namespace thepixelage\fragments\migrations;

use craft\db\Migration;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\models\Zone;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();

        return true;
    }

    public function safeDown(): bool
    {
        $this->deleteTables();

        return true;
    }

    private function createTables()
    {
        $this->createTable(Table::FRAGMENTS, [
            'id' => $this->integer()->notNull(),
            'zoneId' => $this->integer()->notNull(),
            'fragmentTypeId' => $this->integer()->notNull(),
            'settings' => $this->text(),
            'entryCondition' => $this->text(),
            'userCondition' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);

        $this->createTable(Table::FRAGMENTTYPES, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable(Table::ZONES, [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'structureId' => $this->integer(),
            'enableVersioning' => $this->boolean()->defaultValue(false)->notNull(),
            'propagationMethod' => $this->string()->defaultValue(Zone::PROPAGATION_METHOD_ALL)->notNull(),
            'settings' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateDeleted' => $this->dateTime(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable(Table::ZONES_SITES, [
            'id' => $this->primaryKey(),
            'zoneId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'enabledByDefault' => $this->boolean()->defaultValue(true)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    private function createIndexes()
    {
        $this->createIndex(null, Table::FRAGMENTS, ['zoneId'], false);
        $this->createIndex(null, Table::FRAGMENTS, ['fragmentTypeId'], false);
        $this->createIndex(null, Table::FRAGMENTTYPES, ['name'], false);
        $this->createIndex(null, Table::FRAGMENTTYPES, ['handle'], false);
        $this->createIndex(null, Table::FRAGMENTTYPES, ['fieldLayoutId'], false);
        $this->createIndex(null, Table::FRAGMENTTYPES, ['dateDeleted'], false);
        $this->createIndex(null, Table::FRAGMENTTYPES, ['name'], false);
        $this->createIndex(null, Table::ZONES, ['handle'], false);
        $this->createIndex(null, Table::ZONES, ['structureId'], false);
        $this->createIndex(null, Table::ZONES, ['dateDeleted'], false);
        $this->createIndex(null, Table::ZONES_SITES, ['zoneId', 'siteId'], true);
        $this->createIndex(null, Table::ZONES_SITES, ['siteId'], false);
    }

    private function addForeignKeys()
    {
        $this->addForeignKey(null, Table::FRAGMENTS, ['zoneId'], Table::ZONES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FRAGMENTS, ['fragmentTypeId'], Table::FRAGMENTTYPES, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::FRAGMENTTYPES, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::ZONES, ['structureId'], Table::STRUCTURES, ['id'], 'SET NULL', null);
        $this->addForeignKey(null, Table::ZONES_SITES, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::ZONES_SITES, ['zoneId'], Table::ZONES, ['id'], 'CASCADE', null);
    }

    private function deleteTables()
    {
        $this->dropTableIfExists(Table::ZONES_SITES);
        $this->dropTableIfExists(Table::FRAGMENTS);
        $this->dropTableIfExists(Table::FRAGMENTTYPES);
        $this->dropTableIfExists(Table::ZONES);
    }
}
