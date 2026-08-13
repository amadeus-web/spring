<?php
header("Content-type: text/css");
include_once '../builder/0b-varnames.php';
include_once '../builder/4-array.php';
DEFINE('NEWLINE', "\r\n");
DEFINE('NEWLINES2', NEWLINE . NEWLINE);

if ($raw = valueIfSetAndNotEmpty($_GET, 'raw')) { echo $raw; return; }

$vars = $palette = $fonts = $_GET;

$moreVars = [''];
if ($menuColor = valueIfSetAndNotEmpty($palette, 'sticky-menu'))
	$moreVars[] = '--amadeus-sticky-menu: #' . $menuColor . ';';
if ($themeColor = valueIfSetAndNotEmpty($palette, 'themecolor'))
	$moreVars[] = '--cnvs-themecolor: #' . $themeColor . ';';

$contentFont = valueIfSetAndNotEmpty($fonts, 'content-font');
$cursive = valueIfSetAndNotEmpty($fonts, 'cursive');
$menu = valueIfSetAndNotEmpty($fonts, 'menu');

if ($contentFont || $cursive || $menu) {
	$nonEmpty = [];
	if ($contentFont) $nonEmpty[] = $contentFont;
	if ($cursive) $nonEmpty[] = $cursive;
	if ($menu) $nonEmpty[] = $menu;

	$unique = implode('&family=', array_unique($nonEmpty));
	echo '@import url(\'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $unique) . '&display=swap\');' . NEWLINES2;
}

if ($menu) $moreVars[] = '--cnvs-primary-menu-font: "' . $menu . '", serif;';
if ($cursive) $moreVars[] = '--amadeus-cursive-font: "' . $cursive . '", serif;';
if ($contentFont) $moreVars[] = '--amadeus-content-font: "' . $contentFont . '", serif;';

//TODO: rewrite with all as optional!
$op = ':root {
	--cnvs-header-bg: %header%;
		--cnvs-header-sticky-bg: %sticky-header%;
	--cnvs-footer-bg: %footer%;
	--cnvs-link-color: %link%;
	--amadeus-heading-bgd: %heading%;
	--cnvs-content-padding: 15px;
	--cnvs-mfp-iframe-max-width: 90%;
	--amadeus-after-content-bgd: %paler%;%MOREROOTVARS%
}' . NEWLINES2;

function _color($palette, $key, $default) {
	$val = valueIfSetAndNotEmpty($palette, $key, $default);
	if (is_bool($val)) return $val;
	return $val == 'no' ? 'transparent' : '#' . $val;
}

$content = _color($palette, 'content', 'e9f2ff');

echo replaceItems($op, [
	'header' => _color($palette, 'header', 'no'),
	'sticky-header' => _color($palette, 'sticky-header', 'fff'),
	'footer' => _color($palette, 'footer', '999'),
	'body' => _color($palette, 'body', 'bee6f9'),
	'link' => _color($palette, 'link', '30B1D4'),
	'heading' => _color($palette, 'heading', '86cbffb2'),
	'paler' => _color($palette, 'paler', 'ddd'),
	'MOREROOTVARS' => implode(NEWLINE . '	', $moreVars),
], '%');

if (valueIfSetAndNotEmpty($palette, 'dont-round-logo', false, TYPEBOOLEAN))
	echo '.img-logo { border-radius: 0px!important; }' . NEWLINES2;

if ($node = _color($palette, VARNode, false))
	echo '#page-menu-wrap { background-color: ' . $node . ' }' . NEWLINES2;

if ($content)
	echo '#content, .also-content { background-color: ' . $content . '!important; }' . NEWLINES2;
if ($contentFont)
	echo '#content, #content h1, #content h2, #content h3 { font-family: "' . urldecode($contentFont) . '", sans-serif; }' . NEWLINES2;

if ($cursive) echo '.cursive:not(.plain-font) { font-family: "' . $cursive . '", serif; }' . NEWLINES2;

if (valueIfSet($vars, 'slim')) echo '.header-wrap-clone { height: 54px; }' . NEWLINES2;

if ($menu) {
	$menuSize = valueIfSetAndNotEmpty($fonts, 'menu-size');
	$menuSize = $menuSize ? '--cnvs-primary-menu-font-size: ' . $menuSize . '; --cnvs-primary-menu-submenu-font-size: ' . $menuSize . '; ' : '';
	echo '#header { --cnvs-primary-menu-font: "' . $menu . '", serif; --cnvs-primary-menu-submenu-font:"' . $menu . '", serif; ' . $menuSize . '}' . NEWLINES2;

	$menuHeight = valueIfSetAndNotEmpty($fonts, 'menu-height');
	if ($menuHeight)
		echo '#header .menu-link {  line-height: ' . $menuHeight . '!important; }' . NEWLINES2;
}
