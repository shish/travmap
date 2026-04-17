<?php
declare(strict_types=1);

/*
 * libaima.php (c) Shish 2026
 *
 * SVG output, using a GD-like API
 */

// Abstract base class for image rendering
abstract class AimaImage {
	public int $w;
	public int $h;
	private ?AimaColor $blackColor = null;

	public function __construct(int $w, int $h) {
		$this->w = $w;
		$this->h = $h;
	}

	abstract public function colorAllocate(int $r, int $g, int $b): AimaColor;
	abstract public function fill(int $x, int $y, AimaColor $col): void;
	abstract public function line(int $x1, int $y1, int $x2, int $y2, AimaColor $col): void;
	abstract public function string(int $size, int $x, int $y, string $text, AimaColor $fill, ?AimaColor $stroke = null): void;
	abstract public function ttfText(int $size, int $angle, int $x, int $y, AimaColor $fill, string $font, string $text, ?AimaColor $stroke = null): void;
	abstract public function rectangle(int $x, int $y, int $w, int $h, ?AimaColor $stroke = null, ?AimaColor $fill = null): void;
	abstract public function filledRectangle(int $x, int $y, int $w, int $h, ?AimaColor $fill = null, ?AimaColor $stroke = null): void;
	abstract public function ellipse(int $x, int $y, int $rx, int $ry, ?AimaColor $stroke = null, ?AimaColor $fill = null): void;
	abstract public function filledEllipse(int $x, int $y, int $rx, int $ry, ?AimaColor $fill = null, ?AimaColor $stroke = null): void;
	abstract public function addLink(string $href, callable $callback, ?string $title = null): void;
	abstract public function output(string $format): void;
	abstract public function destroy(): void;
	abstract public function __toString(): string;

	/**
	 * Draw a marker dot with a black outline
	 * @param float $x X coordinate
	 * @param float $y Y coordinate
	 * @param AimaColor $col Fill color
	 * @param float $size Size of the dot (default 5)
	 */
	public function dot(float $x, float $y, AimaColor $col, float $size = 5): void {
		if ($this->blackColor === null) {
			$this->blackColor = $this->colorAllocate(0, 0, 0);
		}
		$diameter = $size * 2;
		$this->filledEllipse((int)$x, (int)$y, (int)$diameter, (int)$diameter, $col);
		$this->ellipse((int)$x, (int)$y, (int)$diameter, (int)$diameter, $this->blackColor);
	}

	public function colorAllocateHSV(float $H, float $S, float $V): AimaColor {
		[$r, $g, $b] = $this->hsv2rgb($H, $S, $V);
		return $this->colorAllocate($r, $g, $b);
	}

	// HSV 0-1 --> RGB 0-255
	protected function hsv2rgb(float $H, float $S, float $V): array {
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
}

// SVG implementation using DOMDocument
class AimaSVGImage extends AimaImage {
	private DOMDocument $dom;
	private DOMElement $svg;
	private AimaColor $noneColor;
	/** @var array<DOMElement> Stack of link elements for nested context */
	private array $linkStack = [];

	public function __construct(int $w, int $h) {
		parent::__construct($w, $h);

		$this->dom = new DOMDocument('1.0', 'UTF-8');
		$this->dom->formatOutput = true;

		// Create DOCTYPE
		$implementation = new DOMImplementation();
		$dtd = $implementation->createDocumentType(
			'svg',
			'-//W3C//DTD SVG 1.1//EN',
			'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd'
		);

		// Create SVG root element
		$this->svg = $this->dom->createElementNS('http://www.w3.org/2000/svg', 'svg');
		$this->svg->setAttribute('width', (string)$w);
		$this->svg->setAttribute('height', (string)$h);
		$this->svg->setAttribute('version', '1.1');
		$this->svg->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xlink', 'http://www.w3.org/1999/xlink');
		$this->dom->appendChild($this->svg);

		// Add comment
		$comment = $this->dom->createComment(' Created with Aima (http://trac.shishnet.org/phplibs/) ');
		$this->svg->insertBefore($comment, $this->svg->firstChild);

		// Add description and title
		$desc = $this->dom->createElement('desc');
		$desc->textContent = "SVG Image ($w x $h)";
		$this->svg->appendChild($desc);

		$title = $this->dom->createElement('title');
		$title->textContent = "SVG Image ($w x $h)";
		$this->svg->appendChild($title);

		// Initialize none color
		$this->noneColor = new AimaColor("none", null);
	}

	public function colorAllocate(int $r, int $g, int $b): AimaColor {
		$svgColor = sprintf("#%02X%02X%02X", $r, $g, $b);
		return new AimaColor($svgColor, null);
	}

	public function fill(int $x, int $y, AimaColor $col): void {
		$this->filledRectangle($x, $y, $this->w, $this->h, $col);
	}

	public function line(int $x1, int $y1, int $x2, int $y2, AimaColor $col): void {
		$line = $this->dom->createElement('line');
		$line->setAttribute('x1', (string)$x1);
		$line->setAttribute('y1', (string)$y1);
		$line->setAttribute('x2', (string)$x2);
		$line->setAttribute('y2', (string)$y2);
		$line->setAttribute('stroke', (string)$col);
		$this->getActiveContext()->appendChild($line);
	}

	/**
	 * Get the current active context for appending elements
	 * Returns the current link element if inside a link, otherwise the root SVG element
	 */
	private function getActiveContext(): DOMElement {
		return empty($this->linkStack) ? $this->svg : end($this->linkStack);
	}

	/**
	 * Safely add a link with child elements generated by a callback
	 * @param string $href The URL to link to (will be escaped)
	 * @param callable $callback Function to call that generates the child elements
	 * @param string|null $title Optional tooltip title (will be escaped)
	 */
	public function addLink(string $href, callable $callback, ?string $title = null): void {
		// Create SVG <a> element with xlink:href
		$link = $this->dom->createElement('a');
		// Escape the href for safety
		$link->setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', $href);
		if ($title !== null) {
			$link->setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:title', $title);
		}
		$this->getActiveContext()->appendChild($link);
		$this->linkStack[] = $link;

		// Generate child elements
		$callback($this);

		// Pop the link off the stack
		if (empty($this->linkStack)) {
			throw new RuntimeException("Link stack corrupted");
		}
		array_pop($this->linkStack);
	}

	public function string(int $size, int $x, int $y, string $text, AimaColor $fill, ?AimaColor $stroke = null): void {
		$stroke = $stroke ?? $this->noneColor;
		$size *= 4;
		$y += 11;

		$textElem = $this->dom->createElement('text');
		$textElem->setAttribute('x', (string)$x);
		$textElem->setAttribute('y', (string)$y);
		$textElem->setAttribute('font-family', 'Verdana');
		$textElem->setAttribute('font-size', (string)$size);
		$textElem->setAttribute('fill', (string)$fill);
		$textElem->setAttribute('stroke', (string)$stroke);
		$textElem->textContent = $text;
		$this->getActiveContext()->appendChild($textElem);
	}

	public function ttfText(int $size, int $angle, int $x, int $y, AimaColor $fill, string $font, string $text, ?AimaColor $stroke = null): void {
		$stroke = $stroke ?? $this->noneColor;
		$size *= 1.3;

		$textElem = $this->dom->createElement('text');
		$textElem->setAttribute('x', (string)$x);
		$textElem->setAttribute('y', (string)$y);
		$textElem->setAttribute('font-family', $font);
		$textElem->setAttribute('font-size', (string)$size);
		$textElem->setAttribute('fill', (string)$fill);
		$textElem->setAttribute('stroke', (string)$stroke);
		$textElem->textContent = $text;
		$this->getActiveContext()->appendChild($textElem);
	}

	public function rectangle(int $x, int $y, int $w, int $h, ?AimaColor $stroke = null, ?AimaColor $fill = null): void {
		$stroke = $stroke ?? $this->noneColor;
		$fill = $fill ?? $this->noneColor;
		$w -= $x; // GD does x1,x2, SVG does x,w
		$h -= $y;

		$rect = $this->dom->createElement('rect');
		$rect->setAttribute('x', (string)$x);
		$rect->setAttribute('y', (string)$y);
		$rect->setAttribute('width', (string)$w);
		$rect->setAttribute('height', (string)$h);
		$rect->setAttribute('fill', (string)$fill);
		$rect->setAttribute('stroke', (string)$stroke);
		$this->getActiveContext()->appendChild($rect);
	}

	public function filledRectangle(int $x, int $y, int $w, int $h, ?AimaColor $fill = null, ?AimaColor $stroke = null): void {
		$this->rectangle($x, $y, $w, $h, $stroke, $fill);
	}

	public function ellipse(int $x, int $y, int $rx, int $ry, ?AimaColor $stroke = null, ?AimaColor $fill = null): void {
		$stroke = $stroke ?? $this->noneColor;
		$fill = $fill ?? $this->noneColor;
		$rx /= 2;
		$ry /= 2;

		$ellipse = $this->dom->createElement('ellipse');
		$ellipse->setAttribute('cx', (string)$x);
		$ellipse->setAttribute('cy', (string)$y);
		$ellipse->setAttribute('rx', (string)$rx);
		$ellipse->setAttribute('ry', (string)$ry);
		$ellipse->setAttribute('fill', (string)$fill);
		$ellipse->setAttribute('stroke', (string)$stroke);
		$this->getActiveContext()->appendChild($ellipse);
	}

	public function filledEllipse(int $x, int $y, int $rx, int $ry, ?AimaColor $fill = null, ?AimaColor $stroke = null): void {
		$this->ellipse($x, $y, $rx, $ry, $stroke, $fill);
	}

	public function output(string $format): void {
	    header("Content-type: image/svg+xml");
		print $this->__toString();
	}

	public function destroy(): void {
		// No cleanup needed for SVG
	}

	public function __toString(): string {
		return $this->dom->saveXML();
	}
}

// GD implementation wrapping PHP's GD library
class AimaGDImage extends AimaImage {
	private $gd;

	public function __construct(int $w, int $h) {
		parent::__construct($w, $h);
		$this->gd = imagecreatetruecolor($w, $h);
	}

	public function colorAllocate(int $r, int $g, int $b): AimaColor {
		$svgColor = sprintf("#%02X%02X%02X", $r, $g, $b);
		$gdColor = imagecolorallocate($this->gd, $r, $g, $b);
		return new AimaColor($svgColor, $gdColor);
	}

	public function addLink(string $href, callable $callback, ?string $title = null): void {
		// GD images don't support links - just call the callback without link wrapper
		$callback($this);
	}

	public function fill(int $x, int $y, AimaColor $col): void {
		imagefill($this->gd, $x, $y, $col->gdColor);
	}

	public function line(int $x1, int $y1, int $x2, int $y2, AimaColor $col): void {
		imageline($this->gd, $x1, $y1, $x2, $y2, $col->gdColor);
	}

	public function string(int $size, int $x, int $y, string $text, AimaColor $fill, ?AimaColor $stroke = null): void {
		imagestring($this->gd, $size, $x, $y, $text, $fill->gdColor);
	}

	public function ttfText(int $size, int $angle, int $x, int $y, AimaColor $fill, string $font, string $text, ?AimaColor $stroke = null): void {
		imagettftext($this->gd, $size, $angle, $x, $y, $fill->gdColor, $font, $text);
	}

	public function rectangle(int $x, int $y, int $w, int $h, ?AimaColor $stroke = null, ?AimaColor $fill = null): void {
		if ($stroke !== null) {
			imagerectangle($this->gd, $x, $y, $w, $h, $stroke->gdColor);
		}
	}

	public function filledRectangle(int $x, int $y, int $w, int $h, ?AimaColor $fill = null, ?AimaColor $stroke = null): void {
		if ($fill !== null) {
			imagefilledrectangle($this->gd, $x, $y, $w, $h, $fill->gdColor);
		}
		if ($stroke !== null) {
			imagerectangle($this->gd, $x, $y, $w, $h, $stroke->gdColor);
		}
	}

	public function ellipse(int $x, int $y, int $rx, int $ry, ?AimaColor $stroke = null, ?AimaColor $fill = null): void {
		if ($stroke !== null) {
			imageellipse($this->gd, $x, $y, $rx, $ry, $stroke->gdColor);
		}
	}

	public function filledEllipse(int $x, int $y, int $rx, int $ry, ?AimaColor $fill = null, ?AimaColor $stroke = null): void {
		if ($fill !== null) {
			imagefilledellipse($this->gd, $x, $y, $rx, $ry, $fill->gdColor);
		}
		if ($stroke !== null) {
			imageellipse($this->gd, $x, $y, $rx, $ry, $stroke->gdColor);
		}
	}

	public function output(string $format): void {
		switch(strtolower($format)) {
			case 'jpeg':
			case 'jpg':
				header('Content-Type: image/jpeg');
				imagejpeg($this->gd);
				break;
			case 'png':
			default:
				header('Content-Type: image/png');
				imagepng($this->gd);
				break;
		}
	}

	public function destroy(): void {
		if ($this->gd !== null) {
			imagedestroy($this->gd);
		}
	}

	public function __toString(): string {
		ob_start();
		$this->output();
		return ob_get_clean();
	}

	/**
	 * @return resource|false
	 */
	public function getGDResource() {
		return $this->gd;
	}
}

class AimaColor {
	public string $col; // SVG color (hex or "none")
	public ?int $gdColor = null; // For GD backend

	public function __construct(?string $svgColor, ?int $gdColor) {
		$this->col = $svgColor ?? "none";
		$this->gdColor = $gdColor;
	}

	public function __toString(): string {
		return $this->col;
	}
}
