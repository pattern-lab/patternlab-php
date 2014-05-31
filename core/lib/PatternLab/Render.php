<?php

/*!
 * Render Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Renders the pattern, pattern header, and pattern footer for storage in PatternData::$store
 *
 */

namespace PatternLab;

use \PatternLab\Data;
use \PatternLab\PatternEngine;
use \PatternLab\Template\Helper;

class Render {

	/**
	* Renders a given pattern file using Mustache and incorporating the provided data
	* @param  {String}       the filename of the file to be rendered
	* @param  {Array}        the data related to the pattern
	*
	* @return {String}       the mark-up as rendered by Mustache
	*/
	public static function Pattern($filePath,$data) {
		
		$pattern = PatternEngine::$patternLoader->render($filePath,$data);
		return $pattern;
		
	}
	
	/**
	* Renders a given mark-up (header) using Mustache and incorporating the provided data
	* @param  {String}       the mark-up to be rendered
	* @param  {Array}        the data related to the pattern
	*
	* @return {String}       the mark-up as rendered by Mustache
	*/
	public static function Header($html,$data) {
		
		$header = Helper::$htmlLoader->render($html,$data);
		return $header;
		
	}
	
	/**
	* Renders a given mark-up (footer) using Mustache and incorporating the provided data
	* @param  {String}       the mark-up to be rendered
	* @param  {Array}        the data related to the pattern
	*
	* @return {String}       the mark-up as rendered by Mustache
	*/
	public static function Footer($html,$data) {
		
		$footer = Helper::$htmlLoader->render($html,$data);
		return $footer;
		
	}
	
}
