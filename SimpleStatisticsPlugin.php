<?php

/**
 * @file plugins/generic/simpleStatistics/SimpleStatisticsPlugin.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SimpleStatisticsPlugin
 * @ingroup plugins_generic_simpleStatistics
 *
 * @brief SimpleStatistics plugin class
 */
 
namespace APP\plugins\generic\simpleStatistics;

use PKP\plugins\GenericPlugin;
use APP\core\Services;
use PKP\plugins\Hook;
use APP\template\TemplateManager;
use PKP\db\DAORegistry;
use APP\facades\Repo;
use APP\statistics\StatisticsHelper;

// show views of supplementary files as a whole or separately
define('DISPLAY_COMBINED_SUPP_FILE_VIEWS', false);

// limit the length of the labels to be displayed
define('MAX_LABEL_LENGTH', 28);

class SimpleStatisticsPlugin extends GenericPlugin {
	public function register($category, $path, $mainContextId = null)
	{
		if (parent::register($category, $path, $mainContextId)) {
			if ($this->getEnabled($mainContextId)) {
				// add some statistics in article details
	                        Hook::add('Templates::Article::Details::SimpleStatistics', array($this, 'addStatistics'));
			}
			return true;
		}
		return false;
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	public function getDisplayName(): string
	{
		return __('plugins.generic.simpleStatistics.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	public function getDescription(): string
	{
		return __('plugins.generic.simpleStatistics.description');
	}


        /**
         * Add stuff to article details page
         * @param $hookName string
         * @param $params array
         */
	public function addStatistics(string $hookName, array $params): bool
	{
                $templateMgr =& $params[1];
                $output =& $params[2];
                $request = $this->getRequest();
		$journal = $request->getContext();
                $publishedArticle = $templateMgr->getTemplateVars('article');
		$publication = $templateMgr->getTemplateVars('publication');

 		// Add Style
                $cssUrl = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/simpleStatistics.css';
		$templateMgr->assign('cssUrl', $cssUrl);


		// Get metrics by type
		$submissionId = $publishedArticle->getId();
		$statsService = Services::get('publicationStats');
		$metricsByType = $statsService->getTotalsByType($submissionId, $request->getContext()->getId(), null, null);
		//error_log("metricsByType:" . var_export($metricsByType, true));


		// Galleys
                $galleyLabels = array();
                $galleyTypes = array();
		$galleys = Repo::galley()->getCollector()
                	->filterByPublicationIds(['publicationIds' => $publication->getId()])
                	->getMany();

		if ($galleys) {
			$genreDao = DAORegistry::getDAO('GenreDAO');
			$primaryGenres = $genreDao->getPrimaryByContextId($journal->getId())->toArray();
			$primaryGenreIds = array_map(function($genre) {
				return $genre->getId();
			}, $primaryGenres);
			$supplementaryGenres = $genreDao->getBySupplementaryAndContextId(true, $journal->getId())->toArray();
			$supplementaryGenreIds = array_map(function($genre) {
				return $genre->getId();
			}, $supplementaryGenres);

			// date range
			$dateRange = Repo::publication()->getDateBoundaries(
				Repo::publication()
					->getCollector()
					->filterByContextIds([$this->getRequest()->getContext()->getId()])
				);
			//$dateStart = StatisticsHelper::STATISTICS_EARLIEST_DATE;
			$dateStart = $dateRange->min_date_published;
			$dateEnd = date('Y-m-d', strtotime('yesterday'));


			$galleyViews = array();
			$galleyViewsTotal = 0;
			$suppViewsTotal = 0; 
			$suppFileIsMedia = false;
			foreach ($galleys as $galley) {
				// remove "(English)" etc. from label
				$label = preg_replace("/\(\w+\)/", "", $galley->getGalleyLabel($label));

				$file = $galley->getFile();
				if (!$file) continue;  // if galleys are remote... TODO
				$i = array_search($label, $galleyLabels);
				if ($i === false) {
					$i = count($galleyLabels);
					$galleyLabels[] = $label;
					// type: 'html' or 'media'
					$galleyTypes[] = $this->getLabelType($label);
				}

				$galleyViews = array_pad($galleyViews, count($galleyLabels), '');

				$fileId = $file->getId();
				if (in_array($file->getGenreId(), $primaryGenreIds)) {
					//error_log("primary fileId, GenreId, Label: $fileId, " . $file->getGenreId() . ", $label");

					$filters = [
						'dateStart' => $dateStart,
						'dateEnd' => $dateEnd,
						'contextIds' => [$journal->getId()],
						'submissionFileIds' => [$fileId],
						];
					$viewsByGalley = Services::get('publicationStats')
						->getQueryBuilder($filters)
						->getSum([])
						->value('metric');
				} elseif (in_array($file->getGenreId(), $supplementaryGenreIds)) {
					//error_log("supp fileId, GenreId, Label: $fileId, " . $file->getGenreId() . ", $label");

					$filters = [
						'dateStart' => $dateStart,
						'dateEnd' => $dateEnd,
						'contextIds' => [$journal->getId()],
						'submissionFileIds' => [$fileId],
						];
					$viewsByGalley = Services::get('publicationStats')
						->getQueryBuilder($filters)
						->getSum([])
						->value('metric');

					$suppViewsTotal += (int) $viewsByGalley;
					$labels[] = $label;
				}
				else  {
					error_log('simpleStatistics: unknown galley!');
				}
				$galleyViews[$i] = (int) $viewsByGalley;
				$galleyViewsTotal += (int) $viewsByGalley;
			}
		}

		//error_log('galleyViews:' . var_export($galleyViews, true));
		//error_log('galleyLabels:' . var_export($galleyLabels, true));
		//error_log('galleyTypes:' . var_export($galleyTypes, true));

		// TODO: string $suppFileLabels as alternative to label "Supplements"?
		if ($labels) $suppFileLabels = implode(', ', $labels); 

		$templateMgr->assign('displayCombinedSuppFileViews', DISPLAY_COMBINED_SUPP_FILE_VIEWS);
		$templateMgr->assign('abstractMetric', $metricsByType['abstract']);
		$templateMgr->assign('maxLabelLength', MAX_LABEL_LENGTH);

		if (DISPLAY_COMBINED_SUPP_FILE_VIEWS) {
			$templateMgr->assign('pdfMetric', $metricsByType['pdf']);
			$templateMgr->assign('htmlMetric', $metricsByType['html']);
			$templateMgr->assign('otherMetric', $metricsByType['other']);
			$templateMgr->assign('suppFileMetric', $metricsByType['suppFileViews']);
			$templateMgr->assign('suppFileLabels', $suppFileLabels);
			$templateMgr->assign('metricsTotal', $metricsByType['pdf'] + $metricsByType['html'] + $metricsByType['other'] + $metricsByType['suppFileViews']);
		} else {
			$templateMgr->assign('galleyViewsTotal', $galleyViewsTotal);
			$templateMgr->assign('galleyDownloads', $galleyViews);
			$templateMgr->assign('galleyLabels', $galleyLabels);
			$templateMgr->assign('galleyTypes', $galleyTypes);
			$templateMgr->assign('galleyCount', count($galleyLabels));
			$templateMgr->assign('journalPath', $request->getJournal()->getPath());
		}

                $output = $templateMgr->fetch($this->getTemplateResource('simpleStatistics.tpl'));

                return false;
        }

	private function getLabelType($label): string
	{
		$lowercaseInput = strtolower($label);

		if (strpos($lowercaseInput, 'htm') !== false || strpos($lowercaseInput, 'jats') !== false) {
			return 'html';
		} elseif (strpos($lowercaseInput, 'audio') !== false ||
			strpos($lowercaseInput, 'podcast') !== false ||
			strpos($lowercaseInput, 'mp3') !== false ||
			strpos($lowercaseInput, 'mp4') !== false ||
			strpos($lowercaseInput, 'film') !== false ||
			strpos($lowercaseInput, 'video') !== false ||
			strpos($lowercaseInput, 'movie') !== false) {
			return 'media';
		}
		return $lowercaseInput;
	}

}
