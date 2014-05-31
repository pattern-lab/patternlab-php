<?php

/*!
 * Pattern Data Nav Items Exporter Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Generates the array of navigation data for Pattern Lab
 *
 */

namespace PatternLab\PatternData\Exporters;

use \PatternLab\Config;
use \PatternLab\PatternData;

class NavItemsExporter extends \PatternLab\PatternData\Exporter {
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		
	}
	
	public function run() {
		
		$bi                       = 0;
		$ni                       = 0;
		$patternSubtypeSet        = false;
		$patternType              = "";
		$patternTypeDash          = "";
		
		$navItems                 = array();
		$navItems["patternTypes"] = array();
		
		// iterate over the different categories and add them to the navigation
		foreach (PatternData::$store as $patternStoreKey => $patternStoreData) {
			
			if ($patternStoreData["category"] == "patternType") {
				
				$bi = (count($navItems["patternTypes"]) == 0) ? 0 : $bi + 1;
				
				// add a new patternType to the nav
				$navItems["patternTypes"][$bi] = array("patternTypeLC"    => strtolower($patternStoreData["nameClean"]),
													   "patternTypeUC"    => ucwords($patternStoreData["nameClean"]),
													   "patternType"      => $patternStoreData["name"],
													   "patternTypeDash"  => $patternStoreData["nameDash"],
													   "patternTypeItems" => array(),
													   "patternItems"     => array());
				
				// starting a new set of pattern types. it might not have any pattern subtypes
				$patternSubtypeSet = false;
				$patternType       = $patternStoreData["name"];
				$patternTypeDash   = $patternStoreData["nameDash"];
				
			} else if ($patternStoreData["category"] == "patternSubtype") {
				
				$ni = (!$patternSubtypeSet) ? 0 : $ni + 1;
				
				// add a new patternSubtype to the nav
				$navItems["patternTypes"][$bi]["patternTypeItems"][$ni] = array("patternSubtypeLC"    => strtolower($patternStoreData["nameClean"]),
																				"patternSubtypeUC"    => ucwords($patternStoreData["nameClean"]),
																				"patternSubtype"      => $patternStoreData["name"],
																				"patternSubtypeDash"  => $patternStoreData["nameDash"],
																				"patternSubtypeItems" => array());
				
				// starting a new set of pattern types. it might not have any pattern subtypes
				$patternSubtype     = $patternStoreData["name"];
				$patternSubtypeDash = $patternStoreData["nameDash"];
				$patternSubtypeSet  = true;
				
			} else if ($patternStoreData["category"] == "pattern") {
				
				if (!$patternStoreData["hidden"]) {
					
					// set-up the info for the nav
					$patternInfo = array("patternPath"    => $patternStoreData["pathDash"]."/".$patternStoreData["pathDash"].".html",
										 "patternSrcPath" => $patternStoreData["pathName"],
										 "patternName"    => ucwords($patternStoreData["nameClean"]),
										 "patternState"   => $patternStoreData["state"],
										 "patternPartial" => $patternStoreData["partial"]);
					
					// add to the nav
					if ($patternStoreData["depth"] == 1) {
						$navItems["patternTypes"][$bi]["patternItems"][] = $patternInfo;
					} else {
						$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][] = $patternInfo;
					}
					
				}
				
			}
			
		}
		
		// review each subtype. add a view all link or remove the subtype as necessary
		foreach ($navItems["patternTypes"] as $patternTypeKey => $patternTypeValues) {
			
			$reset           = false;
			$patternType     = $patternTypeValues["patternType"];
			$patternTypeDash = $patternTypeValues["patternTypeDash"];
			
			if (!in_array($patternType,Config::$options["styleGuideExcludes"])) {
				
				foreach ($patternTypeValues["patternTypeItems"] as $patternSubtypeKey => $patternSubtypeValues) {
					
					// if there are no sub-items in a section remove it
					if (empty($patternSubtypeValues["patternSubtypeItems"])) {
						
						unset($navItems["patternTypes"][$patternTypeKey]["patternTypeItems"][$patternSubtypeKey]);
						$reset = true;
						
					} else {
						
						$patternSubtype     = $patternSubtypeValues["patternSubtype"];
						$patternSubtypeDash = $patternSubtypeValues["patternSubtypeDash"];
						$subItemsCount      = count($patternSubtypeValues["patternSubtypeItems"]);
						
						// add a view all link
						$navItems["patternTypes"][$patternTypeKey]["patternTypeItems"][$patternSubtypeKey]["patternSubtypeItems"][$subItemsCount] = array(
																												 "patternPath"    => $patternType."-".$patternSubtype."/index.html", 
																												 "patternName"    => "View All",
																												 "patternType"    => $patternType,
																												 "patternSubtype" => $patternSubtype,
																												 "patternPartial" => "viewall-".$patternTypeDash."-".$patternSubtypeDash);
						
					}
					
				}
				
			}
			
			if ($reset) {
				$navItems["patternTypes"][$patternTypeKey]["patternTypeItems"] = array_values($navItems["patternTypes"][$patternTypeKey]["patternTypeItems"]);
				$reset = false;
			}
			
		}
		
		return $navItems;
		
	}
	
}