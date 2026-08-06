<?php
include_once __DIR__ . '/all-registry.php';

$name = isset($_GET['name']) ? $_GET['name'] : explode('.', $_SERVER['HTTP_HOST'])[0];
if ($name == 'localhost') $name = 'imran';

$rootPath = realpath(__DIR__ . '/../../') . '/';
$siteInfo = ALLREGISTRY[ALLSITENAME];
$siteInfo['name'] = $name;

if (isset($siteInfo['subfolders'])) {
	$fol = $rootPath . $siteInfo['folder'];
	$found = false;
	foreach ($siteInfo['subfolders'] as $item) {
		if (is_dir($fol . $item . '/' . $name)) {
			$siteInfo['subfolder'] = $item;
			$siteInfo['folder'] .= $item . '/';
			$found = true;
			break;
		}
	}
	if (!$found) die($name . ' not found in: ' . $fol . ' - checked: ' . implode(' / ', $siteInfo['subfolder']));
}

DEFINE('ALLSITEPATH', $rootPath . $siteInfo['folder'] . $siteInfo['name']);

define('SITEINFO', $siteInfo);
define('SITEPATH', ALLSITEPATH);

include_once __DIR__ . '/../builder/1-entry.php';

function enhanceAllSite(&$vars, $info) {
	$subfolder = valueIfSet($info, 'subfolder', '');
	if (!$subfolder) return;
	setWildcardUrl($vars, $subfolder, $info);
}

runFrameworkFile('site/begin');
