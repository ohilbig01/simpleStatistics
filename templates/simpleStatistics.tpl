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
		<div class="simpleStatistics_infotext">
			{translate key="plugins.generic.simpleStatistics.infotext"}
               	</div>
                <ul class="item" id="simpleStatistics_item">
                    <li class="simpleStatistics_views">
			<span class="simpleStatistics_label">{translate key="article.abstract"}</span><div class="simpleStatistics_value">{$article->getViews()}</div>
                    </li>
			{if $galleyCount > 0}
				{for $i=0 to $galleyCount - 1}
					{if $galleyLabels[$i]|stristr:"html" or $galleyLabels[$i]|stristr:"jats"}
						<li class="simpleStatistics_views">
					{elseif $galleyLabels[$i]|stristr:"audio"}
						<li class="simpleStatistics_media">
					{elseif $galleyLabels[$i]|stristr:"video"}
						<li class="simpleStatistics_media">
                                        {else}
						<li class="simpleStatistics_downloads">
                                        {/if}
							<span class="simpleStatistics_label">{$galleyLabels[$i]}</span><div class="simpleStatistics_value">{$galleyDownloads[$i]}</div>
						</li>
				{/for}	
			{/if}
                </ul>
		{* Link for further information *}
		{* <a class=simpleStatistics_link" href="/ojsdemo/modpub/metrics">{translate key="plugins.generic.simpleStatistics.linkText"}</a> *}
		{* <div class="simpleStatistics_downloads">Total Galley Downloads: <div class="simpleStatistics_value">{$galleyViewTotal}</div></div> *}
        </div>
</div>



