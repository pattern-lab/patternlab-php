<?php

/*!
 * Pattern Data Pattern Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Adds the generic data about a pattern to PatternData::$store
 *
 */

namespace PatternLab\PatternData\Rules;

use \PatternLab\Config;
use \PatternLab\PatternData;

class PatternRule extends \PatternLab\PatternData\Rule {
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->depthProp  = 3; // 3 means that depth won't be checked
		$this->extProp    = Config::$options["patternExtension"];
		$this->isDirProp  = false;
		$this->isFileProp = true;
		$this->searchProp = "";
		$this->ignoreProp = "";
		
	}
	
	public function run($depth, $ext, $path, $pathName, $name) {
		
		// load default vars
		$patternSubtype      = PatternData::$patternSubtype;
		$patternSubtypeClean = PatternData::$patternSubtypeClean;
		$patternSubtypeDash  = PatternData::$patternSubtypeDash;
		$patternType         = PatternData::$patternType;
		$patternTypeClean    = PatternData::$patternTypeClean;
		$patternTypeDash     = PatternData::$patternTypeDash;
		$dirSep              = PatternData::$dirSep;
		
		// set-up the names
		$patternFull      = $name;                                                              // 00-colors.mustache
		$pattern          = str_replace(".".Config::$options["patternExtension"],"",$patternFull); // 00-colors
		$patternState     = "";
		
		// check for pattern state
		if (strpos($pattern,"@") !== false) {
			$patternBits  = explode("@",$pattern,2);
			$pattern      = $patternBits[0];
			$patternState = $patternBits[1];
		}
		
		// finish setting up vars
		$patternDash      = $this->getPatternName(str_replace("_","",$pattern),false);       // colors
		$patternClean     = str_replace("-"," ",$patternDash);                               // colors (dashes replaced with spaces)
		$patternPartial   = $patternTypeDash."-".$patternDash;                               // atoms-colors
		$patternPath      = str_replace(".".Config::$options["patternExtension"],"",$pathName); // 00-atoms/01-global/00-colors
		$patternPathDash  = str_replace($dirSep,"-",$patternPath);                           // 00-atoms-01-global-00-colors (file path)
		
		// should this pattern get rendered?
		$hidden           = ($patternFull[0] == "_");
		
		// create a key for the data store
		$patternStoreKey  = $patternPartial;
		
		// collect the data
		$patternStoreData = array("category"   => "pattern",
								  "name"       => $pattern,
								  "partial"    => $patternPartial,
								  "nameDash"   => $patternDash,
								  "nameClean"  => $patternClean,
								  "type"       => $patternType,
								  "typeDash"   => $patternTypeDash,
								  "breadcrumb" => $patternTypeClean,
								  "state"      => $patternState,
								  "hidden"     => $hidden,
								  "depth"      => $depth,
								  "ext"        => $ext,
								  "path"       => $path,
								  "pathName"   => $patternPath,
								  "pathDash"   => $patternPathDash,
								  "isDir"      => $this->isDirProp,
								  "isFile"     => $this->isFileProp);
		
		// add any subtype info if necessary
		if ($depth == 2) {
			$patternStoreData["subtype"]     = $patternSubtype;
			$patternStoreData["subtypeDash"] = $patternSubtypeDash;
			$patternStoreData["breadcrumb"]  = $patternTypeClean." &gt; ".$patternSubtypeClean;
		}
		
		// if the pattern data store already exists make sure it is merged and overwrites this data
		PatternData::$store[$patternStoreKey] = isset(PatternData::$store[$patternStoreKey]) ? array_replace_recursive($patternStoreData,PatternData::$store[$patternStoreKey]) : $patternStoreData;
		
	}
	
}