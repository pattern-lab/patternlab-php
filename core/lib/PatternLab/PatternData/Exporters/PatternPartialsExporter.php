<?php

/*!
 * Pattern Data Pattern Partials Exporter Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Generates the partials to be used in the viewall & styleguide
 *
 */

namespace PatternLab\PatternData\Exporters;

use \PatternLab\Config;
use \PatternLab\Data;
use \PatternLab\PatternData;

class PatternPartialsExporter extends \PatternLab\PatternData\Exporter {
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		
	}
	
	/**
	* Compare the search and ignore props against the name.
	* Can use && or || in the comparison
	* @param  {String}       the type of the pattern that should be used in the view all
	* @param  {String}       the subtype of the pattern that be used in the view all
	*
	* @return {Array}        the list of partials
	*/
	public function run($type = "", $subtype = "") {
		
		$patternPartials = array();
		
		foreach (PatternData::$store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && (!$patternStoreData["hidden"]) && ($patternStoreData["depth"] == 2) && (!in_array($patternStoreData["type"],Config::$options["styleGuideExcludes"]))) {
				
				if ((empty($type) && empty($subtype)) || (($patternStoreData["type"] == $type) && ($patternStoreData["subtype"] == $subtype))) {
					
					$patternPartialData                           = array();
					$patternPartialData["patternName"]            = ucwords($patternStoreData["nameClean"]);
					$patternPartialData["patternLink"]            = $patternStoreData["pathDash"]."/".$patternStoreData["pathDash"].".html";
					$patternPartialData["patternPartial"]         = $patternStoreData["partial"];
					
					$patternPartialData["patternLineageExists"]   = isset($patternStoreData["lineages"]);
					$patternPartialData["patternLineages"]        = isset($patternStoreData["lineages"]) ? $patternStoreData["lineages"] : array();
					$patternPartialData["patternLineageRExists"]  = isset($patternStoreData["lineagesR"]);
					$patternPartialData["patternLineagesR"]       = isset($patternStoreData["lineagesR"]) ? $patternStoreData["lineagesR"] : array();
					$patternPartialData["patternLineageEExists"]  = (isset($patternStoreData["lineages"]) || isset($patternStoreData["lineagesR"]));
					
					$patternPartialData["patternDescExists"]      = isset($patternStoreData["desc"]);
					$patternPartialData["patternDescExists"]      = isset($patternStoreData["desc"]) ? $patternStoreData["desc"] : "";
					
					$patternPartialData["patternModifiersExists"] = isset($patternStoreData["modifiers"]);
					$patternPartialData["patternModifiersExists"] = isset($patternStoreData["modifiers"]) ? $patternStoreData["modifiers"] : array();
					
					$patternPartialData["patternCSSExists"]       = Config::$options["enableCSS"];
					
					$patternPartials[]                            = $patternPartialData;
				
				}
				
			}
			
		}
		
		return array("partials" => $patternPartials);
		
	}
	
}


