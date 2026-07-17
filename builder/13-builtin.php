<?php
DEFINE('CONTENTFILES', 'php, md, tsv, txt, html');
DEFINE('CONTENTFILEEXTENSIONS', explode(', ', CONTENTFILES));
DEFINE('ENGAGEFILES', 'md, tsv');
DEFINE('FILESWITHMETA', 'md, tsv, php');

DEFINE('ExcludedFolders', $xc = ['assets', 'data']);
$xc[] = 'home';
DEFINE('ExcludedFoldersAndHome', $xc);

function isContentFile($fileOrRaw) {
	foreach (CONTENTFILEEXTENSIONS as $extn)
		if (endsWith($fileOrRaw, '.' . $extn)) return true;
	return false;
}

function builtinOrRender($file, $type = false, $useHeading = true) {
	if (endsWith($file, '.php')) {
		renderAny($file);
		return;
	}

	if (variable('skip-heading-for-page'))
		$useHeading = false;

	//TODO: engage, blurbs, deck, tsv
	$raw = disk_file_exists($file) ? disk_file_get_contents($file) : '[RAW]';
	$embed = hasPageParameter('embed');
	$pageName = title(FORHEADING);

	//cannot use startsWith as edit in vs-code wouldnt work
	$detectedEngage = contains($raw, '|is-engage') || contains($raw, '<!--is-engage-->');
	if ($type != features::engage && $detectedEngage) $type = features::engage;

	if ($type == features::engage) {
		$md = !endsWith($file, '.tsv');

		features::ensureEngage();

		if ($detectedEngage)
			sectionId('special-form' . ($ix = variableOr('special-form', 1)), 'container');

		if ($md)
			renderEngage($pageName, $raw);
		else
			runEngageFromSheet(getPageName(), $file);

		if ($detectedEngage) {
			variableOr('special-form', ++$ix);
			sectionEnd();
		}

		pageMenu($file);
		return;
	}

	if (endsWith($file, '.md')) {
		sectionId('special-md', 'container');
		if (contains($raw, '<!--is-blurbs-->')) {
			features::runWithFile(features::blurbs, $file);
		} else if (contains($raw, '<!--is-deck-->')) {
			_renderDeck($file);
		} else if (contains($raw, '<!--is-family-tree-->')) {
			features::runWithFile(features::familyTree, $file);
		} else {
			$wantsNoCB = variable('skip-content-box-for-this-page') || contains(disk_file_get_contents($file), WANTSNOCONTENTBOX);
			$settings = ['use-content-box' => !$wantsNoCB];
			if ($useHeading) $settings['heading'] = $pageName;
			if (variable(FIRSTSECTIONONLY)) $settings[FIRSTSECTIONONLY] = true;
			renderAny($file, $settings);
		}

		sectionEnd();
		pageMenu($file);
		return;
	}

	if (endsWith($file, '.tsv')) {
		features::ensureTables();

		$meta = getSheet($file, false);
		$istwt = contains($raw, '|is-table-with-template');
		if ($meta && isset($meta->values['use-template']))
			$meta->values = array_merge($meta->values, getSheet(getTableTemplate($meta), false)->values);

		$noCB = $meta ? valueIfSet($meta->values, 'no-content-box') : false;

		if (valueIfSet($meta->values, 'no-title')) $title = false;
		else if (($mh = variable('menu-humanize')) && isset($mh[nodeValue()])) $title = $mh[nodeValue()];
		else $title = title(FORHEADING);
		if ($title) h2($title, 'amadeus-icon');

		$isDeck = contains($raw, '|is-deck');
		$notRendering = !hasPageParameter('embed') && !hasPageParameter('expanded');

		if ($noCB) sectionId('special-table', 'container'); else
		if (!$embed) sectionId('special-table', _getCBClassIfWanted('container' . ($isDeck && !$notRendering ? ' deck deck-from-sheet' : '')));

		if ($isDeck)
			renderSheetAsDeck($file, variableOr('all_page_parameters', nodeValue()) . '/');
		else if (startsWith($raw, '|is-rich-page'))
			renderRichPage($file);
		else if ($istwt || contains($raw, '|is-table'))
			add_table(pathinfo($file, PATHINFO_FILENAME), $file, valueIfSet($meta->values, 'head-columns', 'auto'), valueIfSet($meta->values, 'row-template', 'auto'), $meta->values);
		else
			showDebugging('unsupported tsv file - see line 1 for type definition', $file, true);

		if (!$embed) sectionEnd();
		pageMenu($file);
		return;
	}

	$siteTheme = variable('site-has-theme') || variable('skip-container-for-this-page');
	if (!$siteTheme) sectionId('file', _getCBClassIfWanted('container'));
	renderAny($file);
	if (!$siteTheme) sectionEnd();
}

function renderInPageDeck($section, $node, $title = false) {
	$deck = concatSlugs([variable('path'), $section, $node . '.md']);
	doToBuffering(1);
	if ($title) echo tagUX::h2Plain($title, cssUX::CenterContainer);
	_renderDeck($deck, pageUrl($node . '/'), true);
	$result = doToBuffering(2);
	doToBuffering(3);
	return $result;
}

function renderSheetAsDeck($deck, $link) {
	if (!hasPageParameter('embed') && !hasPageParameter('expanded')) {
		_renderDeck($deck);
		return;
	}

	$sheet = getSheet($deck, false);
	$op = [];
	foreach ($sheet->rows as $item) {
		$type = $item[$sheet->columns['type']];
		$text = $item[$sheet->columns['text']];

		if ($type == 'slide') {
			if (count($op)) { $op[] = ''; $op[] = '----'; $op[] = ''; }
			$op[] = '<input type="hidden" value="' . $text . '" />';
			$op[] = '';
		} else if ($type == 'heading') {
			$op[] = '## ' . $text;
			$op[] = '';
		} else if ($type == 'sub-heading') {
			$op[] = '### ' . $text;
			$op[] = '';
		} else if ($type == 'paragraph') {
			$op[] = $text;
			$op[] = '';
		} else if ($type == 'image') {
			$op[] = replaceHtml($text);
			$op[] = '';
		} else if ($type == 'style-file') {
			variable('style-file', replaceHtml($text));
		} else if ($type == 'print-config') {
			variable('print-config', $text);
		} else if ($type == 'item') {
			if (end($op) != '') $op[] = '';
			$op[] = '* ' . $text;
		}
	}

	variable('nodeLink', $link);
	$op = implode(NEWLINE, $op);
	_renderDeck($op);
}

function __parseDeck($deck) {
	$hrWith = NEWLINE . '</section><section>' . NEWLINE;
	$deck = renderMarkdown($deck, [ 'echo' => false, VARStripParagraphTag => true, 'plainReplaces' => ['---' => $hrWith]]);
	return $deck;
}

function _renderDeck($deck, $goesTo = false, $skipTitle = false) {
	if (hasPageParameter('embed')) {
		$deck = __parseDeck($deck);
		variable('deck', $deck);
		runModule('revealjs');
		return true;
	}

	$expanded = hasPageParameter('expanded');
	$url = $goesTo ? $goesTo : currentUrl();

	$embedUrl = $url .'?embed=1';

	sectionId('deck-toolbar', 'text-center');
	if (!$skipTitle) echo tagUX::h2Plain(title(FORHEADING), cssUX::CenterContainer);
	contentBox('deck', 'toolbar');
	echo 'PRESENTATION: ' . variable('nl');
	$links = [];

	//TODO: UI FIX: if (!$expanded) $links[] = '<a class="toggle-deck-fullscreen" href="javascript: $(\'.deck-container\').show();"><span class="text">maximize</span> ' . getIconSpan('expand', 'normal') . '</a>';
	if ($expanded) $links[] = makeLink('open deck page', $url, false);
	$links[] = makeLink('open deck fully', $embedUrl, false);
	$links[] = makeLink('print', $embedUrl . '&print=1', false); //TODO: wip - make this on demand
	$links[] = $expanded ? 'expanded deck below' : makeLink('open deck expanded', $url . '?expanded=1', false);
	//TODO: get this working and support multi decks
	//$(this).closest(\'.deck-toolbar\').next(\'.deck-container\').toggle();
	if (!$expanded) $links[] = makeLink('toggle deck below', 'javascript: $(\'.deck-container\').toggle();', false);

	echo implode(' &nbsp;&nbsp;&mdash;&nbsp;&nbsp; ' . NEWLINE, $links);
	contentBox('end');
	sectionEnd();

	if ($expanded) {
		$deck = __parseDeck($deck);
		$deck = cbWrapAndReplaceHr($deck, 'container');
		echo $deck;
	} else {
		echo sprintf('<section class="deck-container container">'
			. '<iframe src="%s&iframe=1"></iframe></section>', $embedUrl);
		addScript('presentation-toolbar', COREASSETS);
	}
}

function hasBuiltin() {
	$scaffold = variableOr('scaffold', []);
	//NOTE: sitemap always needed
	$always = false; //variable(VARLocal) && nodeIs('sitemap');
	if (!$always && !nodeIsOneOf($scaffold))
		return false;

	if (hasPageParameter('embed')) variable('embed', true);
	variable('scaffoldCode', nodeValue());
	return true;
}

function renderedBuiltin() {
	$code = variable('scaffoldCode');
	if (!$code) return false;

	runFrameworkFile('pages/' . $code);
	return true;
}

/* ai stuff - no parser.php anymore */
DEFINE('FROM_GEMINI_AI', '<!--exported-from-gemini-ai-->');
DEFINE('HAS_GEMINI_AI', '<!--has-gemini-ai-elements-->');
DEFINE('GEMINI_AI_MSG', 'This is a Chat with "Gemini AI"');
DEFINE('GEMINI_CLASSES', 'with-ai has-gemini-ai has-prompts');

function peekAtMainFile($file, $notMain = false) {
	$raw = disk_file_get_contents($file);
	$ai = contains($raw, FROM_GEMINI_AI);
	if ($notMain) return $ai ? ' ' . GEMINI_CLASSES : '';

	if ($ai) add_body_class(GEMINI_CLASSES);
}

function processAI($raw, $aiName) {
	$replaces = [
		FROM_GEMINI_AI => FROM_GEMINI_AI . SPACERSTART . GEMINI_AI_MSG . SPACEREND,
		'## Prompt:' => '[prompt]',
		'## Response:' => '[/prompt]' . NEWLINES2,
		'## User:' => '[promptv2]',
		'## Gemini:' => '[/promptv2]' . NEWLINES2,
		'***' => '<p>***</p>',
	];

	foreach (['https://www.geminiexporter.com/'] as $item)
		$replaces[$item] = $item . NOFOLLOWSUFFIX;


	if ($sr = variable('siteAIReplaces'))
		$raw = replaceItems($raw, $sr);

	return replaceItems($raw, $replaces);
}

DEFINE('GOOGLEIMAGES', 'https://lh3.googleusercontent.com');

function adjustOutputOfAI($raw, $aiName) {
	$noFollow = nofollowReplace(GOOGLEIMAGES);
	$raw = replaceItems($raw, $noFollow);

	if (!contains($raw, '<p>| ')) return $raw;

	features::ensureTables();
	_includeDatatables(false);
	_includeTableAssets();

	return replaceItems($raw, [
		'<p>| ' => '<table class="datatables table-sans-th table table-striped table-bordered"><thead></thead><tbody><tr><td>',
		'|</p> ' => '</tr></tbody></table>',
		' | ' => '</td><td>',
		'| ' => '<tr><td>',
		' |' => '</td></tr>',
		'</td></tr></p>' => '</tr></tbody></table>',
		'--- |' . NEWLINE => '-->' . NEWLINE,
		'| --- |' => '<!--',
		'<tr><td>---' => '<tr class="d-none"><td>',
	]);
}
