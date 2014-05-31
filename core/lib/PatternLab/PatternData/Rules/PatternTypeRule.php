<?php

/*!
 * Pattern Data Type Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * If it's a directory at level 0 add the type info to PatternData::$store
 *
 */

namespace PatternLab\PatternData\Rules;

use \PatternLab\PatternData;

class PatternTypeRule extends \PatternLab\PatternData\Rule {
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->depthProp  = 0;
		$this->extProp    = "";
		$this->isDirProp  = true;
		$this->isFileProp = false;
		$this->searchProp = "";
		$this->ignoreProp = "";
		
	}
	
	public function run($depth, $ext, $path, $pathName, $name) {
		
		// set-up the names
		$patternType      = $name;                                 // 00-atoms
		$patternTypeDash  = $this->getPatternName($name,false);    // atoms
		$patternTypeClean = str_replace("-"," ",$patternTypeDash); // atoms (dashes replaced with spaces)
		
		// create a key for the data store
		$patternStoreKey  = $patternTypeDash."-pltype";
		
		// add a new patternType to the nav
		PatternData::$store[$patternStoreKey] = array("category"  => "patternType",
													  "name"      => $patternType,
													  "nameDash"  => $patternTypeDash,
													  "nameClean" => $patternTypeClean,
													  "depth"     => $depth,
													  "ext"       => $ext,
													  "path"      => $path,
													  "pathName"  => $pathName,
													  "isDir"     => $this->isDirProp ,
													  "isFile"    => $this->isFileProp);
		
		// starting a new set of pattern types. it might not have any pattern subtypes
		PatternData::$patternType      = $patternType;
		PatternData::$patternTypeClean = $patternTypeClean;
		PatternData::$patternTypeDash  = $patternTypeDash;
		
	}
	
}