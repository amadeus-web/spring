<?php
DEFINE('NETWORKDEFINEDAT', DEFINED('NETWORKPATH') ? NETWORKPATH . '/' : AMADEUSSITEDATA);
DEFINE('NETWORKABBR', 'DAWN');

setupNetwork();

function network_menu($renderFn = false) {
	$wantsRender = $renderFn != false;
	if (variable(VARDAWNMenu) === BOOLNoString) return;

	if (variable(VARNetwork) != BOOLNoString && !$wantsRender)
		flatMenu(variable('networkSites'), variable(VARNetwork));

	$urlKey = _getUrlKeySansPreview();

	domain::includeAll();

	$all = [];
	foreach (domain::$all as $item)
		$all[] = getSiteInfo($item->mainSites[0], $urlKey);

	$items = ['DAWN' => $all];

	foreach (domain::$all as $item) {
		$fol = $item->folder;
		if (!is_dir(ALLSITESROOT . $fol)) continue;

		$these = [];
		foreach ($wantsRender ? [] : $item->mainSites as $slug) {
			if (!is_dir(ALLSITESROOT . $fol . $slug)) continue;
			$these[] = getSiteInfo($fol . $slug, $urlKey);
		}

		if (!$wantsRender)
			$items[] = MENUSEPARATOR;
		$items[$item->heading] = $these;

		foreach ($item->subFolders as $slug) {
			if (!is_dir(ALLSITESROOT . $fol . $slug)) continue;
			$items[humanize($slug)] = setupNetwork($fol . $slug, $slug, $item);
		}
	}

	if ($wantsRender)
		$renderFn($items);
	else
		twoLevelMenu($items, NETWORKABBR);
}

function setupNetwork(sheet | null | string $sheet = null, $subfolder = false, domain | bool $site = false) {
	$networkSites = [];

	$networkName = variable(VARNetwork);

	$items = [];
	$urlKey = _getUrlKeySansPreview();
	$returnArray = false;

	if (is_string($sheet) && $sheet != null) {
		$fols = _skipNodeFiles(scandir(ALLSITESROOT . $sheet), ONLYFOLDERS);
		foreach ($fols as $fol) $items[] = $sheet . '/' . $fol;
		$returnArray = true;
	} else if ($sheet) {
		$items = $sheet->rows;
		$returnArray = true;
	} else {
		if (disk_file_exists($txt = NETWORKDEFINEDAT . $networkName . '.txt')) {
			$items = textToList(disk_file_get_contents($txt));
		} else {
			$sheet = getSheet(NETWORKDEFINEDAT . $networkName . '.tsv', false);
			$items = $sheet->rows;
		}
	}

	$hasNode = !is_string($sheet) && isset($sheet) && $sheet->hasColumn('node');
	foreach ($items as $key => $row) {
		$plain = is_string($row);
		$key = $plain ? $row : $sheet->getValue($row, 'key');
		if (startsWith($key, '~')) {
			$networkSites[] = $key;
			continue;
		}

		$item = getSiteInfo($plain ? $row : $sheet->getValue($row, 'path'), $urlKey);

		if ($item === false) continue;

		$bits = explode('/', $key);
		$leafKey = array_pop($bits);
		if ($subfolder && $site)
			setWildcardUrl($item, $subfolder, $site, $leafKey);

		if ($hasNode && $node = $sheet->getValue($row, 'node')) {
			$item[$urlKey] .= $node . '/';
			$item['key'] .= '/' . $key;
			$item['name'] = humanize($node) . ' &larr; ' . $item['name'];
		}

		$networkSites[] = $item;
	}

	if ($returnArray) return $networkSites;
	variable('networkSites', $networkSites);
}
