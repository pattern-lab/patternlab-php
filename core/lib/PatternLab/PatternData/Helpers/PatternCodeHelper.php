<?php

/*!
 * Pattern Data Pattern Code Helper Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Renders patterns and stores the rendered code in PatternData::$store
 *
 */

namespace PatternLab\PatternData\Helpers;

use \PatternLab\Config;
use \PatternLab\Data;
use \PatternLab\PatternData;
use \PatternLab\PatternEngine;
use \PatternLab\Render;
use \PatternLab\Template\Helper;

class PatternCodeHelper extends \PatternLab\PatternData\Helper {
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		
		$this->patternPaths = $options["patternPaths"];
		
	}
	
	public function run() {
		
		$options                 = array();
		$options["patternPaths"] = $this->patternPaths;
		PatternEngine::setup($options);
		
		foreach (PatternData::$store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && !$patternStoreData["hidden"]) {
				
				$patternFooterData = array("patternFooterData" => array());
				//$patternFooterData["patternFooterData"]["cssEnabled"]      = (Config::$options["enableCSS"] && isset($this->patternCSS[$p])) ? "true" : "false";
				$patternFooterData["patternFooterData"]["lineage"]           = isset($patternStoreData["lineages"])  ? json_encode($patternStoreData["lineages"]) : "[]";
				$patternFooterData["patternFooterData"]["lineageR"]          = isset($patternStoreData["lineagesR"]) ? json_encode($patternStoreData["lineagesR"]) : "[]";
				$patternFooterData["patternFooterData"]["patternBreadcrumb"] = $patternStoreData["breadcrumb"];
				$patternFooterData["patternFooterData"]["patternDesc"]       = (isset($patternStoreData["desc"])) ? $patternStoreData["desc"] : "";
				$patternFooterData["patternFooterData"]["patternExtension"]  = Config::$options["patternExtension"];
				$patternFooterData["patternFooterData"]["patternModifiers"]  = (isset($patternStoreData["modifiers"])) ? json_encode($patternStoreData["modifiers"]) : "[]";
				$patternFooterData["patternFooterData"]["patternName"]       = $patternStoreData["nameClean"];
				$patternFooterData["patternFooterData"]["patternPartial"]    = $patternStoreData["partial"];
				$patternFooterData["patternFooterData"]["patternState"]      = $patternStoreData["state"];
				
				$srcPath = (isset($patternStoreData["pseudo"])) ? PatternData::$store[$patternStoreData["original"]]["pathName"] : $patternStoreData["pathName"];
				
				$data    = Data::getPatternSpecificData($patternStoreKey,$patternFooterData);
				
				$header  = Render::Header(Helper::$patternHead,$data);
				$code    = Render::Pattern($srcPath,$data);
				$footer  = Render::Footer(Helper::$patternFoot,$data);
				
				PatternData::$store[$patternStoreKey]["header"] = $header;
				PatternData::$store[$patternStoreKey]["code"]   = $code;
				PatternData::$store[$patternStoreKey]["footer"] = $footer;
				
			}
			
		}
		
	}
	
}
