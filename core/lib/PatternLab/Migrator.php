
<?php

/*!
 * Pattern Lab Migrator Class - v0.6.2
 *
 * Copyright (c) 2013-2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Configures Pattern Lab by checking config files and required files
 *
 */

namespace PatternLab;

class Migrator {
	
	/**
	* Make sure the config paths are set
	*/
	public function __construct() {
		// don't do anything
	}
	
	/**
	* Read through the migrations and move files as needed
	* @param  {Array}        the old configuration file
	*
	* @return {Array}        the new configuration
	*/
	protected function migrate($version) {

		$objects = new \DirectoryIterator(__DIR__."/../../migrations/");
		$objects->setFlags(\FilesystemIterator::SKIP_DOTS);

		foreach ($objects as $name => $object) {
		    print $name."\n";
		}

	}
	
}
