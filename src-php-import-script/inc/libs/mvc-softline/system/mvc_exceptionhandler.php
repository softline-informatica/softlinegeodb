<?php

/**
* MVC_ExceptionHandler
*
* A simple	exception handler to display exceptions	in a formatted box.
*
* @package		softlineMVC
* @author		Juanga Covas - A hack of TinyMVC by	Monte Ohrt
*/

class MVC_ExceptionHandler extends ErrorException {

	/**
	* printException
	*
	* @access	public
	*/
	public static function printException($e) { // support PHP 7+
	//public static function printException(Exception $e) {
		switch ($e->getCode()) {
			case E_ERROR:
			$code_name = 'E_ERROR';
			break;
			case E_WARNING:
			$code_name = 'E_WARNING';
			break;
			case E_PARSE:
			$code_name = 'E_PARSE';
			break;
			case E_NOTICE:
			$code_name = 'E_NOTICE';
			break;
			case E_CORE_ERROR:
			$code_name = 'E_CORE_ERROR';
			break;
			case E_CORE_WARNING:
			$code_name = 'E_CORE_WARNING';
			break;
			case E_COMPILE_ERROR:
			$code_name = 'E_COMPILE_ERROR';
			break;
			case E_COMPILE_WARNING:
			$code_name = 'E_COMPILE_WARNING';
			break;
			case E_USER_ERROR:
			$code_name = 'E_USER_ERROR';
			break;
			case E_USER_WARNING:
			$code_name = 'E_USER_WARNING';
			break;
			case E_USER_NOTICE:
			$code_name = 'E_USER_NOTICE';
			break;
			case E_STRICT:
			$code_name = 'E_STRICT';
			break;
			case E_RECOVERABLE_ERROR:
			$code_name = 'E_RECOVERABLE_ERROR';
			break;
			default:
			$code_name = $e->getCode();
			break;
		}
		?>
		<span style="font-family:Arial;	text-align:	left; background-color:	lightyellow; border: 1px solid #600; color:	#600; display: block; margin: 1em 0; padding: .33em	6px">
			<b style="background-color:#004d95; color:white; border-top:2px solid	#004d95; border-bottom:4px solid #e0007a;">&nbsp;&nbsp;SoftLine&nbsp;&nbsp;</b><br /><br />
			<b>Error:</b>	<?=$code_name?><br />
			<b>Message:</b> <?=$e->getMessage()?><br />
			<b>File:</b> <?=$e->getFile()?><br />
			<b>Line:</b> <?=$e->getLine()?>
		</span>
		<?php
	}

	/**
	* handleException
	*
	* @access	public
	*/
	public static function handleException($e) { // support PHP 7+
	//public static	function handleException(Exception $e) {
		return self::printException($e);
	}
}

