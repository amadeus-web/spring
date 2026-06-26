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
		$from = $sheet->getValue($item, 'from');
		$location = $values['cloneAt'] . ($at = $sheet->getValue($item, 'at'));

		$exists = disk_is_dir(ALLSITESROOT . $location);
		$actions = '';
		//https://github.com/amadeus-web-archives/admin/blob/main/repositories/manage.php#L41
		if ($isMobile && !$exists) {
			$actions = linkBuilder::factory('Clone URL', $from, linkBuilder::copyUrl)
				. ' ' . linkBuilder::factory('Relative Path', $location, linkBuilder::copyRelUrl);
		}

		$rel_r = implode(' &mdash; ', explode('/', $at == '' ? $values['cloneAt'] : $location));
		$rows[] = [
			'name' => humanize($rel_r),
			'gitUrl' => $from,
			'exists' => ($exists ? $yes : $no) . (!$exists ? ' &mdash; ' . _clone($location) : ''),
			'actions' => $exists && !$actions ? _pull_and_log($location) : $actions,
		];
	}
	(new tableBuilder('repo', $rows))->render();
	cbCloseAndOpen(cssUX::container);
}

function _pull_and_log($location) {
	return _getGuiLink($location, 'pull', 'outline-success') . NEWLINE
		. ' ' . _getGuiLink($location, 'log', 'outline-info') . NEWLINE;
}

function _clone($location) {
	return _getGuiLink($location, 'clone', 'outline-primary', '&git-url=' . $location);
}

function _getGuiLink($site, $action, $classSuffix, $optional = '') {
	$script = 'http://localhost/git-web-ui.php';
	$qs = '?git-action=' . $action . '&site=' . $site . $optional;
	return getLink($action, $script . $qs, 'btn btn-' . $classSuffix, true);
}

variables(['dir_skip_node' => true, 'dir_pop_breadcrumbs' => true]);
features::ensureDirectory();
