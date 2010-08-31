<?php

	session_start();

	/**
	 * Randoms the captcha text as preset in typoscript with regular expressions
	 *
	 * @author	Thomas Imboden <timboden@snowflake.ch>
	 * @param	int		$length: Lenght of the captcha-text
	 *
	 * @return	string	The random captcha text
	 */
	function strrand($length) {
		$str = '';

		while (strlen($str) < $length){
			$random = rand(48,122);
			// Prevent possible regular expression warnings
			if (@preg_match('/[' . $_GET['fontEreg'].']/', chr($random))) {
				$str .= chr($random);
			}
		}
		return $str;
	}

	/**
	 * Transforms hec color code into an array of rgb color schemes
	 *
	 * @author	Thomas Imboden <timboden@snowflake.ch>
	 * @param	string			$hex: Hex color code
	 *
	 * @return	array	rgb color scheme
	 */
	function hex2rgb($hex) {
	    $hex = preg_replace('/[^a-fA-F0-9]/', '', $hex);

	    $rgb = array();
	    if (strlen($hex) == 3) {
	        $rgb[0] = hexdec($hex[0] . $hex[0]);
	        $rgb[1] = hexdec($hex[1] . $hex[1]);
	        $rgb[2] = hexdec($hex[2] . $hex[2]);
	    }
	    elseif (strlen($hex) == 6) {
	        $rgb[0] = hexdec ($hex[0] . $hex[1]);
	        $rgb[1] = hexdec ($hex[2] . $hex[3]);
	        $rgb[2] = hexdec ($hex[4] . $hex[5]);
	    }
	    else {
	        return array(255, 0, 0);
	    }

    	return $rgb;
	}

	/**
	 * Checks that file name is inside suitable bounds. If not, aborts the script.
	 *
	 * @return void
	 */
	function validateFileName($fileName) {
		$validDirectory = $_SERVER['DOCUMENT_ROOT'];
		if ($validDirectory == '') {
			$validDirectory = dirname(__FILE__) . '/';
		}
		$validDirectory = realpath($validDirectory);
		$fileName = realpath($fileName);
		if (substr($fileName, 0, strlen($validDirectory)) != $validDirectory) {
			// Remote file inclusion attempt -> block!
			die('Sorry.');
		}
	}

	/**
	 * Validates passed file names
	 *
	 * @return void
	 */
	function validateFileNames() {
		foreach(array('image' => 'image/', 'font' => 'font/') as $parameter => $prefix) {
			if (isset($_GET[$parameter]) && $_GET[$parameter]) {
				validateFileName($prefix . $_GET[$parameter]);
			}
		}
	}

	validateFileNames();

	$imgHeight = 40;
	$imgWidth = 140;

	$fontSize   		= intval($_GET['fontSize']);
	$fontColor  		= hex2rgb($_GET['fontColor']);
	$backgroundColor 	= hex2rgb($_GET['backgroundColor']);
	// FIXME RFIV
	$showImage  		= $_GET['showImage'];

	// sets rg color scheme into function
 	$i = 1;
  	foreach($fontColor as $key) {
	   	${fontColor.$i} = $key;
    	$i++;
   	}

   	// sets rg color scheme into function
   	$j = 1;
   	foreach($backgroundColor as $colorkey) {
   		${backgroundColor.$j} = $colorkey;
   		$j++;
   	}

	// if captcha shall have a background image
	if ($showImage == 1) {
		// FIXME RFIV
		$img = imagecreatefrompng('image/' . $_GET['image']);
	}
	else {
		// only background color
		$img = imagecreate($imgWidth,$imgHeight);
		$backcolor = imagecolorallocate($img,$backgroundColor1,$backgroundColor2,$backgroundColor3);
		imagefill($img,0,0,$backcolor);
	}

	$text 		= $_SESSION['tx_captcha_string'] = strrand(5);
	// FIXME RFIV
	$fontfile	= 'font/' . $_GET['font'];
	$ttfsize 	= $fontSize;
 	$angle 		= rand(0,5);
 	$t_x 		= rand(5,30);
 	$t_y 		= 35;
	$textcolor 	= imagecolorallocate($img,$fontColor1,$fontColor2,$fontColor3);

	imagettftext($img, $ttfsize, $angle, $t_x, $t_y, $textcolor, $fontfile, $text);

	//some obfuscation in the image
	$lineCount = intval($_GET['lines']);
	for ($i=0; $i<$lineCount; $i++) {
		$x1 = rand(0, $imgWidth - 1);
		$y1 = rand(0, round($imgHeight / 10, 0));
		$x2 = rand(0, round($imgWidth / 10, 0));
		$y2 = rand(0, $imgHeight - 1);
		imageline($img, $x1, $y1, $x2, $y2, $textcolor);

		$x1 = rand(0, $imgWidth - 1);
		$y1 = $imgHeight - rand(1, round($imgHeight / 10, 0));
		$x2 = $imgWidth - rand(1, round($imgWidth / 10, 0));
		$y2 = rand(0, $imgHeight - 1);
		imageline($img, $x1, $y1, $x2, $y2, $textcolor);
	}

	ob_start();
	header('Content-type: image/png');
	header('Cache-control: private,no-cache');
	header('Pragma: no-cache');

	imagepng($img);
	imagedestroy($img);

	// Must be after 'imagepng()' call!
	header('Content-length: ' . ob_get_length());
