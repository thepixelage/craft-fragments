<?php

namespace thepixelage\fragments\controllers;

use Craft;
use craft\web\Controller;
use thepixelage\fragments\elements\Fragment;
use thepixelage\fragments\models\FragmentType;
use thepixelage\fragments\Plugin;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class FragmentTypesController extends Controller
{
    /**
     * @throws ForbiddenHttpException
     */
    public function actionIndex(): Response
    {
        $this->requireAdmin();

        return $this->renderTemplate('@fragments/settings/fragmenttypes/_index.twig', [
            'types' => Plugin::getInstance()->fragmentTypes->getAllFragmentTypes(),
        ]);
    }

    /**
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionEdit(?int $fragmentTypeId = null, ?FragmentType $fragmentType = null): Response
    {
        $this->requireAdmin();

        if (!$fragmentType) {
            if ($fragmentTypeId) {
                $fragmentType = Plugin::getInstance()->fragmentTypes->getFragmentTypeById($fragmentTypeId);
                if (!$fragmentType) {
                    throw new BadRequestHttpException("Invalid fragment type ID: $fragmentTypeId");
                }
            } else {
                $fragmentType = new FragmentType();
            }
        }

        return $this->renderTemplate('@fragments/settings/fragmenttypes/_edit.twig', [
            'fragmentType' => $fragmentType,
            'isNew' => ($fragmentType->id == null),
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
        $fragmentTypeId = $this->request->getBodyParam('fragmentTypeId');

        if ($fragmentTypeId) {
            $fragmentType = Plugin::getInstance()->fragmentTypes->getFragmentTypeById($fragmentTypeId);
            if (!$fragmentType) {
                throw new BadRequestHttpException("Invalid fragment type ID: $fragmentTypeId");
            }
        } else {
            $fragmentType = new FragmentType();
        }

        $fragmentType->name = $this->request->getBodyParam('name');
        $fragmentType->handle = $this->request->getBodyParam('handle');

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Fragment::class;
        $fragmentType->setFieldLayout($fieldLayout);

        /** @noinspection PhpUnhandledExceptionInspection */
        if (!Plugin::getInstance()->fragmentTypes->saveFragmentType($fragmentType)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['errors' => $fragmentType->getErrors()]);
            }

            $this->setFailFlash(Craft::t('fragments', "Couldn’t save fragment type."));

            Craft::$app->urlManager->setRouteParams([
                'fragmentType' => $fragmentType,
            ]);

            return null;
        }

        Craft::$app->fields->saveLayout($fieldLayout);

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        $this->setSuccessFlash(Craft::t('fragments', "Fragment type saved."));
        $this->redirectToPostedUrl($fragmentType);

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

        $fragmentTypesService = Plugin::getInstance()->fragmentTypes;
        $fragmentTypeId = $this->request->getRequiredBodyParam('id');
        $fragmentType = $fragmentTypesService->getFragmentTypeById($fragmentTypeId);
        $fragmentTypesService->deleteFragmentType($fragmentType);

        return $this->asJson(['success' => true]);
    }
}
