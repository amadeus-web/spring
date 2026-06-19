<?php
DEFINE('VARErrorStart', '<div class="container mt-4 p-5 alert alert-warning" style="border-radius: 25px;">');

DEFINE('BOOLYes', true);
DEFINE('BOOLNo', false);
DEFINE('BOOLNoString', 'no');
	DEFINE('PleaseDie', BOOLYes);
	DEFINE('IncludeTrace', BOOLYes);
	DEFINE('NeverExecute', BOOLNo);
DEFINE('EmptyArray', []);

function showDebugging($msg, $param, $die = false, $trace = false, $echo = true, $skip = false) {
	if ($skip) return;
	$op = VARErrorStart . $msg . '<hr><pre>' . print_r($param, 1);
	if ($trace) { $op .= '</pre><br>STACK TRACE:<hr><pre>'; $op .= print_r(debug_backtrace(), true); }
	$op .= '</pre></div>';
	if (!$echo) return $op;
	echo $op;
	if ($die) die();
}

//from text and array for comments
DEFINE('NEWLINE', "\r\n");
DEFINE('BRTAG', $br = '<br>');
DEFINE('BRNL', $br . NEWLINE);

DEFINE('SAFENEWLINE', "\r"); ////platform safe
DEFINE('VARTab', '	');
function trimCrLf($txt) { return trim($txt, "\r\n"); }
function contains($haystack, $needle) { return stripos($haystack, $needle) !== false; }

global $shortPaths; $shortPaths = [
	AMADEUSCORE => 'CORE/',
	DEFINED('SITEPATH') ? SITEPATH : '~~NOSITEPATH~~' => 'SITE',
	AMADEUSROOT => 'ROOT/',
];

function shortPath($path) {
	global $shortPaths;
	foreach ($shortPaths as $start => $short)
		if (contains($path, $start))
			return str_replace($start, $short, $path);
	return $path;
}

function printReadable($array) {
	$r = print_r($array, 1);
	$r = substr($r, strlen('Array' . PHP_EOL . '(' . NEWLINE . '  '));
	return substr($r, 0, strlen($r) - strlen(')' . PHP_EOL));
}

DEFINE('SLASHES', '////'); ////defined as #description, describes, category, link, line
DEFINE('TODO', '//TODO: '); ////defines the TODO marker
function processComments($file) {
	if (!isset($_GET['comments'])) return;
	_disk_start();
	$lines = explode(SAFENEWLINE, file_get_contents($file));

	foreach ($lines as $ix => $line)
	{
		$line = trimCrLf($line);
		if ($line == '') continue;

		$hasSlash = contains($line, SLASHES);
		$hasTodo = contains($line, TODO);
		if (!$hasSlash && !$hasTodo) continue;

		if ($hasSlash) _processSlash($line, $file, $ix + 1);
	}

	$time = _disk_end();
	disk_call('slashesParse', $file, $time);
}

function _processSlash($line, $file, $lineNumber) {
	$bits = explode(SLASHES, $line);
	$comment = $bits[isset($bits[2]) ? 2 : 1];
	
	$extra = [
		'file' => shortPath($file),
		'category' => isset($bits2[2]) ? $bits2[2] : 'spring',
	];
	if (contains($comment, VARTab)) {
		$bits2 = explode(VARTab, $comment);
		$extra['describes'] = $bits2[1];
		if (isset($bits2[3])) $extra['link'] = $bits2[3];
		$extra['fullLine'] = $line;
		if (isset($bits2[4])) {
			$extra['actualLine'] = $lineNumber;
			$offset = $bits2[3];
			if ($offset == 'next') $lineNumber += 1;
			else throw new Error('Unsupported lineOffset: ' . print_r($extra, 1));
		}
		$extra['fullComment'] = $comment;
		$comment = $bits2[0];
	}

	$item = array_merge([
		'comment' => $comment,
		'lineNumber' => $lineNumber,
	], $extra);
	
	add4SComment($item);
}

global $comments; $comments = [];
global $todos; $todos = [];

function add4SComment($item) {
	global $comments;
	$comments[] = $item;
}

function printOrSaveComments() {
	$allow = getQueryParameter('comments');
	if (!$allow) return;
	echo '<style>#footer { display: none; }</style>' . NEWLINES2;
	global $comments;
	_printComments($comments, '4s Comments');
}

function _printComments($items, $heading) {
	echo '<div class="container">';
	echo '<h3 class="m-2 text-center">' . $heading . ' (' . count($items) . ')</h3>';
	echo '<textarea class="vh-50 w-100">' . NEWLINE;
	foreach ($items as $comment)
		echo printReadable($comment) . NEWLINES2;
	echo '</textarea>' . NEWLINES2;
	echo TAGDIVEND;
}
