<?php

/*!
 * Pattern Lab Generator Class - v0.1
 *
 * Copyright (c) 2013 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Compiles and moves all files in the source/patterns dir to public/patterns dir.
 * Vast majority of logic is in builder.lib.php
 *
 */

class Generator extends Builder {
	
	/**
	* Use the Builder __construct to gather the config variables
	*/
	public function __construct() {
		
		// construct the parent
		parent::__construct();
		
	}
	
	/**
	* Main logic. Gathers data, gets partials, and generates patterns
	* Also generates the main index file and styleguide
	*/
	public function generate() {
		
		// gather data
		$this->gatherData();
		
		// render out the patterns and move them to public/patterns
		$this->renderAndMove();
		
		// render out the index and style guide
		$this->generateMainPages();
		
		// check the user-supplied watch files (e.g. css)
		$i = 0;
		foreach($this->wf as $wf) {
			$this->moveFile($wf,$this->mf[$i]);
			$i++;
		}
		
		// update the change time so the auto-reload will fire (doesn't work for the index and style guide)
		$this->updateChangeTime();
		
	}
	
}