<?php
/*
 * libaima.php (c) Shish 2006
 *
 * SVG output, using a GD-like API
 */

class AimaImage {
	public int $w;
	public int $h;
	public string $buffer;

	function __construct(int $w, int $h) {
		$this->w = $w;
		$this->h = $h;
		$this->buffer = <<<EOD
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" 
  "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<!-- Created with Aima (http://trac.shishnet.org/phplibs/) -->
<svg width="$w" height="$h" version="1.1"
	xmlns="http://www.w3.org/2000/svg"
	xmlns:xlink="http://www.w3.org/1999/xlink"
	>
	<desc>SVG Image ($w x $h)</desc>
	<title>SVG Image ($w x $h)</title>
EOD;
	}

	function write(string $text): void {
		$this->buffer .= $text;
	}

	function __toString(): string {
		return $this->buffer;
	}
}

class AimaColor {
	public string $col; // public just to set to "none"

	function __construct(int $r, int $g, int $b) {
		$this->col = sprintf("#%02X%02X%02X", $r, $g, $b);
	}

	function __toString(): string {
		return $this->col;
	}
}

$none = new AimaColor(0, 0, 0);
$none->col = "none";

// functions {{{

function _aimawrite(AimaImage $im, string $text): void {
	$im->buffer .= $text;
}

function aimacreate(int $w, int $h): AimaImage {
	return new AimaImage($w, $h);
}

function aimacolorallocate(AimaImage $im, int $r, int $g, int $b): AimaColor {
	return new AimaColor($r, $g, $b);
}

function aimacustom(AimaImage $im, string $text): void {
	$im->write($text);
}

function aimafill(AimaImage $im, int $x, int $y, AimaColor $col): void {
	aimafilledrectangle($im, $x, $y, $im->w, $img->h, $col);
}

function aimaline(AimaImage $im, int $x1, int $y1, int $x2, int $y2, AimaColor $col): void {
	$im->write("<line x1='$x1' y1='$y1' x2='$x2' y2='$y2' stroke='$col' />\n");
}

function aimastring(AimaImage $im, int $size, int $x, int $y, string $text, AimaColor $fill, AimaColor $stroke=null): void {
	global $none;
	$stroke ??= $none;
	$size *= 4;
	$y += 11;
	$im->write("<text x='$x' y='$y' font-family='Verdana' font-size='$size' fill='$fill' stroke='{$stroke}'>$text</text>\n");
}

function aimattftext(AimaImage $im, int $size, int $angle, int $x, int $y, AimaColor $fill, string $font, string $text, AimaColor $stroke=null): void {
	global $none;
	$stroke ??= $none;
	$size *= 1.3;
	$text = svgentities($text);
	$im->write("<text x='$x' y='$y' font-family='$font' font-size='$size' fill='$fill' stroke='{$stroke}'>$text</text>\n");
}

function aimarectangle(AimaImage $im, int $x, int $y, int $w, int $h, AimaColor $stroke, AimaColor $fill=null): void {
	global $none;
	$fill ??= $none;
	$w -= $x; // GD does x1,x2, SVG does x,w
	$h -= $y;
	$im->write("<rect x='$x' y='$y' width='$w' height='$h' fill='$fill' stroke='$stroke'/>\n");
}
function aimafilledrectangle(AimaImage $im, int $x, int $y, int $w, int $h, AimaColor $fill, AimaColor $stroke=null): void {
	aimarectangle($im, $x, $y, $w, $h, $stroke, $fill);
}


function aimaellipse(AimaImage $im, int $x, int $y, int $rx, int $ry, AimaColor $stroke, AimaColor $fill=null): void {
	global $none;
	$fill ??= $none;
	$rx /= 2;
	$ry /= 2;
	$im->write("<ellipse cx='$x' cy='$y' rx='$rx' ry='$ry' fill='$fill' stroke='$stroke'/>\n");
}
function aimafilledellipse(AimaImage $im, int $x, int $y, int $rx, int $ry, AimaColor $fill, AimaColor $stroke=null): void {
	aimaellipse($im, $x, $y, $rx, $ry, $stroke, $fill);
}


function aimasvg(AimaImage $im): string {
	$im->write("</svg>");
	print $im;
}
function imagesvg(AimaImage $im): string {aimasvg($im);}

function aimadestroy(AimaImage $im): void {}

function svgentities(string $text): string {
	$text = str_replace("<", "&lt;", $text);
	$text = str_replace(">", "&gt;", $text);
	$text = str_replace("&", "&amp;", $text);
	$text = str_replace("'", "", $text);
	$text = str_replace("\"", "", $text);
	return $text;
}
// }}}

// Select renderer {{{
switch($_GET["format"] ?? "svg") {
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

	return [(int)$R, (int)$G, (int)$B];
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
