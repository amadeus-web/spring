<?php
$nl = NEWLINE;
DEFINE('NEWLINES2', $nl . $nl);
DEFINE('NEWLINES3', $nl . $nl . $nl);
DEFINE('HRTAG', $hr = '<hr>');

DEFINE('NBSP', ' &nbsp; ');
DEFINE('PIPEWS', ' | '); ////whitespace

DEFINE('BREADCRUMBSEPARATOR', ' <large>&#8680;</large> ');

function urlize($txt) {
	return replaceItems(strtolower($txt), ["'" => '', ' ' => '-', '&hellip;' => '__', '&' => 'and']);
}

function strip_hyphens($txt) {
	return replaceItems($txt, ['-' => ' ']);
}

function strip_paragraph($txt) {
	return replaceItems($txt, ['</p>' => '', '<p>' => '']);
}

function first_of_underscore($txt) {
	return explode('_', $txt, 2)[0];
}

DEFINE('NOSITEHUMANIZE', 'no-site');

function humanizeThis($var = VARNode) {
	return humanize(variable($var));
}

function humanize($txt, $how = false) {
	$words = ucwords(replaceItems($txt, ['--' => ' &mdash; ', '-' => ' ', '_' => '']));
	if ($how !== 'no-site' && function_exists('site_humanize')) $words = site_humanize($words, 'title', $how);
	if (contains($words, ' A ')) $words = replaceItems($words, [' A ' => ' a ', ' &mdash; a' => ' &mdash; A']);
	if (contains($words, ' An ')) $words = str_replace(' An ', ' an ', $words);
	if (contains($words, ' And ')) $words = str_replace(' And ', ' and ', $words);
	if (contains($words, ' As ')) $words = str_replace(' As ', ' as ', $words);
	if (contains($words, ' Of ')) $words = str_replace(' Of ', ' of ', $words);
	if (contains($words, ' To ')) $words = str_replace(' To ', ' to ', $words);
	if (contains($words, ' Version ')) $words = str_replace(' Version ', ' version ', $words);
	return $words;
}

function blog_heading($name, $fol) {
	if (contains($fol, 'by') || in_array($fol, variableOr('flat-blogs', []))) return humanize($name);

	$fileName_r = explode('-', $name, 2);
	$month_r = explode('-', $fol, 2);

	$fileName_r = $fileName_r[0] . ' ' . $month_r[0] . ' &mdash; ' . $fileName_r[1];
	return humanize($fileName_r);
}

////https://github.com/yieldmore/MicroVC/blob/master/tlr/app/functions.io.php	orgin of contains, startsWith and endsWith	spring
function startsWith($haystack, $needle)
{
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle)
{
	$length = strlen($needle);
	if ($length == 0) return true;
	return (substr($haystack, -$length) === $needle);
}

//contains moved to 0a-process.php

function contact_r($text) {
	$text = replaceItems($text, ['tel:' => '', 'mailto:' => '', 'https://' => '', 'www.' => '']);
	if (contains($text, '?subject')) $text = explode('?subject', $text)[0];
	return $text;
}

function explodeByArray(array $delimeters, string $input, int $limit = -1): array {
	if($delimeters===[]) return [$input];

	$unidelim = $delimeters[0];
	$step = str_replace($delimeters, $unidelim, $input);
	return explode($unidelim, $step, $limit);
}
