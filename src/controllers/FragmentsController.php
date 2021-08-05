<?php

namespace thepixelage\fragments\controllers;

use craft\web\Controller;
use thepixelage\fragments\elements\Fragment;
use thepixelage\fragments\Plugin;
use yii\web\Response;

class FragmentsController extends Controller
{
    public function actionIndex(): Response
    {
        return $this->renderTemplate('@fragments/fragments/_index.twig');
    }

    public function actionEdit(string $fragmentTypeHandle): Response
    {
        $fragmentType = Plugin::getInstance()->fragmentTypes->getFragmentTypeByHandle($fragmentTypeHandle);
        $fragment = new Fragment();
        $fragment->fragmentTypeId = $fragmentType->id;

        return $this->renderTemplate('@fragments/fragments/_edit.twig', [
            'element' => $fragment,
            'fragmentType' => $fragmentType,
        ]);
    }
}
