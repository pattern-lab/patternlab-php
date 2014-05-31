<?php

/*!
 * Snapshot Filter Iterator
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Based on http://www.php.net/manual/en/class.recursivefilteriterator.php#103830
 *
 */

namespace PatternLab\Snapshot;

class FilterIterator extends \RecursiveFilterIterator {
	
	public static $FILTERS = array(
		'snapshots',
		'.DS_Store'
	);
	
	public function accept() {
		return !in_array($this->current()->getFilename(),self::$FILTERS,true);
	}
	
}