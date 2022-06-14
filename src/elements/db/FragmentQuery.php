<?php

namespace thepixelage\fragments\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use craft\helpers\Json;
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
    public ?string $type = null;
    public ?int $typeId;
    public ?string $zone = null;
    public ?int $zoneId;
    public ?bool $editable = false;

    public ?string $currentUrl = null;

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
        $currentUrl = null;

        if ($this->currentUrl) {
            $currentUrl = $this->currentUrl;
        }

        if (!$currentUrl && !(Craft::$app->request->isCpRequest || Craft::$app->request->isConsoleRequest)) {
            $currentUrl = Craft::$app->request->getUrl();
        }

        /** @var Fragment[] $fragments */
        $fragments = parent::all($db);

        if ($currentUrl == null) {
            return $fragments;
        }

        return array_filter($fragments, function ($fragment) use ($currentUrl) {
            $fragmentSettings = is_object($fragment) ? $fragment->settings : [];

            if (empty($fragmentSettings) && is_array($fragment) && isset($fragment['settings'])) {
                $fragmentSettings = Json::decode($fragment['settings']);
            }

            $ruleType = $fragmentSettings['visibility']['ruletype'];
            if ($ruleType == '' || count($fragmentSettings['visibility']['rules']) == 0) {
                return true;
            }

            $returnBool = ($ruleType == 'include');

            foreach ($fragmentSettings['visibility']['rules'] as $rule) {
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

    /**
     * @throws InvalidConfigException
     */
    public function one($db = null)
    {
        $fragments = $this->all($db);

        return count($fragments) > 0 ? reset($fragments) : null;
    }

    public function type($value): FragmentQuery
    {
        if (is_int($value)) {
            $this->typeId = $value;
        }

        if (is_string($value)) {
            $this->type = $value;
        }

        return $this;
    }

    public function zone($value): FragmentQuery
    {
        if (is_int($value)) {
            $this->zoneId = $value;
        }

        if (is_string($value)) {
            $this->zone = $value;
        }

        return $this;
    }

    public function currentUrl($value): FragmentQuery
    {
        $this->currentUrl = $value;

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

        if (!empty($this->typeId)) {
            $this->subQuery->andWhere(Db::parseParam(sprintf('%s.fragmentTypeId', $fragmentsTableName), $this->typeId));
        }

        if (!empty($this->type)) {
            $fragmentTypesTableName = 'fragmenttypes';
            $this->subQuery
                ->innerJoin($fragmentTypesTableName, sprintf('%s.id = %s.fragmentTypeId', $fragmentTypesTableName, $fragmentsTableName))
                ->andWhere(Db::parseParam(sprintf('%s.handle', $fragmentTypesTableName), $this->type));
        }

        if (!empty($this->zoneId)) {
            $this->subQuery->andWhere(Db::parseParam(sprintf('%s.zoneId', $fragmentsTableName), $this->zoneId));
        }

        if (!empty($this->zone)) {
            $zonesTableName = Craft::$app->db->schema->getRawTableName(Table::ZONES);
            $this->subQuery
                ->innerJoin($zonesTableName, sprintf('%s.id = %s.zoneId', $zonesTableName, $fragmentsTableName))
                ->andWhere(Db::parseParam(sprintf('%s.handle', $zonesTableName), $this->zone));
        }

        return parent::beforePrepare();
    }
}
