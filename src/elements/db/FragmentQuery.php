<?php

namespace thepixelage\fragments\elements\db;

use Craft;
use craft\controllers\GraphqlController;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\web\Request;
use stdClass;
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
    public ?int $entryId = null;
    public ?int $userId = null;
    public ?stdClass $requestProps = null;

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
        $currentEntry = null;
        $currentUser = null;
        $currentRequest = null;
        $entryUri = null;

        if ($this->entryUri) {
            $entryUri = $this->entryUri;
        }

        if (!$entryUri && !(Craft::$app->request->isCpRequest || Craft::$app->request->isConsoleRequest)) {
            $entryUri = Craft::$app->request->getUrl();
        }

        if ($entryUri) {
            $element = Entry::find()->uri($entryUri)->one();
            if ($element instanceof Entry) {
                $currentEntry = $element;
            } else {
                if ($element = Craft::$app->urlManager->getMatchedElement()) {
                    $currentEntry = Craft::$app->entries->getEntryById($element->id);
                }
            }
        }

        if ($this->entryId) {
            $currentEntry = Craft::$app->entries->getEntryById($this->entryId);
        }

        if ($this->userId) {
            $currentUser = Craft::$app->users->getUserById($this->userId);
        } else {
            if ($currentUserId = Craft::$app->getUser()->id) {
                $currentUser = Craft::$app->users->getUserById($currentUserId);
            }
        }

        if ($this->requestProps) {
            $currentRequest = $this->requestProps;
        }

        /** @var Fragment[] $fragments */
        $fragments = parent::all($db);

        if ((Craft::$app->request->isCpRequest || Craft::$app->request->isConsoleRequest) && !(Craft::$app->controller instanceof GraphqlController)) {
            return $fragments;
        }

        return array_filter($fragments, function ($fragment) use ($currentEntry, $currentUser, $currentRequest) {
            return Plugin::getInstance()->fragments->matchConditions($fragment, $currentEntry, $currentUser, $currentRequest);
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

        if (in_array(trim($this->entryUri), ['', '/'])) {
            $this->entryUri = '__home__';
        }

        return $this;
    }

    public function entryId($value): FragmentQuery
    {
        $this->entryId = $value;

        return $this;
    }

    public function userId($value): FragmentQuery
    {
        $this->userId = $value;

        return $this;
    }

    public function requestProps($value): FragmentQuery
    {
        $this->requestProps = json_decode($value);

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
            sprintf('%s.requestCondition', $fragmentsTableName),
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

    protected function isCpOrConsoleRequest()
    {

    }
}
