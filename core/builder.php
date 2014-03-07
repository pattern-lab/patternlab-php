<?php

/*!
 * Pattern Lab Builder CLI - v0.7.8
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
 * 	php builder.php -gp
 * 		Generates only the patterns a site. Does NOT clean public/ when generating the site.
 * 	
 * 	php builder.php -w
 * 		Generates the site like the -g flag and then watches for changes in the 'source' directories &
 * 		files. Will re-generate files if they've changed.
 * 	
 * 	php builder.php -wr
 * 		In addition to the -w flag features it will also automatically start the auto-reload server.
 * 	
 * 	php builder.php -wp
 * 		Similar to the -w flag but it only generates and then watches the patterns. Does NOT clean public/ when generating the site.
 * 	
 * 	php builder.php -v
 * 		Prints out the current version of Pattern Lab.
 *
 */

// check to see if json_decode exists. might be disabled in installs of PHP 5.5
if (!function_exists("json_decode")) {
	print "Please check that your version of PHP includes the JSON extension. It's required for Pattern Lab to run. Aborting.\n";
	exit;
}

// auto-load classes
require(__DIR__."/lib/SplClassLoader.php");

$loader = new SplClassLoader('PatternLab', __DIR__.'/lib');
$loader->register();

$loader = new SplClassLoader('Mustache', __DIR__.'/lib');
$loader->setNamespaceSeparator("_");
$loader->register();

// make sure this script is being accessed from the command line
if (php_sapi_name() != 'cli') {
	print "The builder script can only be run from the command line.\n";
	exit;
}

// grab the arguments from the command line
$args = getopt("gwcrvpn");

// load Pattern Lab's config, if first time set-up move files appropriately too
$co     = new PatternLab\Configurer;
$config = $co->getConfig();

// show the version of Pattern Lab
if (isset($args["v"])) {
	print "You're running v".$config["v"]." of the PHP version of Pattern Lab.\n";
	exit;
}

// generate the pattern lab site if appropriate
if (isset($args["g"]) || isset($args["w"])) {
	
	$g = new PatternLab\Generator($config);
	
	// set some default values
	$enableCSS     = false;
	$moveStatic    = true;
	$noCacheBuster = false;
	
	// check to see if CSS for patterns should be parsed & outputted
	if (isset($args["c"]) && !isset($args["w"])) {
		$enableCSS = true;
	}
	
	// check to see if we should just generate the patterns
	if (isset($args["p"])) {
		$moveStatic = false;
	}
	
	// check to see if we should turn off the cachebuster value
	if (isset($args["n"])) {
		$noCacheBuster = true;
	}
	
	$g->generate($enableCSS,$moveStatic,$noCacheBuster);
	
	// have some fun
	if (!isset($args["w"])) {
		$g->printSaying();
	}
	
}

// watch the source directory and regenerate any changed files
if (isset($args["w"])) {
	
	$w = new PatternLab\Watcher($config);
	
	// set some default values
	$reload = false;
	
	if (isset($args["r"])) {
		$reload = true;
	}
	
	$w->watch($reload,$moveStatic);
	
}

// when in doubt write out the usage
if (!isset($args["g"]) && !isset($args["w"]) && !isset($args["v"])) {
	
	print "\n";
	print "Usage:\n\n";
	print "  php ".$_SERVER["PHP_SELF"]." -g\n";
	print "    Iterates over the 'source' directories & files and generates the entire site a single time.\n";
	print "    It also cleans the 'public' directory.\n\n";
	print "  php ".$_SERVER["PHP_SELF"]." -gc\n";
	print "    In addition to the -g flag features it will also generate CSS for each pattern. Resource intensive.\n\n";
	print "  php ".$_SERVER["PHP_SELF"]." -gp\n";
	print "    Generates only the patterns a site. Does NOT clean public/ when generating the site.\n\n";
	print "  php ".$_SERVER["PHP_SELF"]." -w\n";
	print "    Generates the site like the -g flag and then watches for changes in the 'source' directories &\n";
	print "    files. Will re-generate files if they've changed.\n\n";
	print "  php ".$_SERVER["PHP_SELF"]." -wr\n";
	print "    In addition to the -w flag features it will also automatically start the auto-reload server.\n\n";
	print "  php ".$_SERVER["PHP_SELF"]." -wp\n";
	print "    Similar to the -w flag but it only generates and then watches the patterns. Does NOT clean public/ when generating the site.\n\n";
	print "  php ".$_SERVER["PHP_SELF"]." -v\n";
	print "    Prints out the current version of Pattern Lab.\n\n";
	
}
