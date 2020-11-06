{**
 * plugins/generic/simpleStatistics/simpleStatisticsEdit.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 *}
<link rel="stylesheet" type="text/css" href="{$cssUrl|escape}">
<div class="item issue">
        <div class="sub_item">
                <div class="label">
			{translate key="plugins.generic.simpleStatistics.headline"}
                </div>
                <ul class="item" id="simpleStatistics_item">
                    <li class="simpleStatistics_views">
                        {translate key="article.abstract"} {translate key="plugins.generic.simpleStatistics.views"}: <div class="simpleStatistics_value">{$article->getViews()}</div> 
                    </li>
			{if $galleyCount > 0}
				{for $i=0 to $galleyCount - 1}
					<li class="simpleStatistics_downloads">
                                		{$galleyLabels[$i]} {translate key="plugins.generic.simpleStatistics.downloads"}: <div class="simpleStatistics_value">{$galleyDownloads[$i]}</div>
					</li>
				{/for}	
			{/if}
                </ul>
		{* <div class="simpleStatistics_downloads">Total Galley Downloads: <div class="simpleStatistics_value">{$galleyViewTotal}</div></div> *}
        </div>
</div>



