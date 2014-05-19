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

class PatternSubtypeRule extends \PatternLab\PatternInfoRule {
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->depthProp  = 1;
		$this->extProp    = "";
		$this->isDirProp  = true;
		$this->isFileProp = false;
		$this->searchProp = "";
		
	}
	
	public function runRule($depth, $ext, $path, $pathName, $name) {
		
		// is this the first bucket to be set?
		PatternInfo::$ni = (!PatternInfo::$patternSubtypeSet) ? 0 : PatternInfo::$ni + 1;
		$ni              = PatternInfo::$ni;
		$bi              = PatternInfo::$bi;
		$patternTypeDash = PatternInfo::$patternTypeDash;
		
		// set-up the names
		$patternSubtype      = $name;                                    // 02-blocks
		$patternSubtypeDash  = $this->getPatternName($name,false);       // blocks
		$patternSubtypeClean = str_replace("-"," ",$patternSubtypeDash); // blocks (dashes replaced with spaces)
		
		// add to patternPartials
		PatternInfo::$patternPartials[$patternTypeDash."-".$patternSubtypeDash] = array();
		
		// add a new patternSubtype to the nav
		PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni] = array("patternSubtypeLC"    => strtolower($patternSubtypeClean),
																					 "patternSubtypeUC"    => ucwords($patternSubtypeClean),
																					 "patternSubtype"      => $patternSubtype,
																					 "patternSubtypeDash"  => $patternSubtypeDash,
																					 "patternSubtypeItems" => array());
		
		// starting a new set of pattern types. it might not have any pattern subtypes
		PatternInfo::$patternSubtype     = $patternSubtype;
		PatternInfo::$patternSubtypeDash = $patternSubtypeDash;
		PatternInfo::$patternSubtypeSet  = true;
		
		
	}
	
}