<?php

/*!
 * Pattern Lab Generator Class - v0.6.2
 *
 * Copyright (c) 2013 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Compiles and moves all files in the source/patterns dir to public/patterns dir ONCE.
 * Vast majority of logic is in builder.lib.php
 *
 */

class Generatr extends Buildr {
	
	/**
	* Use the Builder __construct to gather the config variables
	*/
	public function __construct() {
		
		// construct the parent
		parent::__construct();
		
	}
	
	/**
	* Pulls together a bunch of functions from builder.lib.php in an order that makes sense
	* @param  {Boolean}       decide if CSS should be parsed and saved. performance hog.
	*/
	public function generate($enableCSS = false) {
		
		$timePL = true; // track how long it takes to generate a PL site
		
		if ($timePL) {
			$mtime = microtime(); 
			$mtime = explode(" ",$mtime); 
			$mtime = $mtime[1] + $mtime[0]; 
			$starttime = $mtime;
		}
		
		if ($enableCSS) {
			
			// enable CSS globally throughout PL
			$this->enableCSS = true;
			
			// initialize CSS rule saver
			$this->initializeCSSRuleSaver();
			
			print "CSS generation enabled. This could take a few seconds...\n";
			
		}
		
		
		
		// clean the public directory to remove old files
		$this->cleanPublic();
		
		// gather data
		$this->gatherData();
		
		// render out the patterns and move them to public/patterns
		$this->generatePatterns();
		
		// render out the index and style guide
		$this->generateMainPages();
		
		// iterate over the data files and regenerate the entire site if they've changed
		$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__."/../../source/_data/"), RecursiveIteratorIterator::SELF_FIRST);
		
		// make sure dots are skipped
		$objects->setFlags(FilesystemIterator::SKIP_DOTS);
		
		foreach($objects as $name => $object) {
			
			$fileName = str_replace(__DIR__."/../../source/_data".DIRECTORY_SEPARATOR,"",$name);
			if (($fileName[0] != "_") && $object->isFile()) {
				$this->moveStaticFile("_data/".$fileName,"","_data","data");
			}
			
		}
		
		// iterate over all of the other files in the source directory
		$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__."/../../source/"), RecursiveIteratorIterator::SELF_FIRST);
		
		// make sure dots are skipped
		$objects->setFlags(FilesystemIterator::SKIP_DOTS);
		
		foreach($objects as $name => $object) {
			
			// clean-up the file name and make sure it's not one of the pattern lab files or to be ignored
			$fileName = str_replace(__DIR__."/../../source".DIRECTORY_SEPARATOR,"",$name);
			if (($fileName[0] != "_") && (!in_array($object->getExtension(),$this->ie)) && (!in_array($object->getFilename(),$this->id))) {
				
				// catch directories that have the ignored dir in their path
				$ignoreDir = $this->ignoreDir($fileName);
				
				// check to see if it's a new directory
				if (!$ignoreDir && $object->isDir() && !is_dir(__DIR__."/../../public/".$fileName)) {
					mkdir(__DIR__."/../../public/".$fileName);
				}
				
				// check to see if it's a new file or a file that has changed
				if (!$ignoreDir && $object->isFile() && (!file_exists(__DIR__."/../../public/".$fileName))) {
					$this->moveStaticFile($fileName);
				}
				
			}
			
		}
		
		// update the change time so the auto-reload will fire (doesn't work for the index and style guide)
		$this->updateChangeTime();
		
		if ($timePL) {
			$mtime = microtime(); 
			$mtime = explode(" ",$mtime); 
			$mtime = $mtime[1] + $mtime[0]; 
			$endtime = $mtime; 
			$totaltime = ($endtime - $starttime); 
			print "PL site generation took ".$totaltime." seconds...\n";
		}
		
	}
	
}