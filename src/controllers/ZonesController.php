<?php

namespace thepixelage\fragments\controllers;

use Craft;
use craft\web\Controller;
use thepixelage\fragments\models\Zone;
use thepixelage\fragments\Plugin;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class ZonesController extends Controller
{
    public function actionIndex(): Response
    {
        return $this->renderTemplate('@fragments/settings/zones/_index.twig', [
            'zones' => Plugin::getInstance()->zones->getAllZones(),
        ]);
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionEdit(?int $zoneId = null, ?Zone $zone = null): Response
    {
        if (!$zone) {
            if ($zoneId) {
                $zone = Plugin::getInstance()->zones->getZoneById($zoneId);
                if (!$zone) {
                    throw new BadRequestHttpException("Invalid zone ID: $zoneId");
                }
            } else {
                $zone = new Zone();
            }
        }

        return $this->renderTemplate('@fragments/settings/zones/_edit.twig', [
            'zone' => $zone,
            'isNew' => ($zone->id == null),
        ]);
    }

    /**
     * @throws NotSupportedException
     * @throws InvalidConfigException
     * @throws ErrorException
     * @throws Exception
     * @throws ServerErrorHttpException
     * @throws BadRequestHttpException
     */
    public function actionSave(): ?Response
    {
        $zoneId = $this->request->getBodyParam('zoneId');

        if ($zoneId) {
            $zone = Plugin::getInstance()->zones->getZoneById($zoneId);
            if (!$zone) {
                throw new BadRequestHttpException("Invalid zone ID: $zoneId");
            }
        } else {
            $zone = new Zone();
        }

        $zone->name = $this->request->getBodyParam('name');
        $zone->handle = $this->request->getBodyParam('handle');

        /** @noinspection PhpUnhandledExceptionInspection */
        if (!Plugin::getInstance()->zones->saveZone($zone)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['errors' => $zone->getErrors()]);
            }

            $this->setFailFlash(Craft::t('fragments', "Couldn’t save zone."));

            Craft::$app->urlManager->setRouteParams([
                'zone' => $zone,
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        $this->setSuccessFlash(Craft::t('fragments', "Zone saved."));
        $this->redirectToPostedUrl($zone);

        return null;
    }

    /**
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin();

        $zonesService = Plugin::getInstance()->zones;
        $zoneId = $this->request->getRequiredBodyParam('id');
        $zone = $zonesService->getZoneById($zoneId);
        $zonesService->deleteZone($zone);

        return $this->asJson(['success' => true]);
    }
}
