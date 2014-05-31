<?php

/*!
 * Pattern Data Pseudo-Pattern Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * If it's a JSON or YAML file with a ~ add the pseudo-pattern info to the PatternData::$store
 *
 */

namespace PatternLab\PatternData\Rules;

use \PatternLab\Config;
use \PatternLab\PatternData;
use \PatternLab\JSON;
use \Symfony\Component\Yaml\Yaml;

class PseudoPatternRule extends \PatternLab\PatternData\Rule {
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->depthProp  = 3; // 3 means that depth won't be checked
		$this->extProp    = "json||yaml";
		$this->isDirProp  = false;
		$this->isFileProp = true;
		$this->searchProp = "~";
		$this->ignoreProp = "";
		
	}
	
	public function run($depth, $ext, $path, $pathName, $name) {
		
		// load default vars
		$patternSubtype     = PatternData::$patternSubtype;
		$patternSubtypeDash = PatternData::$patternSubtypeDash;
		$patternType        = PatternData::$patternType;
		$patternTypeDash    = PatternData::$patternTypeDash;
		$dirSep             = PatternData::$dirSep;
		
		// set-up the names
		$patternFull        = $name;                                                           // 00-colors.mustache
		$patternState       = "";
		
		// check for pattern state
		if (strpos($patternFull,"@") !== false) {
			$patternBits    = explode("@",$patternFull,2);
			$patternState   = str_replace(".".$ext,"",$patternBits[1]);
			$patternFull    = preg_replace("/@(.*?)\./",".",$patternFull);
		}
		
		// finish setting up vars
		$patternBits         = explode("~",$patternFull);
		$patternBase         = $patternBits[0].".".Config::$options["patternExtension"];        // 00-homepage.mustache
		$patternBaseDash     = $this->getPatternName($patternBits[0],false);                    // homepage
		$patternBaseOrig     = $patternTypeDash."-".$patternBaseDash;                           // pages-homepage
		$patternBaseData     = $patternBits[0].".".$ext;                                        // 00-homepage.json
		$stripJSON           = str_replace(".".$ext,"",$patternBits[1]);
		$patternBitClean     = preg_replace("/@(.*?)/","",$patternBits[0]);
		$pattern             = $patternBitClean."-".$stripJSON;                                 // 00-homepage-00-emergency
		$patternInt          = $patternBitClean."-".$this->getPatternName($stripJSON, false);   // 00-homepage-emergency
		$patternDash         = $this->getPatternName($patternInt,false);                        // homepage-emergency
		$patternClean        = str_replace("-"," ",$patternDash);                               // homepage emergency
		$patternPartial      = $patternTypeDash."-".$patternDash;                               // pages-homepage-emergency
		$patternPath         = str_replace(".".$ext,"",str_replace("~","-",$pathName));         // 00-atoms/01-global/00-colors
		$patternPathDash     = str_replace($dirSep,"-",$patternPath);                           // 00-atoms-01-global-00-colors (file path)
		$patternPathOrigBits = explode("~",$pathName);                                          
		$patternPathOrig     = $patternPathOrigBits[0];                                         // 04-pages/00-homepage
		$patternPathOrigDash = str_replace($dirSep,"-",$patternPathOrig);                       // 04-pages-00-homepage
		
		// should this pattern get rendered?
		$hidden             = ($patternFull[0] == "_");
		
		// create a key for the data store
		$patternStoreKey    = $patternPartial;
		
		// collect the data
		$patternStoreData   = array("category"     => "pattern",
									"name"         => $pattern,
									"partial"      => $patternPartial,
									"nameDash"     => $patternDash,
									"nameClean"    => $patternClean,
									"type"         => $patternType,
									"typeDash"     => $patternTypeDash,
									"breadcrumb"   => $patternType,
									"state"        => $patternState,
									"hidden"       => $hidden,
									"depth"        => $depth,
									"ext"          => $ext,
									"path"         => $path,
									"pathName"     => $patternPath,
									"pathDash"     => $patternPathDash,
									"isDir"        => $this->isDirProp,
									"isFile"       => $this->isFileProp,
									"pseudo"       => true,
									"original"     => $patternBaseOrig,
									"pathOrig"     => $patternPathOrig,
									"pathOrigDash" => $patternPathOrigDash);
		
		// add any subtype info if necessary
		if ($depth == 2) {
			$patternStoreData["subtype"]     = $patternSubtype;
			$patternStoreData["subtypeDash"] = $patternSubtypeDash;
			$patternStoreData["breadcrumb"]  = $patternType." &gt; ".$patternSubtype;
		}
		
		$patternDataBase = array();
		if (file_exists(__DIR__."/../..".Config::$options["patternSourceDir"]."/".$path."/".$patternBaseData)) {
			$data = file_get_contents(__DIR__."/../..".Config::$options["patternSourceDir"]."/".$path."/".$patternBaseData);
			if ($ext == "json") {
				$patternDataBase = json_decode($data,true);
				if ($jsonErrorMessage = JSON::hasError()) {
					JSON::lastErrorMsg($patternBaseJSON,$jsonErrorMessage,$data);
				}
			} else {
				$patternDataBase = Yaml::parse($data);
			}
			
		}
		
		// get the data for the pseudo-pattern
		$data = file_get_contents(__DIR__."/../..".Config::$options["patternSourceDir"]."/".$pathName);
		if ($ext == "json") {
			$patternData = json_decode($data,true);
			if ($jsonErrorMessage = JSON::hasError()) {
				JSON::lastErrorMsg($name,$jsonErrorMessage,$data);
			}
		} else {
			$patternData = Yaml::parse($data);
		}
		
		$patternStoreData["data"] = array_replace_recursive($patternDataBase, $patternData);
		
		// if the pattern data store already exists make sure it is merged and overwrites this data
		PatternData::$store[$patternStoreKey] = isset(PatternData::$store[$patternStoreKey]) ? array_replace_recursive($patternStoreData,PatternData::$store[$patternStoreKey]) : $patternStoreData;
		
	}
	
}

