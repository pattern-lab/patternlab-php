<?php

/*!
 * Pattern Data Data Link Exporter Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Populate the data.link attribute
 *
 */

namespace PatternLab\PatternData\Exporters;

use \PatternLab\Data;
use \PatternLab\PatternData;

class DataLinkExporter extends \PatternLab\PatternData\Exporter {
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		
	}
	
	public function run() {
		
		foreach (PatternData::$store as $patternStoreKey => $patternStoreData) {
			
			if ($patternStoreData["category"] == "pattern") {
				
				Data::$store["link"][$patternStoreKey] = "../../".$patternStoreData["pathDash"]."/".$patternStoreData["pathDash"].".html";
				
			}
			
		}
		
	}
	
}
