<?php

namespace thepixelage\fragments\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\StringHelper;
use Exception;
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
     * @throws Exception
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(Table::FRAGMENTS, 'entryCondition')) {
            $this->addColumn(Table::FRAGMENTS, 'entryCondition', $this->text()->after('settings'));
        }

        if (!$this->db->columnExists(Table::FRAGMENTS, 'userCondition')) {
            $this->addColumn(Table::FRAGMENTS, 'userCondition', $this->text()->after('entryCondition'));
        }

        $this->convertLegacyRules();

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

    /**
     * @throws Exception
     * @noinspection PhpUnnecessaryLocalVariableInspection
     */
    private function convertLegacyRules(): void
    {
        $rows = (new Query())
            ->select('*')
            ->from(Table::FRAGMENTS)
            ->all();

        foreach ($rows as $row) {
            $settings = json_decode($row['settings']);
            if (is_array($settings->visibility->rules)) {
                $newRuleString = join('|', array_map(function($rule) {
                    $replaced = preg_replace('/^\//', '', $rule->uri);
                    $replaced = str_replace('/', '\/', $replaced);
                    $replaced = str_replace('?', '\?', $replaced);
                    $replaced = str_replace('.', '\.', $replaced);
                    $replaced = str_replace('+', '\+', $replaced);
                    $replaced = str_replace('*', '.+', $replaced);
                    return $replaced;
                }, $settings->visibility->rules));

                $entryCondition = [
                    'elementType' => null,
                    'fieldContext' => 'global',
                    'class' => 'thepixelage\\fragments\\conditions\\FragmentEntryCondition',
                    'conditionRules' => [
                        [
                            'class' => 'thepixelage\\fragments\\conditions\\entries\\EntryUriConditionRule',
                            'uid' => StringHelper::UUID(),
                            'operator' => $settings->visibility->ruletype == 'include' ? 'REGEXMATCH' : '!REGEXMATCH',
                            'value' => $newRuleString,
                        ]
                    ]
                ];

                $this->update(
                    Table::FRAGMENTS,
                    [
                        'entryCondition' => str_replace('REGEXMATCH', '//', json_encode($entryCondition)),
                    ],
                    [
                        'id' => $row['id'],
                    ],
                );
            }
        }
    }
}
