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

namespace PatternLab\PatternInfoRules;

use PatternLab\PatternInfo;

class PatternTypeRule extends \PatternLab\PatternInfoRule {
	
	public $depthProp;
	public $extProp;
	public $isDirProp;
	public $isFileProp;
	public $searchProp;
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->depthProp  = 0;
		$this->extProp    = "";
		$this->isDirProp  = true;
		$this->isFileProp = false;
		$this->searchProp = "";
		
	}
	
	public function runRule($depth, $ext, $path, $pathName, $name) {
		
		PatternInfo::$bi = (count(PatternInfo::$navItems["patternTypes"]) == 0) ? 0 : PatternInfo::$bi + 1;
		$bi = PatternInfo::$bi;
		
		// set-up the names
		$patternType      = $name;                                 // 00-atoms
		$patternTypeDash  = $this->getPatternName($name,false);    // atoms
		$patternTypeClean = str_replace("-"," ",$patternTypeDash); // atoms (dashes replaced with spaces)
		
		// add to pattern types & pattern paths
		$patternTypes[]                 = $patternType;
		$patternPaths[$patternTypeDash] = array();
		
		// add a new patternType to the nav
		PatternInfo::$navItems["patternTypes"][$bi] = array("patternTypeLC"    => strtolower($patternTypeClean),
															"patternTypeUC"    => ucwords($patternTypeClean),
															"patternType"      => $patternType,
															"patternTypeDash"  => $patternTypeDash,
															"patternTypeItems" => array(),
															"patternItems"     => array());
		
		// starting a new set of pattern types. it might not have any pattern subtypes
		PatternInfo::$patternSubtypeSet = false;
		PatternInfo::$patternType       = $patternType;
		PatternInfo::$patternTypeDash   = $patternTypeDash;
		
	}
	
}