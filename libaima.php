<?php
/*
 * libaima.php (c) Shish 2006
 *
 * SVG output, using a GD-like API
 */

$svgBuffs = Array();
$svgSizes = Array();
$colBuffs = Array("none");

// functions {{{

function aimaoutput($im, $text) {
	global $svgBuffs;
	if(is_int($im)) $svgBuffs[$im] .= $text;
}

function aimacreate($w, $h) {
	global $svgBuffs, $svgSizes, $colBuffs;
	$im = count($svgBuffs);
	$svgSizes[$im][0] = $w;
	$svgSizes[$im][1] = $h;
	aimaoutput($im, <<<EOD
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" 
  "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<!-- Created with Aima (http://shish.is-a-geek.net/) -->
<svg width="$w" height="$h" version="1.1"
	xmlns="http://www.w3.org/2000/svg"
	xmlns:xlink="http://www.w3.org/1999/xlink"
	>
	<desc>SVG Image ($w x $h)</desc>
	<title>SVG Image ($w x $h)</title>
EOD
);
	return $im;
}

function aimacolorallocate($im, $r, $g, $b) {
	global $svgBuffs, $colBuffs;
	$id = count($colBuffs);
	$colBuffs[$id] = sprintf("#%02X%02X%02X", $r, $g, $b);
	return $id;
}

function aimacustom($im, $text) {
	aimaoutput($im, $text);
}

function aimafill($im, $x, $y, $colid) {
	global $svgSizes;
	aimafilledrectangle($im, $x, $y, $svgSizes[$im][0], $svgSizes[$im][1], $colid);
}

function aimaline($im, $x1, $y1, $x2, $y2, $colid) {
	global $colBuffs;
	$col = $colBuffs[$colid];
	aimaoutput($im, "<line x1='$x1' y1='$y1' x2='$x2' y2='$y2' stroke='$col' />\n");
}

function aimastring($im, $size, $x, $y, $text, $fill, $stroke=0) {
	global $colBuffs;
	$fill = $colBuffs[$fill];
	$stroke = $colBuffs[$stroke];
	$size *= 4;
	$y += 11;
	aimaoutput($im, "<text x='$x' y='$y' font-family='Verdana' font-size='$size' fill='$fill' stroke='$stroke'>$text</text>\n");
}

function aimattftext($im, $size, $angle, $x, $y, $colour, $font, $text, $stroke=0) {
	global $colBuffs;
	$fill = $colBuffs[$colour];
	$stroke = $colBuffs[$stroke];
	$size *= 1.3;
	aimaoutput($im, "<text x='$x' y='$y' font-family='$font' font-size='$size' fill='$fill' stroke='$stroke'>$text</text>\n");
}

function aimarectangle($im, $x, $y, $w, $h, $stroke, $fill=0) {
	global $colBuffs;
	$fill = $colBuffs[$fill];
	$stroke = $colBuffs[$stroke];
	$w -= $x; // GD does x1,x2, SVG does x,w
	$h -= $y;
	aimaoutput($im, "<rect x='$x' y='$y' width='$w' height='$h' fill='$fill' stroke='$stroke'/>\n");
}
function aimafilledrectangle($im, $x, $y, $w, $h, $fill, $stroke=0) {
	aimarectangle($im, $x, $y, $w, $h, $stroke, $fill);
}


function aimaellipse($im, $x, $y, $rx, $ry, $stroke, $fill=0) {
	global $colBuffs;
	$fill = $colBuffs[$fill];
	$stroke = $colBuffs[$stroke];
	$rx /= 2;
	$ry /= 2;
	aimaoutput($im, "<ellipse cx='$x' cy='$y' rx='$rx' ry='$ry' fill='$fill' stroke='$stroke'/>\n");
}
function aimafilledellipse($im, $x, $y, $rx, $ry, $fill, $stroke=0) {
	aimaellipse($im, $x, $y, $rx, $ry, $stroke, $fill);
}


function aimasvg($im) {
	global $svgBuffs;
    aimaoutput($im, "</svg>");
	print $svgBuffs[$im];
}
function imagesvg($im) {aimasvg($im);}

function aimadestroy($im) {
	global $svgBuffs;
	$svgBuffs[$im] = null;
}
// }}}

// Select renderer {{{
switch($_GET["format"]) {
	case "SVG": case "svg":
		$imagestring = "aimastring";
		$imagecreate = "aimacreate";
		$imagecreatetruecolor = "aimacreate";
		$imagecolorallocate = "aimacolorallocate";
		$imagecolorallocate_hsv = "aimacolorallocate_hsv";
		$imagefill = "aimafill";
		$imageline = "aimaline";
		$imagestring = "aimastring";
		$imagettftext = "aimattftext";
		$imagerectangle = "aimarectangle";
		$imagefilledrectangle = "aimafilledrectangle";
		$imageellipse = "aimaellipse";
		$imagefilledellipse = "aimafilledellipse";
		$imagedestroy = "aimadestroy";
		break;
	case "JPEG": case "jpeg":
	case "PNG": case "png":
	default:
		$imagestring = "imagestring";
		$imagecreate = "imagecreate";
		$imagecreatetruecolor = "imagecreate";
		$imagecolorallocate = "imagecolorallocate";
		$imagecolorallocate_hsv = "imagecolorallocate_hsv";
		$imagefill = "imagefill";
		$imageline = "imageline";
		$imagestring = "imagestring";
		$imagettftext = "imagettftext";
		$imagerectangle = "imagerectangle";
		$imagefilledrectangle = "imagefilledrectangle";
		$imageellipse = "imageellipse";
		$imagefilledellipse = "imagefilledellipse";
		$imagedestroy = "imagedestroy";
		break;
}
// }}}

// misc {{{
/*
 * add a couple of custom functions to both renderers
 */

// HSV 0-1 --> RGB 0-255
function hsv2rgb($H, $S, $V) {
	// hack to get rid of unreadable pale yellow on white
	if($H > 0.1 && $H < 0.7) $V -= 0.15;

	if($S == 0) {
		$R = $G = $B = $V * 255;
	}
	else {
		$var_H = $H * 6;
		$var_i = floor( $var_H );
		$var_1 = $V * ( 1 - $S );
		$var_2 = $V * ( 1 - $S * ( $var_H - $var_i ) );
		$var_3 = $V * ( 1 - $S * (1 - ( $var_H - $var_i ) ) );

		if       ($var_i == 0) { $var_R = $V     ; $var_G = $var_3  ; $var_B = $var_1 ; }
		else if  ($var_i == 1) { $var_R = $var_2 ; $var_G = $V      ; $var_B = $var_1 ; }
		else if  ($var_i == 2) { $var_R = $var_1 ; $var_G = $V      ; $var_B = $var_3 ; }
		else if  ($var_i == 3) { $var_R = $var_1 ; $var_G = $var_2  ; $var_B = $V     ; }
		else if  ($var_i == 4) { $var_R = $var_3 ; $var_G = $var_1  ; $var_B = $V     ; }
		else                   { $var_R = $V     ; $var_G = $var_1  ; $var_B = $var_2 ; }

		$R = $var_R * 255;
		$G = $var_G * 255;
		$B = $var_B * 255;
	}

	return Array($R, $G, $B);
}

function imagecolorallocate_hsv($im, $H, $S, $V) {
	$rgb = hsv2rgb($H, $S, $V);
	return imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
}
function aimacolorallocate_hsv($im, $H, $S, $V) {
	$rgb = hsv2rgb($H, $S, $V);
	return aimacolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
}
// }}}
?>
