<?php

namespace thepixelage\fragments\controllers;

use craft\web\Controller;
use yii\web\Response;

class ZonesController extends Controller
{
    public function actionIndex(): Response
    {
        return $this->renderTemplate('@fragments/settings/zones/_index.twig', [
            'zones' => [],
        ]);
    }
}
