<?php

namespace thepixelage\fragments\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use thepixelage\fragments\elements\Fragment;
use thepixelage\fragments\models\FragmentType;
use thepixelage\fragments\Plugin;
use thepixelage\fragments\services\FragmentTypes;
use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FragmentTypesController extends Controller
{
    /**
     * @throws ForbiddenHttpException
     */
    public function actionIndex(): Response
    {
        $this->requireAdmin();

        /** @var FragmentTypes $fragmentTypesService */
        $fragmentTypesService = Plugin::$plugin->fragmentTypes;
        $types = $fragmentTypesService->getAllFragmentTypes();

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

        return $this->renderTemplate('fragments/settings/types/_index', [
            'fragmentTypes' => $types,
            'crumbs' => $crumbs,
        ]);
    }


    /**
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionEdit(int $typeId = null, FragmentType $fragmentType = null): Response
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
                'label' => Craft::t('app', 'Fragment Types'),
                'url' => UrlHelper::url('fragments/settings/types'),
            ],
        ];

        $variables['brandNewType'] = false;

        if ($typeId !== null) {
            if ($fragmentType === null) {
                /** @var FragmentTypes $fragmentTypesService */
                $fragmentTypesService = Plugin::$plugin->fragmentTypes;
                $fragmentType = $fragmentTypesService->getFragmentTypeById($typeId);

                if (!$fragmentType) {
                    throw new NotFoundHttpException('Fragment type not found');
                }
            }

            $variables['title'] = trim($fragmentType->name) ?: Craft::t('app', 'Edit Fragment Type');
        } else {
            if ($fragmentType === null) {
                $fragmentType = new FragmentType();
                $variables['brandNewType'] = true;
            }

            $variables['title'] = Craft::t('app', 'Create a new fragment type');
        }

        $variables['fragmentTypeId'] = $typeId;
        $variables['fragmentType'] = $fragmentType;

        return $this->renderTemplate('fragments/settings/types/_edit', $variables);
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

        /** @var FragmentTypes $fragmentTypesService */
        $fragmentTypesService = Plugin::$plugin->fragmentTypes;
        $typeId = $this->request->getBodyParam('typeId');

        if ($typeId) {
            $type = $fragmentTypesService->getFragmentTypeById($typeId);
            if (!$type) {
                throw new BadRequestHttpException("Invalid fragment type ID: $typeId");
            }
        } else {
            $type = new FragmentType();
        }

        $type->name = $this->request->getBodyParam('name');
        $type->handle = $this->request->getBodyParam('handle');

        // Group the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Fragment::class;
        $type->setFieldLayout($fieldLayout);

        // Save it
        if (!$fragmentTypesService->saveFragmentType($type)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save the fragment type.'));

            // Send the fragment type back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'fragmentType' => $type,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Fragment type saved.'));
        return $this->redirectToPostedUrl($type);
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

        /** @var FragmentTypes $fragmentTypesService */
        $fragmentTypesService = Plugin::$plugin->fragmentTypes;

        $typeId = $this->request->getRequiredBodyParam('id');
        $fragmentType = $fragmentTypesService->getFragmentTypeById($typeId);

        if ($fieldLayout = $fragmentType->getFieldLayout()) {
            Craft::$app->getFields()->deleteLayout($fieldLayout);
        }

        $fragmentTypesService->deleteFragmentType($fragmentType);

        return $this->asJson(['success' => true]);
    }
}
