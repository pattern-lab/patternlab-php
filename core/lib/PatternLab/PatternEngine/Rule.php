<?php

/*!
 * Pattern Engine Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Tests the engine property of the rule agains the pattern extension in the config
 *
 */

namespace PatternLab\PatternEngine;

use \PatternLab\Config;

class Rule {
	
	protected $engineProp;
	
	public function __construct($options) {
		
		// nothing here yet
		
	}
	
	public function test() {
		
		return ($this->engineProp == Config::$options["patternExtension"]);
		
	}
	
}
