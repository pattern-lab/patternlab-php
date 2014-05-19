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

class PatternRule extends \PatternLab\PatternInfoRule {
	
	protected $patternExtension;
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->patternExtension = $options["patternExtension"];
		
		$this->depthProp  = 3; // 3 means that depth won't be checked
		$this->extProp    = $this->patternExtension;
		$this->isDirProp  = false;
		$this->isFileProp = true;
		$this->searchProp = "";
		
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
		
		$patternFull  = $name;                                                    // 00-colors.mustache
		$pattern      = str_replace(".".$this->patternExtension,"",$patternFull);      // 00-colors
		
		// check for pattern state
		$patternState = "";
		if (strpos($pattern,"@") !== false) {
			$patternBits  = explode("@",$pattern,2);
			$pattern      = $patternBits[0];
			$patternState = $patternBits[1];
		}
		
		if ($patternSubtypeSet) {
			$patternPath     = $patternType.$dirSep.$patternSubtype.$dirSep.$pattern; // 00-atoms/01-global/00-colors
			$patternPathDash = str_replace($dirSep,"-",$patternPath);                             // 00-atoms-01-global-00-colors (file path)
		} else {
			$patternPath     = $patternType.$dirSep.$pattern;                         // 00-atoms/00-colors
			$patternPathDash = str_replace($dirSep,"-",$patternPath);                 // 00-atoms-00-colors (file path)
		}
		
		// track to see if this pattern should get rendered
		$render = false;
		
		// make sure the pattern isn't hidden
		if ($patternFull[0] != "_") {
			
			// set-up the names
			$patternDash    = $this->getPatternName($pattern,false);                  // colors
			$patternClean   = str_replace("-"," ",$patternDash);                      // colors (dashes replaced with spaces)
			$patternPartial = $patternTypeDash."-".$patternDash;                      // atoms-colors
			
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
								 "patternSrcPath" => str_replace($this->patternSourceDir,"",$pathName),
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
				$key = $this->findKey(PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"],$patternPartial,"patternPartial");
				if ($key !== false) {
					PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][$key] = array_merge(PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][$key], $patternInfo);
				} else {
					PatternInfo::$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][] = $patternInfo;
				}
			}
			
			// add to the link var for inclusion in patterns
			PatternInfo::$d["link"][$patternPartial] = "../../patterns/".$patternPathDash."/".$patternPathDash.".html";
			
			// yup, this pattern should get rendered
			$render = true;
			
		} else {
			
			// replace the underscore to generate a good file pattern name
			$patternDash    = $this->getPatternName(str_replace("_","",$pattern),false); // colors
			$patternPartial = $patternTypeDash."-".$patternDash;                         // atoms-colors
			
		}
		
		// add all patterns to patternPaths
		$patternSrcPath  = str_replace($this->patternSourceDir,"",str_replace(".".$this->patternExtension,"",$pathName));
		$patternDestPath = $patternPathDash;
		PatternInfo::$patternPaths[$patternTypeDash][$patternDash] = array("patternSrcPath"  => $patternSrcPath,
																		   "patternDestPath" => $patternDestPath,
																		   "patternPartial"  => $patternPartial,
																		   "patternState"    => $patternState,
																		   "patternType"     => $patternTypeDash,
																		   "render"          => $render);
		
	}
	
}