<?php

/*!
 * Util Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Generic utilities for Pattern Lab
 *
 */

namespace PatternLab;

use \PatternLab\Config;

class Util {
	
	/**
	* Delete patterns and user-created directories and files in public/
	*/
	public static function cleanPublic() {
		
		// make sure patterns exists before trying to clean it
		if (is_dir(Config::$options["patternPublicDir"])) {
			
			$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(Config::$options["patternPublicDir"]), \RecursiveIteratorIterator::CHILD_FIRST);
			
			// make sure dots are skipped
			$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
			
			// for each file figure out what to do with it
			foreach($objects as $name => $object) {
				
				if ($object->isDir()) {
					// if this is a directory remove it
					rmdir($name);
				} else if ($object->isFile() && ($object->getFilename() != "README")) {
					// if this is a file remove it
					unlink($name);
				}
				
			}
			
		}
		
		// scan source/ & public/ to figure out what directories might need to be cleaned up
		$sourceDirs = glob(Config::$options["sourceDir"]."/*",GLOB_ONLYDIR);
		$publicDirs = glob(Config::$options["publicDir"]."/*",GLOB_ONLYDIR);
		
		// make sure some directories aren't deleted
		$ignoreDirs = array("styleguide","snapshots");
		foreach ($ignoreDirs as $ignoreDir) {
			$key = array_search(Config::$options["publicDir"]."/".$ignoreDir,$publicDirs);
			if ($key !== false){
				unset($publicDirs[$key]);
			}
		}
		
		// compare source dirs against public. remove those dirs w/ an underscore in source/ from the public/ list
		foreach ($sourceDirs as $sourceDir) {
			$cleanDir = str_replace(Config::$options["sourceDir"]."/","",$sourceDir);
			if ($cleanDir[0] == "_") {
				$key = array_search(Config::$options["publicDir"]."/".str_replace("_","",$cleanDir),$publicDirs);
				if ($key !== false){
					unset($publicDirs[$key]);
				}
			}
		}
		
		// for the remaining dirs in public delete them and their files
		foreach ($publicDirs as $dir) {
			
			$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir), \RecursiveIteratorIterator::CHILD_FIRST);
			
			// make sure dots are skipped
			$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
			
			foreach($objects as $name => $object) {
				
				if ($object->isDir()) {
					rmdir($name);
				} else if ($object->isFile()) {
					unlink($name);
				}
				
			}
			
			rmdir($dir);
			
		}
		
	}
	
	/**
	* Go through data and replace any values that match items from the link.array
	* @param  {String}       an entry from one of the list-based config entries
	*
	* @return {String}       trimmed version of the given $v var
	*/
	public static function compareReplace(&$value) {
		if (is_string($value)) {
			$valueCheck = strtolower($value);
			$valueThin  = str_replace("link.","",$valueCheck);
			if ((strpos($valueCheck, 'link.') !== false) && array_key_exists($valueThin,Data::$store["link"])) {
				$value = Data::$store["link"][$valueThin];
			}
		}
		
	}
	
	/**
	* Lowercase the given string. Used in the array_walk() function in __construct as a sanity check
	* @param  {String}       an entry from one of the list-based config entries
	*
	* @return {String}       lowercased version of the given $v var
	*/
	public static function strtolower(&$v) {
		$v = strtolower($v);
	}
	
	/**
	* Trim a given string. Used in the array_walk() function in __construct as a sanity check
	* @param  {String}       an entry from one of the list-based config entries
	*
	* @return {String}       trimmed version of the given $v var
	*/
	public static function trim(&$v) {
		$v = trim($v);
	}
	
	/**
	* Write out the time tracking file so the content sync service will work. A holdover
	* from how I put together the original AJAX polling set-up.
	*/
	public static function updateChangeTime() {
		
		if (is_dir(Config::$options["publicDir"]."/")) {
			file_put_contents(Config::$options["publicDir"]."/latest-change.txt",time());
		} else {
			print "Either the public directory for Pattern Lab doesn't exist or the builder is in the wrong location. Please fix.";
			exit;
		}
		
	}
	
}
