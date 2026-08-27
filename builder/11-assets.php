<?php
DEFINE('SCRIPTTAG', NEWLINE . '	<script src="%s" type="text/javascript"></script>');

function scriptTag($url) {
	echo sprintf(SCRIPTTAG, $url);
}

DEFINE('CSSTAG', NEWLINE . '	<link href="%s" rel="stylesheet" type="text/css">');
function cssTag($url) {
	echo sprintf(CSSTAG, $url);
}

function getPageName($tailOnly = true) {
	if ($tailOnly) {
		$tail = explode('/', variableOr('all_page_parameters', nodeValue()));
		return end($tail);
	}
	//todo - alternatives??
}

DEFINE('TITLEONLY', 'title-only');
DEFINE('FORHEADING', 'for-heading');

function title($what = 'default') {
	if (hasVariable('custom-title')) {
		return variable('custom-title') . ' | ' . variable('name');
	}

	if ($what === TITLEONLY) return humanizeThis();

	$siteRoot = nodeIs(SITEHOME) || variable('under-construction');

	if ($siteRoot)
		return variable(VARName) . ' | ' . variable(VARByline);

	$forHeading = $what == FORHEADING;
	$result = $forHeading ? [humanizeThis()] : [];

	$exclude = ['print', 'embed'];
	$items = variableOr('page_parameters', []);
	if (!$forHeading) $items = array_reverse($items);

	foreach($items as $slug)
		if (!in_array($slug, $exclude)) $result[] = humanize($slug);

	if (!$forHeading) $result[] = humanizeThis();

	return implode($forHeading ? ' &mdash;&gt; ' : ' &lt; ', $result) . ($forHeading ? '' : ' | ' . variable('name'));
}

class assetManager {
	private const prefix = 'AssetOf_';

	//locations
	const section = 'Section';
	const leafNode = 'LeafNode';
	const node = 'Node';
	const network = 'Network';
	const site = 'Site';
	const core = 'Core';
	const theme = 'Theme';

	const folderSuffix = '_Folder';

	private static $hierarchy = [];

	public static function getHierarchy() {
		if (!count(self::$hierarchy))
			self::$hierarchy = [self::section, self::node, self::network, self::site, self::core];
		return self::$hierarchy;
	}

	static function set($key, $val) {
		variable(self::prefix . $key, $val);
	}

	static function get($key) {
		return variableOr(self::key($key), '');
	}

	static function has($key) {
		return hasVariable(self::key($key));
	}

	private static function key($key, $suffix = '') {
		return self::prefix . $key . $suffix;
	}

	static function meta($location = assetManager::site, $setValueOr = false) {
		$key = '__assetmanager_meta_' . $location; //cache it to prevent long manipulations/file reads below

		if (is_array($setValueOr)) {
			variable($key, $setValueOr);
			return;
		}

		//dont do early return as get could be for one item of array alone
		if (!($result = variable($key))) {
			$mainFol = variable(assetManager::key($location, assetManager::folderSuffix));
			$versionFile = $mainFol . '_version.txt';
			$version = disk_file_exists($versionFile) ? '?' . disk_file_get_contents($versionFile) : '';

			$result = ['location' => assetManager::get($location), 'version' => $version];

			//print_r($result); debug_print_backtrace();
			variable($key, $result);
		}

		if ($setValueOr == 'version')
			return $result['version'];
		//TODO: not yet implemented for url!

		return $result;
	}
}

DEFINE('COREASSETS', 'Core'); //TODO: HI: remove this alias for assetManager::core

//what == logo | icon
//which = site | node (falls back to site)
function getLogoOrIcon($what, $which = 'site') {
	$suffix = ($what == 'icon' ? '-icon' : '-logo') . '.png';

	$netWorkManaged = variable('network-manages-site-assets');
	$prefix = $netWorkManaged && DEFINED('SITENAME') ? SITENAME . '/' : '';
	$nodeIcon = $what == 'icon' && ($which == 'node' || is_array($which)) && variable('use-node-icons');
	$site = variable(VARDontOverwriteLogo) && !$nodeIcon;

	if (is_array($which) && !$site) {
		$inNode = true;
		$name = $which[VARNodeSafeName] . $suffix;
		$where = $which[assetManager::node];
	} else {
		$inNode = $which == 'node' && !$site && hasVariable(VARNodeSafeName) && DEFINED('NODEPATH');
		$name = variableOr($inNode ? VARNodeSafeName : VARSafeName, 'amadeusweb9') . $suffix; //TODO: update when major version changes

		$where = $inNode ? STARTATNODE : ($netWorkManaged ? STARTATNETWORK : STARTATSITE);
	}

	return _resolveFile($prefix . $name, $where, $inNode);
}

DEFINE('STARTATSECTION', 0);
DEFINE('STARTATNODE', 1);
DEFINE('STARTATNETWORK', 2);
DEFINE('STARTATSITE', 3);
DEFINE('STARTATCORE', 4);

function _resolveFile($file, $where = 0, $includeAssets = true) {
	if (is_integer($where)) {
		$hierarchy = assetManager::getHierarchy();
		while (true) { if (assetManager::has($hierarchy[$where])) break; else $where++; }
		$result = assetUrl($file, $hierarchy[$where]);
	} else {
		$result = $where . $file;
	}
	if (!$includeAssets) $result = str_replace('/assets/', '/', $result);
	return $result;
}

function assetUrl($file, $location) {
	if (startsWith($file, 'http') || startsWith($file, '//'))
		showDebugging('ASSETMANAGER: direct urls not supported in beta', $file, true, true);

	$meta = assetManager::meta($location);
	$match = $meta['location'];
	if (hasVariable(VARWildcardUrl))
		$match = str_replace(variable('assets-url'), variable(VARWildcardUrl), $match);

	return $match . $file . (contains($file, '.') ? $meta['version'] : '');
}

variables(['styles' => [], 'scripts' => []]);
//NODE: This breaks the offline mode...
variables(['3pStyles' => [], '3pScripts' => []]);

function add3pStyle($url) {
	$array = variable('3pStyles'); $array[] = $url; variable('3pStyles', $array);
}

function add3pScript($url) {
	$array = variable('3pScripts'); $array[] = $url; variable('3pScripts', $array);
}

function addStyle($name, $location = assetManager::site) {
	_addAssets($name, $location, 'styles');
}

function addScript($name, $location = assetManager::site) {
	_addAssets($name, $location, 'scripts');
}

function _addAssets($names, $location, $type) {
	$existing = variable($type);

	if (!is_array($names)) $names = [$names]; //magic - single or array. location can be defined only once
	foreach ($names as $name) {
		$key = concatSlugs([$type, $location, $name]);
		if (isset($existing[$key])) return;

		$existing[$key] = [ 'name' => $name, 'location' => $location ];
	}
	variable($type, $existing);
}

function styles_and_scripts() {
	foreach (variable('styles') as $item)
		cssTag(assetUrl($item['name'] . '.css', $item['location']));
	foreach (variable('scripts') as $item)
		scriptTag(assetUrl($item['name'] . '.js', $item['location']));

	foreach (variable('3pStyles') as $url) cssTag($url);
	foreach (variable('3pScripts') as $url) scriptTag($url);

	if (variable(VARMediakit))
		cssTag(getSiteUrl(SITESPRING) . 'assets/mediakit.php' . variable(VARMediakit));
}

class mediakit {
	const vars = 'mediakit-vars';
	const noLogo = ['name' => 'no-logo', 'type' => TYPEBOOLEAN];

	static function setVars($op) {
		$qs = valueIfSet($op, VARMediakit, '');
		if (startsWith($qs, '?')) $qs = substr($qs, 1); 
		$mk = [];
		parse_str($qs, $mk);
		variable(self::vars, $mk);
	}

	static function getVar(array $var) {
		return valueIfSetAndNotEmpty(variableOr(self::vars, []), $var['name'],
			valueIfSet($var, 'default'), valueIfSet($var,'type', TYPENOCHANGE));
	}
}