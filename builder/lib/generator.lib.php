<?php

/*!
 * Pattern Lab Generator Class - v0.3.4
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
	*/
	public function generate() {
		
		// gather data
		$this->gatherData();
		
		// render out the patterns and move them to public/patterns
		$this->generatePatterns();
		
		// render out the index and style guide
		$this->generateMainPages();
		
		// iterate over all of the other files in the source directory and move them if their modified time has changed
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
				if (!$ignoreDir && $object->isFile() && (!file_exists(__DIR__."/../../public/".$fileName) || ($object->getMTime() > filemtime(__DIR__."/../../public/".$fileName)))) {
					$this->moveStaticFile($fileName);
				}
				
			}
			
		}
		
		// update the change time so the auto-reload will fire (doesn't work for the index and style guide)
		$this->updateChangeTime();
		
	}
	
}