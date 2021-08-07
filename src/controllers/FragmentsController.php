<?php

namespace thepixelage\fragments\controllers;

use Craft;
use craft\base\Element;
use craft\errors\ElementNotFoundException;
use craft\errors\SiteNotFoundException;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\web\Controller;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\elements\Fragment;
use thepixelage\fragments\Plugin;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FragmentsController extends Controller
{
    public function actionPluginIndex(): Response
    {
        if (Plugin::getInstance()->fragments->hasTypesAndZonesSetup()) {
            return $this->redirect('fragments/fragments');
        }

        return $this->redirect('fragments/settings');
    }

    public function actionIndex(): Response
    {
        if (!Plugin::getInstance()->fragments->hasTypesAndZonesSetup()) {
            return $this->redirect('fragments/settings');
        }

        $fragmentTypes = array_filter(
            Plugin::getInstance()->fragmentTypes->getAllFragmentTypes(),
            function ($type) {
                return [
                    'handle' => $type['handle'],
                    'id' => (int)$type['id'],
                    'name' => Craft::t('site', $type['name']),
                    'uid' => Craft::t('site', $type['uid']),
                ];
            }
        );

        $indexJsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@thepixelage/fragments/resources/js/FragmentIndex.js',
            true
        );

        $fragmentTypesJson = Json::encode($fragmentTypes, JSON_UNESCAPED_UNICODE);

        return $this->renderTemplate('@fragments/fragments/_index.twig', [
            'indexJsUrl' => $indexJsUrl,
            'fragmentTypesJson' => $fragmentTypesJson,
        ]);
    }

    /**
     * @throws BadRequestHttpException
     * @throws SiteNotFoundException
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     */
    public function actionEdit(string $zone, string $type, ?int $fragmentId = null, ?string $site = null, ?Fragment $fragment = null): Response
    {
        $zone = Plugin::getInstance()->zones->getZoneByHandle($zone);
        $fragmentType = Plugin::getInstance()->fragmentTypes->getFragmentTypeByHandle($type);

        if ($site !== null) {
            $siteHandle = $site;
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$site) {
                throw new NotFoundHttpException('Invalid site handle: ' . $siteHandle);
            }
        }

        if (!$fragment) {
            if ($fragmentId) {
                $fragment = Fragment::find()
                    ->id($fragmentId)
                    ->structureId($zone->structureId)
                    ->site($site)
                    ->anyStatus()
                    ->one();

                if (!$fragment) {
                    throw new BadRequestHttpException("Invalid fragment ID: $fragmentId");
                }
            } else {
                $fragment = new Fragment();
                $fragment->fragmentTypeId = $fragmentType->id;
                $fragment->zoneId = $zone->id;
                $fragment->siteId = $site->id;
            }
        }

        if (Craft::$app->getIsMultiSite()) {
            $siteIds = array_map(function ($site) {
                return $site['siteId'];
            }, $fragment->getSupportedSites());
        } else {
            /* @noinspection PhpUnhandledExceptionInspection */
            $siteIds = [Craft::$app->getSites()->getPrimarySite()->id];
        }

        return $this->renderTemplate('@fragments/fragments/_edit.twig', [
            'element' => $fragment,
            'zone' => $zone,
            'site' => $site,
            'fragmentType' => $fragmentType,
            'siteIds' => $siteIds,
            'canUpdateSource' => true,
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
        $siteId = $this->request->getBodyParam('siteId');
        $zoneId = $this->request->getBodyParam('zoneId');
        $fragmentTypeId = $this->request->getBodyParam('fragmentTypeId');

        $zone = Plugin::getInstance()->zones->getZoneById($zoneId);
        $fragmentType = Plugin::getInstance()->fragmentTypes->getFragmentTypeById($fragmentTypeId);
        $site = Craft::$app->getSites()->getSiteById($siteId);

        if ($fragmentId) {
            $fragment = Fragment::find()
                ->id($fragmentId)
                ->structureId($zone->structureId)
                ->site($site)
                ->anyStatus()
                ->one();

            if (!$fragment) {
                throw new BadRequestHttpException("Invalid fragment ID: $fragmentId");
            }
        } else {
            $fragment = new Fragment();
        }

        $fragment->zoneId = $zone->id;
        $fragment->fragmentTypeId = $fragmentType->id;

        $fragment->title = $this->request->getBodyParam('title', $fragment->title);
        $fragment->slug = $this->request->getBodyParam('slug', $fragment->slug);
        $fragment->enabled = (bool)$this->request->getBodyParam('enabled', $fragment->enabled);
        $fragment->setFieldValuesFromRequest($this->request->getParam('fieldsLocation', 'fields'));

        $enabledForSite = $this->enabledForSiteValue();
        if (is_array($enabledForSite)) {
            // Set the global status to true if it's enabled for *any* sites, or if already enabled.
            $fragment->enabled = in_array(true, $enabledForSite, false) || $fragment->enabled;
        } else {
            $fragment->enabled = (bool)$this->request->getBodyParam('enabled', $fragment->enabled);
        }
        $fragment->setEnabledForSite($enabledForSite ?? $fragment->getEnabledForSite());

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
     * @throws ElementNotFoundException
     * @throws Throwable
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     */
    public function actionDeleteForSite(): Response
    {
        $this->requirePostRequest();

        // Make sure they have permission to access this site
        $siteId = $this->request->getRequiredBodyParam('siteId');
        $sitesService = Craft::$app->getSites();
        $site = $sitesService->getSiteById($siteId);

        if (!$site) {
            throw new BadRequestHttpException("Invalid site ID: $siteId");
        }

//        $this->enforceSitePermission($site);

        // Get the entry in any but the to-be-deleted site -- preferably one the user has access to edit
        $fragmentId = $this->request->getBodyParam('sourceId');
        $editableSiteIds = $sitesService->getEditableSiteIds();

        $query = Fragment::find()
            ->id($fragmentId)
            ->siteId(['not', $siteId])
            ->preferSites($editableSiteIds)
            ->unique()
            ->anyStatus();

        $fragment = $query->one();
        if (!$fragment) {
            throw new NotFoundHttpException('Fragment not found');
        }

//        $this->enforceEditEntryPermissions($fragment);
//        $this->enforceDeleteEntryPermissions($fragment);

        // Delete the row in elements_sites
        Db::delete(Table::ELEMENTS_SITES, [
            'elementId' => $fragment->id,
            'siteId' => $siteId,
        ]);

        // Resave the fragment
        $fragment->setScenario(Element::SCENARIO_ESSENTIALS);
        Craft::$app->getElements()->saveElement($fragment);

        $this->setSuccessFlash(Craft::t('app', 'Fragment deleted for site.'));

        if (!in_array($fragment->siteId, $editableSiteIds)) {
            // That was the only site they had access to, so send them back to the Entries index
            return $this->redirect('entries');
        }

        // Redirect them to the same entry in the fetched site
        return $this->redirect($fragment->getCpEditUrl());
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
        $fragment = Craft::$app->getElements()->getElementById($fragmentId);

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

    /**
     * @throws ForbiddenHttpException
     */
    protected function enabledForSiteValue()
    {
        $enabledForSite = $this->request->getBodyParam('enabledForSite');
        if (is_array($enabledForSite)) {
            // Make sure they are allowed to edit all of the posted site IDs
            $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();
            if (array_diff(array_keys($enabledForSite), $editableSiteIds)) {
                throw new ForbiddenHttpException('User not permitted to edit the statuses for all the submitted site IDs');
            }
        }

        return $enabledForSite;
    }
}
