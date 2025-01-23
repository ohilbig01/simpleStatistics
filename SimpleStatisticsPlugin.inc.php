<?php
/**
 * @file plugins/generic/simpleStatistics/SimpleStatisticsPlugin.inc.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SimpleStatisticsPlugin
 * @ingroup plugins_generic_simpleStatistics
 *
 * @brief SimpleStatistics plugin class
 */

define('MAX_LABEL_LENGTH', 28);
 
import('lib.pkp.classes.plugins.GenericPlugin');

class SimpleStatisticsPlugin extends GenericPlugin
{
	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {
			if ($this->getEnabled($mainContextId)) {
				// add some statistics in article details
	                        HookRegistry::register('Templates::Article::Details::SimpleStatistics', array($this, 'addStatistics'));
			}
			return true;
		}
		return false;
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.simpleStatistics.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.simpleStatistics.description');
	}



	/*
	 * see lib/pkp/api/v1/stats/publications/PKPStatsPublicationHandler.inc.php
	 */
	function getAllViews($request, $articleId)
	{
		// Get the earliest date of publication
		$contextId = $request->getContext()->getId();
		$dateRange = Services::get('publication')->getDateBoundaries(['contextIds' => $contextId]);
		$allowedParams['dateStart'] = $dateRange[0];
                //$allowedParams['dateEnd'] = date('Y-m-d', strtotime('yesterday'));
                $allowedParams['submissionIds'] = $articleId;
                $allowedParams['contextIds'] = $contextId;

                $statsService = Services::get('stats');

		// Get the abstract records
                $abstractRecords = $statsService->getRecords(array_merge($allowedParams, [
                        'assocTypes' => [ASSOC_TYPE_SUBMISSION],
			'submissionIds' => [$articleId],
                ]));
                $abstractViews = array_reduce($abstractRecords, [$statsService, 'sumMetric'], 0);

                // Get the galley totals for each file type (pdf, html, other)
                $galleyRecords = $statsService->getRecords(array_merge($allowedParams, [
                        'assocTypes' => [ASSOC_TYPE_SUBMISSION_FILE],
                ]));
                $galleyViews = array_reduce($galleyRecords, [$statsService, 'sumMetric'], 0);
                $pdfViews = array_reduce(array_filter($galleyRecords, [$statsService, 'filterRecordPdf']), [$statsService, 'sumMetric'], 0);
                $htmlViews = array_reduce(array_filter($galleyRecords, [$statsService, 'filterRecordHtml']), [$statsService, 'sumMetric'], 0);
                $otherViews = array_reduce(array_filter($galleyRecords, [$statsService, 'filterRecordOther']), [$statsService, 'sumMetric'], 0);

		return [
                        'abstractViews' => $abstractViews,
                        'galleyViews' => $galleyViews,
                        'pdfViews' => $pdfViews,
                        'htmlViews' => $htmlViews,
                        'otherViews' => $otherViews,
                ];
	}


        /**
         * Add stuff to article details page
         * @param $hookName string
         * @param $params array
         */
	function addStatistics($hookName, $params)
       	{
                $templateMgr =& $params[1];
                $output =& $params[2];

                $request = $this->getRequest();
		$journal = $request->getContext();
                $publishedArticle = $templateMgr->get_template_vars('article');

		$metrics = $this->getAllViews($request, $publishedArticle->getId());
		//error_log("SimpleStatistic metrics: " . var_export($metrics, true));

 		// Add Style
                $cssUrl = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/simpleStatistics.css';
		$templateMgr->assign('cssUrl', $cssUrl);

		// Count galley views
                $galleyLabels = array();
		$galleys = $publishedArticle->getGalleys();

		if ($galleys) {
			$this->import('SupplementaryMetricsDAO');
			$supplementaryMetrics = new SupplementaryMetricsDAO();

			$genreDao = DAORegistry::getDAO('GenreDAO');
			$primaryGenres = $genreDao->getPrimaryByContextId($journal->getId())->toArray();
			$primaryGenreIds = array_map(function($genre) {
				return $genre->getId();
			}, $primaryGenres);
			$supplementaryGenres = $genreDao->getBySupplementaryAndContextId(true, $journal->getId())->toArray();
			$supplementaryGenreIds = array_map(function($genre) {
				return $genre->getId();
			}, $supplementaryGenres);


			$galleyViews = array();
			foreach ($galleys as $galley) {
				// remove "(English)" etc. from label
				$label = preg_replace("/\(\w+\)/", "", $galley->getGalleyLabel($label));

				// next if there's no corresponding file (remote galley)
				if (!$file = $galley->getFile()) continue;

				$i = array_search($label, $galleyLabels);
				if ($i === false) {
					$i = count($galleyLabels);
					$galleyLabels[] = $label;
				}

				$galleyViews = array_pad($galleyViews, count($galleyLabels), '');

				if (in_array($file->getGenreId(), $primaryGenreIds)) {
					$views = $galley->getViews();
				} elseif (in_array($file->getGenreId(), $supplementaryGenreIds)) {
					$views = $supplementaryMetrics->getSupplementaryGalleyView($galley->getBestGalleyId());
				}
				else  {
					error_log('SimpleStatistics: unknown galley!');
				}
				// if it starts with 'pdf' or 'htm' (case insensitive)
				if (stripos($galleyLabels[$i], "pdf") === 0) {
					//$galleyViews[$i] = $views;
					$galleyViews[$i] = $metrics['pdfViews'];
				} elseif (stripos($galleyLabels[$i], "htm") === 0) {
					//$galleyViews[$i] = $views;
					$galleyViews[$i] = $metrics['htmlViews'];
				} else {
					$galleyViews[$i] = $views;
				}
			}
		}

		$templateMgr->assign('galleyDownloads', $galleyViews);
		$templateMgr->assign('abstractViews', $metrics['abstractViews']); // same as $article->getViews()
		$templateMgr->assign('galleyLabels', $galleyLabels);
		$templateMgr->assign('galleyCount', count($galleyLabels));
		$templateMgr->assign('journalPath', $request->getJournal()->getPath());
		$templateMgr->assign('maxLabelLength', MAX_LABEL_LENGTH);

                $output = $templateMgr->fetch($this->getTemplateResource('simpleStatistics.tpl'));

                return false;
        }
}
