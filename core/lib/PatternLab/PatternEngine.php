<?php

/*!
 * Pattern Engine Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Set-up the selected pattern engine
 *
 */

namespace PatternLab;

class PatternEngine {
	
	public static $patternLoader;
	public static $rules          = array();
	
	/**
	* Load a new instance of the Pattern Loader
	*/
	public static function setup($options) {
		
		$found = false;
		self::loadRules($options);
		
		foreach (self::$rules as $rule) {
			if ($rule->test()) {
				$found = true;
				self::$patternLoader = $rule->getInstance($options);
			}
		}
		
		if (!$found) {
			print "the supplied pattern extension didn't match a pattern loader rule. please check.\n";
			exit;
		}
		
	}
	
	/**
	* Load all of the rules related to Pattern Engine
	*/
	public static function loadRules($options) {
		
		foreach (glob(__DIR__."/PatternEngine/Rules/*.php") as $filename) {
			$rule          = str_replace(".php","",str_replace(__DIR__."/PatternEngine/Rules/","",$filename));
			$ruleClass     = "\PatternLab\PatternEngine\Rules\\".$rule;
			self::$rules[] = new $ruleClass($options);
		}
		
	}
	
}
