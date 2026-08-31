<?php
variable('upiFormat', 'upi://pay?pa=%id%&amp;pn=%name%&amp;cu=INR');

function runAllMacros($html) {
	if (contains($html, '-snippet%'))
		$html = replaceSnippets($html);

	if (contains($html, '-coresnippet%'))
		$html = replaceSnippets($html, false, CORESNIPPET);

	if (contains($html, '-nodesnippet%'))
		$html = replaceSnippets($html, false, NODESNIPPET);

	if (contains($html, '-codesnippet%'))
		$html = replaceCodeSnippets($html);

	if (contains($html, '-corecodesnippet%'))
		$html = replaceCodeSnippets($html, false, CORESNIPPET);

	if (contains($html, '#upi') || contains($html, '%upi'))
		$html = replaceUPIs($html);

	if (contains($html, '[cta'))
		$html = processCtaShortcode($html);

	if (contains($html, '[youtube]'))
		$html = processYouTubeShortcode($html);

	if (contains($html, '[canva]'))
		$html = processCanvaShortcode($html);

	if (contains($html, '[google-'))
		$html = processGoogleShortcodes($html);

	if (contains($html, '[spotify]'))
		$html = processSpotifyShortcode($html);

	if (contains($html, '[suno]'))
		$html = processSunoShortcode($html);

	if (contains($html, '[instagram')) //post/reel
		$html = processInstagramShortcode($html);

	if (contains($html, '[audio]'))
		$html = processAudioShortcode($html);

	if (contains($html, '[video'))
		$html = processVideoShortcode($html);

	if (contains($html, '[pdf]'))
		$html = processPdfShortcode($html);

	if (contains($html, '[spacer]'))
		$html = processSpacerShortcode($html);

	if (contains($html, '[prompt'))
		$html = processPromptShortcode($html);

	if (contains($html, 'content-box]'))
		$html = processContentBoxShortcode($html);

	return $html;
}

DEFINE('CORESNIPPET', 'use-core');
DEFINE('NODESNIPPET', 'use-node');

function _getSnippetPath($fol, $type = 'plain') {
	if ($type == 'node') {
		$base = pathinfo(variable('file'), PATHINFO_DIRNAME) . '/data/';
		return $base . ($type != 'code' ? 'node-snippets/' : 'node-code-snippets/');
	}

	if ($fol && $fol != CORESNIPPET) return $fol;
	$result = false;
	if ($type == 'plain')
		$result = $fol == CORESNIPPET
			? (AMADEUSCORE . 'data/core-snippets/')
			: '/snippets/';
	else
		$result = $fol == CORESNIPPET
			? (AMADEUSCORE . 'data/core-code-snippets/')
			: ('code-snippets/');
	
	if ($fol == CORESNIPPET)
		return $result;

	return (defined('NETWORKDATA') ? NETWORKDATA : SITEPATH . '/data/') . $result;
}

function getSnippet($name, $fol = false) {
	$core = $fol == CORESNIPPET ? '-core' : '-';

	$fileFol = _getSnippetPath($fol); //plain

	$ext = disk_one_of_files_exist($fileFol . $name . '.', 'html, md');
	if (!$ext) return '';
	
	return replaceSnippets('%' . $name . $core . 'snippet%', [$name . '.' . $ext], $fol);
}

function replaceSnippets($html, $files = false, $fol = false) {
	$prefix = $fol == CORESNIPPET ? '-core' : ($fol == NODESNIPPET ? '-node' : '-');
	if (!$fol || $fol == CORESNIPPET || $fol == NODESNIPPET) $fol = _getSnippetPath($fol, $fol == NODESNIPPET ? 'node' : 'plain'); //plain	

	if (!$files) $files = disk_scandir($fol);

	foreach ($files as $file) {
		if ($file[0] == '.') continue;

		$fwoe = replaceItems($file, ['.md' => '', '.html' => '']);
		$ext = disk_one_of_files_exist($fol . $fwoe . '.', 'html, md');
		$key = '%' . $fwoe . $prefix . 'snippet%';

		if (!contains($html, $key)) continue;
		$op = renderMarkdown($fol . $file, [
			'echo' => false,
			'raw' => $ext == 'html',
		]);

		if ($ext == 'html')
			$op = replaceHtml($op);

		$html = str_replace($key, $op, $html);
	}

	return $html;
}

function getCodeSnippet($name, $fol = false) {
	$core = $fol == CORESNIPPET ? '-core' : '-';
	return replaceCodeSnippets('%' . $name . $core . 'codesnippet%', [$name . '.php'], $fol);
}

function replaceCodeSnippets($html, $files = false, $fol = false) {
	$core = ($fol == CORESNIPPET ? '-core' : '-');
	$fol = _getSnippetPath($fol, 'code');

	if (!$files) $files = disk_scandir($fol);

	foreach ($files as $file) {
		if ($file[0] == '.' || getExtension($file) != 'php') continue;

		$fwoe = replaceItems($file, ['.php' => '']);
		$key = '%' . $fwoe . $core . 'codesnippet%';
		if (!contains($html, $key)) continue;

		$html = str_replace($key, include $fol . $file, $html);
	}

	return $html;
}

function replaceUPIs($html) {
	$items = variableOr('upi', []);

	if (empty($items)) {
		if (is_local() || isset($_GET['debug'])) showDebugging('Amadeus Variable for **upi** missing', ['html' => $html]);
		return $html;
	}

	foreach ($items as $key => $item) {
		$replaces = ['id' => $item['id'], 'name' => urlencode($item['name'])];
		$html = replaceItems($html, [
			'#upi-' . $key => replaceVariables(variable('upiFormat'), $replaces),
			'%upi-' . $key . '%' => $item['id'],
			'%upi-' . $key . '-textbox%' => textBoxWithCopyOnClick('UPI ID for Indian Money Transfer (GPay / PhonePe etc):', $item['id'], $item['name'], 'fa fa-indian-rupee-sign'),
		]);
	}

	return $html;
}

function textBoxWithCopyOnClick($title, $value, $label = false, $icon = '') {
	$bits = [];
	$bits[] = '<div title="' . ($icon == '' ? $title : '') . '">' . ($label ? '<label class="d-block"><i class="' . $icon . '"></i> ' . $label . '<br>' : '');
	$bits[] = '<textarea class="autofit copyable" style="text-align: center; width: 100%" onfocus="this.select(); document.execCommand(\'copy\'); this.setSelectionRange(0, 0); if (!this.classList.contains(\'text-copied\')) { this.classList.add(\'text-copied\'); alert(\'Copied: \' + $(this).text()); }" rows="3" readonly>' . $value . '</textarea>';

	if ($label) $bits[] = '</label>' . TAGDIVEND;

	return implode(NEWLINE, $bits) . NEWLINES2;
}

function processCtaShortcode($html) {
	return replaceItems($html, [
		'[cta-link]' => '<a href="',
		'[cta-delimiter]' => bootstrapAndUX::toButtons(VARCTAONLY . 'BTNSITE">'),
		'[/cta-link]' => '</a>',
		'[cta]' => '<div class="video-container google-video"><iframe src="',
		'[/cta]' => VARCTAONLY . '" title="AW Spring Engage" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" allow="autoplay"></iframe></div>',
	]);
}

function processYouTubeShortcode($html) {
	return replaceItems($html, [
		'[youtube]' => '<iframe width="560" height="315" src="https://www.youtube.com/embed/',
		'[/youtube]' => '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>',
	]);
}

function processCanvaShortcode($html) {
	return replaceItems($html, [
		'[canva]' => '<div style="position: relative; width: 100%; height: 0; padding-top: 56.2500%; padding-bottom: 0; box-shadow: 0 2px 8px 0 rgba(63,69,81,0.16); margin-top: 1.6em; margin-bottom: 0.9em; overflow: hidden; border-radius: 8px; will-change: transform;">  <iframe loading="lazy" style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; border: none; padding: 0;margin: 0;" src="https://www.canva.com/design/',
		'[/canva]' => '/watch?embed" allowfullscreen="allowfullscreen" allow="fullscreen">  </iframe></div>',
	]);
}

function processGoogleShortcodes($html) {
	return replaceItems($html, [
		'[google-video]' => '<div class="video-container google-video"><iframe src="https://drive.google.com/file/d/',
		'[/google-video]' => '/preview" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" allow="autoplay"></iframe></div>',
	]);
}

function processSpotifyShortcode($html) {
	return replaceItems($html, [
		'[spotify]' => '<iframe data-testid="embed-iframe" style="border-radius:12px" src="https://open.spotify.com/embed/',
		'[/spotify]' => '?utm_source=generator" width="100%" height="152" frameBorder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>',
	]);
}

function processSunoShortcode($html) {
	return replaceItems($html, [
		'[suno]' => '<iframe src="https://suno.com/embed/',
		'[/suno]' => '" width="760" height="240" frameborder="0" allow="autoplay; encrypted-media; fullscreen" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
	]);
}

DEFINE('_IGPOSTSTART', '<blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/');
DEFINE('_IGREELSTART', '<blockquote class="instagram-media" style="max-width:540px; min-width:200px;" data-instgrm-permalink="https://www.instagram.com/p/');
DEFINE('_IGEND', '/" data-instgrm-version="14"></blockquote>');

DEFINE('IGPOSTFORMAT', _IGPOSTSTART . '%Instagram%' . _IGEND);
DEFINE('IGREELFORMAT', _IGREELSTART . '%Instagram%' . _IGEND);
DEFINE('IGEMBED', '<script async src="//www.instagram.com/embed.js"></script>');

function processInstagramShortcode($html) {
	$html .= NEWLINE . IGEMBED . NEWLINES2;
	return replaceItems($html, [
		'[instagram-post]' => _IGPOSTSTART,
		'[instagram-reel]' => _IGREELSTART,
		'[/instagram-post]' => _IGEND,
		'[/instagram-reel]' => _IGEND,
	]);
}

function processAudioShortcode($html) {
	return replaceItems($html, [
		'[audio]' => '<audio style="width: 100%" height="27" preload="none" controls><source src="',
		'[/audio]' => '" type="audio/mp3"></audio>',
	]);
}

function processVideoShortcode($html) {
	return replaceItems($html, [
		'[video-plain]' => '<video class="w-100" loop autoplay controls><source src="',
		'[/video-plain]' => '" type="video/mp4"></video>',
		'[video]' => '<div class="video-container text-center"><video class="w-100" loop autoplay controls><source src="',
		'[/video]' => '" type="video/mp4"></video></div>',
	]);
}

function processPdfShortcode($html) {
	return replaceItems($html, [
		'[pdf]' => '<iframe src="',
		'[/pdf]' => '" style="height: 80vh" frameborder="0"></iframe>',
	]);
}

DEFINE('SPACERSTART', '<div class="divider divider-center m-0 mt-3"><h1 class="h4">');
DEFINE('SPACEREND', '</h1></div>');

function printSpacer($heading) {
	echo SPACERSTART . $heading . SPACEREND;
}

function processSpacerShortcode($html) {
	if (variable(USETRUEHR))
		return replaceItems($html, [
			'[spacer]' => HRTAG . '<h1 class="h4">',
			'[/spacer]' => '</h1>',
			'[spacerpara]' => '</h1><p class="text-center">',
			'[spacerparaend]' => '</p>' . NEWLINE,
		]);

	return replaceItems($html, [
		'[spacer][no-content-box]' => SPACERSTART,
		'[spacer]' => cbCloseAndOpen('spacer container') . SPACERSTART,
		'[/spacer][plaincontainer]' => NEWLINE . SPACEREND . '</div><div class="container">',
		'[/spacer]' => NEWLINE . SPACEREND,
		'[spacerpara]' => '</h1></div><p class="text-center">',
		'[spacerparaend]' => '</p>' . NEWLINE,
	]);
}

//cant do before markdown is come
function processPromptShortcode($html) {
	return replaceItems($html, [
		'[prompt]' => cbCloseAndOpen('prompt'),
		'[/prompt]' => cbCloseAndOpen('response'),
		'[promptv2]' => cbCloseAndOpen('prompt geminiv2'),
		'[/promptv2]' => cbCloseAndOpen('response geminiv2'),
	]);
}

function processContentBoxShortcode($html) {
	return replaceItems($html, [
		'[content-box]' => contentBox('', '', true),
		'[/content-box]' => contentBox('end', '', true),
	]);
}

function printH1InDivider($text, $cb = true) {
	echo SPACERSTART . $text . NEWLINE . SPACEREND . ( $cb ? cbCloseAndOpen('container') : '');
}
