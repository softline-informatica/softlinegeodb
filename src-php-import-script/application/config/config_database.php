<?php

/**
 * database.php
 *
 * application database(s) configuration
 *
 */

//$config['default']['library'] = 'MVC_PDO'; // plugin for db access
$config['default']['library'] = 'MVC_MYSQL'; // plugin for db access
$config['default']['type'] = 'mysql';        // connection type
$config['default']['host'] = 'localhost';    // db hostname
$config['default']['name'] = 'softlinegeodb'; 	 // db name
$config['default']['user'] = 'root';         // db username
$config['default']['pass'] = '';     	     // db password

$config['default']['persistent'] = false;  	 // db connection persistence?

// settings for MVC_MYSQL only (my library)
$config['default']['debug'] = true;     	 // get queries to be able to print them later
$config['default']['debug_results'] = false; // get query results to be able to print them later
$config['default']['debug_format_queries'] = true; // format queries as HTML (newlines, tabs, etc)

$config['default']['flashmode'] = false;   // act as API por flash (do not die, set errors instead)
