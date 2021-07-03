<?php

namespace thepixelage\fragments\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use thepixelage\fragments\models\Zone;
use thepixelage\fragments\Plugin;
use thepixelage\fragments\services\Zones;
use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ZonesController extends Controller
{
    /**
     * @throws ForbiddenHttpException
     */
    public function actionIndex()
    {
        $this->requireAdmin();

        /** @var Zones $zonesService */
        $zonesService = Plugin::$plugin->zones;
        $zones = $zonesService->getAllZones();

        // Breadcrumbs
        $crumbs = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => UrlHelper::url('settings'),
            ],
            [
                'label' => Craft::t('app', 'Fragments'),
                'url' => UrlHelper::url('fragments/settings'),
            ],
        ];

        return $this->renderTemplate('fragments/settings/zones/_index', [
            'zones' => $zones,
            'crumbs' => $crumbs,
        ]);
    }

    /**
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionEdit(int $zoneId = null, Zone $zone = null): Response
    {
        $this->requireAdmin();

        $variables = [];

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => UrlHelper::url('settings'),
            ],
            [
                'label' => Craft::t('app', 'Fragments'),
                'url' => UrlHelper::url('fragments/settings'),
            ],
            [
                'label' => Craft::t('app', 'Zones'),
                'url' => UrlHelper::url('fragments/settings/zones'),
            ],
        ];

        $variables['brandNewZone'] = false;

        if ($zoneId !== null) {
            if ($zone === null) {
                /** @var Zones $zonesService */
                $zonesService = Plugin::$plugin->zones;
                $zone = $zonesService->getZoneById($zoneId);

                if (!$zone) {
                    throw new NotFoundHttpException('Zone not found');
                }
            }

            $variables['title'] = trim($zone->name) ?: Craft::t('app', 'Edit Zone');
        } else {
            if ($zone === null) {
                $zone = new Zone();
                $variables['brandNewZone'] = true;
            }

            $variables['title'] = Craft::t('app', 'Create a new zone');
        }

        $variables['zoneId'] = $zoneId;
        $variables['zone'] = $zone;

        return $this->renderTemplate('fragments/settings/zones/_edit', $variables);
    }

    /**
     * @throws Throwable
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     */
    public function actionSave()
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        /** @var Zones $zonesService */
        $zonesService = Plugin::$plugin->zones;
        $zoneId = $this->request->getBodyParam('zoneId');

        if ($zoneId) {
            $zone = $zonesService->getZoneById($zoneId);
            if (!$zone) {
                throw new BadRequestHttpException("Invalid zone ID: $zoneId");
            }
        } else {
            $zone = new Zone();
        }

        $zone->name = $this->request->getBodyParam('name');
        $zone->handle = $this->request->getBodyParam('handle');

        // Save it
        if (!$zonesService->saveZone($zone)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save the zone.'));

            // Send the fragment type back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'zone' => $zone,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Zone saved.'));
        return $this->redirectToPostedUrl($zone);
    }

    /**
     * @throws Throwable
     * @throws BadRequestHttpException
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        /** @var Zones $zonesService */
        $zonesService = Plugin::$plugin->zones;

        $zoneId = $this->request->getRequiredBodyParam('id');
        $zone = $zonesService->getZoneById($zoneId);

        $zonesService->deleteZone($zone);

        return $this->asJson(['success' => true]);
    }
}
