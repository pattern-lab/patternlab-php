<?php

/*!
 * Pattern Lab Builder CLI - v0.6.1
 *
 * Copyright (c) 2013 Dave Olsen, http://dmolsen.com
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
 */

// load builder classes
require __DIR__."/lib/builder.lib.php";
require __DIR__."/lib/generator.lib.php";
require __DIR__."/lib/watcher.lib.php";

// load mustache & register it
require __DIR__."/lib/Mustache/Autoloader.php";
Mustache_Autoloader::register();

// load css rule saver
require __DIR__."/lib/css-rule-saver/css-rule-saver.php";

// make sure this script is being accessed from the command line
if (php_sapi_name() == 'cli') {
	
	$args = getopt("gwcr");
	
	if (isset($args["g"])) {
		
		// initiate the g (generate) switch
		
		// iterate over the source directory and generate the site
		$g = new Generatr();
		
		// check to see if CSS for patterns should be parsed & outputted
		(isset($args["c"])) ? $g->generate(true) : $g->generate();
		
		print "your site has been generated...\n";
		
	} else if (isset($args["w"])) {
		
		// initiate the w (watch) switch
		
		// iterate over the source directory and generate the site
		$g = new Generatr();
		$g->generate();
		print "your site has been generated...\n";
		
		// watch the source directory and regenerate any changed files
		$w = new Watchr();
		print "watching your site for changes...\n";
		if (isset($args["r"])) {
			print "starting page auto-reload...\n";
			$w->watch(true);
		} else {
			$w->watch();
		}
		
	} else {
		
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
		
	}

} else {

	print "The builder script can only be run from the command line.";

}
