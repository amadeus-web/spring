<?php
DEFINE('NETWORKDEFINEDAT', DEFINED('NETWORKPATH') ? NETWORKPATH . '/' : AMADEUSSITEROOT . 'data/');
DEFINE('NETWORKNAME', '~JoyfulEarth\'s ');
DEFINE('NETWORKABBR', 'DAWN');

setupNetwork();

function network_menu() {
	if (variable(VARDAWNMenu) === BOOLNoString) return;

	if (variable(VARNetwork) != BOOLNoString)
		flatMenu(variable('networkSites'), variable(VARNetwork));

	$urlKey = _getUrlKeySansPreview();
	$dawnFols = ['joyfulearth', 'spring', 'us/imran', 'us/smithy'];
	$dawn = [];
	foreach ($dawnFols as $slug) {
		if (!is_dir(ALLSITESROOT . $slug)) continue;
		$dawn[] = getSiteInfo($slug, $urlKey);
	}

	$items = ['DAWN' => $dawn];

	$skipRest = false; $skipAfterFor = ['vidya'];

	$linuxPath = str_replace('\\', '/', SITEPATH);
	if (contains($linuxPath, '/for/')) {
		$slug = explode('/for/', $linuxPath)[1];
		$slug = explode('/', $slug)[0];
		$items[humanize($slug)] = setupNetwork('for/' . $slug);
		$skipRest = in_array($slug, $skipAfterFor);
	}

	disk_include_once(AMADEUSSITEROOT . '/entries/all-registry.php');
	$folders = $skipRest ? [] : ALLREGISTRY['public_html']['subfolders'];
	foreach ($folders as $slug) {
		if (!is_dir(ALLSITESROOT . $slug)) continue;
		$items[humanize($slug)] = setupNetwork($slug);
	}
	twoLevelMenu($items, NETWORKABBR);
}

function setupNetwork(sheet | null | string $sheet = null) {
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
