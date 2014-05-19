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
use PatternLab\JSON;

class PseudoPatternRule extends \PatternLab\PatternInfoRule {
	
	protected $patternExtension;
	
	public function __construct($options) {
		
		parent::__construct($options);
		$this->patternExtension = $options["patternExtension"];
		
		$this->depthProp  = 3; // 3 means that depth won't be checked
		$this->extProp    = "json";
		$this->isDirProp  = false;
		$this->isFileProp = true;
		$this->searchProp = "~";
		
	}
	
	public function runRule($depth, $ext, $path, $pathName, $name) {
		
		$bi                 = PatternInfo::$bi;
		$ni                 = PatternInfo::$ni;
		$patternSubtype     = PatternInfo::$patternSubtype;
		$patternSubtypeDash = PatternInfo::$patternSubtypeDash;
		$patternSubtypeSet  = PatternInfo::$patternSubtypeSet;
		$patternType        = PatternInfo::$patternType;
		$patternTypeDash    = PatternInfo::$patternTypeDash;
		$dirSep             = DIRECTORY_SEPARATOR;
		
		$patternSubtypeInclude = ($patternSubtypeSet) ? $patternSubtype."-" : "";
		$patternFull = $name;
		
		if ($patternFull[0] != "_") {
			
			// check for a pattern state
			$patternState = "";
			$patternBits  = explode("@",$patternFull,2);
			if (isset($patternBits[1])) {
				$patternState = str_replace(".json","",$patternBits[1]);
				$patternFull  = preg_replace("/@(.*?)\./",".",$patternFull);
			}
			
			// set-up the names
			// $patternFull is defined above                                                     00-colors.mustache
			$patternBits     = explode("~",$patternFull);
			$patternBase     = $patternBits[0].".".$this->patternExtension;                   // 00-homepage.mustache
			$patternBaseDash = $this->getPatternName($patternBits[0],false);                  // homepage
			$patternBaseJSON = $patternBits[0].".json";                                       // 00-homepage.json
			$stripJSON       = str_replace(".json","",$patternBits[1]);
			$patternBitClean = preg_replace("/@(.*?)/","",$patternBits[0]);
			$pattern         = $patternBitClean."-".$stripJSON;                               // 00-homepage-00-emergency
			$patternInt      = $patternBitClean."-".$this->getPatternName($stripJSON, false); // 00-homepage-emergency
			$patternDash     = $this->getPatternName($patternInt,false);                      // homepage-emergency
			$patternClean    = str_replace("-"," ",$patternDash);                             // homepage emergency
			$patternPartial  = $patternTypeDash."-".$patternDash;                             // pages-homepage-emergency
			
			// add to patternPaths
			if ($patternSubtypeSet) {
				$patternPath     = $patternType.$dirSep.$patternSubtype.$dirSep.$pattern;    // 00-atoms/01-global/00-colors
				$patternPathDash = str_replace($dirSep,"-",$patternPath);                    // 00-atoms-01-global-00-colors (file path)
			} else {
				$patternPath     = $patternType.$dirSep.$pattern;                            // 00-atoms/00-colors
				$patternPathDash = str_replace($dirSep,"-",$patternPath);                    // 00-atoms-00-colors (file path)
			}
			
			// add all patterns to patternPaths
			$patternSrcPath  = PatternInfo::$patternPaths[$patternTypeDash][$patternBaseDash]["patternSrcPath"];
			$patternDestPath = $patternPathDash;
			PatternInfo::$patternPaths[$patternTypeDash][$patternDash] = array("patternSrcPath"  => $patternSrcPath,
																			   "patternDestPath" => $patternDestPath,
																			   "patternPartial"  => $patternPartial,
																			   "patternState"    => $patternState,
																			   "patternType"     => $patternTypeDash,
																			   "render"          => true);
			
			// see if the pattern name is already set via .md file
			$patternName = ucwords($patternClean);
			if ($depth == 2) {
				$key = $this->findKey(PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"],$patternPartial,"patternPartial");
				if ($key !== false) {
					$patternName = PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][$key]["patternName"];
				}
			}
			
			// set-up the info for the nav
			$patternInfo = array("patternPath"    => $patternPathDash."/".$patternPathDash.".html",
								 "patternSrcPath" => str_replace($this->patternSourceDir,"",preg_replace("/\~(.*)\.json/",".".$this->patternExtension,$pathName)),
								 "patternName"    => $patternName,
								 "patternState"   => $patternState,
								 "patternPartial" => $patternPartial);
			
			// add to the nav
			if ($depth == 1) {
				$key = $this->findKey(PatternInfo::$navItems["patternTypes"][$bi]["patternItems"],$patternPartial,"patternPartial");
				if ($key !== false) {
					PatternInfo::$navItems["patternTypes"][$bi]["patternItems"][$key] = array_merge(PatternInfo::$navItems["patternTypes"][$bi]["patternItems"][$key], $patternInfo);
				} else {
					PatternInfo::$navItems["patternTypes"][$bi]["patternItems"][] = $patternInfo;
				}
			} else {
				if ($key = $this->findKey(PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"],$patternPartial,"patternPartial")) {
					PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][$key] = array_merge(PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][$key], $patternInfo);
				} else {
					PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][$key] = $patternInfo;
				}
			}
			
			// add to the link var for inclusion in patterns
			PatternInfo::$d["link"][$patternPartial] = "../../patterns/".$patternPathDash."/".$patternPathDash.".html";
			
			// get the base data
			$patternDataBase = array();
			if (file_exists($path."/".$patternBaseJSON)) {
				$data = file_get_contents($path."/".$patternBaseJSON);
				$patternDataBase = json_decode($data,true);
				if ($jsonErrorMessage = JSON::hasError()) {
					JSON::lastErrorMsg($patternBaseJSON,$jsonErrorMessage,$data);
				}
			}
			
			// get the special pattern data
			$data        = file_get_contents($pathName);
			$patternData = (array) json_decode($data);
			if ($jsonErrorMessage = JSON::hasError()) {
				JSON::lastErrorMsg($name,$jsonErrorMessage,$data);
			}
			
			// merge them for the file
			if (!isset(PatternInfo::$d["patternSpecific"][$patternPartial])) {
				PatternInfo::$d["patternSpecific"][$patternPartial]              = array();
				PatternInfo::$d["patternSpecific"][$patternPartial]["data"]      = array();
				PatternInfo::$d["patternSpecific"][$patternPartial]["listItems"] = array();
			}
			
			if (is_array($patternDataBase) && is_array($patternData)) {
				PatternInfo::$d["patternSpecific"][$patternPartial]["data"] = array_merge($patternDataBase, $patternData);
			}
			
		}
		
	}
	
}

