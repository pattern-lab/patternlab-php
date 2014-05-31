<?php

/*!
 * KSS Plugin Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\Parsers\Plugins;

use \Scan\Kss as KSSParser;

class KSS {
	
	/**
	* Parse the CSS, Sass, Less, and Stylus files in sourceDir to find KSS comments
	* @param  {String}       the sourceDir to be checked
	*
	* @return {Object}       an object containing the properties that were found for the styleguide
	*/
	public static function parse($sourceDir) {
		
		$styleguide = new KssParser\Parser($sourceDir);
		return $styleguide;
		
	}
	
}