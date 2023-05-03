<?php

/****************************************************************************\
 *
 *		softlineMVC
 *
 *		An MVC application framework for PHP
 *
 *		Hacked by Juanga Covas from	TinyMVC	by Monte Ohrt
 *
 ****************************************************************************/

define('MVC_VERSION','1.2.4-softline');

// Tell PHP that we're using UTF-8 strings until the end of the script
mb_internal_encoding('UTF-8');
// Tell PHP that we'll be outputting UTF-8 to the browser
mb_http_output('UTF-8');
// Set the UTF-8 charset at http response headers
header('Content-Type: text/html; charset=UTF-8');

/* define to 0 if you want errors/exceptions handled externally */
define('MVC_ERROR_HANDLING', 1);

// directory separator alias
if(!defined('DS')) {
	define('DS',DIRECTORY_SEPARATOR);
}

// set include_path	for	spl_autoload
set_include_path(get_include_path()
. PATH_SEPARATOR . MVC_MYAPPDIR . 'controllers' . DS
. PATH_SEPARATOR . MVC_MYAPPDIR . 'config' . DS
. PATH_SEPARATOR . MVC_MYAPPDIR . 'models' . DS
. PATH_SEPARATOR . MVC_MYAPPDIR . 'libraries' . DS
//. PATH_SEPARATOR . MVC_MYAPPDIR . 'views' . DS
. PATH_SEPARATOR . MVC_BASEDIR . 'system' . DS
. PATH_SEPARATOR . "inc/libs/smarty/libs/plugins" . DS
. PATH_SEPARATOR . "inc/libs/smarty/libs/sysplugins" . DS
//. PATH_SEPARATOR . MVC_BASEDIR . 'system' . DS . 'views' . DS
);

// set .php	first for speed
spl_autoload_extensions('.php,.inc');

$spl_funcs = spl_autoload_functions();
if($spl_funcs === false) {
	spl_autoload_register();
}
elseif(!in_array('spl_autoload',$spl_funcs)) {
	spl_autoload_register('spl_autoload');
}

/**
* mvc
*
* main object class
*
*/

class mvc {
	/* config file values */
	public $config = null;
	/* controller object */
	public $controller = null;
	/* controller method name */
	public $action = null;
	/* server path_info	*/
	public $path_info =	null;
	/* array of	url	path_info segments */
	public $url_segments = array();

	public $uri	= null;
	public $uri_routed = null;
	
	public $argv = null;

	/**
	* class	constructor
	*
	* @access	public
	*/
	public function	__construct($id='default')
	{
		/* set instance	*/
		self::instance($this,$id);
		
		global $argv;
		$this->argv = $argv;
	}

	/**
	* main method of execution
	*
	* @access	public
	*/
	public function	main()
	{
		/* set initial timer */
		self::timer('mvc_app_start');

		/* set path_info */
		$this->path_info = !empty($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] :
		(!empty($_SERVER['ORIG_PATH_INFO'])	? $_SERVER['ORIG_PATH_INFO'] : '');

		/* internal	error handling */
		$this->setupErrorHandling();

		/* require application config */
		require 'config_application.php';
		$this->config =	$config;

		/* url remapping/routing */
		$this->setupRouting();

		/* split path_info into	array */
		// Juanga 2016-04-11: This is done by setupRouting now
		//$this->setupSegments();

		/* setup controller method */
		$this->setupAction();

		/* create controller object	*/
		$this->setupController();
		
		/* run library/script autoloaders */
		$this->setupAutoloaders();

		/* capture output if timing	*/
		//if($this->config['timer'])
		//	ob_start();

		/* execute controller action */
		//$this->controller->{$this->action}();

		// Juanga 2016-04-10
		if (! method_exists($this->controller, $this->action)) {
			$this->show404();
		}
		
		
		if (PHP_SAPI != 'cli') {
			// first segment is the controller, second is the method/action
			$action_params = (!empty($this->url_segments)) ? array_slice($this->url_segments, 2) : array();
		} else {
			// $argv[0] is the filename of the script, then 1st controller, 2nd method/action
			$action_params = (!empty($this->argv)) ? array_slice($this->argv, 3) : array();
		}
		$this->config['mvc']['method_params'] = $action_params;
		
		// Juanga 2016-04-10: use call_user_func_array like	CodeIgniter	so we pass any additional "URL parameters" to method
		call_user_func_array(array($this->controller, $this->action), $action_params);

		// Juanga 2016-04-16:
		// Usar <%$mvc_app_time%> en plantilla smarty para mostrar el tiempo de ejecución hasta smarty->display()
		
		/*
		if($this->config['timer']) {
			// insert timing info
			//$output =	ob_get_contents();
			//ob_end_clean();
			//self::timer('mvc_app_end');
			//echo str_replace('{MVCTIMER}',sprintf('%0.5f',self::timer('mvc_app_start','mvc_app_end')),$output);
		}
		*/

	} // main()

	/**
	* setup	error handling for mvc
	*
	* @access	public
	*/
	public function	setupErrorHandling() {
		if(defined('MVC_ERROR_HANDLING') &&	MVC_ERROR_HANDLING==1) {
			// catch all uncaught exceptions
			set_exception_handler(array('MVC_ExceptionHandler','handleException'));
			require 'mvc_errorhandler.php';
			set_error_handler('MVC_ErrorHandler');
		}
	}

	/**
	* setup	url	routing	for	mvc
	*
	* @access	public
	*/
	public function	setupRouting() {
		/*
		if(!empty($this->config['routing']['search'])&&!empty($this->config['routing']['replace']))
		$this->path_info = preg_replace(
		$this->config['routing']['search'],
		$this->config['routing']['replace'],
		$this->path_info);
		*/

		// Juanga 2015-04-10: added	routes as MVCRoutes	to get CodeIgniter behaviour

		if (empty($this->path_info)) {
			$this->url_segments	= array();
			return;
		}
		$this->uri = trim($this->path_info,	"/ ");
		$this->uri_routed =	MVCRoutes::route( $this->uri );

		// keep	index [1] => 'controller'
		// array_filter	will return	an indexed array omitting FALSE	elements, so for /controller/action/one/ will be array(1 =>	'controller'...)
		$this->url_segments	= array_filter(explode('/',	'/'.$this->uri_routed));
		
		$this->config['mvc']['uri'] = $this->uri;
		$this->config['mvc']['uri_routed'] = $this->uri_routed;
		$this->config['mvc']['uri_match'] = ($this->uri == $this->uri_routed);
	}

	/**
	* setup	controller
	*
	* @access	public
	*/
	public function	setupController() {
		/* get controller/method */
		if(!empty($this->config['root_controller'])) {
			$controller_name = $this->config['root_controller'];
			$controller_file = "{$controller_name}.php";
		} else {

			if (PHP_SAPI != 'cli') {			
				// class names should be AZ	and	underscore _ only
				$controller_name = !empty($this->url_segments[1]) ? preg_replace('!\W!', '', $this->url_segments[1]) : $this->config['default_controller'];
			} else {
				//echo "PHP_SAPI: ".PHP_SAPI."\n";
				//echo " argv[1]: [{$this->argv[1]}]\n";
				$controller_name = !empty($this->argv[1]) ? preg_replace('!\W!', '', $this->argv[1]) : $this->config['default_controller'];				
			}
			
			$controller_file = "{$controller_name}.php";
			
			// if no controller, show 404 // use default
			if (!stream_resolve_include_path($controller_file)) {
				
				$this->show404();
				// using the default controller is not done anymore
				$controller_name = $this->config['default_controller'];
				$controller_file = "{$controller_name}.php";
			}
		}

		//$segments_base = explode('/', MVC_BASEDIR);
		//$back_str = str_repeat('../', count($segments_base));
		require MVC_MYAPPDIR . 'controllers/' . $controller_file;

		// see if controller class exists
		$controller_class =	$controller_name.'_Controller';

		$this->config['mvc']['controller'] = $controller_name;

		// instantiate the controller
		$this->controller =	new	$controller_class(true);
	}

	/**
	* setup	controller method (action) to execute
	*
	* @access	public
	*/
	public function	setupAction() {
		if(!empty($this->config['root_action'])) {
			// user	override if	set
			$this->action =	$this->config['root_action'];
		} else {
			if (PHP_SAPI != 'cli') {
				// get from	url	if present,	else use default
				$this->action =	!empty($this->url_segments[2]) ? preg_replace('!\W!', '', $this->url_segments[2]) : (!empty($this->config['default_action']) ? $this->config['default_action'] : 'index');
			} else {
				// get from argv
				$argv[2] = trim($this->argv[2]);
				$this->action = !empty($this->argv[2]) ? preg_replace('!\W!', '', $this->argv[2]) : (!empty($this->config['default_action']) ? $this->config['default_action'] : 'index');
			}
			
			// cannot call method names	starting with underscore
			if(substr($this->action,0,1)=='_') {
				throw new Exception("Action	name not allowed '{$this->action}'");
			}
		}
		$this->config['mvc']['method'] = $this->action;
	}

	/**
	* autoload any libs/scripts
	*
	* @access	public
	*/
	public function	setupAutoloaders() {
		require 'config_autoload.php';
		if(!empty($config['libraries'])) {
			foreach($config['libraries'] as	$library)
			if(is_array($library)) {
				$this->controller->load->library($library[0],$library[1]);
			} else {
				$this->controller->load->library($library);
			}
		}
		if(!empty($config['scripts'])) {
			foreach($config['scripts'] as $script) {
				$this->controller->load->script($script);
			}
		}
		if(!empty($config['models'])) {
			foreach($config['models'] as $model) {
				$this->controller->load->model($model);
			}
		}
	}

	public function show404() {
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
		echo <<< ERROR
			<html><head>
			<title>404 Not Found</title>
			</head><body>
			<h1>Not Found</h1>
			<p>The requested URL was not found on this server.</p>
			<p>App routing also failed resolving the requested URL.</p>
			</body></html>		
ERROR;
		exit;
	}
	
	/**
	* instance
	*
	* get/set the mvc object instance(s)
	*
	* @access	public
	* @param   object $new_instance	reference to new object	instance
	* @param   string $id object instance id
	* @return  object $instance	reference to object	instance
	*/
	public static function &instance($new_instance=null,$id='default') {
		static $instance = array();
		if(isset($new_instance)	&& is_object($new_instance)) {
			$instance[$id] = $new_instance;
		}
		return $instance[$id];
	}

	/**
	* timer
	*
	* get/set timer	values
	*
	* @access  public
	* @param   string $id the timer	id to set (or compare with $id2)
	* @param   string $id2 the timer id	to compare with	$id
	* @return  float  difference of	two	times
	*/
	public static function timer($id=null,$id2=null) {
		static $times =	array();
		if($id !== null	&& $id2	!==	null) {
			return (isset($times[$id]) && isset($times[$id2])) ? ($times[$id2] - $times[$id]) :	false;
		}
		elseif($id !== null) {
			return $times[$id] = microtime(true);
		}
		return false;
	}

}

class MVCRoutes	{

	/*

	Routes is low-level	PHP	class for defining and using URL routing patterns, based on	CodeIgniter's implementation:

	https://github.com/EllisLab/CodeIgniter/blob/develop/system/core/Router.php

	Author:	Simon Hamp
	
	See: https://github.com/simonhamp/routes

	
	Usage:
	----------------------------------------------------------------

	MVCRoutes::add(array(
	'testing/(:num)' =>	'test/$1',
	'moretesting/(:num)/something/(:alnum)'	=> 'test/$1/$2',
	'posts/(:any)' => 'news/$1'
	));

	$origin	= 'testing/1';
	$origin	= 'moretesting/654/something/321';
	echo 'Origin: '	. $origin .	'<br>';
	echo 'Reroute: ' . MVCRoutes::route( $origin );

	*/

	protected static $allow_query =	false;
	protected static $routes = array();

	public static function add($src, $dest = null) {
		// TODO: Validate the routes?

		if (!empty($src) &&	is_array($src))	{
			foreach	($src as $key => $val) {
				static::$routes[$key] =	$val;
			}
		}
		elseif ($dest) {
			static::$routes[$src] =	$dest;
		}
	}

	public static function route($uri) {

		$qs	= '';

		if (static::$allow_query &&	strpos($uri, '?') !== false) {
			// Break the query string off and attach later
			$qs	= '?' .	parse_url($uri,	PHP_URL_QUERY);
			$uri = str_replace($qs,	'',	$uri);
		}

		if (empty(static::$routes))	{
			return $uri	. $qs;
		}

		// Is there	a literal match?
		if (isset(static::$routes[$uri])) {
			return static::$routes[$uri] . $qs;
		}

		// Loop	through	the	route array	looking	for	wild-cards
		foreach	(static::$routes as	$key =>	$val) {
			// Convert wild-cards to RegEx
			if (FALSE !== strpos($key, ':')) { // strpos is faster than strstr/strchr
				$key = str_replace(':any', '.+', $key);
				$key = str_replace(':num', '[0-9]+', $key);
				$key = str_replace(':nonum', '[^0-9]+',	$key);
				$key = str_replace(':alpha', '[A-Za-z_\-]+', $key);
				$key = str_replace(':alnum', '[A-Za-z0-9_\-]+',	$key);
				$key = str_replace(':hex', '[A-Fa-f0-9]+', $key);
			}

			// Does	the	RegEx match?
			if (preg_match('#^'	. $key . '$#', $uri)) {
				// Do we have a	back-reference?
				if (strpos($val, '$') !== FALSE	&& strpos($key,	'(') !== FALSE)	{
					$val = preg_replace('#^' . $key	. '$#',	$val, $uri);
				}

				return $val	. $qs;
			}
		}

		return $uri	. $qs;
	}

	public static function reverseRoute($controller, $root = "/") {
		$index = array_search($controller, static::$routes);
		if($index === false)
		return null;

		return $root . static::$routes[$index];
	}
}

