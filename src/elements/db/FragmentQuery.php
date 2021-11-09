<?php

namespace thepixelage\fragments\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\elements\Fragment;
use yii\base\InvalidConfigException;

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
    public ?bool $editable = false;

    public function init()
    {
        if ($this->withStructure === null) {
            $this->withStructure = true;
        }

        parent::init();
    }

    /**
     * @throws InvalidConfigException
     */
    public function all($db = null): ?array
    {
        if (Craft::$app->request->getIsCpRequest()) {
            return parent::all($db);
        }

        /** @var Fragment[] $fragments */
        $fragments = parent::all($db);

        if (Craft::$app->request->isConsoleRequest) {
            return $fragments;
        }

        $currentUrl = Craft::$app->request->getUrl();

        return array_filter($fragments, function ($fragment) use ($currentUrl) {
            $ruleType = $fragment->settings['visibility']['ruletype'];
            if ($ruleType == '' || count($fragment->settings['visibility']['rules']) == 0) {
                return true;
            }

            $returnBool = ($ruleType == 'include');

            foreach ($fragment->settings['visibility']['rules'] as $rule) {
                if (stristr($rule['uri'], '*')) {
                    $pattern = str_replace('*', '.*', $rule['uri']);
                    $pattern = str_replace('/', '\/', $pattern);
                    if (preg_match("/$pattern/", $currentUrl)) {
                        return $returnBool;
                    }
                } else {
                    if ($rule['uri'] == $currentUrl) {
                        return $returnBool;
                    }
                }
            }

            return !$returnBool;
        });
    }

    public function one($db = null)
    {
        $fragments = $this->all($db);

        return count($fragments) > 0 ? $fragments[0] : null;
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

    public function editable(bool $value = true): FragmentQuery
    {
        $this->editable = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        $fragmentsTableName = 'fragments';
        $this->joinElementTable($fragmentsTableName);

        $this->query->select([
            sprintf('%s.uid', $fragmentsTableName),
            sprintf('%s.zoneId', $fragmentsTableName),
            sprintf('%s.fragmentTypeId', $fragmentsTableName),
            sprintf('%s.settings', $fragmentsTableName),
        ]);

        if (!empty($this->fragmentTypeId)) {
            $this->subQuery->andWhere(Db::parseParam(sprintf('%s.fragmentTypeId', $fragmentsTableName), $this->fragmentTypeId));
        }

        if (!empty($this->fragmentTypeHandle)) {
            $fragmentTypesTableName = 'fragmenttypes';
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
