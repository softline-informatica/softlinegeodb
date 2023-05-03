<?php

/**
* MVC_ErrorHandler
*
* A simple	exception handler to display exceptions	in a formatted box.
*
* @package		softlineMVC
* @author		Juanga Covas - A hack of TinyMVC by	Monte Ohrt
*/
function MVC_ErrorHandler($errno, $errstr, $errfile, $errline) {
	
	// do nothing if error reporting is turned off or @ is used
	if (PHP_MAJOR_VERSION === 8) {
		if (! (error_reporting() & $errno)) return;
	} else {
		if (error_reporting() === 0) return;
	}

	// PHP 8.x: we could want to avoid raising an exception for *some* new E_WARNING / E_DEPRECATED ($errno) errors for undefined $vars/index behaviour and other shit
	$skip = false;

	if (PHP_MAJOR_VERSION === 8 || error_reporting() & $errno) {

		if (PHP_MAJOR_VERSION === 8) {
			
			if ($errno === 2) { // E_WARNING check under PHP 8.x

				if (strpos($errstr, 'Undefined array key') === 0 ||
					strpos($errstr, 'Undefined variable') === 0 ||
					strpos($errstr, 'Undefined property') === 0 ||
					strpos($errstr, 'Trying to access array offset on value of type') === 0 ||
					strpos($errstr, 'Attempt to read property') === 0 ||
					strpos($errstr, 'Undefined global variable') === 0	// PHP 8.1 fuckery
				) {
					$skip = true;
				}
				
			} elseif ($errno === 8192) { // E_DEPRECATED check under PHP 8.x

				if (strpos($errstr, 'Function strftime() is deprecated') === 0 ||
					strpos($errstr, 'Passing null to parameter') !== FALSE ||
					strpos($errstr, 'Function utf8_') === 0 ||
					strpos($errstr, 'mb_convert_encoding(): Handling HTML') === 0 
					
				) {
					$skip = true;
				}			
			}
		}
	}
	
	if (! $skip) {

		throw new MVC_ExceptionHandler($errstr,	$errno,	$errno,	$errfile, $errline);
	}
}

