<?php

/*!
 * Pattern Data Info List Items Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * If it's a *.listitems.json or *.listitems.yaml file it adds the data to PatternData::$store
 *
 */

namespace PatternLab\PatternData\Rules;

use \PatternLab\Config;
use \PatternLab\Data;
use \PatternLab\PatternData;
use \PatternLab\JSON;
use \Symfony\Component\Yaml\Yaml;

class PatternInfoListItemsRule extends \PatternLab\PatternData\Rule {
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->depthProp  = 3; // 3 means that depth won't be checked
		$this->extProp    = "json||yaml";
		$this->isDirProp  = false;
		$this->isFileProp = true;
		$this->searchProp = ".listitems.";
		$this->ignoreProp = "";
		
	}
	
	public function run($depth, $ext, $path, $pathName, $name) {
		
		// load default vars
		$patternTypeDash = PatternData::$patternTypeDash;
		
		// set-up the names
		$patternFull     = $name;                                          // foo.listitems.json
		$pattern         = str_replace(".listitems.".$ext,"",$patternFull); // foo
		$patternDash     = $this->getPatternName($pattern,false);          // foo
		$patternPartial  = $patternTypeDash."-".$patternDash;              // atoms-foo
		
		// should this pattern get rendered?
		$hidden          = ($patternFull[0] == "_");
		
		if (!$hidden) {
			
			$patternStoreData = array("category" => "pattern");
			
			$data = Data::getListItems($pathName,$ext);
			$patternStoreData["listItems"] = $data;
			
		}
		
		// create a key for the data store
		$patternStoreKey = $patternPartial;
		
		// if the pattern data store already exists make sure it is merged and overwrites this data
		PatternData::$store[$patternStoreKey] = isset(PatternData::$store[$patternStoreKey]) ? array_replace_recursive(PatternData::$store[$patternStoreKey],$patternStoreData) : $patternStoreData;
		
	}
	
}

