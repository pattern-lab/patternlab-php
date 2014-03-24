<?php

/*!
 * Pattern Lab Configurer Class - v0.7.12
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
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
		
	}
	
	/**
	* Returns the appropriate config. If it's an old version it updates the config and runs migrations
	* @param  {String}       the version number for Pattern Lab from builder.php
	*
	* @return {Array}        the configuration
	*/
	public function getConfig() {
		
		// make sure migrate doesn't happen by default
		$migrate     = false;
		$diffVersion = false;
		
		// double-check the default config file exists
		if (!file_exists($this->plConfigPath)) {
			print "Please make sure config.ini.default exists before trying to have Pattern Lab build the config.ini file automagically.\n";
			exit;
		}
		
		// set the default config using the pattern lab config
		$config        = parse_ini_file($this->plConfigPath);
		$defaultConfig = $config;
		
		// check to see if the user config exists, if not create it
		print "configuring pattern lab...\n";
		if (!file_exists($this->userConfigPath)) {
			$migrate = true;
		} else {
			$config = parse_ini_file($this->userConfigPath);
		}
		
		// compare version numbers
		$diffVersion = ($config["v"] != $defaultConfig["v"]) ? true : false;
		
		// run an upgrade and migrations if necessary
		if ($migrate || $diffVersion) {
			print "upgrading your version of pattern lab...\n";
			print "checking for migrations...\n";
			$m = new Migrator;
			$m->migrate(true);
			if ($migrate) {
				if (!@copy($this->plConfigPath, $this->userConfigPath)) {
					print "Please make sure that Pattern Lab can write a new config to config/.\n";
					exit;
				}
			} else {
				$config = $this->writeNewConfig($config,$defaultConfig);
			}
		}
		
		return $config;
		
	}
	
	/**
	* Use the default config as a base and update it with old config options. Write out a new user config.
	* @param  {Array}        the old configuration file options
	* @param  {Array}        the default configuration file options
	*
	* @return {Array}        the new configuration
	*/
	protected function writeNewConfig($oldConfig,$defaultConfig) {
		
		// iterate over the old config and replace values in the new config
		foreach ($oldConfig as $key => $value) {
			if ($key != "v") {
				$defaultConfig[$key] = $value;
			}
		}
		
		// create the output data
		$configOutput = "";
		foreach ($defaultConfig as $key => $value) {
			$configOutput .= $key." = \"".$value."\"\n";
		}
		
		// write out the new config file
		file_put_contents($this->userConfigPath,$configOutput);
		
		return $defaultConfig;
		
	}
	
}
