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

	$all = [];
	foreach (domain::$all as $item)
		$all[] = getSiteInfo($item->mainSites[0], $urlKey);

	$items = ['DAWN' => $all];

	foreach (domain::$all as $item) {
		$fol = $item->folder;
		if (!is_dir(ALLSITESROOT . $fol)) continue;

		$these = [];
		foreach ($wantsRender ? [] : $item->mainSites as $slug) {
			if (!is_dir(ALLSITESROOT . $slug)) continue;
			$item->prepareMainSite($slug);
			$these[] = getSiteInfo($slug, $urlKey, $item);
			$item->cleanSubfolder();
		}

		if (!$wantsRender)
			$items[] = MENUSEPARATOR;
		$items[$item->heading] = $these;

		foreach ($item->subFolders as $slug) {
			if (!is_dir(ALLSITESROOT . $fol . $slug)) continue;
			$item->currentSubfolder = $slug;
			$items[humanize($slug)] = setupNetwork($fol . $slug, $item);
			$item->cleanSubfolder();
		}
	}

	if ($wantsRender)
		$renderFn($items);
	else
		twoLevelMenu($items, NETWORKABBR);
}

function setupNetwork(string | bool $where = false, domain $domain = null) {
	$networkSites = [];

	$networkName = variable(VARNetwork);

	$items = [];
	$urlKey = _getUrlKeySansPreview();
	$returnArray = false;

	if ($where) {
		$fols = _skipNodeFiles(scandir(ALLSITESROOT . $where), ONLYFOLDERS);
		foreach ($fols as $fol) $items[] = $where . '/' . $fol;
		$returnArray = true;
	} else {
		if (!disk_file_exists($txt = NETWORKDEFINEDAT . $networkName . '.txt'))
			return;

		$items = textToList(disk_file_get_contents($txt));
	}

	foreach ($items as $item) {
		if (startsWith($item, '~')) {
			$networkSites[] = $item;
			continue;
		}

		$item = getSiteInfo($item, $urlKey, $domain);

		if ($item === false) continue;

		$networkSites[] = $item;
	}

	if ($returnArray) return $networkSites;
	variable('networkSites', $networkSites);
}
