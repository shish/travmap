<?php
/*
 * loading.php (c) Shish 2006
 *
 * create a "loading" message
 */

require_once "localise.php";
require_once "libaima.php";
require_once "imageinit.php";

$imagerectangle($im, 10, 10, 758, 502, $black);
$imagestring($im, 3, (768/2)-strlen($words['loading'])*3.45, 512/2, $words['loading'], $black);

require_once "output.php";
?>
