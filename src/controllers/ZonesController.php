<?php

namespace thepixelage\fragments\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\web\Controller;
use craft\web\View;
use Exception;
use thepixelage\fragments\models\Zone;
use thepixelage\fragments\Plugin;
use thepixelage\fragments\services\Zones;
use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class ZonesController extends Controller
{
    /** @var Zones $zonesService */
    protected $zonesService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);

        $this->zonesService = Plugin::$plugin->zones;
    }

    public function actionCreate(): Response
    {
        return $this->renderTemplate('fragments/settings/zones/_edit', [], View::TEMPLATE_MODE_CP);
    }

    public function actionUpdate($id): Response
    {
        $zone = $this->zonesService->getZoneById($id);
        $variables = [
            'zone' => $zone,
        ];

        return $this->renderTemplate('fragments/settings/zones/_edit', $variables, View::TEMPLATE_MODE_CP);
    }

    /**
     * @throws BadRequestHttpException
     * @throws Exception
     */
    public function actionSaveZone()
    {
        $this->requirePostRequest();

        $zoneId = $this->request->getBodyParam('id');
        if ($zoneId) {
            $zone = $this->zonesService->getZoneById($zoneId);
            if (!$zone) {
                throw new BadRequestHttpException('Zone not found');
            }
        } else {
            $zone = new Zone();
        }

        $zone->name = $this->request->getBodyParam('name');
        $zone->handle = $this->request->getBodyParam('handle');

        // Did it save?
        if (!$this->zonesService->saveZone($zone)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save zone.'));

            // Send the group back to the template
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
     * @throws MissingComponentException
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    public function actionDeleteZone(): Response
    {
        $this->requirePostRequest();

        $zoneId = $this->request->getBodyParam('zoneId') ?? $this->request->getRequiredBodyParam('id');
        $zone = $this->zonesService->getZoneById($zoneId);

        if (!$zone) {
            throw new BadRequestHttpException("Invalid zone ID: $zoneId");
        }

        $success = $this->zonesService->deleteZone($zone);

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => $success]);
        }

        if (!$success) {
            throw new ServerErrorHttpException("Unable to delete zone ID $zoneId");
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', '“{name}” deleted.', [
            'name' => $zone->name,
        ]));

        return $this->redirectToPostedUrl();
    }
}
