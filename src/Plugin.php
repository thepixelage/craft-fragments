<?php

namespace thepixelage\fragments;

use Craft;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlSchemaComponentsEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Gql;
use craft\services\UserPermissions;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use thepixelage\fragments\behaviors\CraftVariableBehavior;
use thepixelage\fragments\elements\Fragment;
use thepixelage\fragments\fields\Fragments as FragmentsField;
use thepixelage\fragments\gql\interfaces\elements\Fragment as FragmentInterface;
use thepixelage\fragments\gql\queries\Fragment as FragmentGqlQuery;
use thepixelage\fragments\services\Fragments;
use thepixelage\fragments\services\FragmentTypes;
use thepixelage\fragments\services\Zones;
use yii\base\Event;

/**
 * Class Plugin
 *
 * @package thepixelage\fragments
 *
 * @property Fragments $fragments
 * @property FragmentTypes $fragmentTypes
 * @property Zones $zones
 *
 * @property-read null|array $cpNavItem
 * @property-read mixed $settingsResponse
 */
class Plugin extends \craft\base\Plugin
{
    public static Plugin $plugin;

    public string $schemaVersion = '4.0.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->registerServices();
        $this->registerElementTypes();
        $this->registerFieldTypes();
        $this->registerVariables();
        $this->registerTemplateRoot();
        $this->registerCpRoutes();
        $this->registerProjectConfigChangeListeners();
        $this->registerFieldLayoutStandardFields();
        $this->registerUserPermissions();
        $this->registerGql();
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->controller->redirect(UrlHelper::cpUrl('fragments/settings'));
    }

    public function getCpNavItem(): ?array
    {
        $subNavs = [];
        $navItem = parent::getCpNavItem();
        $currentUser = Craft::$app->getUser()->getIdentity();
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if ($generalConfig->allowAdminChanges && $currentUser->admin) {
            $subNavs['fragments'] = [
                'label' => Craft::t('fragments', "Fragments"),
                'url' => 'fragments/fragments',
            ];

            $subNavs['settings'] = [
                'label' => Craft::t('fragments', "Settings"),
                'url' => 'fragments/settings',
            ];

            if (!Plugin::getInstance()->fragments->hasTypesAndZonesSetup()) {
                unset($subNavs['fragments']);
            }
        }

        return array_merge($navItem, [
            'subnav' => $subNavs,
        ]);
    }

    private function registerServices()
    {
        $this->setComponents([
            'fragments' => Fragments::class,
            'fragmentTypes' => FragmentTypes::class,
            'zones' => Zones::class,
        ]);
    }

    private function registerElementTypes()
    {
        Event::on(Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = Fragment::class;
            }
        );
    }

    private function registerFieldTypes()
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = FragmentsField::class;
            }
        );
    }

    private function registerVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $e) {
            /** @var CraftVariable $variable */
            $variable = $e->sender;
            $variable->attachBehaviors([
                CraftVariableBehavior::class,
            ]);
        });
    }

    private function registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $rules = include __DIR__ . '/config/routes.php';
            $event->rules = array_merge($event->rules, $rules);
        });
    }

    private function registerTemplateRoot()
    {
        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $event) {
                $event->roots['@fragments'] = __DIR__ . '/templates/';
            }
        );
    }

    private function registerProjectConfigChangeListeners()
    {
        Craft::$app->projectConfig
            ->onAdd('fragmentZones.{uid}', [$this->zones, 'handleChangedZone'])
            ->onUpdate('fragmentZones.{uid}', [$this->zones, 'handleChangedZone'])
            ->onRemove('fragmentZones.{uid}', [$this->zones, 'handleDeletedZone'])
            ->onAdd('fragmentTypes.{uid}', [$this->fragmentTypes, 'handleChangedFragmentType'])
            ->onUpdate('fragmentTypes.{uid}', [$this->fragmentTypes, 'handleChangedFragmentType'])
            ->onRemove('fragmentTypes.{uid}', [$this->fragmentTypes, 'handleDeletedFragmentType']);
    }

    private function registerFieldLayoutStandardFields()
    {
        Event::on(FieldLayout::class, FieldLayout::EVENT_DEFINE_NATIVE_FIELDS, function(DefineFieldLayoutFieldsEvent $event) {
            /* @var FieldLayout $fieldLayout */
            $fieldLayout = $event->sender;

            if ($fieldLayout->type == Fragment::class) {
                $event->fields[] = TitleField::class;
            }
        });
    }

    private function registerUserPermissions()
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $zones = $this->zones->getAllZones();
                foreach ($zones as $zone) {
                    $event->permissions[] = [
                        'heading' => Craft::t('fragments', 'Fragment Zone - ' . $zone->name),
                        'permissions' =>  [
                            ('editFragments:' . $zone->uid) => [
                                'label' => 'Edit fragments',
                                'nested' => [
                                    ('createFragments:' . $zone->uid) => [
                                        'label' => 'Create fragments',
                                    ],
                                    ('deleteFragments:' . $zone->uid) => [
                                        'label' => 'Delete fragments',
                                    ],
                                ],
                            ],
                        ]
                    ];
                }
            }
        );
    }

    private function registerGql()
    {
        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_QUERIES,
            function(RegisterGqlQueriesEvent $event) {
                $event->queries = array_merge(
                    $event->queries,
                    FragmentGqlQuery::getQueries()
                );
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_TYPES,
            function(RegisterGqlTypesEvent $event) {
                $event->types[] = FragmentInterface::class;
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_SCHEMA_COMPONENTS,
            function(RegisterGqlSchemaComponentsEvent $event) {
                $fragmentTypes = $this->fragmentTypes->getAllFragmentTypes();

                if (!empty($fragmentTypes)) {
                    $queryComponents = [];
                    foreach ($fragmentTypes as $fragmentType) {
                        $queryComponents['fragmenttypes.' . $fragmentType->uid . ':read'] = [
                            'label' => 'View fragment type - ' . $fragmentType->name
                        ];
                    }

                    $event->queries = array_merge($event->queries, [
                        'Fragments' => $queryComponents,
                    ]);
                }
            }
        );
    }
}
