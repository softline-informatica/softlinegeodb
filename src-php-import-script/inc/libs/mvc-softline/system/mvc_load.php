<?php

/**
* MVC_Load
*
* @package		softlineMVC
* @author		Juanga Covas - A fork of TinyMVC by Monte Ohrt
*/

class MVC_Load {
	/**
	* class constructor
	*
	* @access	public
	*/
	function __construct() { }

	/**
	* model
	*
	* load	a model	object
	*
	* @access	public
	* @param	string $model_name the name	of the model class
	* @param	string $model_alias	the	property name alias
	* @param	string $filename the filename
	* @param	string $pool_name the database pool	name to	use
	* @return	boolean
	*/
	//public function	model($model_name,$model_alias=null,$filename=null,$pool_name=null)
	public function model($model_name, $model_alias=null, $pool_name='default', $filename=null) {

		// if no alias,	use	the	model name
		if (!isset($model_alias))
		$model_alias = $model_name;

		// if no filename, use the lower-case model	name
		if (!isset($filename))
		$filename = strtolower($model_name).'.php';

		if (empty($model_alias))
			throw new Exception("Model name cannot be empty");

		if (!preg_match('!^[a-zA-Z][a-zA-Z0-9_]+$!',$model_alias))
			throw new Exception("Model name '{$model_alias}' is an invalid syntax");

		if (method_exists($this,$model_alias))
			throw new Exception("Model name '{$model_alias}' is an invalid (reserved) name");

		// get instance	of controller object
		$controller	= mvc::instance(null,'controller');

		// model already loaded? silently skip
		if (isset($controller->$model_alias))
			return true;

		// instantiate the object as a property
		$controller->$model_alias =	new	$model_name($pool_name);

		return true;

	}

	/**
	* library
	*
	* load	a library plugin
	*
	* @access	public
	* @param	string $class_name the class name
	* @param	string $alias the property name	alias
	* @param	string $filename the filename
	* @return	boolean
	*/
	public function library($lib_name, $alias=null, $filename=null) {
		// if no alias,	use	the	class name
		if (!isset($alias))
			$alias = $lib_name;

		if (empty($alias))
			throw new Exception("Library name	cannot be empty");

		if (!preg_match('!^[a-zA-Z][a-zA-Z_]+$!',$alias))
			throw new Exception("Library name '{$alias}' is an invalid syntax");

		if (method_exists($this,$alias))
			throw new Exception("Library name '{$alias}' is an invalid (reserved) name");

		// get instance	of mvc object
		$controller	= mvc::instance(null,'controller');

		// library already loaded? silently	skip
		if (isset($controller->$alias)) {
			return true;
		}

		$class_name	= "MVC_Library_{$lib_name}";

		// instantiate the object as a property
		$controller->$alias	= new $class_name;
		
		$controller->$alias->CI = mvc::instance(null, 'controller');
		
		return true;
	}

	/**
	* script
	*
	* load	a script plugin
	*
	* @access	public
	* @param	string $script_name	the	script plugin name
	* @return	boolean
	*/
	public function script($script_name) {
		
		if(!preg_match('!^[a-zA-Z][a-zA-Z_]+$!',$script_name)) {
			throw new Exception("Invalid script name '{$script_name}'");
		}

		$filename =	strtolower("MVC_Script_{$script_name}.php");

		try	{
			require $filename;
		} catch	(Exception $e) {
			throw new Exception("Unknown script file '{$filename}'");
		}
	}

	/**
	* database
	*
	* returns a	database plugin	object
	*
	* @access	public
	* @param	string $poolname the name of the database pool (if NULL	default	pool is	used)
	* @return	object
	*/
	public function database($poolname = null) {

		static $dbs	= array();

		// Juanga 2016-04-17: Put this first so we only require config_database when using more than one pool, which is rare
		if ($poolname && isset($dbs[$poolname])) {
			/* returns object from runtime cache */
			return $dbs[$poolname];
		}

		// load	config information
		require 'config_database.php';

		if (!$poolname)
			$poolname=isset($config['default_pool']) ? $config['default_pool'] : 'default';
			
		if ($poolname && isset($config[$poolname]) && !empty($config[$poolname]['library'])) {
			/* add to runtime cache */
			$dbs[$poolname] = new $config[$poolname]['library']($config[$poolname]);
			return $dbs[$poolname];
		}
	}

}

