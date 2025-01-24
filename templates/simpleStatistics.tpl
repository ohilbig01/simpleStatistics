{**
 * plugins/generic/simpleStatistics/simpleStatisticsEdit.tpl
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2003-2023 John Willinsky
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
				<span class="simpleStatistics_label">{translate key="article.abstract"}</span><div class="simpleStatistics_value">{$abstractMetric}</div>
			</li>
			{if $displayCombinedSuppFileViews}
				{if $pdfMetric}
					<li class="simpleStatistics_downloads">
						<span class="simpleStatistics_label">PDF</span><div class="simpleStatistics_value">{$pdfMetric}</div>
					</li>
				{/if}
				{if $htmlMetric}
					<li class="simpleStatistics_downloads">
						<span class="simpleStatistics_label">HTML</span><div class="simpleStatistics_value">{$htmlMetric}</div>
					</li>
				{/if}
				{if $otherMetric}
					<li class="simpleStatistics_downloads">
						<span class="simpleStatistics_label">Other</span><div class="simpleStatistics_value">{$otherMetric}</div>
					</li>
				{/if}
				{if $suppFileMetric}
					<li class="simpleStatistics_supplements">
						<span class="simpleStatistics_label">{*$suppFileLabels*}Supplements</span><div class="simpleStatistics_value">{$suppFileMetric}</div>
					</li>
				{/if}
			{else}
				{if $galleyCount > 0}
					{for $i=0 to $galleyCount - 1}
						{if $galleyTypes[$i] eq "html"}
							<li class="simpleStatistics_views">
						{elseif $galleyTypes[$i] eq "media"}
							<li class="simpleStatistics_media">
						{else}
							<li class="simpleStatistics_downloads">
						{/if}
								<span class="simpleStatistics_label">{$galleyLabels[$i]|truncate:$maxLabelLength:"...":true}</span><div class="simpleStatistics_value">{$galleyDownloads[$i]}</div>
						</li>
					{/for}	

					{*	
					<li class="simpleStatistics_downloads" style="padding-top: 10px;">&nbsp;&nbsp;Total Galley Downloads<div class="simpleStatistics_value">{$galleyViewsTotal}</div></li>
					*}	
				{/if}
			{/if}
                </ul>
		{* Link for further information *}
		<a class="simpleStatistics_link" href="{url journal=$journalPath}/metrics">{translate key="plugins.generic.simpleStatistics.linkText"}</a>
        </div>
</div>
