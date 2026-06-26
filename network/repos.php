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
		$location = $values['cloneAt'] . ($at = $sheet->getValue($item, 'at'));

		$exists = disk_is_dir(ALLSITESROOT . $location);
		$actions = '';
		//https://github.com/amadeus-web-archives/admin/blob/main/repositories/manage.php#L41
		if ($isMobile && !$exists) {
			$actions = linkBuilder::factory('Clone URL', $gitUrl, linkBuilder::copyUrl)
				. ' ' . linkBuilder::factory('Relative Path', $location, linkBuilder::copyRelUrl);
		}

		$rel_r = implode(' &mdash; ', explode('/', $at == '' ? $values['cloneAt'] : $location));
		$rows[] = [
			'name' => humanize($rel_r),
			'gitUrl' => $gitUrl,
			'exists' => ($exists ? $yes : $no) . (!$exists ? ' &mdash; ' . _clone($location, $gitUrl) : ''),
			'actions' => $exists && !$actions ? _pull_and_log($location) : $actions,
		];
	}
	(new tableBuilder('repo', $rows))->render();
	cbCloseAndOpen(cssUX::container);
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
