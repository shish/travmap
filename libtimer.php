<?php
/*
 * libtimer.php (c) Shish 2006
 *
 * a generic api to time how long a script takes to run,
 * and save the time to a file
 */

$start_time = 0;
$last_time = 0;

function timer_start() {
	global $start_time, $last_time;
	$start_time = explode(' ', microtime());
	$start_time = $start_time[1] + $start_time[0];
	$last_time = $start_time;
}

function timer_reset() {
	$end_time = explode(' ', microtime());
	$last_time = $end_time[0] + $end_time[1];
}

function timer_note($text, $fname="log_detail.txt") {
	global $start_time, $last_time;

	$end_time = explode(' ', microtime());
	$total_time = $end_time[0] + $end_time[1] - $last_time;
	$last_time = $end_time[0] + $end_time[1];

	$fp = fopen($fname, "a");
	fputs($fp, sprintf("%s %.3f\n", $text, $total_time));
	fclose($fp);
}

function timer_save($fname="log.txt") {
	global $start_time;

	$end_time = explode(' ', microtime());
	$total_time = $end_time[0] + $end_time[1] - $start_time;

	$fp = fopen($fname, "a");
	fputs($fp, sprintf("%.3f\n", $total_time));
	fclose($fp);
}
?>
