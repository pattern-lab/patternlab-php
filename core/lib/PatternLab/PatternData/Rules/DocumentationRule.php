<?php

/*!
 * Pattern Data Documentation Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * If a documentation file (.md) it is parsed and the info added to PatternData::$store
 *
 */

namespace PatternLab\PatternData\Rules;

use \PatternLab\Config;
use \PatternLab\PatternData;
use \PatternLab\Parsers\Documentation;

class DocumentationRule extends \PatternLab\PatternData\Rule {
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->depthProp  = 3; // 3 means that depth won't be checked
		$this->extProp    = "md";
		$this->isDirProp  = false;
		$this->isFileProp = true;
		$this->searchProp = "";
		$this->ignoreProp = "";
		
	}
	
	public function run($depth, $ext, $path, $pathName, $name) {
		
		// load default vars
		$patternType        = PatternData::$patternType;
		$patternTypeDash    = PatternData::$patternTypeDash;
		$dirSep             = PatternData::$dirSep;
		
		// set-up the names
		$docFull    = $name;                                                 // 00-colors.md
		$doc        = str_replace(".".$this->extProp,"",$docFull);           // 00-colors
		$docDash    = $this->getPatternName(str_replace("_","",$doc),false); // colors
		$docPartial = $patternTypeDash."-".$docDash;
		
		// make sure the pattern isn't hidden
		$hidden  = ($docFull[0] == "_");
		
		// if the pattern isn't hidden do stuff
		if (!$hidden) {
			
			// parse data
			$text = file_get_contents(__DIR__."/../..".Config::$options["patternSourceDir"]."/".$pathName);
			list($yaml,$markdown) = Documentation::parse($text);
			
			// grab the title and unset it from the yaml so it doesn't get duped in the meta
			if (isset($yaml["title"])) {
				$title = $yaml["title"];
				unset($yaml["title"]);
			}
			
			// figure out if this is a pattern subtype
			$patternSubtypeDoc = false;
			if ($depth == 1) {
				// go through all of the directories to see if this one matches our doc
				foreach (glob(__DIR__."/../..".Config::$options["patternSourceDir"].$patternType."/*",GLOB_ONLYDIR) as $dir) {
					$dir = str_replace(__DIR__."/../..".Config::$options["patternSourceDir"].$patternType."/","",$dir);
					if ($dir == $doc) {
						$patternSubtypeDoc = true;
						break;
					}
				}
				
			}
			
			$category         = ($patternSubtypeDoc) ? "patternSubtype" : "pattern";
			$patternStoreKey  = ($patternSubtypeDoc) ? $docPartial."-plsubtype" : $docPartial;
			
			$patternStoreData = array("category"   => $category,
									  "nameClean"  => $title,
									  "desc"       => $markdown,
									  "descExists" => true,
									  "meta"       => $yaml,
									  "full"       => $doc);
			
			// if the pattern data store already exists make sure this data overwrites it
			PatternData::$store[$patternStoreKey] = isset(PatternData::$store[$patternStoreKey]) ? array_replace_recursive(PatternData::$store[$patternStoreKey],$patternStoreData) : $patternStoreData;
			
		}
		
	}
	
}