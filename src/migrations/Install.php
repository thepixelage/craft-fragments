<?php

namespace thepixelage\fragments\migrations;

use craft\db\Migration;
use thepixelage\fragments\db\Table;

class Install extends Migration
{
    public function safeUp()
    {
        $this->_createTables();

    }

    public function safeDown()
    {
        $this->_deleteTables();
    }

    private function _createTables()
    {
        $this->createTable(Table::FRAGMENTS, [
            'id'             => $this->integer()->notNull(),
            'zoneId'         => $this->integer()->notNull(),
            'fragmentTypeId' => $this->integer()->notNull(),
            'dateCreated'    => $this->dateTime()->notNull(),
            'dateUpdated'    => $this->dateTime()->notNull(),
            'uid'            => $this->uid(),
            'PRIMARY KEY([[id]])',
        ]);

        $this->createTable(Table::FRAGMENTTYPES, [
            'id'            => $this->primaryKey(),
            'name'          => $this->string()->notNull(),
            'handle'        => $this->string()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'dateCreated'   => $this->dateTime()->notNull(),
            'dateDeleted'   => $this->dateTime(),
            'dateUpdated'   => $this->dateTime()->notNull(),
            'uid'           => $this->uid(),
        ]);

        $this->createTable(Table::ZONES, [
            'id'            => $this->primaryKey(),
            'name'          => $this->string()->notNull(),
            'handle'        => $this->string()->notNull(),
            'structureId'   => $this->integer(),
            'dateCreated'   => $this->dateTime()->notNull(),
            'dateDeleted'   => $this->dateTime(),
            'dateUpdated'   => $this->dateTime()->notNull(),
            'uid'           => $this->uid(),
        ]);
    }

    private function _deleteTables()
    {
        $this->dropTableIfExists(Table::FRAGMENTS);
        $this->dropTableIfExists(Table::FRAGMENTTYPES);
        $this->dropTableIfExists(Table::ZONES);
    }
}
