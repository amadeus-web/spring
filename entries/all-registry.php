<?php
class siteEntry {
	const aurodawns = 'aurodawns';
	const public_html = 'public_html';

	/**
	 * @var siteEntry[] All siteEntries
	 */
	static $all = [];

	static function add(string $key, siteEntry $item) {
		self::$all[$key] = $item;
	}

	static function remove(string $key) {
		if (!isset(self::$all[$key])) return;
		unset(self::$all[$key]);
	}

	static function addFrom($before = true) {
		if (!function_exists('variableOr')) return;
		foreach(variableOr('Sites' . ($before ? 'Before' : 'After'), []) as $key => $item)
			self::add($key, $item);
	}

	public static string $currentName = '';

	public string $currentSubfolder = '';

	public string $folder;
	public string $heading;
	public string $local;
	public string $live;
	public string $localBase;
	public string $liveBase;
	public array $mainSites;
	public array $subFolders;

	public function __construct(array $vars, array $mainSites, $subFolders = []) {
		$this->folder = $vars['folder'];
		$this->heading = $vars['heading'];
		$this->local = $vars['local'];
		$this->live = $vars['live'];
		$this->localBase = $vars['local-base'];
		$this->liveBase = $vars['live-base'];
		$this->mainSites = $mainSites;
		$this->subFolders = $subFolders;
	}
}

siteEntry::addFrom();

siteEntry::add(siteEntry::aurodawns, new siteEntry([
		'folder' => 'msa/',
		'heading' => 'MSA',
		'local' => 'http://localhost/msa/%subfol%/%site%/',
		'live' => 'https://%site%.msa.joyfulearth.org/',
		'local-base' => 'http://localhost/msa/%subfol%/',
		'live-base' => 'https://%subfol%.msa.joyfulearth.org/',
	],
	['ad/aurodawns'],
	['ad', 'educators', 'libraries', 'organizations', 'publications', 'auroville'],
));

siteEntry::add(siteEntry::public_html, new siteEntry([
		'folder' => '',
		'heading' => 'JE',
		'local' => 'http://localhost/%subfol%/%site%/',
		'live' => 'https://%site%.joyfulearth.org/',
		'local-base' => 'http://localhost/%subfol%/',
		'live-base' => 'https://%subfol%.joyfulearth.org/',
	],
	['joyfulearth', 'spring', 'us/imran'],
	['us', 'sunlight', 'networks', 'people', 'sites', 'ngos', 'families', 'businesses'],
));

siteEntry::addFrom(false);
