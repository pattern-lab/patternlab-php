<?php

/*!
 * Pattern Lab Configurer Class - v0.6.2
 *
 * Copyright (c) 2013-2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Configures Pattern Lab by checking config files and required files
 *
 */

namespace PatternLab;

class Configurer {
	
	protected $userConfigPath;
	protected $plConfigPath;
	
	/**
	* Make sure the config paths are set
	*/
	public function __construct() {
		
		// set-up the configuration options for patternlab
		$this->userConfigPath = __DIR__."/../../../config/config.ini";
		$this->plConfigPath   = __DIR__."/../../config/config.ini.default";
		
		// double-check the default config file exists
		if (!file_exists($this->plConfigPath)) {
			print "Please make sure config.ini.default exists before trying to have Pattern Lab build the config.ini file automagically.\n";
			exit;
		}
		
	}
	
	/**
	* Returns the appropriate config. If it's an old version it updates the config and runs migrations
	* @param  {String}       the version number for Pattern Lab from builder.php
	*
	* @return {Array}        the configuration
	*/
	public function getConfig($version = "") {
		
		// make sure a version number has been set
		if ($version == "") {
			print "Calling getConfig() requires a version number.\n";
			exit;
		}
		
		// check the config
		if (!($config = @parse_ini_file($this->userConfigPath))) {
			
			// config.ini didn't exist so attempt to create it using the default file
			if (!@copy($this->plConfigPath, $this->userConfigPath)) {
				print "Please make sure config.ini.default exists before trying to have Pattern Lab build the config.ini file automagically. Check permissions of config/.\n";
				exit;
			} else {
				$config = parse_ini_file($this->userConfigPath);
			}
			
		}
		
		// check the config version and update it if necessary
		if (!isset($config["v"]) || ($config["v"] != $version)) {
			$config = $this->writeNewConfig($config);
		}
		
		return $config;
		
	}
	
	/**
	* Write out a new config using the previous version
	* @param  {Array}        the old configuration file
	*
	* @return {Array}        the new configuration
	*/
	protected function writeNewConfig($config) {
		
		// set-up
		$configOutput = "";
		$oldConfig    = $config;
		
		// get the new config options
		$config       = parse_ini_file($this->plConfigPath);
		
		// iterate over the old config and replace values in the new config
		foreach ($oldConfig as $key => $value) {
			if ($key != "v") {
				$config[$key] = $value;
			}
		}
		
		// create the output data
		foreach ($config as $key => $value) {
			$configOutput .= $key." = \"".$value."\"\n";
		}
		
		// write out the new config file
		file_put_contents($this->userConfigPath,$configOutput);
		
		return $config;
		
	}
	
}
