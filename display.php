<?php

require './ElementaryCellularAutomaton/Iterator.php';


function display($rule, $width, $height, $pixel_size, $start) {
	$iter = new ElementaryCellularAutomaton\Iterator($rule, $width, $start);

	$img = imagecreatetruecolor($width * ($pixel_size + 1), $height * ($pixel_size + 1));
	$blk = imagecolorallocate($img, 0, 0, 0);
	$wht = imagecolorallocate($img, 255, 255, 255);
	$gry = imagecolorallocate($img, 119, 119, 119);

	$i = 0;
	foreach ($iter as $row) {
		$y1 = $i * ($pixel_size + 1);
		$y2 = ($i + 1) * ($pixel_size + 1) - 1;

		foreach ($row as $k => $elem) {
			$x1 = $k * ($pixel_size + 1);
			$x2 = ($k + 1) * ($pixel_size + 1) - 1;

			imagefilledrectangle($img, $x1, $y1, $x2, $y2, $gry); // border
			imagefilledrectangle($img, $x1 + 1, $y1 + 1, $x2, $y2, ($elem ? $blk : $wht));
		}

		if ($i === $height) {
			break;
		}

		++$i;
	}

	header('Content-type: image/png');
	imagepng($img);
	imagedestroy($img);
}
