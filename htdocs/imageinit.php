<?php
/*
 * imageinit.php (c) Shish 2006
 *
 * create an image to be drawn onto
 */

require_once "libaima.php";

putenv('GDFONTPATH=' . realpath('.'));

$im = $imagecreatetruecolor(768, 512);
# FIXME: aimacustom(javascript)
# imageantialias($im, true);
$white = $imagecolorallocate($im, 255, 255, 255);
$wgrey = $imagecolorallocate($im, 240, 240, 240);
$lgrey = $imagecolorallocate($im, 220, 220, 220);
$mgrey = $imagecolorallocate($im, 180, 180, 180);
$grey  = $imagecolorallocate($im, 127, 127, 127);
$dgrey = $imagecolorallocate($im, 63,  63,  63);
$black = $imagecolorallocate($im, 0,   0,   0);
$imagefill($im, 0, 0, $white);
