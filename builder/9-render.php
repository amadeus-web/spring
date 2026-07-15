<?php
class renderSET extends builderBase {
	const default = 'implementation';

	private $method, $pageId;

	static function create($method = self:: default, $echo = BOOLYes, $pageId = null) {
		$r = new renderSET();
		$r->echo($echo);
		$r->method = $method;
		$r->pageId = $pageId != null ? $pageId : nodeValue();
		return $r;
	}

	function render($fileOrRaw, $settings = []) {
		$this->settings = array_merge($this->settings, $settings);
		$op = _renderImplementation($fileOrRaw, $this->settings);

		$noEcho = $this->settings[VAREcho] == BOOLDontEcho;
		if ($this->method == features::engage) {
			if (!$noEcho) showDebugging('renderSET.render', ['$noEcho for render implementation expected as: ' . bool_r(BOOLDontEcho)], true);
			features::ensureEngage();
			$settings = [];
			if ($this->settingIs(VARNoContentBoxes)) $settings[VARNoContentBoxes] = true;
			renderEngage($this->pageId, $op, BOOLYes, EmptyArray, $settings);
		}
		
		if ($noEcho) return $op;
	}

	function echo($yes = BOOLNo) {
		return $this->setValue(VAREcho, $yes);
	}

	function excerpt($yes = BOOLYes) {
		return $this->setValue(VARExcerpt, $yes);
	}

	function markdown($yes = BOOLYes) {
		return $this->setValue(VARMarkdown, $yes);
	}

	function noParas($yes = BOOLYes) {
		return $this->setValue(VARStripParagraphTag, $yes);
	}

	function noCB($yes = BOOLYes) {
		return $this->setValue(VARNoContentBoxes, $yes);
	}
}

function renderWith($fileOrRaw, renderSET $settingsObj, $settings = []) {
	$settingsObj->render($fileOrRaw, $settings);
}

function renderExcerpt($file, $link, $prefix = '', $echo = BOOLYes) {
	$prefix = $prefix ? renderMarkdown($prefix) : '';

	$meta = read_seo($file);

	$text = disk_file_exists($file) ? disk_file_get_contents($file) : $file;
	$hasMoreTag = contains($text, MORETAG);
	if (!$hasMoreTag && $meta && isset($meta['excerpt']))
		$raw = returnLine($meta['excerpt']);
	else
		$raw = renderSET::create(renderSET::default, BOOLNo)->markdown()->excerpt()->render($text);

	$raw .= '<hr class="m-2" /><div style="text-align: right;">';
	if ($meta && isset($meta['meta']['Date'])) $raw .= 'on ' . $meta['meta']['Date'] . '';

	$result = $prefix . _excludeFromGoogleSearch($raw)
		. BRTAG . '<a class="mt-1 read-more btn btn-success" href="' . $link . '">Read More&hellip;</a></div>';

	if (!$echo) return $result;
	echo $result;
}

DEFINE('GOOGLEOFF', '<!--googleoff: all-->'. NEWLINE);
DEFINE('GOOGLEON', '<!--googleon: all-->'. NEWLINES2);

function _excludeFromGoogleSearch($raw) {
	return GOOGLEOFF . $raw . NEWLINE . GOOGLEON;
}

function renderOnlyMarkdownOrRaw($raw, $wantsMD, $settings = []) {
	return $wantsMD ? renderSingleLineMarkdown($raw, $settings) : $raw; //so we can use inline in code
}

function renderMarkdown($raw, $settings = []) {
	$settings[VARMarkdown] = BOOLYes;
	return _renderImplementation($raw, $settings);
}

function returnLines($raw) {
	return renderMarkdown($raw, [VAREcho => BOOLDontEcho]);
}

function returnLinesNoParas($raw) {
	return renderSingleLineMarkdown($raw, [VAREcho => BOOLDontEcho, VARStripParagraphTag => BOOLYes]);
}

function returnLine($raw) {
	return renderSingleLineMarkdown($raw, [VAREcho => BOOLDontEcho]);
}

function renderSingleLineMarkdown($raw, $settings = []) {
	return renderMarkdown($raw, array_merge($settings, [VARStripParagraphTag => BOOLYes]));
}

function renderAny($file, $settings = []) {
	if (endsWith($file, '.php'))
		return disk_include_once($file);
	else
		return _renderImplementation($file, $settings);
}

//_ denotes its not to be called from outside - see flavours above + remove deprecated
function _renderImplementation($fileOrRaw, $settings) {
	$endsWithMd = BOOLNo;
	$raw = $fileOrRaw; $fileName = '[RAW]';
	$treatAsMarkdown = valueIfSet($settings, 'markdown');
	$echo = valueIfSet($settings, VAREcho, BOOLYes);
	$noReplaces = BOOLNo;

	if ($wasFile = isContentFile($fileOrRaw)) {
		$fileName = $fileOrRaw;

		if (NeverExecute && is_local()) {
			//checks if second call and dies
			if ($firstCall = variable($doneKey = 'done_' . $fileName))
				showDebugging('second call on: ' . $fileName, ['thisCall' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2), 'firstCall' => $firstCall], PleaseDie);
			variable($doneKey, debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2));
			//simulate 2nd call _renderImplementation($fileName, []);
		}

		$endsWithMd = endsWith($fileOrRaw, '.md');
		$raw = disk_file_get_contents($fileOrRaw);
		$noReplaces = contains($raw, NOREPLACES);
	}

	if (valueIfSet($settings, FIRSTSECTIONONLY)) {
		$raw = explode('---', $raw, 2)[0];
		if ($fan = variable(FULLACCESSNOTICE))
			$raw .= '---' . NEWLINE . $fan;
	}

	$excerpt = valueIfSet($settings, 'excerpt', BOOLNo);
	$no_processing = $noReplaces || valueIfSet($settings, 'raw', BOOLNo) || contains($raw, WANTSNOPROCESSING) || wants_md_in_parser($raw);
	if (!$noReplaces && contains($raw, WANTSNOPARATAGS)) $settings[VARStripParagraphTag] = BOOLYes;

	if ($excerpt) $raw = explode(MORETAG, $raw)[0];
	if ($excerpt && contains($raw, EXCERPTSTART)) $raw = explode(EXCERPTSTART, $raw)[1];

	if (function_exists('site_render_content')) $raw = site_render_content($raw);

	$replacesParams = isset($settings['replaces']) ? $settings['replaces'] : [];
	$plainReplaces = isset($settings['plainReplaces']) ? $settings['plainReplaces'] : [];
	$builtinReplaces = [
		'site-assets' => variable(assetKey(SITEASSETS)),
		'site-assets-images' => variable(assetKey(SITEASSETS)) . 'images/',
		'spring' => getSiteUrl(SITESPRING),
		'spring-assets' => variable(assetKey(COREASSETS)),
	];

	$raw = replaceItems($raw, $replacesParams, WRAPREPLACE);
	$raw = replaceItems($raw, $plainReplaces, NOWRAPREPLACE);
	$raw = replaceItems($raw, $builtinReplaces, HASHREPLACE);

	if ($svars = variable('siteReplaces')) $raw = replaceItems($raw, $svars, WRAPREPLACE, BOOLYes);
	if ($svars = variable('siteRawReplaces')) $raw = replaceItems($raw, $svars, NOWRAPREPLACE, BOOLYes);

	$autop = $raw != '' && contains($raw, WANTSAUTOPARA);
	$md = $raw != '' && ($raw[0] == '#' || startsWith($raw, WANTSMARKDOWN));
	$engageContent = BOOLNo;

	if ($rawVars = variable('rawReplaces'))
		$raw = replaceItems($raw, $rawVars, '%');

	if (contains($raw, $secureSeparator = '<!--secure-page-->')) {
		if (!function_exists('is_page_secure')) {
			$raw = '[UNABLE TO SHOW SECURE CONTENT]';
			showDebugging('secure page detected', ['message' => 'Unable to find a method to resolve', 'notes' => 'this is a technical error and you should contact the developer']);
		} else {
			$raw = explode($secureSeparator, $raw)[is_page_secure() ? 1 : 0];
		}
	}
	
	if ($no_processing) {
		$output = $raw;
	} else {
		$inProgress = '<!--render-processing-->';
		$engageSansCB = BOOLNo;
		if (wants_engage_until_eof($raw)) {
			$engageBits = explode(ENGAGESTART, $raw);
			$engageSansCB = contains($raw, ENGAGESANSCB);
			$raw = $engageBits[0];
			$engageContent = $engageBits[1];
		}

		if (is_engage($raw) && !contains($raw, $inProgress)) {
			features::ensureEngage();
			$settings[VARUseContentBox] = BOOLNo;
			$meta = $wasFile ? variable('meta_' . $fileName) : [];
			$no = variable(VARNoContentBoxes);
			variable(VARNoContentBoxes, $engageSansCB);

			if ($autop) $raw = wpautop($raw);
			$raw = runAllMacros($raw);
			$output = renderEngage(getPageName(), $raw . $inProgress, BOOLNo, $meta);
			variable(VARNoContentBoxes, $no);
		} else {
			$ai = contains($raw, FROM_GEMINI_AI);
			if ($ai) $raw = processAI($raw, 'gemini');

			$output = !$autop && ($md || $endsWithMd || $treatAsMarkdown) ? markdown($raw) : wpautop($raw);

			if ($ai || contains($raw, HAS_GEMINI_AI)) $output = adjustOutputOfAI($output, 'gemini');
		}
	}

	if (!$noReplaces) {
		$output = runAllMacros($output);
		$output = replaceHtml($output);
	}

	if (!$noReplaces && !isset($settings[VARDontPrepareLinks]))
		$output = prepareLinks($output); //if doing before markdown then this gets messed up

	if (!$noReplaces && valueIfSet($settings, VARStripParagraphTag))
		$output = strip_paragraph($output);

	if (contains($output, '%fileName%'))
		$output = replaceItems($output, ['%fileName%' => '<u>EDIT FILE:</u> ' .
			replaceItems($fileName, [SITEPATH => '', '//' => '/'])]);

	if (!$noReplaces && isset($settings[VARWrapInSection]))
		$output = TAGSECTION . NEWLINE . $output . NEWLINE . TAGSECTIONEND . NEWLINES2;

	if (valueIfSet($settings, VARUseContentBox, BOOLNo))
		$output = cbWrapAndReplaceHr($output);
	else
		$output = str_replace(HRTAG, cbCloseAndOpen('container'), $output);

	if (!$noReplaces && isset($settings['heading'])) $output = variableOr('custom-heading', h2($settings['heading'], 'amadeus-heading amadeus-icon', BOOLYes)) . NEWLINES2 . $output;

	if ($engageContent) {
		features::ensureEngage();
		$settings[VARUseContentBox] = BOOLNo;
		$meta = $wasFile ? read_seo($fileName) : [];
		$no = variable(VARNoContentBoxes);
		variable(VARNoContentBoxes, $engageSansCB);
		$output .= renderEngage(getPageName(), $engageContent . $inProgress . WANTSNOPARATAGS, BOOLNo, $meta);
		variable(VARNoContentBoxes, $no);
	}

	if ($wasFile)
		$output .= _txtInfo('File Rendered', $fileName);

	if (!$echo) return $output;
	echo $output;
}

function _txtInfo($msg, $info) {
	if (BOOLYes || !is_local()) return '';
	return textBoxWithCopyOnClick($msg, _makeSlashesConsistent($info), 'Link Copied');
}

function renderRichPage($sheetFile, $groupBy = 'section', $templateName = 'home') {
	variable('home', getSheet($sheetFile, $groupBy));
	$call = variable('theme_folder') . $templateName . '.php';
	disk_include_once($call);
}
