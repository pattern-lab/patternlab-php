<?php

/*!
 * Pattern Lab Mustache Class - v0.7.12
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Provides generic access to loading Mustache instances
 *
 */

namespace PatternLab;

class TemplateHelper {
	
	public $patternHead;
	public $patternFoot;
	public $mainPageHead;
	public $mainPageFoot;
	
	/**
	* Set-up default vars
	*/
	public function __construct($sp) {
		
		// load pattern-lab's resources
		$htmlHead           = file_get_contents(__DIR__."/../../templates/pattern-header-footer/header.html");
		$htmlFoot           = file_get_contents(__DIR__."/../../templates/pattern-header-footer/footer.html");
		$extraFoot          = file_get_contents(__DIR__."/../../templates/pattern-header-footer/footer-pattern.html");
		
		// gather the user-defined header and footer information
		$patternHeadPath    = __DIR__.$sp."00-atoms/00-meta/_00-head.mustache";
		$patternFootPath    = __DIR__.$sp."00-atoms/00-meta/_01-foot.mustache";
		$patternHead        = (file_exists($patternHeadPath)) ? file_get_contents($patternHeadPath) : "";
		$patternFoot        = (file_exists($patternFootPath)) ? file_get_contents($patternFootPath) : "";
		
		// add pattern lab's resource to the user-defined files
		$this->patternHead  = str_replace("{% pattern-lab-head %}",$htmlHead,$patternHead);
		$this->patternFoot  = str_replace("{% pattern-lab-foot %}",$extraFoot.$htmlFoot,$patternFoot);
		$this->mainPageHead = $this->patternHead;
		$this->mainPageFoot = str_replace("{% pattern-lab-foot %}",$htmlFoot,$patternFoot);
		
	}
	
}