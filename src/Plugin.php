<?php

namespace thepixelage\fragments;

use Craft;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\web\twig\variables\Cp;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use thepixelage\fragments\elements\Fragment;
use thepixelage\fragments\services\FragmentTypes;
use thepixelage\fragments\services\Zones;
use thepixelage\fragments\variables\FragmentsVariable;
use yii\base\Event;

/**
 * Class Plugin
 *
 * @package thepixelage\fragments
 *
 * @property FragmentTypes $fragmentTypes
 * @property-read mixed $settingsResponse
 * @property Zones $zones
 */
class Plugin extends \craft\base\Plugin
{
    public static Plugin $plugin;

    public $schemaVersion = '1.0.0';
    public $hasCpSettings = true;
    public $hasCpSection = false;

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->registerServices();
        $this->registerElementTypes();
        $this->registerVariables();
        $this->registerTemplateRoot();
        $this->registerCpRoutes();
        $this->registerCpNavItems();
        $this->registerProjectConfigChangeListeners();
        $this->registerFieldLayoutStandardFields();
    }

    public function getSettingsResponse()
    {
        $url = UrlHelper::cpUrl('fragments/settings');

        return Craft::$app->controller->redirect($url);
    }

    private function registerServices()
    {
        $this->setComponents([
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

    private function registerVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $e) {
            $variable = $e->sender;
            $variable->set('fragments', FragmentsVariable::class);
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

    private function registerCpNavItems()
    {
        Event::on(
            Cp::class,
            Cp::EVENT_REGISTER_CP_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event) {
                $fragmentNavItems = [
                    'url' => 'fragments',
                    'label' => 'Fragments',
                    'icon' => '@thepixelage/fragments/icon.svg',
                    'subnav' => [
                        'fragments' => ['label' => 'Fragments', 'url' => 'fragments'],
                        'zones' => ['label' => 'Zones', 'url' => 'fragments/zones'],
                        'settings' => ['label' => 'Settings', 'url' => 'settings/plugins/fragments'],
                    ],
                ];

                if ($this->fragmentTypes->getFragmentTypeCount() == 0) {
                    unset($fragmentNavItems['subnav']['fragments']);
                }

                if ($this->zones->getZoneCount() == 0) {
                    unset($fragmentNavItems['subnav']['zones']);
                }

                if (!Craft::$app->config->general->allowAdminChanges) {
                    unset($fragmentNavItems['subnav']['settings']);
                }

                $event->navItems[] = $fragmentNavItems;
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
        Event::on(FieldLayout::class, FieldLayout::EVENT_DEFINE_STANDARD_FIELDS, function(DefineFieldLayoutFieldsEvent $event) {
            /* @var FieldLayout $fieldLayout */
            $fieldLayout = $event->sender;

            if ($fieldLayout->type == Fragment::class) {
                $event->fields[] = TitleField::class;
            }
        });
    }
}
