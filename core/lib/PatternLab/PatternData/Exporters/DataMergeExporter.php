<?php

/*!
 * Pattern Data Data Merge Exporter Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Merges any of the data found related to patterns (e.g. pseudo-patterns and pattern specific data files)
 * with Data::$store
 *
 */

namespace PatternLab\PatternData\Exporters;

use \PatternLab\Data;
use \PatternLab\PatternData;

class DataMergeExporter extends \PatternLab\PatternData\Exporter {
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		
	}
	
	public function run() {
		
		foreach (PatternData::$store as $patternStoreKey => $patternStoreData) {
			
			if ($patternStoreData["category"] == "pattern") {
				
				if (isset($patternStoreData["data"]) || isset($patternStoreData["listItems"])) {
					Data::$store["patternSpecific"][$patternStoreKey] = array();
				}
				
				if (isset($patternStoreData["data"])) {
					Data::$store["patternSpecific"][$patternStoreKey]["data"] = $patternStoreData["data"];
				}
				
				if (isset($patternStoreData["listItems"])) {
					Data::$store["patternSpecific"][$patternStoreKey]["listItems"] = $patternStoreData["listItems"];
				}
				
			}
			
		}
		
		// walk across the data and change link.pattern-partial to real source
		array_walk_recursive(Data::$store,'\PatternLab\Util::compareReplace');
		
	}
	
}
