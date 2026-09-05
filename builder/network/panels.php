<?php
variable('network-panels', [
	'divFormat' => '<div class="p-3 col-md-%s col-sm-12 align">' . NEWLINE,
	'sectionFormat' => '	<section class="network-site%s my-3 text-center%s">' . NEWLINE,
]);

function renderSides(string $group) {
	$sheet = getSheet(NETWORKPATH . '/data/sites.tsv', 'group');
	$relativePrefix = domain::$current->folder;
	foreach ($sheet->group[$group] as $item) {
		$slug = $sheet->getValue($item, 'slug');
		if (!disk_is_dir(ALLSITESROOT . $relativePrefix . $slug)) continue;
		$site = getSiteInfo($relativePrefix . $slug);
		$class = ' site-' . $site->key . '-bgd';
		echo sprintf(subVariable('network-panels', 'sectionFormat'), $class . ' site-button', '');
		renderNetworkPanel($site, ['h3-class' => $class, 'side' => true]);
		echo '	<hr /></section>' . NEWLINES2;
	}
}

function renderCenter(string $logo) {
	$site = getSiteInfo(substr(NETWORKPATH, strlen(ALLSITESROOT)));
	
	renderNetworkPanel($site, ['in-center' => true, 'logo' =>  'assets/' . $logo]);
	echo '<div class="network-splash-name text-center">' . $site->byline . '</div>';
}

function renderNetworkPanel(site $site, array $settings) {
	$result = [];

	$url = $site->getUrl(_getUrlKeySansPreview());
	$logo = '<img src="' . $url . valueIfSet($settings, 'logo', $site->key . '-logo.png') . '" height="100" class="img-fluid img-max-400 content-network-logo" />';
	$result[] = '<div class="text-center">' . $logo . '</div>';

	if (valueIfSet($settings, 'in-center')) {
		echo implode(NEWLINE, $result);
		return;
	}

	$h3Class = valueIfSet($settings, 'h3-class', '');

	$link = '<h3 class="site-button text-center' . $h3Class . '"><a href="' . $url . '">' . $site->siteName . '</a></h3>';

	$result[] = $link;
	$result[] = '<small>' . $site->byline . '</small>';

	echo implode(NEWLINE, $result);
}

