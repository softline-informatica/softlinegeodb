<?php

/**
* MVC_View
*
* @package		softlineMVC
* @author		Juanga Covas - A hack of TinyMVC by	Monte Ohrt
*/

// ************************************************
// Original MVC_View of TinyMVC, we don't want this
// ************************************************

class MVC_View {
	/**
	* $view_vars
	*
	* vars	for	view file assignment
	*
	* @access	public
	*/
	public $view_vars = array();

	/**
	* class constructor
	*
	* @access	public
	*/
	function __construct() {}

	/**
	* assign
	*
	* assign view variables
	*
	* @access	public
	* @param	mixed $key key of assignment, or value to assign
	* @param	mixed $value value of assignment
	*/
	public function assign($key, $value=null)
	{
		if(isset($value))
		$this->view_vars[$key] = $value;
		else
		foreach($key as $k =>	$v)
		if(is_int($k))
		$this->view_vars[] = $v;
		else
		$this->view_vars[$k] = $v;
	}

	/**
	* display
	*
	* display a view file
	*
	* @access	public
	* @param	string $filename the name of the view file
	* @return	boolean
	*/
	public function display($_tmvc_filename,$view_vars=null)
	{
		return $this->_view("{$_tmvc_filename}.php",$view_vars);
	}

	/**
	* fetch
	*
	* return the contents of a	view file
	*
	* @access	public
	* @param	string $filename
	* @return	string contents	of view
	*/
	public function fetch($filename,$view_vars=null)
	{
		ob_start();
		$this->display($filename,$view_vars);
		$results = ob_get_contents();
		ob_end_clean();
		return $results;
	}

	/**
	* sysview
	*
	* internal: view a	system file
	*
	* @access	private
	* @param	string $filename
	* @return	boolean
	*/
	public function sysview($filename,$view_vars = null)
	{
		$filepath =	"{$filename}.php";
		return $this->_view($filepath,$view_vars);
	}

	/**
	* _view
	*
	* internal: display a view	file
	*
	* @access	public
	* @param	string $_tmvc_filepath
	* @param	  array	$view_vars
	*/
	public function _view($_tmvc_filepath,$view_vars = null)
	{
		// bring view vars into	view scope
		extract($this->view_vars);
		if(isset($view_vars))
		extract($view_vars);
		try	{
			require $_tmvc_filepath;
		} catch	(Exception $e) {
			throw	new	Exception("Unknown file	'$_tmvc_filepath'");
		}
	}

}

