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

class TemplateLoader {

	/**
	* Load a new Mustache instance that uses the File System Loader
	*
	* @return {Object}       an instance of the Mustache engine
	*/
	public static function fileSystem() {
		return new \Mustache_Engine(array(
						"loader" => new \Mustache_Loader_FilesystemLoader(__DIR__."/../../templates/"),
						"partials_loader" => new \Mustache_Loader_FilesystemLoader(__DIR__."/../../templates/partials/")
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