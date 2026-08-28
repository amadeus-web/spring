<?php
class domain {
	static function includeAll() {
		$sheet = getSheet(AMADEUSSITEDATA . 'domains.tsv', false);
		foreach ($sheet->rows as $item) {
			$site = $sheet->getValue($item, 'site');
			if (!disk_is_dir(ALLSITESROOT . $site)) continue;
			disk_include_once(ALLSITESROOT . $site . '/domain.php');
		}
	}

	/**
	 * @var domain[] All domains
	 */
	static $all = [];

	static function add(string $key, string $folder, bool $skipIfNotMatching, domain $item) {
		$match = contains($folder, $key); //doesnt handle substring collision
		if ($skipIfNotMatching && !$match) return;
		if ($match) DEFINE('NETWORKPATH', $folder);
		self::$all[$key] = $item;
	}

	static function remove(string $key) {
		if (!isset(self::$all[$key])) return;
		unset(self::$all[$key]);
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
		foreach ($mainSites as $ix => $val) {
			if (contains($val, '%folder%'))
				$mainSites[$ix] = replaceItems($val, ['folder' => $this->folder], WRAPREPLACE);
		}
		$this->mainSites = $mainSites;
		$this->subFolders = $subFolders;
	}
}
