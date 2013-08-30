<?php

/*!
 * Pattern Lab Builder CLI - v0.1
 *
 * Copyright (c) 2013 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Usage:
 *
 * 	php builder.php -g
 * 		Iterates over the 'source' directories & files and generates the entire site a single time.
 * 	
 * 	php builder.php -w
 * 		Generates the site like the -g flag and then watches for changes in the 'source' directories &
 * 		files. Will re-generate files if they've changed.
 *
 */

// load builder classes
require __DIR__."/lib/builder.lib.php";
require __DIR__."/lib/generator.lib.php";
require __DIR__."/lib/watcher.lib.php";

// load mustache & register it
require __DIR__."/lib/Mustache/Autoloader.php";
Mustache_Autoloader::register();

// make sure this script is being accessed from the command line
if (php_sapi_name() == 'cli') {
	
	$args = getopt("gw");
	
	if (isset($args["g"])) {
		
		// initiate the g (generate) switch
		
		// iterate over the source directory and generate the site
		$g = new Generator();
		$g->generate();
		print "your site has been generated...\n";
		
	} elseif (isset($args["w"])) {
		
		// initiate the w (watch) switch
		
		// iterate over the source directory and generate the site
		$g = new Generator();
		$g->generate();
		print "your site has been generated...\n";
		
		// watch the source directory and regenerate any changed files
		$w = new Watcher();
		print "watching your site for changes...\n";
		$w->watch();
		
	} else {
		
		// when in doubt write out the usage
		print "\n";
		print "Usage:\n\n";
		print "  php ".$_SERVER["PHP_SELF"]." -g\n";
		print "    Iterates over the 'source' directories & files and generates the entire site a single time.\n\n";
		print "  php ".$_SERVER["PHP_SELF"]." -w\n";
		print "    Generates the site like the -g flag and then watches for changes in the 'source' directories &\n";
		print "    files. Will re-generate files if they've changed.\n\n";
		
	}

} else {

	print "The builder script can only be run from the command line.";

}
