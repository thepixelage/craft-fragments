<?php

namespace thepixelage\fragments\controllers;

use Craft;
use craft\errors\SiteNotFoundException;
use craft\errors\UnsupportedSiteException;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use thepixelage\fragments\elements\Fragment;
use thepixelage\fragments\Plugin;
use thepixelage\fragments\services\Fragments;
use thepixelage\fragments\services\FragmentTypes;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FragmentsController extends Controller
{
    /** @var Fragments $fragmentsService */
    private $fragmentsService;

    /** @var FragmentTypes $fragmentTypesService */
    private $fragmentTypesService;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->fragmentsService = Plugin::$plugin->fragments;
        $this->fragmentTypesService = Plugin::$plugin->fragmentTypes;
    }

    public function actionSettingsIndex()
    {
        $this->redirect('fragments/settings/types');
    }

    /**
     * @throws ForbiddenHttpException
     */
    public function actionFragmentIndex(string $fragmentTypeHandle = null): Response
    {
        $fragmentTypes = $this->fragmentTypesService->getAllFragmentTypes();

        $this->view->registerTranslations('fragments', [
            'New fragment type',
        ]);

        return $this->renderTemplate('fragments/fragments/_index', [
            'fragmentTypeHandle' => $fragmentTypeHandle,
            'fragmentTypes' => $fragmentTypes,
        ]);
    }


    /**
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     * @throws SiteNotFoundException
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function actionEditFragment(string $fragmentTypeHandle, int $fragmentId = null, string $siteHandle = null, Fragment $fragment = null)
    {
        $variables = [
            'fragmentTypeHandle' => $fragmentTypeHandle,
            'fragmentId' => $fragmentId,
            'fragment' => $fragment,
        ];

        if ($siteHandle !== null) {
            $variables['site'] = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$variables['site']) {
                throw new NotFoundHttpException('Invalid site handle: ' . $siteHandle);
            }
        }

        $this->_prepEditFragmentVariables($variables);

        /* @var Site $site */
        $site = $variables['site'];
        /* @var Fragment $fragment */
        $fragment = $variables['fragment'];

        $this->_enforceEditFragmentPermissions($fragment);

        // Other variables
        // ---------------------------------------------------------------------

        // Body class
        $variables['bodyClass'] = 'edit-fragment site--' . $site->handle;

        // Page title
        if ($fragment->id === null) {
            $variables['title'] = Craft::t('app', 'Create a new fragment');
        } else {
            $variables['docTitle'] = $variables['title'] = trim($fragment->title) ?: Craft::t('app', 'Edit Fragment');
        }

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('app', 'Fragments'),
                'url' => UrlHelper::url('fragments/fragments'),
            ],
            [
                'label' => Craft::t('site', $variables['fragmentType']->name),
                'url' => UrlHelper::url('fragments/fragments/' . $variables['fragmentType']->handle),
            ],
        ];



        /*
        $variables['showPreviewBtn'] = false;

        // Enable Live Preview?
        if (!$this->request->isMobileBrowser(true) && Craft::$app->getCategories()->isGroupTemplateValid($variables['fragmentType'], $fragment->siteId)) {
            $this->getView()->registerJs('Craft.LivePreview.init(' . Json::encode([
                    'fields' => '#fields > .flex-fields > .field',
                    'extraFields' => '#settings',
                    'previewUrl' => $fragment->getUrl(),
                    'previewAction' => Craft::$app->getSecurity()->hashData('fragments/fragments/preview-category'),
                    'previewParams' => [
                        'groupId' => $variables['fragmentType']->id,
                        'categoryId' => $fragment->id,
                        'siteId' => $fragment->siteId,
                    ],
                ]) . ');');

            if (!Craft::$app->getConfig()->getGeneral()->headlessMode) {
                $variables['showPreviewBtn'] = true;
            }

            // Should we show the Share button too?
            if ($fragment->id !== null) {
                // If the category is enabled, use its main URL as its share URL.
                if ($fragment->getStatus() === Element::STATUS_ENABLED) {
                    $variables['shareUrl'] = $fragment->getUrl();
                } else {
                    $variables['shareUrl'] = UrlHelper::actionUrl('categories/share-category', [
                        'fragmentId' => $fragment->id,
                        'siteId' => $fragment->siteId,
                    ], null, false);
                }
            }
        }
        */

        // Set the base CP edit URL
        $variables['baseCpEditUrl'] = "fragments/fragments/{$variables['fragmentType']->handle}/{id}-{slug}";

        // Set the "Continue Editing" URL
        $siteSegment = Craft::$app->getIsMultiSite() && Craft::$app->getSites()->getCurrentSite()->id != $site->id ? "/{$site->handle}" : '';
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'] . $siteSegment;

        // Set the "Save and add another" URL
        $variables['nextCategoryUrl'] = "fragments/fragments/{$variables['fragmentType']->handle}/new$siteSegment?parentId={parent.id}#";

        // Render the template!
        return $this->renderTemplate('fragments/fragments/_edit', $variables);
    }

    /**
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionSaveFragment(bool $duplicate = false)
    {
        $this->requirePostRequest();

        $fragment = $this->_getFragmentModel();

        $this->_populateFragmentModel($fragment);

        if (!Craft::$app->getElements()->saveElement($fragment)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $fragment->getErrors(),
                ]);
            }

            $this->setFailFlash(Craft::t('app', 'Couldn’t save fragment.'));

            // Send the category back to the template
//            Craft::$app->getUrlManager()->setRouteParams([
//                $fragmentVariable => $fragment,
//            ]);

            return null;
        }




        $fragment->typeId = $this->request->getBodyParam('typeId', $fragment->typeId);
        $fragment->slug = $this->request->getBodyParam('slug', $fragment->slug);
        if (($postDate = $this->request->getBodyParam('postDate')) !== null) {
            $fragment->postDate = DateTimeHelper::toDateTime($postDate) ?: null;
        }
        if (($expiryDate = $this->request->getBodyParam('expiryDate')) !== null) {
            $fragment->expiryDate = DateTimeHelper::toDateTime($expiryDate) ?: null;
        }

        $fragment->title = $this->request->getBodyParam('title', $fragment->title);

        $fragment->fieldLayoutId = null;

        try {
            $success = $this->fragmentsService->saveFragment($fragment);
        } catch (UnsupportedSiteException $e) {
            $fragment->addError('siteId', $e->getMessage());
            $success = false;
        }



    }

    /**
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     */
    private function _getFragmentModel(): Fragment
    {
        $fragmentId = $this->request->getBodyParam('draftId') ?? $this->request->getBodyParam('sourceId') ?? $this->request->getBodyParam('entryId');

        if ($fragmentId) {
            $fragment = $this->fragmentsService->getFragmentById($fragmentId, 1);

            if (!$fragment) {
                throw new NotFoundHttpException('Fragment not found');
            }
        } else {
            $fragment = new Fragment();
            $fragment->typeId = $this->request->getRequiredBodyParam('typeId');
        }

        return $fragment;
    }

    private function _populateFragmentModel(Fragment $fragment)
    {

    }

    /**
     * @throws SiteNotFoundException
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     */
    private function _prepEditFragmentVariables(array &$variables)
    {
        // Get the fragment type
        // ---------------------------------------------------------------------

        if (!empty($variables['fragmentTypeHandle'])) {
            $variables['fragmentType'] = $this->fragmentTypesService->getFragmentTypeByHandle($variables['fragmentTypeHandle']);
        }

        if (empty($variables['fragmentType'])) {
            throw new NotFoundHttpException('Fragment type not found');
        }

        // Get the site
        // ---------------------------------------------------------------------

        if (Craft::$app->getIsMultiSite()) {
            // Only use the sites that the user has access to
            $variables['siteIds'] = Craft::$app->getSites()->getEditableSiteIds();
        } else {
            /* @noinspection PhpUnhandledExceptionInspection */
            $variables['siteIds'] = [Craft::$app->getSites()->getPrimarySite()->id];
        }

        if (!$variables['siteIds']) {
            throw new ForbiddenHttpException('User not permitted to edit content in any sites');
        }

        if (empty($variables['site'])) {
            /* @noinspection PhpUnhandledExceptionInspection */
            $variables['site'] = Craft::$app->getSites()->getCurrentSite();

            if (!in_array($variables['site']->id, $variables['siteIds'], false)) {
                $variables['site'] = Craft::$app->getSites()->getSiteById($variables['siteIds'][0]);
            }

            $site = $variables['site'];
        } else {
            // Make sure they were requesting a valid site
            /* @var Site $site */
            $site = $variables['site'];
            if (!in_array($site->id, $variables['siteIds'], false)) {
                throw new ForbiddenHttpException('User not permitted to edit content in this site');
            }
        }

        // Get the category
        // ---------------------------------------------------------------------

        if (empty($variables['fragment'])) {
            if (!empty($variables['fragmentId'])) {
                $variables['fragment'] = $this->fragmentsService->getFragmentById($variables['fragmentId'], $site->id);

                if (!$variables['fragment']) {
                    throw new NotFoundHttpException('Fragment not found');
                }
            } else {
                $variables['fragment'] = new Fragment();
                $variables['fragment']->fragmentTypeId = $variables['fragmentType']->id;
                $variables['fragment']->enabled = true;
                $variables['fragment']->siteId = $site->id;
            }
        }

        // Prep the form tabs & content
        $form = $variables['fragmentType']->getFieldLayout()->createForm($variables['fragment']);
        $variables['tabs'] = $form->getTabMenu();
        $variables['fieldsHtml'] = $form->render();
    }

    /**
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     */
    private function _enforceEditFragmentPermissions(Fragment $fragment)
    {
        if (Craft::$app->getIsMultiSite()) {
            // Make sure they have access to this site
            $this->requirePermission('editSite:' . $fragment->getSite()->uid);
        }
    }
}
