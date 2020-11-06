<?php

/**
 * @file plugins/generic/simpleStatistics/SupplementaryMetricsDAO.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SupplementaryMetricsDAO
 * @ingroup plugins_generic_simpleStatistics
 *
 * @brief Operation for retrieving Supplementary Galley Views
 */


class SupplementaryMetricsDAO extends DAO {

	function __construct() {
		parent::__construct();
	}

	function getSupplementaryGalleyView($id) {
		$result = $this->retrieve(
			'SELECT sum(metric) FROM metrics WHERE representation_id = ?', (int)$id
		); 

		$row = $result->GetRowAssoc(false);
		$returner = $row['sum(metric)'] ? $row['sum(metric)'] : 0 ;
		$result->Close();
		return $returner;
	}
}

