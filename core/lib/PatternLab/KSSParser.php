<?php

/*!
 * Pattern Lab Pattern KSS Class - v0.7.12
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab;

use \Scan\Kss;

class KSSParser {
	
	public static function parse($sourceDir) {
		
		$styleguide = new Kss\Parser($sourceDir);
		return $styleguide;
		
	}
	
}