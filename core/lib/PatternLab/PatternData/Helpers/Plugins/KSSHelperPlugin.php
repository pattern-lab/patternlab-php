<?php

/*!
 * Pattern Data KSS Helper Plugin Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Find KSS info and then add it to the relevant patterns in PatternData::$store
 *
 */

namespace PatternLab\PatternData\Helpers\Plugins;

use \PatternLab\Config;
use \PatternLab\Data;
use \PatternLab\Parsers\Plugins\KSS;
use \PatternLab\PatternData;
use \PatternLab\PatternEngine;
use \PatternLab\Render;

class KSSHelperPlugin extends \PatternLab\PatternData\Helper {
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		
		$this->patternPaths = $options["patternPaths"];
		
	}
	
	public function run() {
		
		$options                 = array();
		$options["patternPaths"] = $this->patternPaths;
		PatternEngine::setup($options);
		
		$kss = KSS::parse(Config::$options["sourceDir"]);
		
		foreach (PatternData::$store as $patternStoreKey => $patternStoreData) {
			
			if ($patternStoreData["category"] == "pattern") {
				
				if ($kssSection = $kss->getSection($patternStoreKey)) {
					
					PatternData::$store[$patternStoreKey]["name"]       = $kssSection->getTitle();
					PatternData::$store[$patternStoreKey]["desc"]       = $kssSection->getDescription();
					PatternData::$store[$patternStoreKey]["descExists"] = true;
					$modifiers = $kssSection->getModifiers();
					
					if (!empty($modifiers)) {
						
						PatternData::$store[$patternStoreKey]["modifiersExist"] = true;
						$patternModifiers = array();
						
						foreach ($modifiers as $modifier) {
							
							$name               = $modifier->getName();
							$class              = $modifier->getClassName();
							$desc               = $modifier->getDescription();
							$code               = "";
							$modifierCodeExists = false;
							
							if ($name[0] != ":") {
								
								$data    = Data::getPatternSpecificData($patternStoreKey);
								$data    = array_merge($data,array("styleModifier" => $class));
								
								$srcPath = (isset($patternStoreData["pseudo"])) ? PatternData::$store[$patternStoreData["original"]]["pathName"] : $patternStoreData["pathName"];
								$code    = Render::Pattern($srcPath,$data);
								
								$modifierCodeExists    = true;
								
							}
							
							$patternModifiers[] = array("modifierName"       => $name,
														"modifierDesc"       => $desc,
														"modifierCode"       => $code,
														"modifierCodeExists" => $modifierCodeExists);
						}
						
						PatternData::$store[$patternStoreKey]["modifiers"] = $patternModifiers;
						
					}
					
				}
				
			}
			
		}
		
		unset($patternLoader);
		unset($patternLoaderInstance);
		unset($kss);
		
	}
	
}

