<?php

namespace thepixelage\fragments\controllers;

use craft\web\Controller;
use yii\web\Response;

class FragmentTypesController extends Controller
{
    public function actionIndex(): Response
    {
        return $this->renderTemplate('@fragments/settings/fragmenttypes/_index.twig', [
            'types' => [],
        ]);
    }
}
