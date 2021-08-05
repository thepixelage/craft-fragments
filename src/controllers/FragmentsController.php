<?php

namespace thepixelage\fragments\controllers;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\web\Controller;
use thepixelage\fragments\elements\Fragment;
use thepixelage\fragments\Plugin;
use Throwable;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FragmentsController extends Controller
{
    public function actionIndex(): Response
    {
        return $this->renderTemplate('@fragments/fragments/_index.twig');
    }

    /**
     * @throws BadRequestHttpException
     */
    public function actionEdit(string $fragmentTypeHandle, ?int $fragmentId = null, ?Fragment $fragment = null): Response
    {
        $fragmentType = Plugin::getInstance()->fragmentTypes->getFragmentTypeByHandle($fragmentTypeHandle);

        if (!$fragment) {
            if ($fragmentId) {
                $fragment = Plugin::getInstance()->fragments->getFragmentById($fragmentId);
                if (!$fragment) {
                    throw new BadRequestHttpException("Invalid fragment ID: $fragmentId");
                }
            } else {
                $fragment = new Fragment();
                $fragment->fragmentTypeId = $fragmentType->id;
            }
        }

        return $this->renderTemplate('@fragments/fragments/_edit.twig', [
            'element' => $fragment,
            'fragmentType' => $fragmentType,
        ]);
    }

    /**
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public function actionSave(): ?Response
    {
        $fragmentId = $this->request->getBodyParam('sourceId');
        $fragmentTypeId = $this->request->getBodyParam('fragmentTypeId');
        $fragmentType = Plugin::getInstance()->fragmentTypes->getFragmentTypeById($fragmentTypeId);

        if ($fragmentId) {
            $fragment = Plugin::getInstance()->fragments->getFragmentById($fragmentId);
            if (!$fragment) {
                throw new BadRequestHttpException("Invalid fragment ID: $fragmentId");
            }
        } else {
            $fragment = new Fragment();
            $fragment->fragmentTypeId = $fragmentType->id;
        }

        $fragment->title = $this->request->getBodyParam('title', $fragment->title);
        $fragment->slug = $this->request->getBodyParam('slug', $fragment->slug);
        $fragment->enabled = (bool)$this->request->getBodyParam('enabled', $fragment->enabled);
        $fragment->setFieldValuesFromRequest($this->request->getParam('fieldsLocation', 'fields'));

        if (!Craft::$app->elements->saveElement($fragment)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['errors' => $fragmentType->getErrors()]);
            }

            $this->setFailFlash(Craft::t('fragments', "Couldn’t save fragment type."));

            Craft::$app->urlManager->setRouteParams([
                'fragmentType' => $fragmentType,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('fragments', "Fragment saved."));
        $this->redirectToPostedUrl($fragment);

        return null;
    }

    /**
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    public function actionDelete(): ?Response
    {
        $this->requirePostRequest();

        $fragmentId = $this->request->getRequiredBodyParam('sourceId');
        $fragment = Plugin::getInstance()->fragments->getFragmentById($fragmentId);

        if (!$fragment) {
            throw new NotFoundHttpException("Fragment not found");
        }

        if (!Craft::$app->getElements()->deleteElement($fragment)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            $this->setFailFlash(Craft::t('app', "Couldn’t delete category."));

            Craft::$app->getUrlManager()->setRouteParams([
                'fragment' => $fragment,
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        $this->setSuccessFlash(Craft::t('app', "Fragment deleted."));

        return $this->redirectToPostedUrl($fragment);
    }
}
