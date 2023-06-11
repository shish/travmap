<?php
/*
 * output.php (c) Shish 2006
 *
 * Send the image to the browser
 */

require_once "imageinit.php";
require_once "libaima.php";


if(@$_GET["debug"] != "on")
switch($_GET["format"]) {
	case "PNG": case "png": default:
		header("Content-type: image/png");
		imagepng($im);
		break;
	case "JPEG": case "jpeg":
		header("Content-type: image/jpeg");
		imagejpeg($im);
		break;
	case "SVG": case "svg":
		header("Content-type: image/svg+xml");
		imagesvg($im);
		break;
}
$imagedestroy($im);
