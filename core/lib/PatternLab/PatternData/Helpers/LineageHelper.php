<?php

/*!
 * Pattern Data Lineage Helper Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Finds and adds lineage information to the PatternData::$store
 *
 */

namespace PatternLab\PatternData\Helpers;

use \PatternLab\Config;
use \PatternLab\PatternData;

class LineageHelper extends \PatternLab\PatternData\Helper {
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		
	}
	
	public function run() {
		
		$foundLineages = array();
		
		// check for the regular lineages in only normal patterns
		foreach (PatternData::$store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && (!isset($patternStoreData["pseudo"]))) {
				
				$patternLineages = array();
				$fileName        = $patternStoreData["pathName"].".".Config::$options["patternExtension"];
				$fileNameFull    = __DIR__."/../..".Config::$options["patternSourceDir"].$fileName;
				
				if (file_exists($fileNameFull)) {
					$foundLineages = $this->findLineages($fileNameFull);
				}
				
				if (!empty($foundLineages)) {
					
					foreach ($foundLineages as $lineage) {
						
						if (isset(PatternData::$store[$lineage])) {
							
							$patternLineages[] = array("lineagePattern" => $lineage,
													   "lineagePath"    => "../../patterns/".$patternStoreData["pathDash"]."/".$patternStoreData["pathDash"].".html");
							
						} else {
							
							if (strpos($lineage, '/') === false) {
								print "You may have a typo in ".$fileName.". {{> ".$lineage." }} is not a valid pattern.\n";
							}
							
						}
						
					}
					
					// add the lineages to the PatternData::$store
					PatternData::$store[$patternStoreKey]["lineages"] = $patternLineages;
					
				}
				
			}
			
		}
		
		// handle all of those pseudo patterns
		foreach (PatternData::$store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && (isset($patternStoreData["pseudo"]))) {
				
				// add the lineages to the PatternData::$store
				$patternStoreKeyOriginal = $patternStoreData["original"];
				PatternData::$store[$patternStoreKey]["lineages"] = PatternData::$store[$patternStoreKeyOriginal]["lineages"];
				
			}
			
		}
		
		// check for the reverse lineages and skip pseudo patterns
		foreach (PatternData::$store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && (!isset($patternStoreData["pseudo"]))) {
				
				$patternLineagesR = array();
				
				foreach (PatternData::$store as $haystackKey => $haystackData) {
					
					if (($haystackData["category"] == "pattern") && (isset($haystackData["lineages"]))) {
						
						foreach ($haystackData["lineages"] as $haystackLineage) {
							
							if ($haystackLineage["lineagePattern"] == $patternStoreData["partial"]) {
								
								$foundAlready = false;
								foreach ($patternLineagesR as $patternCheck) {
									
									if ($patternCheck["lineagePattern"] == $patternStoreData["partial"]) {
										$foundAlready = true;
										break;
									}
								
								}
							
								if (!$foundAlready) {
									
									if (isset(PatternData::$store[$haystackKey])) {
										
										$path = PatternData::$store[$haystackKey]["pathDash"];
										$patternLineagesR[] = array("lineagePattern" => $haystackKey, 
																	"lineagePath"    => "../../patterns/".$path."/".$path.".html");
																
									}
								
								}
							
							}
						
						}
						
					}
					
				}
				
				PatternData::$store[$patternStoreKey]["lineagesR"] = $patternLineagesR;
				
			}
			
		}
		
		// handle all of those pseudo patterns
		foreach (PatternData::$store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && (isset($patternStoreData["pseudo"]))) {
				
				// add the lineages to the PatternData::$store
				$patternStoreKeyOriginal = $patternStoreData["original"];
				PatternData::$store[$patternStoreKey]["lineagesR"] = PatternData::$store[$patternStoreKeyOriginal]["lineagesR"];
				
			}
			
		}
		
	}
	
	
	/**
	* Get the lineage for a given pattern by parsing it and matching mustache partials
	* @param  {String}       the filename for the pattern to be parsed
	*
	* @return {Array}        a list of patterns
	*/
	protected function findLineages($filename) {
		$data = file_get_contents($filename);
		if (preg_match_all('/{{>([ ]+)?([A-Za-z0-9-_]+)(?:\:[A-Za-z0-9-]+)?(?:(| )\(.*)?([ ]+)?}}/',$data,$matches)) {
			return array_unique($matches[2]);
		}
		return array();
	}
	
}