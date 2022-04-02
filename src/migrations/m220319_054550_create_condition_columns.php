<?php

namespace thepixelage\fragments\migrations;

use Craft;
use craft\db\Migration;
use thepixelage\fragments\db\Table;
use yii\base\NotSupportedException;

/**
 * m220319_054550_create_condition_columns migration.
 */
class m220319_054550_create_condition_columns extends Migration
{
    /**
     * @inheritdoc
     * @throws NotSupportedException
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FRAGMENTS, 'entryCondition')) {
            $this->addColumn(Table::FRAGMENTS, 'entryCondition', $this->text()->after('settings'));
        }

        if (!$this->db->columnExists(Table::FRAGMENTS, 'userCondition')) {
            $this->addColumn(Table::FRAGMENTS, 'userCondition', $this->text()->after('entryCondition'));
        }

        return true;
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException
     */
    public function safeDown(): bool
    {
        if ($this->db->columnExists(Table::FRAGMENTS, 'entryCondition')) {
            $this->dropColumn(Table::FRAGMENTS, 'entryCondition');
        }

        if ($this->db->columnExists(Table::FRAGMENTS, 'userCondition')) {
            $this->dropColumn(Table::FRAGMENTS, 'userCondition');
        }

        return true;
    }
}
