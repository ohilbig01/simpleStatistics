<?php
/**
 * @file plugins/generic/simpleStatistics/SimpleStatisticsPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SimpleStatisticsPlugin
 * @ingroup plugins_generic_simpleStatistics
 *
 * @brief SimpleStatistics plugin class
 */
 
import('lib.pkp.classes.plugins.GenericPlugin');

class SimpleStatisticsPlugin extends GenericPlugin {

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


        /**
         * Add stuff to article details page
         * @param $hookName string
         * @param $params array
         */
        function addStatistics($hookName, $params) {
                $templateMgr =& $params[1];
                $output =& $params[2];

                $request = $this->getRequest();
		$journal = $request->getContext();
                $publishedArticle = $templateMgr->get_template_vars('article');

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
			$galleyViewTotal = 0;
			foreach ($galleys as $galley) {
				// remove "(English)" etc. from label
				$label = preg_replace("/\(\w+\)/", "", $galley->getGalleyLabel($label));

				$file = $galley->getFile();
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
				$galleyViews[$i] = $views;
				$galleyViewTotal += $views;
			}
		}

		$templateMgr->assign('galleyViewTotal', $galleyViewTotal);
		$templateMgr->assign('galleyDownloads', $galleyViews);
		$templateMgr->assign('galleyLabels', $galleyLabels);
		$templateMgr->assign('galleyCount', count($galleyLabels));
		$templateMgr->assign('journalPath', $request->getJournal()->getPath());

                $output = $templateMgr->fetch($this->getTemplateResource('simpleStatistics.tpl'));

                return false;
        }
}
?>
