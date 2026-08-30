<?php
function showSite($allItems) {
	echo tagUX::tagStart(tagUX::Div, cssUX::CenterContainer);
	$urlKey = _getUrlKeySansPreview();
	$slim = getQueryParameter('slim');
	foreach ($allItems as $menu => $items) {
		if ($items == MENUSEPARATOR) continue;
		if ($slim) tagUX::contentBox(urlize($menu));
		h2($menu);
		foreach ($items as $item) {
			$site = site::cast($item);
			$link = $site->getUrl($urlKey);
			$img = $site->getUrl($urlKey, true) . $site->key . '-logo.png';
			if ($slim) {
				echo
					'<hr /><a class="text-center" href="' . $link . '" target="_blank">' . 
					'	<img class="img-fluid img-max-200" src="' . $img . '" />' . BRNL .
					'	<b>' . $site->siteName . '</b>' . BRNL .
					'</a>' . NEWLINE;
				continue;
			}

			tagUX::contentBox($site->key);

			echo
				//'<input class="img-fluid text-center" value="' . $img . '" />' . BRNL .
				'<a class="text-center" href="' . $link . '" target="_blank">' . 
				'	<img class="img-fluid img-max-600" src="' . $img . '" />' . BRNL .
				'	<b>' . $site->siteName . '</b>' . BRNL .
				'	<i>' . $site->byline . '</i>' . BRNL .
				//'	<input class="img-fluid text-center" value="' . $item[$urlKey] . '" />' . BRNL .
				'</a>' . NEWLINE;
			
			$file = ALLSITESROOT . $site->path . '/whois/introduction/home.md';
			$id = $site->key . '-intro';
			$introduction = disk_file_exists($file)
				? renderSET::create(renderSET::default, false, $id)->excerpt()
					->render($file, [VARWrapInSection => true, replacer::replaces => ['url' => $link]])
				: bootstrapAndUX::colouredDiv('Not found: ' . substr($file, strlen(ALLSITESROOT)), bootstrapAndUX::warning, $id);

			echo '<hr><h5 class="mb-1">Introduction</h5>' . NEWLINE
				. tagUX::tag(tagUX::Div, 'p-3 rounded-3 introduction', $id . '-div', $introduction);

			tagUX::contentBoxEnd();
		}
		if ($slim) tagUX::contentBoxEnd();
	}
	echo tagUX::tagEnd(tagUX::Div);
}
?>
<style type="text/css">
.introduction { background-color: azure; text-align: left; }
.introduction p:last-of-type { margin-bottom: 0; }
</style>
