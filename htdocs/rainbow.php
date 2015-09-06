<?php
/*
 * rainbow.php (c) Shish 2006
 *
 * Generate as many distinct (to the human eye) colours as possible, 
 * put them in an array for later reference
 */

require_once "imageinit.php";
require_once "libaima.php";


/*
 * Hardcode our own colour table, spiralling through HSV space
 */
$ct = Array();
$s = 1.0; $v = 1.0;  for($h=0.00; $h<0.99; $h+=0.166) {$ct[] = $imagecolorallocate_hsv($im, $h, $s, $v);}
$s = 1.0; $v = 0.75; for($h=0.00; $h<0.99; $h+=0.166) {$ct[] = $imagecolorallocate_hsv($im, $h, $s, $v);}
$s = 0.5; $v = 1.0;  for($h=0.00; $h<0.99; $h+=0.166) {$ct[] = $imagecolorallocate_hsv($im, $h, $s, $v);}
$s = 1.0; $v = 1.0;  for($h=0.08; $h<0.99; $h+=0.166) {$ct[] = $imagecolorallocate_hsv($im, $h, $s, $v);}
$s = 1.0; $v = 0.75; for($h=0.08; $h<0.99; $h+=0.166) {$ct[] = $imagecolorallocate_hsv($im, $h, $s, $v);}
$s = 0.5; $v = 1.0;  for($h=0.08; $h<0.99; $h+=0.166) {$ct[] = $imagecolorallocate_hsv($im, $h, $s, $v);}
