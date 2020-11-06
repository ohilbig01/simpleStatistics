<?php

/**
 * @defgroup plugins_generic_simpleStatistics SimpleStatistics Plugin
 */
 
/**
 * @file plugins/generic/simpleStatistics/index.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_simpleStatistics
 * @brief Wrapper for simpleStatistics plugin.
 *
 */
require_once('SimpleStatisticsPlugin.inc.php');

return new SimpleStatisticsPlugin();

?>
