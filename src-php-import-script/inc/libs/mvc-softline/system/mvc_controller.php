<?php

/**
* MVC_Controller
*
* @package		softlineMVC
* @author		Juanga Covas - A hack of TinyMVC by	Monte Ohrt
*/
#[AllowDynamicProperties]
class MVC_Controller {
	/**
	* class constructor
	*
	* @access	public
	*/
	function __construct()
	{
		/* save controller instance	*/
		mvc::instance($this,'controller');

		/* instantiate load	library	*/
		$this->load	= new MVC_Load;

		/* instantiate view	library	*/
		//$this->view = new MVC_View;
		$this->view = new MVC_Smarty_View;
	}

	/**
	* index
	*
	* the default controller method
	*
	* @access	public
	*/
	function index() { }

	/**
	* __call
	*
	* gets	called when	an unspecified method is used
	*
	* @access	public
	*/
	function __call($function, $args)	{

		throw new Exception("Unknown controller	method '{$function}'");

	}

}

