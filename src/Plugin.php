<?php

namespace thepixelage\fragments;

use Craft;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\Cp;
use craft\web\View;
use thepixelage\fragments\services\FragmentTypes;
use thepixelage\fragments\services\Zones;
use yii\base\Event;

/**
 * Class Plugin
 *
 * @package thepixelage\fragments
 *
 * @property FragmentTypes $fragmentTypes
 * @property Zones $zones
 */
class Plugin extends \craft\base\Plugin
{
    public static $plugin;

    public $schemaVersion = '1.0.0';
    public $hasCpSettings = true;
    public $hasCpSection = false;

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->registerServices();
        $this->registerTemplateRoot();
        $this->registerCpNavItems();
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
}
