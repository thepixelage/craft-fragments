<?php

namespace thepixelage\fragments\controllers;

use craft\web\Controller;

class FragmentsController extends Controller
{
    public function actionIndex()
    {
        $this->renderTemplate('@fragments/fragments/_index.twig');
    }
}
