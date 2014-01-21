<?php

/*!
 * Auto-Reload Server, v0.2
 *
 * Copyright (c) 2013-2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * The server that clients attach to to learn about content updates. See
 * lib/Wrench/Application/contentSyncBroadcasterApplication.php for logic
 *
 */

// auto-load classes
require(__DIR__."/lib/SplClassLoader.php");

// load wrench
$loader = new SplClassLoader('Wrench', __DIR__.'/lib');
$loader->register();

// parse the main config for the content sync port
if (!($config = @parse_ini_file(__DIR__."/../config/config.ini"))) {
	print "Missing the configuration file. Please build it using the Pattern Lab builder.\n";
	exit;	
}

// give it a default port
$port     = ($config) ? trim($config['autoReloadPort']) : '8001';
$args     = getopt("s");
$newlines = (isset($args["s"])) ? true : false;

// start the content sync server
$server   = new \Wrench\Server('ws://0.0.0.0:'.$port.'/', array());

// register the application
$server->registerApplication('autoreload', new \Wrench\Application\AutoReloadApplication($newlines));

if (!isset($args["s"])) {
	print "\n";
	print "Auto-reload Server Started...\n";
	print "Use CTRL+C to stop this service...\n";
}

// run it
$server->run();
