<?php

/*!
 * Pattern Engine Twig Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * If the test matches "mustache" it will return an instance of the Twig Pattern Engine
 *
 */


namespace PatternLab\PatternEngine\Rules;

use \PatternLab\Config;
use \PatternLab\PatternEngine\Loaders\TwigLoader;

class TwigRule extends \PatternLab\PatternEngine\Rule {
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->engineProp = "twig";
		
	}
		
	public function getInstance() {
		
		$options = new TwigLoader(Config::$options["patternSourceDir"],array("patternPaths" => $options["patternPaths"]));
		
		return new \Twig_Environment($options);
		
	}
	
}
