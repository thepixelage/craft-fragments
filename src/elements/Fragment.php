<?php

namespace thepixelage\fragments\elements;

use Craft;
use craft\base\Element;
use craft\controllers\ElementIndexesController;
use craft\elements\actions\Delete;
use craft\elements\actions\Duplicate;
use craft\elements\actions\Edit;
use craft\elements\actions\NewChild;
use craft\elements\actions\Restore;
use craft\elements\actions\SetStatus;
use craft\elements\actions\View;
use craft\elements\db\CategoryQuery;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\errors\SiteNotFoundException;
use craft\helpers\UrlHelper;
use craft\models\CategoryGroup;
use thepixelage\fragments\Plugin;
use yii\base\InvalidConfigException;

class Fragment extends Element
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Fragment');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('app', 'fragment');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('app', 'Fragments');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('app', 'fragments');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'fragment';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasUris(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @return CategoryQuery The newly created [[CategoryQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new CategoryQuery(static::class);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlTypeNameByContext($context): string
    {
        /* @var CategoryGroup $context */
        return $context->handle . '_Fragment';
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function gqlScopesByContext($context): array
    {
        /* @var CategoryGroup $context */
        return ['fragmenttypes.' . $context->uid];
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public static function gqlMutationNameByContext($context): string
    {
        /* @var CategoryGroup $context */
        return 'save_' . $context->handle . '_Fragment';
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [];

        $types = Plugin::$plugin->fragmentTypes->getAllFragmentTypes();

        foreach ($types as $type) {
            $sources[] = [
                'key' => 'group:' . $type->uid,
                'label' => Craft::t('site', $type->name),
                'data' => ['handle' => $type->handle],
                'criteria' => ['typeId' => $type->id],
            ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public static function defineFieldLayouts(string $source): array
    {
        $fieldLayouts = [];
        if (
            preg_match('/^type:(.+)$/', $source, $matches) &&
            ($type = Plugin::$plugin->fragmentTypes->getFragmentTypeById($matches[1]))
        ) {
            $fieldLayouts[] = $type->getFieldLayout();
        }
        return $fieldLayouts;
    }

    /**
     * @inheritdoc
     * @throws SiteNotFoundException
     */
    protected static function defineActions(string $source = null): array
    {
        // Get the selected site
        $controller = Craft::$app->controller;
        if ($controller instanceof ElementIndexesController) {
            /* @var ElementQuery $elementQuery */
            $elementQuery = $controller->getElementQuery();
        } else {
            $elementQuery = null;
        }
        $site = $elementQuery && $elementQuery->siteId
            ? Craft::$app->getSites()->getSiteById($elementQuery->siteId)
            : Craft::$app->getSites()->getCurrentSite();

        // Get the group we need to check permissions on
        if (preg_match('/^group:(\d+)$/', $source, $matches)) {
            $group = Craft::$app->getCategories()->getGroupById($matches[1]);
        } else if (preg_match('/^group:(.+)$/', $source, $matches)) {
            $group = Craft::$app->getCategories()->getGroupByUid($matches[1]);
        }

        // Now figure out what we can do with it
        $actions = [];
        $elementsService = Craft::$app->getElements();

        if (!empty($group)) {
            // Set Status
            $actions[] = SetStatus::class;

            // View
            // They are viewing a specific category group. See if it has URLs for the requested site
            if (isset($group->siteSettings[$site->id]) && $group->siteSettings[$site->id]->hasUrls) {
                $actions[] = $elementsService->createAction([
                    'type' => View::class,
                    'label' => Craft::t('app', 'View category'),
                ]);
            }

            // Edit
            $actions[] = $elementsService->createAction([
                'type' => Edit::class,
                'label' => Craft::t('app', 'Edit category'),
            ]);

            // New Child
            if ($group->maxLevels != 1) {
                $newChildUrl = 'categories/' . $group->handle . '/new';

                if (Craft::$app->getIsMultiSite()) {
                    $newChildUrl .= '/' . $site->handle;
                }

                $actions[] = $elementsService->createAction([
                    'type' => NewChild::class,
                    'label' => Craft::t('app', 'Create a new child category'),
                    'maxLevels' => $group->maxLevels,
                    'newChildUrl' => $newChildUrl,
                ]);
            }

            // Duplicate
            $actions[] = Duplicate::class;

            if ($group->maxLevels != 1) {
                $actions[] = [
                    'type' => Duplicate::class,
                    'deep' => true,
                ];
            }

            // Delete
            $actions[] = Delete::class;

            if ($group->maxLevels != 1) {
                $actions[] = [
                    'type' => Delete::class,
                    'withDescendants' => true,
                ];
            }
        }

        // Restore
        $actions[] = $elementsService->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('app', 'Categories restored.'),
            'partialSuccessMessage' => Craft::t('app', 'Some categories restored.'),
            'failMessage' => Craft::t('app', 'Categories not restored.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'slug' => Craft::t('app', 'Slug'),
            'uri' => Craft::t('app', 'URI'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Title')],
            'slug' => ['label' => Craft::t('app', 'Slug')],
            'uri' => ['label' => Craft::t('app', 'URI')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'link',
        ];
    }

    /**
     * @var int|null Group ID
     */
    public $typeId;

    /**
     * @var int|false|null New parent ID
     */
    public $newParentId;

    /**
     * @var bool Whether the category was deleted along with its group
     * @see beforeDelete()
     */
    public $deletedWithGroup = false;

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        $names = parent::extraFields();
        $names[] = 'type';
        return $names;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['typeId'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function getCacheTags(): array
    {
        return [
            "type:$this->typeId",
        ];
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function getIsEditable(): bool
    {
        return Craft::$app->getUser()->checkPermission('editCategories:' . $this->getType()->uid);
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function getCpEditUrl()
    {
        $group = $this->getType();

        $url = UrlHelper::cpUrl('categories/' . $group->handle . '/' . $this->id . ($this->slug ? '-' . $this->slug : ''));

        if (Craft::$app->getIsMultiSite()) {
            $url .= '/' . $this->getSite()->handle;
        }

        return $url;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function getFieldLayout()
    {
        return parent::getFieldLayout() ?? $this->getType()->getFieldLayout();
    }

    /**
     * Returns the category's group.
     *
     * @return CategoryGroup
     * @throws InvalidConfigException if [[groupId]] is missing or invalid
     */
    public function getType(): CategoryGroup
    {
        if ($this->typeId === null) {
            throw new InvalidConfigException('Category is missing its group ID');
        }

        $type = Plugin::$plugin->fragmentTypes->getFragmentTypeById($this->typeId);

        if (!$type) {
            throw new InvalidConfigException('Invalid category group ID: ' . $this->typeId);
        }

        return $type;
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     * @since 3.3.0
     */
    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this->getType());
    }
}
