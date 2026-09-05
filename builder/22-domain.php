<?php
class domain {
	static function includeAll() {
		$sheet = getSheet(AMADEUSSITEDATA . 'domains.tsv', false);
		foreach ($sheet->rows as $item) {
			$site = $sheet->getValue($item, 'site');
			if (!disk_is_dir(ALLSITESROOT . $site)) continue;
			disk_include_once(ALLSITESROOT . $site . '/domain.php');
		}

		if (!self::$current && !DEFINED('SITEPATH'))
			self::$current = end(self::$all);
	}

	/**
	 * @var domain[] All domains
	 */
	static $all = [];

	/**
	 * @var domain Current domain
	 */
	static $current;

	static function add(string $key, string $folder, bool $skipIfNotMatching, domain $item) {
		$path = DEFINED('ALLSITENAME') ? ALLSITENAME : (DEFINED('SITEPATH') ? SITEPATH : false);
		$match = $path && contains($path, $key); //doesnt handle substring collision
		if ($skipIfNotMatching && !$match && !DEFINED('ALLSITENAME')) return;
		if ($match && !DEFINED('NETWORKPATH')) {
			self::$current = $item;
			DEFINE('NETWORKPATH', $folder);
		}
		self::$all[$key] = $item;
	}

	static function remove(string $key) {
		if (!isset(self::$all[$key])) return;
		unset(self::$all[$key]);
	}

	public static string $currentName = '';

	public string $currentSubfolder = '';

	public string $key;

	public string $folder;
	public string $heading;
	public string $local;
	public string $live;
	public string $localBase;
	public string $liveBase;
	public array $mainSites;
	public array $subFolders;

	public function prepareMainSite($site) {
		$site = substr($site, strlen($this->folder));
		if (!contains($site, '/')) return;
		$bits = explode('/', $site);
		$this->currentSubfolder = $bits[0];
	}

	public function cleanSubfolder() {
		$this->currentSubfolder = '';
	}

	public function __construct(array $vars, array $mainSites, $subFolders = []) {
		$this->folder = $vars['folder'];
		$this->key = removeSlash($this->folder, 'end');
		$this->heading = $vars['heading'];
		$this->local = $vars['local'];
		$this->live = $vars['live'];
		$this->localBase = $vars['local-base'];
		$this->liveBase = $vars['live-base'];
		foreach ($mainSites as $ix => $val) {
			if (contains($val, '%folder%'))
				$mainSites[$ix] = replaceItems($val, ['folder' => $this->folder], WRAPREPLACE);
		}
		$this->mainSites = $mainSites;
		$this->subFolders = $subFolders;
	}
}
