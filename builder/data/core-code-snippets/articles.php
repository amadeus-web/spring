<?php
$sheetName = nodeIs(SITEHOME) ? 'articles' : relatedDataFile('articles');

if (!sheetExists($sheetName)) return h2('No articles found.', 'text-danger', true) . '<p>Please add articles in the "' . $sheetName . '" file.</p>';

$sheet = getSheet($sheetName, false);

$op = ['</section><div class="container"><div class="portfolio row grid-container articles-codesnippet">' . NEWLINE];
$format = '<section class="portfolio-item col-lg-4 col-md-6 col-xs-12 mb-4 %moreClasses%"><div class="content-box">%title%
<br />%excerpt%</div></section>';

foreach ($sheet->rows as $item) {
	$site = $sheet->hasColumn('site') ? $sheet->getValue($item, 'site') : '';
	$section = $sheet->hasColumn('section') ? $sheet->getValue($item, 'section') : false;
	$node = $sheet->hasColumn('node') ? $sheet->getValue($item, 'node') : false;
	$path = $sheet->getValue($item, 'path');

	$relPath = str_replace('/home', '', $path);
	if ($relPath == 'home') $relPath = $section;
	$url = replaceHtml(DEFINED('NETWORKPATH') && $site ? getSiteKey($site) : '%url%');

	$link = $url . $relPath . '/';

	$base = $site && DEFINED('NETWORKPATH')
		? NETWORKPATH . '/' . $site . '/'
		: ($section ? SITEPATH . '/' : NODEPATH . '/');

	$file = $base
		. ($section ? $section : $node) . '/'
		. $path
		. $sheet->getValue($item, 'extension');

	$title = $sheet->getValue($item, 'title');
	$moreClasses = peekAtMainFile($file, true);

	$itm = replaceItems($format, [
		'moreClasses' => $moreClasses,
		'title' => getLink('#' . $sheet->getValue($item, 'sno') . NBSP . $title, $link, 'btn d-block btn-outline-info'),
		'excerpt' => renderExcerpt($file, $link, '', false),
	], '%');

	$op[] = $itm;
}

$op[] = '<section></div></div>' . NEWLINES2;

return implode(NEWLINES2, $op);
