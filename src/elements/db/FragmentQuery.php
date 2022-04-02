<?php

namespace thepixelage\fragments\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\helpers\Db;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\elements\Fragment;
use thepixelage\fragments\Plugin;
use yii\base\InvalidConfigException;
use yii\base\Model;

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

    public ?string $entryUri = null;

    public function init(): void
    {
        if ($this->withStructure === null) {
            $this->withStructure = true;
        }

        parent::init();
    }

    /**
     * @throws InvalidConfigException
     */
    public function all($db = null): array
    {
        $entryUri = $this->entryUri ?: Craft::$app->request->getUrl();

        if ((Craft::$app->request->isCpRequest || Craft::$app->request->isConsoleRequest) &&
            !$this->entryUri) {
            $entryUri = null;
        }

        /** @var Fragment[] $fragments */
        $fragments = parent::all($db);

        if ($entryUri == null) {
            return $fragments;
        }

        $element = Entry::find()->uri($entryUri)->one();

        if ($element instanceof Entry) {
            $currentEntry = $element;
        } else {
            if ($element = Craft::$app->urlManager->getMatchedElement()) {
                $currentEntry = Craft::$app->entries->getEntryById($element->id);
            } else {
                $currentEntry = null;
            }
        }

        if ($currentUserId = Craft::$app->getUser()->id) {
            $currentUser = Craft::$app->users->getUserById($currentUserId);
        } else {
            $currentUser = null;
        }

        return array_filter($fragments, function ($fragment) use ($currentEntry, $currentUser) {
            return Plugin::getInstance()->fragments->matchConditions($fragment, $currentEntry, $currentUser);
        });
    }

    /**
     * @throws InvalidConfigException
     */
    public function one($db = null): array|null|Model
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

    public function entryUri($value): FragmentQuery
    {
        $this->entryUri = $value;

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
            sprintf('%s.entryCondition', $fragmentsTableName),
            sprintf('%s.userCondition', $fragmentsTableName),
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
