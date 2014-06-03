<?php

/*!
 * Template Helper Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Set-ups the vars for the template loader
 *
 */

namespace PatternLab\Template;

use \PatternLab\Config;
use \PatternLab\Template\Loader;

class Helper {
	
	public static $patternHead;
	public static $patternFoot;
	public static $mainPageHead;
	public static $mainPageFoot;
	public static $filesystemLoader;
	public static $htmlLoader;
	
	/**
	* Set-up default vars
	*/
	public static function setup() {
		
		// load pattern-lab's resources
		$htmlHead               = file_get_contents(__DIR__."/../../../templates/pattern-header-footer/header.html");
		$htmlFoot               = file_get_contents(__DIR__."/../../../templates/pattern-header-footer/footer.html");
		$extraFoot              = file_get_contents(__DIR__."/../../../templates/pattern-header-footer/footer-pattern.html");
		
		// gather the user-defined header and footer information
		$patternHeadPath        = Config::$options["sourceDir"]."/_meta/_00-head.mustache";
		$patternFootPath        = Config::$options["sourceDir"]."/_meta/_01-foot.mustache";
		$patternHead            = (file_exists($patternHeadPath)) ? file_get_contents($patternHeadPath) : "";
		$patternFoot            = (file_exists($patternFootPath)) ? file_get_contents($patternFootPath) : "";
		
		// add pattern lab's resource to the user-defined files
		self::$patternHead      = str_replace("{% pattern-lab-head %}",$htmlHead,$patternHead);
		self::$patternFoot      = str_replace("{% pattern-lab-foot %}",$extraFoot.$htmlFoot,$patternFoot);
		self::$mainPageHead     = self::$patternHead;
		self::$mainPageFoot     = str_replace("{% pattern-lab-foot %}",$htmlFoot,$patternFoot);
		
		// add the generic loaders
		$templateLoader         = new Loader();
		self::$filesystemLoader = $templateLoader->fileSystem();
		self::$htmlLoader       = $templateLoader->vanilla();
		
	}
	
}