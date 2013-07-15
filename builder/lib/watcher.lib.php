<?php

/*!
 * Pattern Lab Watcher Class - v0.1
 *
 * Copyright (c) 2013 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Watches the source/patterns dir for any changes so they can be automagically
 * moved to the public/patterns dir.
 *
 * This is not the most efficient implementation of a directory watch but I hope
 * it's the most platform agnostic.
 *
 */

class Watcher extends Builder {
	
	/**
	* Use the Builder __construct to gather the config variables
	*/
	public function __construct() {
		
		// construct the parent
		parent::__construct();
		
	}
	
	/**
	* Watch the source directory for any changes to existing files. Will run forever if given the chance
	*/
	public function watch() {
		
		$c = false;          // have the files been added to the overall object?
		$t = false;          // was their a change found? re-render
		$k = false;          // was the entry not a part of the $o object? make sure it's hashes are added
		$m = false;          // does the index page need to be regenerated?
		$o = new stdClass(); // create an object to hold the properties
		
		// build patternTypesRegex for getEntry
		$this->getPatternTypesRegex(); 
		
		// run forever
		while (true) {
			
			foreach ($this->patternTypes as $patternType) {
				
				// generate all of the patterns
				$entries = glob(__DIR__.$this->sp.$patternType."/*/*.mustache");
				
				foreach($entries as $entry) {
					
					$patternParts = explode("/",$this->getEntry($entry));
					
					// because we're globbing i need to check again to see if the pattern should be ignored
					if ($patternParts[2][0] != "_") {
						
						// figure out how to watch for new directories and new files
						if (!isset($o->$entry)) {
							$o->$entry = new stdClass();
							$k = true;
						}
						
						// figure out the md5 hash of a file so we can track changes
						// runs well on a solid state drive. no idea if it thrashes regular disks
						$ph = $this->md5File($entry);
						
						// if the directory wasn't being checked already add the md5 sums
						if ($k) {
							
							$o->$entry->ph = $ph;
							
							// if we're through the first check make sure to note any new directories being added to Pattern Lab
							// assuming a pattern actually exists
							if ($c && ($o->$entry->ph != '')) {
								$patternName = $this->getEntry($entry);
								print $patternName." added to Pattern Lab. You should reload the page to see it in the nav...\n";
								$t = true;
								$m = true;
							}
							
							$k = false;
							
						} else {
							
							if ($o->$entry->ph != $ph) {
								
								$patternName = $this->getEntry($entry);
								if ($c && ($o->$entry->ph == '')) {
									print $patternName." added to Pattern Lab. You should reload the page to see it in the nav...\n";
									$m = true;
								} else {
									print $patternName." changed...\n";
								}
								
								$t = true;
								$o->$entry->ph = $ph;
								
							}
							
						}
						
						// if a file has been added or changed then render & move the *entire* project (shakes fist at partials)
						// if a new directory was added regenerate the main pages
						// also update the change time so that content sync will work properly
						if ($t) {
							$this->gatherData();
							$this->renderAndMove();
							$this->generateViewAllPages();
							$this->updateChangeTime();
							if ($m) {
								$this->generateMainPages();
								$m = false;
							}
							$t = false;
						}
						
					}
					
				}
			}
			
			// iterate over the data files and regenerate the entire site if they've changed
			$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__."/../../source/_data/"), RecursiveIteratorIterator::SELF_FIRST);
			foreach($objects as $name => $object) {
				
				// md5 hash the file to be *sure* its changed
				$dh = $this->md5File($name);
				$fileName = str_replace(__DIR__."/../../source/_data/","",$name);
				
				if (!isset($o->$fileName)) {
					$o->$fileName = $dh;
				} else {
					if ($o->$fileName != $dh) {
						$o->$fileName = $dh;
						$this->gatherData();
						$this->renderAndMove();
						$this->generateViewAllPages();
						$this->updateChangeTime();
						print "_data/".$fileName." changed...\n";
					}
				}
				
			}
			
			// iterate over all of the other files in the source directory and move them if their modified time has changed
			$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__."/../../source/"), RecursiveIteratorIterator::SELF_FIRST);
			foreach($objects as $name => $object) {
				
				// clean-up the file name and make sure it's not one of the pattern lab files or to be ignored
				$fileName = str_replace(__DIR__."/../../source/","",$name);
				if (($fileName[0] != "_") && (!in_array($object->getExtension(),$this->ie)) && (!in_array($object->getFilename(),$this->id))) {
					
					// catch directories that have the ignored dir in their path
					$ignoreDir = $this->ignoreDir($fileName);
					
					// check to see if it's a new directory
					if (!$ignoreDir && $object->isDir() && !isset($o->$fileName) && !is_dir(__DIR__."/../../public/".$fileName)) {
						mkdir(__DIR__."/../../public/".$fileName);
						$o->$fileName = "dir created"; // placeholder
						print $fileName."/ directory was created...\n";
					}
					
					// check to see if it's a new file or a file that has changed
					if (file_exists($name)) {
						
						$mt = $object->getMTime();
						if (!$ignoreDir && $object->isFile() && !isset($o->$fileName) && !file_exists(__DIR__."/../../public/".$fileName)) {
							$o->$fileName = $mt;
							$this->moveStaticFile($fileName,"added");
						} else if (!$ignoreDir && $object->isFile() && isset($o->$fileName) && ($o->$fileName != $mt)) {
							$o->$fileName = $mt;
							$this->moveStaticFile($fileName,"changed");
						} else if (!isset($o->fileName)) {
							$o->$fileName = $mt;
						}
						
					} else {
						unset($o->$fileName);
					}
					
				}
				
			}
			
			$c = true;
			
		}
		
	}
	
	/**
	* Converts a given file into an md5 string
	* @param  {String}       file name to be hashed
	*
	* @return {String}       md5 string of the file or an empty string if the file wasn't found
	*/
	private function md5File($f) {
		$r = file_exists($f) ? md5_file($f) : '';
		return $r;
	}
	
}
