<?php

/*!
 * Pattern Data Subtype Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * If it's a directory at level 1 add the subtype info to PatternData::$store
 *
 */

namespace PatternLab\PatternData\Rules;

use \PatternLab\PatternData;

class PatternSubtypeRule extends \PatternLab\PatternData\Rule {
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->depthProp  = 1;
		$this->extProp    = "";
		$this->isDirProp  = true;
		$this->isFileProp = false;
		$this->searchProp = "";
		$this->ignoreProp = "";
		
	}
	
	public function run($depth, $ext, $path, $pathName, $name) {
		
		// load default vars
		$patternType            = PatternData::$patternType;
		$patternTypeDash        = PatternData::$patternTypeDash;
		$patternTypeClean       = PatternData::$patternTypeClean;
		$dirSep                 = PatternData::$dirSep;
		
		// set-up the names
		$patternSubtype         = $name;                                        // 02-blocks
		$patternSubtypeDash     = $this->getPatternName($name,false);           // blocks
		$patternSubtypeClean    = str_replace("-"," ",$patternSubtypeDash);     // blocks (dashes replaced with spaces)
		$patternSubtypePath     = $pathName;                                    // 00-atoms/02-blocks
		$patternSubtypePathDash = str_replace($dirSep,"-",$patternSubtypePath); // 00-atoms-02-blocks (file path)
		
		// create a key for the data store
		$patternStoreKey        = $patternTypeDash."-".$patternSubtypeDash."-plsubtype";
		
		// collect the data
		$patternStoreData       = array("category"   => "patternSubtype",
										"name"       => $patternSubtype,
										"nameDash"   => $patternSubtypeDash,
										"nameClean"  => $patternSubtypeClean,
										"type"       => $patternType,
										"typeDash"   => $patternTypeDash,
										"breadcrumb" => $patternTypeClean,
										"depth"      => $depth,
										"ext"        => $ext,
										"path"       => $path,
										"pathName"   => $patternSubtypePath,
										"pathDash"   => $patternSubtypePathDash,
										"isDir"      => $this->isDirProp,
										"isFile"     => $this->isFileProp);
		
		// if the pattern data store already exists make sure it is merged and overwrites this data
		PatternData::$store[$patternStoreKey] = isset(PatternData::$store[$patternStoreKey]) ? array_replace_recursive($patternStoreData,PatternData::$store[$patternStoreKey]) : $patternStoreData;
		
		// starting a new set of pattern types. it might not have any pattern subtypes
		PatternData::$patternSubtype      = $patternSubtype;
		PatternData::$patternSubtypeClean = $patternSubtypeClean;
		PatternData::$patternSubtypeDash  = $patternSubtypeDash;
		PatternData::$patternSubtypeSet   = true;
		
	}
	
}