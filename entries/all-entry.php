<?php
include_once __DIR__ . '/all-registry.php';

$name = isset($_GET['name']) ? $_GET['name'] : explode('.', $_SERVER['HTTP_HOST'])[0];
if ($name == 'localhost') $name = 'imran';

$rootPath = realpath(__DIR__ . '/../../') . '/';
$siteInfo = siteEntry::$all[ALLSITENAME];
$siteInfo::$currentName = $name;

if (count($siteInfo->subFolders)) {
	$fol = $rootPath . $siteInfo->folder;
	$found = false;
	foreach ($siteInfo->subFolders as $item) {
		if (is_dir($fol . $item . '/' . $name)) {
			$siteInfo->currentSubfolder = $item;
			$siteInfo->folder .= $item . '/';
			$found = true;
			break;
		}
	}
	if (!$found) die($name . ' not found in: ' . $fol . ' - checked: ' . implode(' / ', $siteInfo->currentSubfolder));
}

DEFINE('ALLSITEPATH', $rootPath . $siteInfo->folder . siteEntry::$currentName);

define('SITEINFO', $siteInfo);
define('SITEPATH', ALLSITEPATH);

include_once __DIR__ . '/../builder/1-entry.php';

function enhanceAllSite(&$vars, siteEntry $info) {
	if (!$info->currentSubfolder) return;
	setWildcardUrl($vars, $info->currentSubfolder, $info);
}

runFrameworkFile('site/begin');
