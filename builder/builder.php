<?php

/*!
 * Pattern Lab Builder CLI - v0.6.2
 *
 * Copyright (c) 2013-2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Usage:
 *
 * 	php builder.php -g
 * 		Iterates over the 'source' directories & files and generates the entire site a single time.
 * 		It also cleans the 'public' directory.
 * 	
 * 	php builder/builder.php -gc
 * 		In addition to the -g flag features it will also generate CSS for each pattern. Resource instensive.
 * 	
 * 	php builder.php -w
 * 		Generates the site like the -g flag and then watches for changes in the 'source' directories &
 * 		files. Will re-generate files if they've changed.
 * 	
 * 	php builder.php -wr
 * 		In addition to the -w flag features it will also automatically start the auto-reload server.
 *
 * 	php builder.php -v
 * 		Prints out the current version of Pattern Lab.
 *
 */

// the version of pattern lab
const VERSION = "0.7.0dev2";

// auto-load classes
require(__DIR__."/lib/SplClassLoader.php");

$loader = new SplClassLoader('PatternLab', __DIR__.'/lib');
$loader->register();

$loader = new SplClassLoader('Mustache', __DIR__.'/lib');
$loader->setNamespaceSeparator("_");
$loader->register();

// make sure this script is being accessed from the command line
if (php_sapi_name() != 'cli') {
	print "The builder script can only be run from the command line.";
	exit;
}

$args = getopt("gwcrv");

// show the version of Pattern Lab
if (isset($args["v"])) {
	print "You're running v".VERSION." of the PHP version of Pattern Lab.\n";
	exit;
}
	
if (isset($args["g"]) || isset($args["w"])) {
		
	// iterate over the source directory and generate the site
	$g = new PatternLab\Generator();
	$c = false;
	
	// check to see if CSS for patterns should be parsed & outputted
	if (isset($args["c"]) && !isset($args["w"])) {
		$c = true;
	}
	
	$g->generate($c);
	
	print "your site has been generated...\n";
	
}

if (isset($args["w"])) {
	
	// watch the source directory and regenerate any changed files
	$w = new PatternLab\Watcher();
	$a = false;
	
	if (isset($args["r"])) {
		print "starting page auto-reload...\n";
		$a = true;
	}
	
	print "watching your site for changes...\n";
	
	$w->watch($a);
	
}
	
if (!isset($args["g"]) && !isset($args["w"]) && !isset($args["v"])) {
	
	// when in doubt write out the usage
	print "\n";
	print "Usage:\n\n";
	print "  php ".$_SERVER["PHP_SELF"]." -g\n";
	print "    Iterates over the 'source' directories & files and generates the entire site a single time.\n";
	print "    It also cleans the 'public' directory.\n\n";
	print "  php ".$_SERVER["PHP_SELF"]." -gc\n";
	print "    In addition to the -g flag features it will also generate CSS for each pattern. Resource instensive.\n\n";
	print "  php ".$_SERVER["PHP_SELF"]." -w\n";
	print "    Generates the site like the -g flag and then watches for changes in the 'source' directories &\n";
	print "    files. Will re-generate files if they've changed.\n\n";
	print "  php ".$_SERVER["PHP_SELF"]." -wr\n";
	print "    In addition to the -w flag features it will also automatically start the auto-reload server.\n\n";
	print "  php ".$_SERVER["PHP_SELF"]." -v\n";
	print "    Prints out the current version of Pattern Lab.\n\n";
	
}
