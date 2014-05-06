<?php

/*!
 * Pattern Lab Snapshot Filter Iterator - v0.7.12
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Based on http://www.php.net/manual/en/class.recursivefilteriterator.php#103830
 *
 */

namespace PatternLab;

class SnapshotFilterIterator extends \RecursiveFilterIterator {
	
	public static $FILTERS = array(
		'snapshots',
		'.DS_Store'
	);
	
	public function accept() {
		return !in_array($this->current()->getFilename(),self::$FILTERS,true);
	}
	
}