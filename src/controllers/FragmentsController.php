<?php

namespace thepixelage\fragments\controllers;

use Craft;
use craft\base\Element;
use craft\errors\ElementNotFoundException;
use craft\errors\SiteNotFoundException;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\models\Site;
use craft\web\Controller;
use thepixelage\fragments\db\Table;
use thepixelage\fragments\elements\Fragment;
use thepixelage\fragments\models\Zone;
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

        $fragmentTypes = [];
        $allFragmentTypes = Plugin::getInstance()->fragmentTypes->getAllFragmentTypes();
        $allZones = Plugin::getInstance()->zones->getAllZones();
        foreach ($allZones as $zone) {
            $allowedFragmentTypeHandles = $zone->settings['fragmentTypes'];
            $fragmentTypes['zone:' . $zone->uid] = array_values(array_filter($allFragmentTypes, function ($type) use ($allowedFragmentTypeHandles) {
                return ($allowedFragmentTypeHandles == '*' || in_array('type:' . $type['uid'], $allowedFragmentTypeHandles));
            }));
        }

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
     * @throws Exception
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
        } else {
            $site = Craft::$app->getSites()->getCurrentSite();
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

        if (!isset($fragment->settings['visibility'])) {
            $fragment->settings = [
                'visibility' => [
                    'ruletype' => '',
                    'rules' => [],
                ]
            ];
        }

        $this->enforceSitePermission($site);
        $this->enforceEditFragmentPermissions($fragment);

        if (
            !$fragment->id &&
            is_array($zone->settings['fragmentTypes']) &&
            !in_array('type:' . $fragmentType->uid, $zone->settings['fragmentTypes'])
        ) {
            throw new Exception(Craft::t('fragments', "{$fragmentType->name} fragments are not allowed in this zone."));
        }

        if (Craft::$app->getIsMultiSite()) {
            $siteIds = array_map(function ($site) {
                return $site['siteId'];
            }, $fragment->getSupportedSites());

            if ($fragment->enabled && $fragment->id) {
                $siteStatusesQuery = $fragment::find()
                    ->select(['elements_sites.siteId', 'elements_sites.enabled'])
                    ->id($fragment->id)
                    ->siteId($siteIds)
                    ->status(null)
                    ->asArray();
                $siteStatuses = array_map(fn($enabled) => (bool)$enabled, $siteStatusesQuery->pairs());
            } else {
                // If the element isn't saved yet, assume other sites will share its current status
                $defaultStatus = !$fragment->id && $fragment->enabled && $fragment->getEnabledForSite();
                $siteStatuses = array_combine($siteIds, array_map(fn() => $defaultStatus, $siteIds));
            }
        } else {
            /* @noinspection PhpUnhandledExceptionInspection */
            $siteIds = [Craft::$app->getSites()->getPrimarySite()->id];
            $siteStatuses = [];
        }

        $craft37 = version_compare(Craft::$app->getVersion(), '3.7', '>=');
        if ($craft37) {
            $sourceId = $fragment->getCanonicalId();
        } else {
            $sourceId = $fragment->getSourceId();
        }

        $settingsJs = Json::encode([
            'canEditMultipleSites' => true,
            'canSaveCanonical' => true,
            'canonicalId' => $sourceId,
            'elementType' => get_class($fragment),
            'enablePreview' => false,
            'enabledForSite' => $fragment->enabled && $fragment->getEnabledForSite(),
            'siteId' => $fragment->siteId,
            'siteStatuses' => $siteStatuses,
        ]);
        $js = <<<JS
new Craft.ElementEditor($('#main-form'), $settingsJs)
JS;
        $this->view->registerJs($js);

        return $this->renderTemplate('@fragments/fragments/_edit.twig', [
            'element' => $fragment,
            'zone' => $zone,
            'site' => $site,
            'fragmentType' => $fragmentType,
            'siteIds' => $siteIds,
            'canUpdateSource' => true,
            'visibilityRuleType' => $fragment->settings['visibility']['ruletype'],
            'visibilityRules' => $fragment->settings['visibility']['rules'],
            'isNew' => $fragment->id == null,
            'sourceId' => $sourceId,
            'sidebarHtml' => $fragment->getSidebarHtml(false),
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
        $this->requirePostRequest();

        $fragmentId = $this->request->getBodyParam('sourceId');
        $siteId = $this->request->getBodyParam('siteId');
        $zoneId = $this->request->getBodyParam('zoneId');
        $fragmentTypeId = $this->request->getBodyParam('fragmentTypeId');

        $zone = Plugin::getInstance()->zones->getZoneById($zoneId);
        $fragmentType = Plugin::getInstance()->fragmentTypes->getFragmentTypeById($fragmentTypeId);
        if ($siteId) {
            $site = Craft::$app->getSites()->getSiteById($siteId);
        } else {
            $site = Craft::$app->getSites()->getCurrentSite();
        }


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
            $fragment->settings = [
                'visibility' => []
            ];
        }

        $fragment->zoneId = $zone->id;
        $fragment->fragmentTypeId = $fragmentType->id;
        $fragment->siteId = $site->id;

        $fragment->setEntryCondition($this->request->getBodyParam('entryCondition'));
        $fragment->setUserCondition($this->request->getBodyParam('userCondition'));

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

        $this->enforceSitePermission($fragment->getSite());
        $this->enforceEditFragmentPermissions($fragment);

        if ($fragment->getEnabledForSite()) {
            $fragment->setScenario(Element::SCENARIO_LIVE);
        }

        if (!Craft::$app->elements->saveElement($fragment)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['errors' => $fragment->getErrors()]);
            }

            $this->setFailFlash(Craft::t('fragments', "Couldn’t save fragment."));

            Craft::$app->urlManager->setRouteParams([
                'fragment' => $fragment,
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

        $this->enforceSitePermission($site);

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

        $this->enforceEditFragmentPermissions($fragment);
        $this->enforceDeleteFragmentPermissions($fragment);

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
        /** @var Fragment $fragment */
        $fragment = Craft::$app->getElements()->getElementById($fragmentId, Fragment::class);

        if (!$fragment) {
            throw new NotFoundHttpException("Fragment not found");
        }

        $this->enforceDeleteFragmentPermissions($fragment);

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
     * @throws SiteNotFoundException
     * @throws ForbiddenHttpException
     */
    protected function editableSiteIds(Zone $zone): array
    {
        if (!Craft::$app->getIsMultiSite()) {
            return [Craft::$app->getSites()->getPrimarySite()->id];
        }

        // Only use the sites that the user has access to
        $zoneSiteIds = array_keys($zone->getSiteSettings());
        $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();
        $siteIds = array_merge(array_intersect($zoneSiteIds, $editableSiteIds));
        if (empty($siteIds)) {
            throw new ForbiddenHttpException('User not permitted to edit content in any sites supported by this zone');
        }

        return $siteIds;
    }

    /**
     * @throws ForbiddenHttpException
     */
    protected function enforceSitePermission(Site $site)
    {
        if (Craft::$app->getIsMultiSite()) {
            $this->requirePermission('editSite:' . $site->uid);
        }
    }

    /**
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     */
    protected function enforceEditFragmentPermissions(Fragment $fragment, bool $duplicate = false)
    {
        $permissionSuffix = ':' . $fragment->getZone()->uid;

        // Make sure the user is allowed to edit entries in this section
        $this->requirePermission('editFragments' . $permissionSuffix);

        // Is it a new entry?
        if (!$fragment->id || $duplicate) {
            // Make sure they have permission to create new fragments in this zone
            $this->requirePermission('createFragments' . $permissionSuffix);
        }
    }

    /**
     * @throws InvalidConfigException
     * @throws ForbiddenHttpException
     */
    protected function enforceDeleteFragmentPermissions(Fragment $fragment)
    {
        $userSession = Craft::$app->getUser()->id;
        $user = Craft::$app->users->getUserById($userSession->id);

        if (!$fragment->canDelete($user)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }

    /**
     * @throws ForbiddenHttpException
     */
    protected function enabledForSiteValue()
    {
        $enabledForSite = $this->request->getBodyParam('enabledForSite');
        if (is_array($enabledForSite)) {
            // Make sure they are allowed to edit all the posted site IDs
            $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();
            if (array_diff(array_keys($enabledForSite), $editableSiteIds)) {
                throw new ForbiddenHttpException('User not permitted to edit the statuses for all the submitted site IDs');
            }
        }

        return $enabledForSite;
    }


}
