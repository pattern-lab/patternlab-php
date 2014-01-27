<?php

/*!
 * Page Follow Server, v0.2
 *
 * Copyright (c) 2013-2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * The server that clients attach to to learn about page updates. See
 * lib/Wrench/Application/navSyncBroadcasterApplication.php for logic
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
$port = ($config) ? trim($config['pageFollowPort']) : '8000';

// start the content sync server
$server = new \Wrench\Server('ws://0.0.0.0:'.$port.'/', array());

// register the application & run it
$server->registerApplication('pagefollow', new \Wrench\Application\PageFollowApplication());

print "\n";
print "Page Follow Server Started...\n";
print "Use CTRL+C to stop this service...\n";

$server->run();
