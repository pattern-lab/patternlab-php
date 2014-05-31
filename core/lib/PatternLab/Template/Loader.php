<?php

/*!
 * Template Loader Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Provides the hooks to the Mustache engine to generate generic templates
 *
 */

namespace PatternLab\Template;

class Loader {
	
	/**
	* Load a new Mustache instance that uses the File System Loader
	*
	* @return {Object}       an instance of the Mustache engine
	*/
	public static function fileSystem() {
		return new \Mustache_Engine(array(
						"loader" => new \Mustache_Loader_FilesystemLoader(__DIR__."/../../../templates/"),
						"partials_loader" => new \Mustache_Loader_FilesystemLoader(__DIR__."/../../../templates/partials/")
		));
	}
	
	/**
	* Load a new Mustache instance that is just a vanilla Mustache rendering engine
	*
	* @return {Object}       an instance of the Mustache engine
	*/
	public static function vanilla() {
		return new \Mustache_Engine;
	}
	
}