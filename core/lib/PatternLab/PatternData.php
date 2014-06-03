<?php

/*!
 * Pattern Data Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Gather together all of the general information related to patterns into one central location
 *
 */

namespace PatternLab;

use \PatternLab\Config;
use \PatternLab\PatternData\Exporters\DataLinkExporter;
use \PatternLab\PatternData\Exporters\DataMergeExporter;
use \PatternLab\PatternData\Exporters\NavItemsExporter;
use \PatternLab\PatternData\Exporters\PatternPartialsExporter;
use \PatternLab\PatternData\Exporters\PatternPathSrcExporter;
use \PatternLab\PatternData\Exporters\ViewAllPathsExporter;
use \PatternLab\PatternData\Helpers\LineageHelper;
use \PatternLab\PatternData\Helpers\PatternCodeHelper;
use \PatternLab\PatternData\Helpers\PatternStateHelper;
use \PatternLab\PatternData\Helpers\Plugins\KSSHelperPlugin;

class PatternData {
	
	public static $store               = array();
	public static $patternSubtype      = "";
	public static $patternSubtypeClean = "";
	public static $patternSubtypeDash  = "";
	public static $patternSubtypeSet   = false;
	public static $patternType         = "";
	public static $patternTypeClean    = "";
	public static $patternTypeDash     = "";
	public static $rules               = array();
	public static $dirSep              = DIRECTORY_SEPARATOR;
	
	/**
	* Check to see if the given pattern type has a pattern subtype associated with it
	* @param  {String}        the name of the pattern
	*
	* @return {Boolean}       if it was found or not
	*/
	public static function hasPatternSubtype($patternType) {
		foreach (self::$store as $patternStoreKey => $patternStoreData) {
			if (($patternStoreData["category"] == "patternSubtype") && ($patternStoreData["typeDash"] == $patternType)) {
				return true;
			}
		}
		return false;
	}
	
	/**
	* Gather all of the information related to the patterns
	*/
	public static function gather($options = array()) {
		
		// load up the rules for parsing patterns and the directories
		self::loadRules($options);
		
		// iterate over the patterns & related data and regenerate the entire site if they've changed
		$patternObjects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__.Config::$options["patternSourceDir"]), \RecursiveIteratorIterator::SELF_FIRST);
		$patternObjects->setFlags(\FilesystemIterator::SKIP_DOTS);
		
		$patternObjects = iterator_to_array($patternObjects);
		ksort($patternObjects);
		
		foreach ($patternObjects as $name => $object) {
			
			$ext      = $object->getExtension();
			$isDir    = $object->isDir();
			$isFile   = $object->isFile();
			
			$path     = str_replace(__DIR__.Config::$options["patternSourceDir"],"",$object->getPath());
			$pathName = str_replace(__DIR__.Config::$options["patternSourceDir"],"",$object->getPathname());
			$name     = $object->getFilename();
			$depth    = substr_count($pathName,DIRECTORY_SEPARATOR);
			
			// iterate over the rules and see if the current file matches one, if so run the rule
			foreach (self::$rules as $rule) {
				if ($rule->test($depth, $ext, $isDir, $isFile, $name)) {
					$rule->run($depth, $ext, $path, $pathName, $name);
				}
			}
		
		}
		
		// make sure all of the appropriate pattern data is pumped into $this->d for rendering patterns
		$dataLinkExporter       = new DataLinkExporter();
		$dataLinkExporter->run();
		
		// make sure all of the appropriate pattern data is pumped into $this->d for rendering patterns
		$dataMergeExporter       = new DataMergeExporter();
		$dataMergeExporter->run();
		
		// add the lineage info to PatternData::$store
		$lineageHelper           = new LineageHelper();
		$lineageHelper->run();
		
		// using the lineage info update the pattern states on PatternData::$store
		$patternStateHelper      = new PatternStateHelper();
		$patternStateHelper->run();
		
		// set-up code pattern paths
		$ppdExporter             = new PatternPathSrcExporter();
		$patternPathSrc          = $ppdExporter->run();
		$options                 = array();
		$options["patternPaths"] = $patternPathSrc;
		
		// render out all of the patterns and store the generated info in PatternData::$store
		$patternCodeHelper       = new PatternCodeHelper($options);
		$patternCodeHelper->run();
		
		// loop through and check KSS (this will change in the future)
		$KSSHelper               = new KSSHelperPlugin($options);
		$KSSHelper->run();
		
	}
	
	/**
	* Load all of the rules related to Pattern Data
	*/
	public static function loadRules($options) {
		foreach (glob(__DIR__."/PatternData/Rules/*.php") as $filename) {
			$rule      = str_replace(".php","",str_replace(__DIR__."/PatternData/Rules/","",$filename));
			$ruleClass = "\PatternLab\PatternData\Rules\\".$rule;
			self::$rules[] = new $ruleClass($options);
		}
	}
	
}