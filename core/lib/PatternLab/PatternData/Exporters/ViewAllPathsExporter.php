<?php

/*!
 * Pattern Data Pattern Path Source Exporter Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Generates an array of the view all paths for use on the front-end
 *
 */

namespace PatternLab\PatternData\Exporters;

use \PatternLab\Config;
use \PatternLab\PatternData;

class ViewAllPathsExporter extends \PatternLab\PatternData\Exporter {
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		
	}
	
	public function run($navItems) {
		
		$viewAllPaths = array();
		
		foreach ($navItems["patternTypes"] as $patternTypeKey => $patternTypeValues) {
			
			$patternType     = $patternTypeValues["patternType"];
			$patternTypeDash = $patternTypeValues["patternTypeDash"];
			
			if (!in_array($patternType,Config::$options["styleGuideExcludes"])) {
				
				foreach ($patternTypeValues["patternTypeItems"] as $patternSubtypeKey => $patternSubtypeValues) {
					
					$patternSubtype     = $patternSubtypeValues["patternSubtype"];
					$patternSubtypeDash = $patternSubtypeValues["patternSubtypeDash"];
					
					if (isset($patternSubtypeValues["patternSubtypeItems"])) {
						
						foreach ($patternSubtypeValues["patternSubtypeItems"] as $patternSubtypeItemKey => $patternSubtypeItemValues) {
							
							if (strpos($patternSubtypeItemValues["patternPartial"],"viewall-") !== false) {
								
								$viewAllPaths[$patternTypeDash][$patternSubtypeDash] = $patternType."-".$patternSubtype;
								
							}
							
						}
						
					}
					
					if (strpos($patternSubtypeItemValues["patternPartial"],"viewall-") !== false) {
						
						$viewAllPaths[$patternTypeDash]["all"] = $patternType;
						
					}
					
				}
				
			}
			
		}
		
		return $viewAllPaths;
		
	}
	
}