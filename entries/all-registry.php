<?php
DEFINE('ALLREGISTRY', [
	'aurodawns' => [
		'folder' => 'msa/',
		'heading' => 'MSA',
		'main' => ['ad/aurodawns'],
		'local' => 'http://localhost/msa/%subfol%/%site%/',
		'live' => 'https://%site%.msa.joyfulearth.org/',
		'local-base' => 'http://localhost/msa/%subfol%/',
		'live-base' => 'https://%subfol%.msa.joyfulearth.org/',
		'subfolders' => ['ad', 'educators', 'libraries', 'organizations', 'publications', 'auroville'],
	],
	'public_html' => [
		'folder' => '',
		'heading' => 'JE',
		'main' => ['joyfulearth', 'spring', 'us/imran'],
		'local' => 'http://localhost/%subfol%/%site%/',
		'live' => 'https://%site%.joyfulearth.org/',
		'local-base' => 'http://localhost/%subfol%/',
		'live-base' => 'https://%subfol%.joyfulearth.org/',
		'subfolders' => ['us', 'initiatives', 'networks', 'people', 'sites', 'ngos', 'families', 'businesses'],
	],
	/*
	'common-planet' => [
		'folder' => '/networks/common-planet/',
		'local' => 'http://localhost/networks/common-planet/%site%/',
		'live' => 'https://%site%.common-planet.joyfulearth.org/',
		'base' => 'https://common-planet.joyfulearth.org/',
	],
	*/
]);
