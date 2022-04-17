<?php

namespace thepixelage\fragments\migrations;

use Craft;
use craft\db\Migration;
use thepixelage\fragments\db\Table;
use yii\base\NotSupportedException;

/**
 * m220417_014547_add_request_condition_column_to_fragments_table migration.
 */
class m220417_014547_add_request_condition_column_to_fragments_table extends Migration
{
    /**
     * @inheritdoc
     * @throws NotSupportedException
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FRAGMENTS, 'requestCondition')) {
            $this->addColumn(Table::FRAGMENTS, 'requestCondition', $this->text()->after('userCondition'));
        }

        return true;
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException
     */
    public function safeDown(): bool
    {
        if ($this->db->columnExists(Table::FRAGMENTS, 'requestCondition')) {
            $this->dropColumn(Table::FRAGMENTS, 'requestCondition');
        }

        return false;
    }
}
