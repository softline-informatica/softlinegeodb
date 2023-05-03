<?php

/**
 * config_application.php
 *
 * application configuration
 *
 */

// set this to force controller and method instead of using URL params
$config['root_controller'] = null;
$config['root_action'] = null;

// name of default controller/method when none is given in the URL
$config['default_controller'] = 'geodb_base';
$config['default_action'] = 'index';

// name of PHP function that handles system errors
$config['error_handler_class'] = 'MVC_ErrorHandler';

// enable timer. use <%$mvc_app_time%> in your Smarty template to see it
$config['timer'] = true;

$config['base_uri'] = '/sites/softlinegeodb'; // no trailing slash

/* Routes

	Examples:

	'posts/(:any)' => 'news/$1',
	'article/(:num)/(:alnum)' => 'default/index/$1/$2',
	'(:alnum).html' => 'default/index/$1',
	'testing/(:num)' => 'test/$1',
	'moretesting/(:num)/something/(:alnum)' => 'test/$1/$2',
*/

MVCRoutes::add(array(
	//'some/url/segments/(:any)' => 'controller/method/$1', // example
	'softline/(:any)' => '$1',
));

/* Rules:

	(':any', '.+');
	(':num', '[0-9]+');
	(':nonum', '[^0-9]+');
	(':alpha', '[A-Za-z_\-]+');
	(':alnum', '[A-Za-z0-9_\-]+');
	(':hex', '[A-Fa-f0-9]+');
*/

