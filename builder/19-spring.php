<?php
class sectionsTsv extends sheet {
	static function load($string, $forNetwork = false) {
		if ($string != 'from-tsv')
			return self::parseString($string, $forNetwork);
		if (!sheetExists('sections')) throw new Error('expected: ' . _sheetPath('sections'));
		$tsv = new self(_sheetPath('sections'), 'group');
		return $tsv->walkRows();
	}

	private function walkRows() {
		//showDebugging(9, $this->group, PleaseDie);
		$groups = [];
		$items = [];
		foreach ($this->rows as $item) {
			$group = $this->getValue($item, 'group');
			$items[] = $name = $this->getValue($item, 'section');
			if (!isset($groups[$group])) $groups[$group] = [];
			$groups[$group][] = $name;
		}

		return ['sections' => $items, 'section-groups' => $groups];
	}

	private static function parseString($sections, $forNetwork = false) {
		if (!$sections) {
			$sections = [];
			if (!$forNetwork) variable('sections', $sections);
			return $sections;
		}

		$vars = [];
		//Eg.: research, causes, solutions, us: programs+members+blog
		if (contains($sections, ':')) {
			$swgs = explode(', ', $sections); //sections wtih groups
			$items = []; $groups = [];

			foreach ($swgs as $item) {
				if (contains($item, ':')) {
					$bits = explode(': ', $item, 2);
					$subItems = explode('+', $bits[1]);
					$groups[$bits[0]] = $subItems;
					$items = array_merge($items, $subItems);
				} else {
					$items[] = $item;
					$groups[] = $item;
				}
			}

			$vars['sections'] = $items;
			$vars['section-groups'] = $groups;
		} else {
			$vars['sections'] = explode(', ', $sections);
		}
		return $vars;
	}
}

////for sno, name etc	menuTsv	spring	next
class menuTsv {
//TODO: HI
}
