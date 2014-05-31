<?php

/*!
 * Config Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Configures Pattern Lab by checking config files and required files
 *
 */

namespace PatternLab;

class Config {
	
	public    static $options        = array();
	protected static $userConfigPath = "/../../../config/config.ini";
	protected static $plConfigPath   = "/../../config/config.ini.default";
	protected static $cleanValues    = array("ie","id","patternStates","styleGuideExcludes");
	
	/**
	* Adds the config options to a var to be accessed from the rest of the system
	* If it's an old config or no config exists this will update and generate it.
	*/
	public static function loadOptions() {
		
		// can't add __DIR__ above so adding here
		self::$userConfigPath = __DIR__.self::$userConfigPath;
		self::$plConfigPath   = __DIR__.self::$plConfigPath;
		
		// make sure migrate doesn't happen by default
		$migrate     = false;
		$diffVersion = false;
		
		// double-check the default config file exists
		if (!file_exists(self::$plConfigPath)) {
			print "Please make sure config.ini.default exists before trying to have Pattern Lab build the config.ini file automagically.\n";
			exit;
		}
		
		// set the default config using the pattern lab config
		$defaultOptions = self::$options = parse_ini_file(self::$plConfigPath);
		
		// check to see if the user config exists, if not create it
		print "configuring pattern lab...\n";
		if (!file_exists(self::$userConfigPath)) {
			$migrate = true;
		} else {
			self::$options = parse_ini_file(self::$userConfigPath);
		}
		
		// compare version numbers
		$diffVersion = (self::$options["v"] != $defaultOptions["v"]) ? true : false;
		
		// run an upgrade and migrations if necessary
		if ($migrate || $diffVersion) {
			print "upgrading your version of pattern lab...\n";
			print "checking for migrations...\n";
			$m = new Migrator;
			$m->migrate(true);
			if ($migrate) {
				if (!@copy(self::$plConfigPath, self::$userConfigPath)) {
					print "Please make sure that Pattern Lab can write a new config to config/.\n";
					exit;
				}
			} else {
				self::$options = self::writeNewConfigFile(self::$options,$defaultOptions);
			}
		}
		
		// making sure the config isn't empty
		if (empty(self::$options)) {
			print "A set of configuration options is required to use Pattern Lab.\n";
			exit;
		}
		
		// set-up the source & public dirs
		self::$options["sourceDir"]        = rtrim(self::$options["sourceDir"],"\\");
		self::$options["publicDir"]        = rtrim(self::$options["publicDir"],"\\");
		self::$options["patternSourceDir"] = "/../../../".self::$options["sourceDir"]."/_patterns".DIRECTORY_SEPARATOR;
		self::$options["patternPublicDir"] = "/../../../".self::$options["publicDir"]."/patterns".DIRECTORY_SEPARATOR;
		self::$options["sourceDir"]        = __DIR__."/../../../".self::$options["sourceDir"];
		self::$options["publicDir"]        = __DIR__."/../../../".self::$options["publicDir"];
		
		// populate some standard variables out of the config
		foreach (self::$options as $key => $value) {
			
			// if the variables are array-like make sure the properties are validated/trimmed/lowercased before saving
			if (in_array($key,self::$cleanValues)) {
				$values = explode(",",$value);
				array_walk($values,'PatternLab\Util::trim');
				self::$options[$key] = $values;
			} else if ($key == "ishControlsHide") {
				self::$options[$key] = new \stdClass();
				$class = self::$options[$key];
				if ($value != "") {
					$values = explode(",",$value);
					foreach($values as $value2) {
						$value2 = trim($value2);
						$class->$value2 = true;
					}
				}
				if (self::$options["pageFollowNav"] == "false") {
					$value = "tools-follow";
					$class->$value = true;
				}
				if (self::$options["autoReloadNav"] == "false") {
					$value = "tools-reload";
					$class->$value = true;
				}
				$toolssnapshot = "tools-snapshot"; // i was an idgit and used dashes
				if (!isset($class->$toolssnapshot)) {
					if (!is_dir(self::$options["patternSourceDir"]."/snapshots")) {
						$class->$toolssnapshot = true;
					}
				}
			}
			
		}
		
		// set the cacheBuster
		self::$options["cacheBuster"] = (self::$options["cacheBusterOn"] == "false") ? 0 : time();
		
		// provide the default for enable CSS. performance hog so it should be run infrequently
		self::$options["enableCSS"] = false;
		
	}
	
	/**
	* Use the default config as a base and update it with old config options. Write out a new user config.
	* @param  {Array}        the old configuration file options
	* @param  {Array}        the default configuration file options
	*
	* @return {Array}        the new configuration
	*/
	protected static function writeNewConfigFile($oldOptions,$defaultOptions) {
		
		// iterate over the old config and replace values in the new config
		foreach ($oldOptions as $key => $value) {
			if ($key != "v") {
				$defaultOptions[$key] = $value;
			}
		}
		
		// create the output data
		$configOutput = "";
		foreach ($defaultOptions as $key => $value) {
			$configOutput .= $key." = \"".$value."\"\n";
		}
		
		// write out the new config file
		file_put_contents(self::$userConfigPath,$configOutput);
		
		return $defaultOptions;
		
	}
	
}
