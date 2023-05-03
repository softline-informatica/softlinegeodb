<?php

/**
* MVC_Model
*
* @package		softlineMVC
* @author		Juanga Covas - A hack of TinyMVC by	Monte Ohrt
*/

class MVC_Model	{
	/**
	* $db
	*
	* the database	object instance
	*
	* @access	public
	*/
	public $db = NULL;
	public $CI = NULL;

	/**
	* class constructor
	*
	* @access	public
	*/
	function __construct($poolname='default') {

		if ($poolname) {
			if (! mvc::instance()->controller) {
				throw new Exception ("Tried to load database without initialized controller");
			}
			$this->db =	mvc::instance()->controller->load->database($poolname);
		}
		$this->CI = mvc::instance(null, 'controller');
	}

}

