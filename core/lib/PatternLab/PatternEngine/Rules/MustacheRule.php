<?php

/*!
 * Pattern Engine Mustache Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * If the test matches "mustache" it will return an instance of the Mustache Pattern Engine
 *
 */


namespace PatternLab\PatternEngine\Rules;

use \PatternLab\Config;
use \PatternLab\PatternEngine\Loaders\MustacheLoader;

class MustacheRule extends \PatternLab\PatternEngine\Rule {
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->engineProp = "mustache";
		
	}
		
	public function getInstance($options) {
		
		$options["loader"]         = new MustacheLoader(__DIR__."/../../".Config::$options["patternSourceDir"],array("patternPaths" => $options["patternPaths"]));
		$options["partial_loader"] = new MustacheLoader(__DIR__."/../../".Config::$options["patternSourceDir"],array("patternPaths" => $options["patternPaths"]));
		
		return new \Mustache_Engine($options);
		
	}
	
}
