<?php

/****************************************************************************\
 *
 *		softlineMVC
 *
 *		An MVC application framework for PHP
 *
 *		Hacked by Juanga Covas from TinyMVC by Monte Ohrt
 *
 *		// 2016-04-10
 *			Added CodeIgniter-like routing
 *			Added support for URLs with controller/method/[param]/[param]
 *		// 2016-04-15
 *			Added MYSQL plugin library for models, load as default. Added query timers
 *			Created CI property to access controller from models and libraries
 * 			Added Smarty support and app execution time as <%$mvc_app_time%> variable 
 *			Cleanups: use of require, indentation, UTF8, etc.
 * 		// 2016-04-26
 * 			Added support for CLI calls, accepting args as method controller [extra args]
 *		// ToDo: environment config
 * 		         add SafeSQL from http://www.phpinsider.com/php/code/SafeSQL/
 *
 ****************************************************************************/


// Tested and hacked trough PHP 5.3 all the way up to 
//                          PHP 8.2


// PHP error reporting level
//error_reporting(E_ALL);
//error_reporting(E_ALL & ~E_NOTICE);
error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED | E_STRICT));

define('MVC_BASEDIR', 'inc/libs/mvc-softline/');

define('MVC_MYAPPDIR', 'application/');

// require main mvc class
require MVC_BASEDIR.'MVC.php';

// instantiate
$mvc = new mvc();

// act as the Front Controller we are
$mvc->main();

// ------------------------------------------------------------------------------------------------------------
// Ver controller 'geodb_base.php' configurado como default controller en application/config/config_application
// Ver config 'config_database.php' para configuración de acceso a la base de datos
// ------------------------------------------------------------------------------------------------------------


/*
   URL syntax: /controller/[method]/[param_one]/[param N]/[...]
                            defaults to "index" method
                defaults to 'default' controller (default.php)
  
   .htaccess should rewrite everything that is not a real file or directory on the server to this file (index.php or other name...)

   Use routes at config_application.php to route special URLs to other /controller/methods/param(s)...
*/

// End of index.php
