<?php
addStyle(features::engage, COREASSETS);
addScript(features::engage, COREASSETS);

//TODO: Make a toggle-more when the md contains <!--more-->
function renderEngage($name, $raw, $echo = true, $meta = [], $settings = []) {
	//if (!$open) echo engageButton($name, $class);

	$salutation = variableOr('salutation', 'Dear ' . variable('name')) . ',';
	$addressee = '';
	$additionalCC = '';
	$whatsapp = variable('whatsapp-txt-start');

	if ($meta) {
		$mailSpacer = ',';
		if (isset($meta['Salutation'])) $salutation = $meta['Salutation'];
		if (isset($meta['Email To'])) $addressee = $meta['Email To'] . $mailSpacer;
		if (isset($meta['Email Cc'])) $additionalCC = $meta['Email Cc'] . $mailSpacer;
		if (isset($meta['WhatsApp To'])) $whatsapp = whatsapp_me($meta['WhatsApp To'], WAQS, false);
	}

	$systemIncluded = contains($addressee, VARSystemEmail) || contains($additionalCC, VARSystemEmail);
	$defaultCC = $systemIncluded ? '' : VARSystemEmail;

	$class = valueIfSet($settings, VARNoContentBoxes) ? features::engage : _getCBClassIfWanted(features::engage);
	$class = '" class="' . $class . '" ';

	$result = '	<div id="engage-' . urlize($name) . $class .
		//($open ? '' : 'style="display: none" ') .
		'data-to="' . ($email = $addressee . variable(VAREmail)) .
		'" data-cc="' . $additionalCC . $defaultCC .
		'" data-whatsapp="' . $whatsapp .
		'" data-site-name="' . variable('name') .
		'" data-salutation="' . $salutation . '">' . NEWLINE;

	$replaces = [];
	if (disk_file_exists($note = (AMADEUSDATA . 'engage-note.md'))) {
		$replaces[VAREngageNote] = '<div class="engage-note-hint d-none">' . returnLine($note) . '</div>';
		if (disk_file_exists($note2 = (AMADEUSDATA . 'engage-note-above.md')))
			$replaces[VAREngageNoteAbove] = returnLine($note2);
		$replaces[VAREmail] = $email;
		$replaces[VARWhatsapp] = getHtmlVariable(VARWhatsapp) . getHtmlVariable('enquiry');
	}

	$result .= renderMarkdown($raw, ['replaces' => $replaces, 'echo' => false]);

	$result .= getSnippet('engage-toolbox', CORESNIPPET);
	
	$result .= '</div>' . NEWLINE;
	if (!$echo) return $result;
	echo $result;
}

function runEngageFromSheet($pageName, $sheetName) {
	$sheet = getSheet($sheetName);
	$contentIndex = $sheet->columns['content'];
	$introIndex = $sheet->columns['section-intro'];
	$varsIndex = valueIfSet($sheet->columns, 'item_vars');
	$introduction = valueIfSet($sheet->values, 'introduction', 'Welcome to <b>' . humanize($pageName) . '</b> page of <b>' . variable('name') . '</b>.');

	//TODO: use faq by category like canvas' FAQ?
	//$items = []; //trying to make as pills in a later version
	$raw = ['<!--engage: SITE //engage--><!--render-processing-->', $introduction, ''];

	$firstSection = true;

	$customEngageNotes = variable('custom-engage-notes');
	if (!$customEngageNotes)
		$raw[] = '%engage-note-above%';

	foreach ($sheet->group as $name => $rows) {
		$raw[] = '## ' . $name;
		$raw[] = '';

		$firstRow = true;
		foreach ($rows as $row) {
			if ($firstRow) {
				$raw[] = $row[$introIndex];
				$raw[] = '';
				$firstRow = false;
			}
	
			$line = $row[$contentIndex];

			if ($varsIndex) {
				$vars = $row[$varsIndex];
				if ($vars) {
					$vbits = explode(', ', $vars);
					if (in_array('open', $vbits))
						$line .= '<!--open-->';
					if (in_array('large', $vbits))
						$line .= '<!--large-->';
				}
			}

			$raw[] = '* '  . $line;
			//$content[] = ;
		}
	
		$raw[] = '';
	}

	$raw[] = '';
	if (!$customEngageNotes)
		$raw[] = '%engage-note%';

	//$raw = print_r($items, 1); //$raw = renderPills($items); //todo: LATER!
	sectionId('engage-' . urlize($pageName));
	renderEngage($pageName, implode(NEWLINE, $raw));
	sectionEnd();
}
