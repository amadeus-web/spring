<?php
function timer_start() {
	global $began;
	$mtime = explode( ' ', microtime() );
	$began = $mtime[1] + $mtime[0];
}

function timer_end()
{
	global $began;
	$mtime = microtime(); $mtime = explode( ' ', $mtime );
	$done = $mtime[1] + $mtime[0]; $taken = $done - $began;
	return time_r($taken, '');
}

function stats_start() {
	timer_start();
}

global $stat_all_size;
global $stat_all_disk_calls;

$stat_all_size = 0;

//NOTE: timeER = exact[0] and readable[1]
function disk_call($function, $parameter, $timeER) {
	global $disk_calls;
	global $disk_calls_by_type;

	if (!DEFINED('SITEPATH')) {
		//needs check else runFrameworkFile causes load error
		if (function_exists('variable')
				&& is_local())
			showDebugging('SITEPATH', 'UNDEFINED: SITEPATH');
	}

	$parameter = shortPath($parameter);
	$exact = time_r($timeER[0], 'micro', true); $time = $timeER[1];
	$call = count($disk_calls) + 1;
	$disk_calls[] = compact('call', 'function', 'parameter', 'exact', 'time');

	$calls = isset($disk_calls_by_type[$function]) ? $disk_calls_by_type[$function] : false;
	if (!$calls) $calls = $disk_calls_by_type[$function] = ['function' => $function, 'time' => 0, 'count' => 0];
	$calls['time'] = $calls['time'] + $timeER[0];
	$calls['count'] = $calls['count'] + 1;
	$disk_calls_by_type[$function] = $calls;
}

function file_stats($file, $call) {
	global $stat_all_size;

	$size = filesize($file);
	$stat_all_size += $size;
	
	$folder = shortPath(dirname($file));
	$name = basename($file);
	$size = size_r($size);

	return compact('call', 'folder', 'name', 'size');
}

function print_stats() {
	printOrSaveComments();
	$allow = getQueryParameter('debug') || getQueryParameter('stats');
	if (!$allow) return;

	features::ensureTables();

	global $stat_all_size;
	$files = array_map('file_stats', $files = get_included_files(), array_keys($files));

	global $disk_calls_total_duration;
	global $disk_calls;
	global $disk_calls_by_type;

	echo '<div id="statistics" class="after-content my-4 p-3">' . NEWLINES2;

	sectionId('statistics-summary', 'container');
	contentBox('summary', 'after-content');
	H2('Statistics: Version 4');

	$data = [
		'+metric' => 'value',
		'load time' => timer_end(),
		'memory' => size_r(memory_get_usage()),
		'included php files' => count($files),
		'size of all php files' => size_r($stat_all_size),
		'disk calls' => count($disk_calls),
		'duration of all disk calls' => time_r($disk_calls_total_duration, 'milli'),
		'+disk calls by name' => 'name count: ' . count($disk_calls_by_type),
	];

	//TODO: LOW: scandir caching on local when ?cache=1
	//is_dir	time: 800.61 microseconds / count: 272
	//time: 407.219 microseconds / count: 102
	foreach ($disk_calls_by_type as $type => $item)
		$data[$type] = 'time: ' . time_r($item['time'], 'micro') . ' / count: ' . $item['count'];

	_tableHeadingsOnLeft('statistics-summary', $data);
	contentBox('end');
	sectionEnd();

	sectionId('statistics-php-files', 'container');
	contentBox('executables', 'after-content');
	H2('Statistics: Executable Files');
	add_table('stats-php-files', $files, 'call, folder, name, size',
		'<tr><td>%call%</td><td>%folder%</td><td>%name%</td><td>%size%</td></tr>' . NEWLINE);
	contentBox('end');
	sectionEnd();

	sectionId('statistics-disk-calls', 'container');
	contentBox('disk-calls', 'after-content');
	H2('Statistics: Disk Calls');
	add_table('statistics-disk-calls', $disk_calls, 'call, function, parameter, time, exact',
		'<tr><td>%call%</td><td>%function%</td><td>%parameter%</td><td>%time%</td><td>%exact%</td></tr>' . NEWLINE);
	contentBox('end');
	sectionEnd();
	
	echo '</div><!--end of #statistics-->' . NEWLINES2;
}

function size_r($bytes) {
	if ($bytes >= 1073741824)
		$bytes = number_format($bytes / 1073741824, 2) . ' GB';
	elseif ($bytes >= 1048576)
		$bytes = number_format($bytes / 1048576, 2) . ' MB';
	elseif ($bytes >= 1024)
		$bytes = number_format($bytes / 1024, 2) . ' KB';
	elseif ($bytes > 1)
		$bytes = $bytes . ' bytes';
	elseif ($bytes == 1)
		$bytes = $bytes . ' byte';
	else
		$bytes = '0 bytes';
	
	return $bytes;
}

stats_start();
