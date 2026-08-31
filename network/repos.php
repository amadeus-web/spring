<?php
if (isset($variables) && isset($variables['items'])) {
	$items = (array)$variables['items'];
	$values = (array)$variables['values'];
	$sheet = (object)$variables['sheet'];

	h2('ROOT ~/' . $values['cloneAt']);
	$isMobile = variable('is-mobile');
	$yes = '<span class="btn btn-success">yes</span>';
	$no = '<span class="btn btn-warning">no</span>';
	$rows = [];

	foreach ($items as $item) {
		$gitUrl = $sheet->getValue($item, 'from');
		$gitLink = getLink('git', str_replace('.git', '', $gitUrl), 'btn btn-outline-info me-2', true);
		$location = $values['cloneAt'] . ($at = $sheet->getValue($item, 'at'));

		$exists = disk_is_dir((defined('REPOSPATH') ? REPOSPATH : ALLSITESROOT) . $location);
		$actions = '';
		//https://github.com/amadeus-web-archives/admin/blob/main/repositories/manage.php#L41
		if ($isMobile && !$exists) {
			$actions = linkBuilder::factory('Clone URL', $gitUrl, linkBuilder::copyUrl)
				. ' ' . linkBuilder::factory('Relative Path', $location, linkBuilder::copyRelUrl);
		}

		$rel_r = implode(' / ', explode('/', $at == '' ? $values['cloneAt'] : $location));
		$rows[] = [
			'name' => $rel_r,
			'gitUrl' => $gitLink  . $gitUrl,
			'exists' => ($exists ? $yes : $no) . (!$exists ? ' &mdash; ' . _clone($location, $gitUrl) : ''),
			'actions' => $exists && !$actions ? _pull_and_log($location) : $actions,
		];
	}
	(new tableBuilder('repo', $rows))->render();
	cbCloseAndOpen(cssUX::container);
} else {
	echo tagUX::tagStart(tagUX::Div, cssUX::CenterContainer);
	$check = getQueryParameter('check');
	if (is_local())
		echo getLink('Check Clone Urls', './?check=1', 'btn btn-primary');
	if ($check) {
		$files = _skipNodeFiles(scandir(__DIR__), 'md, php');
		foreach ($files as $page) {
			echo tagUX::h2Plain($page, 'after-content mt-2');
			$sheet = getSheet(__DIR__ . '/' . $page . '.tsv', false);
			$where = ALLSITESROOT . $sheet->values['cloneAt'];
			$hasIssues = false;
			$op = [];
			foreach ($sheet->rows as $item) {
				$at = $where . $sheet->getValue($item, 'at');
				$heading = '<b>' . $at . '</b>' . BRNL;
				if (disk_is_dir($at)) {
					$config = disk_file_get_contents($at . '/.git/config');
					$value = explode('	url = ', $config)[1];
					$value = explode(LINEFEED, $value)[0];
					$tsv = $sheet->getValue($item, 'from');
					if ($tsv == $value) {
						$op[] = $heading . 'Values Match: ' . $value;
					} else {
						$hasIssues = true;
						$op[] = $heading . '<u style="background-color: mistyrose; padding: 4px;">Values Don\'t Match:</u>' . BRNL . 'Tsv: ' . $tsv . BRNL . 'Cfg: ' . $value;
					}
				} else {
					$op[] = $heading . 'Not Cloned - ' . $at;
				}
			}
			if ($hasIssues)
				echo implode(HRTAG . BRNL, $op);
			else
				echo '<u style="background-color: limegreen; padding: 4px;">All Ok. Repo Count: ' . count($sheet->rows) . '</u>';
		}
	}
	echo tagUX::tagEnd(tagUX::Div);
}

function _pull_and_log($location) {
	$view = disk_file_exists(ALLSITESROOT . $location . '/data/site.tsv') ? ' ' . getLink('view', 'http://localhost/' . $location . '/', 'btn btn-success') : '';
	return _getGuiLink($location, 'pull', 'outline-success') . NEWLINE
		. ' ' . _getGuiLink($location, 'log', 'outline-info') . NEWLINE . $view;
}

function _clone($location, $gitUrl) {
	$bits = explode('/', $location); array_pop($bits); $parent = implode('/', $bits);
	if (!disk_is_dir(ALLSITESROOT . $parent)) return '<a class="btn btn-danger" href="#create--' . $parent . '">PARENT MISSING</a>';
	return _getGuiLink($location, 'clone', 'outline-primary', '&git-url=' . $gitUrl);
}

function _getGuiLink($site, $action, $classSuffix, $optional = '') {
	$script = 'http://localhost/git-web-ui.php';
	$qs = '?git-action=' . $action . '&site=' . $site . $optional;
	return getLink($action, $script . $qs, 'btn btn-' . $classSuffix, true);
}

variables(['dir_skip_node' => true, 'dir_pop_breadcrumbs' => true]);
features::ensureDirectory();
