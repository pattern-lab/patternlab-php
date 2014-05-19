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
use PatternLab\DocumentationParser;

class DocumentationRule extends \PatternLab\PatternInfoRule {
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->depthProp  = 3; // 3 means that depth won't be checked
		$this->extProp    = "md";
		$this->isDirProp  = false;
		$this->isFileProp = true;
		$this->searchProp = "";
		
	}
	
	public function runRule($depth, $ext, $path, $pathName, $name) {
		
		$bi                 = PatternInfo::$bi;
		$ni                 = PatternInfo::$ni;
		$patternSubtype     = PatternInfo::$patternSubtype;
		$patternTypeDash    = PatternInfo::$patternTypeDash;
		
		$patternFull  = $name;                                           // 00-colors.md
		$pattern      = str_replace(".".$this->extProp,"",$patternFull); // 00-colors
		
		// make sure the pattern isn't hidden
		if ($patternFull[0] != "_") {
			
			// parse data
			$text = file_get_contents($pathName);
			list($yaml,$markdown) = DocumentationParser::parse($text);
			
			if ($depth == 1) {
				
				// add to pattern subtype
				if (isset($yaml["title"])) {
					PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeLC"] = strtolower($yaml["title"]);
					PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeUC"] = ucwords($yaml["title"]);
					unset($yaml["title"]);
				}
				PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeDesc"] = $markdown;
				PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeMeta"] = $yaml;
				
			} else if ($depth == 2) {
				
				// get base info
				$patternDash    = $this->getPatternName($pattern,false); // colors
				$patternPartial = $patternTypeDash."-".$patternDash;     // atoms-colors
				
				// see if the pattern is already part of the nav
				$key = $this->findKey(PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"],$patternPartial,"patternPartial");
				if ($key === false) {
					PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][] = array();
					$a = PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"];
					end($a);
					$key = key($a);
				}
				
				// add to the pattern
				if (isset($yaml["title"])) {
					PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][$key]["patternName"] = ucwords($yaml["title"]);
					unset($yaml["title"]);
				}
				PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][$key]["patternDesc"]    = $markdown;
				PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][$key]["patternMeta"]    = $yaml;
				PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][$key]["patternPartial"] = $patternPartial;
				
			}
			
		}
		
	}
	
}