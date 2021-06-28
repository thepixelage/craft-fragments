<?php

namespace thepixelage\fragments\controllers;

use Craft;
use craft\elements\Entry;
use craft\errors\MissingComponentException;
use craft\web\Controller;
use craft\web\View;
use Exception;
use thepixelage\fragments\models\FragmentType;
use thepixelage\fragments\Plugin;
use thepixelage\fragments\services\FragmentTypes;
use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class FragmentTypesController extends Controller
{
    /** @var FragmentTypes $fragmentTypesService */
    protected $fragmentTypesService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);

        $this->fragmentTypesService = Plugin::$plugin->fragmentTypes;
    }

    public function actionCreate(): Response
    {
        return $this->renderTemplate('fragments/settings/fragmenttypes/_edit', [], View::TEMPLATE_MODE_CP);
    }

    public function actionUpdate($id): Response
    {
        $fragmentType = $this->fragmentTypesService->getFragmentTypeById($id);
        $variables = [
            'fragmentType' => $fragmentType,
        ];

        return $this->renderTemplate('fragments/settings/fragmenttypes/_edit', $variables, View::TEMPLATE_MODE_CP);
    }

    /**
     * @throws BadRequestHttpException
     * @throws Exception
     */
    public function actionSaveFragmentType()
    {
        $this->requirePostRequest();

        $fragmentTypeId = $this->request->getBodyParam('id');
        if ($fragmentTypeId) {
            $fragmentType = $this->fragmentTypesService->getFragmentTypeById($fragmentTypeId);
            if (!$fragmentType) {
                throw new BadRequestHttpException('Fragment type not found');
            }
        } else {
            $fragmentType = new FragmentType();
        }

        $fragmentType->name = $this->request->getBodyParam('name');
        $fragmentType->handle = $this->request->getBodyParam('handle');

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Entry::class;
        $fragmentType->setFieldLayout($fieldLayout);

        if (!$this->fragmentTypesService->saveFragmentType($fragmentType)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save fragment type.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'fragmentType' => $fragmentType,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Fragment type saved.'));

        return $this->redirectToPostedUrl($fragmentType);
    }

    /**
     * @throws Throwable
     * @throws MissingComponentException
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    public function actionDeleteFragmentType(): Response
    {
        $this->requirePostRequest();

        $fragmentTypeId = $this->request->getBodyParam('fragmentTypeId') ?? $this->request->getRequiredBodyParam('id');
        $fragmentType = $this->fragmentTypesService->getFragmentTypeById($fragmentTypeId);

        if (!$fragmentType) {
            throw new BadRequestHttpException("Invalid zone ID: $fragmentTypeId");
        }

        if ($fieldLayout = $fragmentType->getFieldLayout()) {
            Craft::$app->getFields()->deleteLayout($fieldLayout);
        }

        $success = $this->fragmentTypesService->deleteFragmentType($fragmentType);

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => $success]);
        }

        if (!$success) {
            throw new ServerErrorHttpException("Unable to delete fragment type ID $fragmentTypeId");
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', '“{name}” deleted.', [
            'name' => $fragmentType->name,
        ]));

        return $this->redirectToPostedUrl();
    }
}
