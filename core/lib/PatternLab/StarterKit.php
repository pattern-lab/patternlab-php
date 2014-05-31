<?php

/*!
 * StarterKit Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Copy a starter kit from GitHub and put it into source/
 *
 */

namespace PatternLab;

use \Alchemy\Zippy\Zippy;
use \PatternLab\Config;

class StarterKit {
	
	/**
	* Set-up a default var
	*/
	public function __construct() {
		if (!is_dir(Config::$options["sourceDir"])) {
			print "Check to make sure your source directory is configured properly...\n";
			exit;
		}
	}
	
	/**
	 * Fetch the starter kit from GitHub and put it into source/
	 * @param  {String}    path of the GitHub repo
	 *
	 * @return {String}    the modified file contents
	 */
	public function fetch($starterKit) {
		
		// see if the user passed anythign useful
		if (empty($starterKit)) {
			print "please provide a path for the starter kit before trying to fetch it...\n";
			exit;
		}
		
		// figure out the options for the GH path
		list($org,$repo,$tag) = $this->getStarterKitInfo($starterKit);
		
		//get the path to the GH repo and validate it
		$tarballUrl = "https://github.com/".$org."/".$repo."/archive/".$tag.".tar.gz";
		
		print "downloading the starter kit...\n";
		
		// try to download the given starter kit
		if (!$starterKit = @file_get_contents($tarballUrl)) {
			$error = error_get_last();
			print "starter kit wasn't downloaded because:\n  ".$error["message"]."\n";
			exit;
		}
		
		// write the starter kit to the temp directory
		$tempFile = tempnam(sys_get_temp_dir(), "pl-sk-archive.tar.gz");
		file_put_contents($tempFile, $starterKit);
		
		// see if the source directory is empty
		$emptyDir = true;
		$objects  = new \DirectoryIterator(Config::$options["sourceDir"]);
		foreach ($objects as $object) {
			if (!$object->isDot() && ($object->getFilename() != "README") && ($object->getFilename() != ".DS_Store")) {
				$emptyDir = false;
			}
		}
		
		print "installing the starter kit...\n";
		
		// if source directory isn't empty ask if it's ok to nuke what's there
		if (!$emptyDir) {
			$stdin = fopen("php://stdin", "r");
			print("delete everything in source/ before installing the starter kit? Y/n\n");
			$answer = strtolower(trim(fgets($stdin)));
			fclose($stdin);
			if ($answer == "y") {
				
				print "nuking everything in source/...\n";
				
				$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(Config::$options["sourceDir"]), \RecursiveIteratorIterator::CHILD_FIRST);
				
				// make sure dots are skipped
				$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
				
				foreach($objects as $name => $object) {
					
					if ($object->isDir()) {
						rmdir($name);
					} else if ($object->isFile()) {
						unlink($name);
					}
					
				}
				
			} else {
				print "aborting install of the starter kit...\n";
				unlink($tempFile);
				exit;
			}
			
		}
		
		// extract
		$zippy      = Zippy::load();
		$zipAdapter = $zippy->getAdapterFor('tar.gz');
		$archiveZip = $zipAdapter->open($tempFile);
		$archiveZip = $archiveZip->extract(Config::$options["sourceDir"]);
		
		// remove the temp file
		unlink($tempFile);
		
		print "starter kit installation complete...\n";
		
	}
	
	/**
	 * Break up the starterKit path
	 * @param  {String}    path of the GitHub repo
	 *
	 * @return {Array}     the parts of the starter kit path
	 */
	protected function getStarterKitInfo($starterKit) {
		
		$org  = "";
		$repo = "";
		$tag  = "master";
		
		if (strpos($starterKit, "#") !== false) {
			list($starterKit,$tag) = explode("#",$starterKit);
		}
		
		if (strpos($starterKit, "/") !== false) {
			list($org,$repo) = explode("/",$starterKit);
		} else {
			print "please provide a real path to a starter kit...\n";
			exit;
		}
		
		return array($org,$repo,$tag);
		
	}
	
}