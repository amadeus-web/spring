<?php
DEFINE('ALLREGISTRY', [
	'public_html' => [
		'folder' => '',
		'local' => 'http://localhost/%subfol%/%site%/',
		'live' => 'https://%site%.joyfulearth.org/',
		'local-base' => 'http://localhost/%subfol%/',
		'live-base' => 'https://%subfol%.joyfulearth.org/',
		'subfolders' => ['us', 'initiatives', 'networks', 'people', 'sites', 'ngos', 'families', 'businesses'],
	],
	'common-planet' => [
		'folder' => '/networks/common-planet/all/',
		'local' => 'http://localhost/networks/common-planet/all/%site%/',
		'live' => 'https://%site%.common-planet.org/',
		'base' => 'https://all.common-planet.org/',
	],
]);
