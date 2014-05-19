<?php

/*!
 * Pattern Lab Pattern Info Class - v0.7.12
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Copy public/ into a snapshot/v* directory
 *
 */

namespace PatternLab;

class PatternInfo {
	
	public static $bi                 = 0;
	public static $ni                 = 0;
	public static $d                  = array();
	public static $navItems           = array();
	public static $patternLineages    = array();
	public static $patternPaths       = array();
	public static $patternPartials    = array();
	public static $patternSubtype     = "";
	public static $patternSubtypeSet  = false;
	public static $patternSubtypeDash = "";
	public static $patternType        = "";
	public static $patternTypes       = array();
	public static $patternTypeDash    = "";
	public static $rules              = array();
	public static $viewAllPaths       = array();
	
	public static function gather($options) {
		
		// iterate over the patterns & related data and regenerate the entire site if they've changed
		$patternObjects  = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($options["patternSourceDir"]), \RecursiveIteratorIterator::SELF_FIRST);
		$patternObjects->setFlags(\FilesystemIterator::SKIP_DOTS);
		
		$patternObjects = iterator_to_array($patternObjects);
		ksort($patternObjects);
		
		self::$d["link"]                = array();
		self::$navItems["patternTypes"] = array();
		
		foreach ($patternObjects as $name => $object) {
			
			$ext      = $object->getExtension();
			$isDir    = $object->isDir();
			$isFile   = $object->isFile();
			$path     = $object->getPath();
			$pathName = $object->getPathname();
			$name     = $object->getFilename();
			
			$depth    = substr_count(str_replace($options["patternSourceDir"],"",$pathName),DIRECTORY_SEPARATOR);
			
			foreach (self::$rules as $rule) {
				if ($rule->testRule($depth, $ext, $isDir, $isFile, $name)) {
					$rule->runRule($depth, $ext, $path, $pathName, $name);
				}
			}
		
		}
		
	}
	
	public static function loadRules($options) {
		foreach (glob(__DIR__."/PatternInfoRules/*.php") as $filename) {
			$rule      = str_replace(".php","",str_replace(__DIR__."/PatternInfoRules/","",$filename));
			$ruleClass = "PatternLab\PatternInfoRules\\".$rule;
			self::$rules[] = new $ruleClass($options);
		}
	}
	
}