<?php

/*!
 * Pattern Lab Generator Class - v0.7.12
 *
 * Copyright (c) 2013-2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Compiles and moves all files in the source/patterns dir to public/patterns dir ONCE.
 * Vast majority of logic is in builder.lib.php
 *
 */

namespace PatternLab;

class Generator extends Builder {
	
	/**
	* Use the Builder __construct to gather the config variables
	*/
	public function __construct($config = array()) {
		
		// construct the parent
		parent::__construct($config);
		
	}
	
	/**
	* Pulls together a bunch of functions from builder.lib.php in an order that makes sense
	* @param  {Boolean}       decide if CSS should be parsed and saved. performance hog.
	* @param  {Boolean}       decide if static files like CSS and JS should be moved
	*/
	public function generate($enableCSS = false, $moveStatic = true, $noCacheBuster = false) {
		
		$timePL = true; // track how long it takes to generate a PL site
		
		if ($timePL) {
			$mtime = microtime(); 
			$mtime = explode(" ",$mtime); 
			$mtime = $mtime[1] + $mtime[0]; 
			$starttime = $mtime;
		}
		
		$this->noCacheBuster = $noCacheBuster;
		
		if ($enableCSS) {
			
			// enable CSS globally throughout PL
			$this->enableCSS = true;
			
			// initialize CSS rule saver
			$this->initializeCSSRuleSaver();
			print "CSS generation enabled. This could take a few seconds...\n";
			
		}
		
		// gather up all of the data to be used in patterns
		$this->gatherData();
		
		// gather all of the various pattern info
		$this->gatherPatternInfo();
		
		// clean the public directory to remove old files
		if (($this->cleanPublic == "true") && $moveStatic) {
			$this->cleanPublic();
		}
		
		// render out the patterns and move them to public/patterns
		$this->generatePatterns();
		
		// render out the index and style guide
		$this->generateMainPages();
		
		// make sure data exists
		if (!is_dir($this->pd."/data")) {
			mkdir($this->pd."/data");
		}
		
		// iterate over the data files and regenerate the entire site if they've changed
		$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->sd."/_data/"), \RecursiveIteratorIterator::SELF_FIRST);
		
		// make sure dots are skipped
		$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
		
		foreach($objects as $name => $object) {
			
			$fileName = str_replace($this->sd."/_data".DIRECTORY_SEPARATOR,"",$name);
			if (($fileName[0] != "_") && $object->isFile()) {
				$this->moveStaticFile("_data/".$fileName,"","_data","data");
			}
			
		}
		
		// move all of the files unless pattern only is set
		if ($moveStatic) {
			
			// iterate over all of the other files in the source directory
			$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->sd."/"), \RecursiveIteratorIterator::SELF_FIRST);
			
			// make sure dots are skipped
			$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
			
			foreach($objects as $name => $object) {
				
				// clean-up the file name and make sure it's not one of the pattern lab files or to be ignored
				$fileName = str_replace($this->sd.DIRECTORY_SEPARATOR,"",$name);
				if (($fileName[0] != "_") && (!in_array($object->getExtension(),$this->ie)) && (!in_array($object->getFilename(),$this->id))) {
					
					// catch directories that have the ignored dir in their path
					$ignoreDir = $this->ignoreDir($fileName);
					
					// check to see if it's a new directory
					if (!$ignoreDir && $object->isDir() && !is_dir($this->pd."/".$fileName)) {
						mkdir($this->pd."/".$fileName);
					}
					
					// check to see if it's a new file or a file that has changed
					if (!$ignoreDir && $object->isFile() && (!file_exists($this->pd."/".$fileName))) {
						$this->moveStaticFile($fileName);
					}
					
				}
				
			}
			
		}
		
		// update the change time so the auto-reload will fire (doesn't work for the index and style guide)
		$this->updateChangeTime();
		
		print "your site has been generated...\n";
		
		// print out how long it took to generate the site
		if ($timePL) {
			$mtime = microtime();
			$mtime = explode(" ",$mtime);
			$mtime = $mtime[1] + $mtime[0];
			$endtime = $mtime;
			$totaltime = ($endtime - $starttime);
			$mem = round((memory_get_peak_usage(true)/1024)/1024,2);
			print "site generation took ".$totaltime." seconds and used ".$mem."MB of memory...\n";
		}
		
	}
	
	/**
	* Randomly prints a saying after the generate is complete
	*/
	public function printSaying() {
		
		$randomNumber = rand(0,60);
		$sayings = array(
		                   "have fun storming the castle",
		                   "be well, do good work, and keep in touch",
		                   "may the sun shine, all day long",
		                   "smile",
		                   "namaste",
		                   "walk as if you are kissing the earth with your feet",
		                   "to be beautiful means to be yourself",
		                   "i was thinking of the immortal words of socrates, who said \"...i drank what?\"",
		                   "let me take this moment to compliment you on your fashion sense, particularly your slippers",
		                   "42",
		                   "he who controls the spice controls the universe",
		                   "the greatest thing you'll ever learn is just to love and be loved in return",
		                   "nice wand",
		                   "i don't have time for a grudge match with every poseur in a parka"
		                );
		if (isset($sayings[$randomNumber])) {
			print $sayings[$randomNumber]."...\n";
		}
		
	}
	
}