<?php

/*!
 * Data Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Acts as the overall data store for Pattern Lab. Takes in data found in JSON and YAML files.
 *
 */

namespace PatternLab;

use \PatternLab\Config;
use \Symfony\Component\Yaml\Yaml;

class Data {
	
	public    static $store        = array();
	protected static $reservedKeys = array("listItems","cacheBuster","link","patternSpecific","patternFooterData");
	
	/**
	* Grab a copy of the $store
	*
	* @return {Array}        a copy of the store
	*/
	public static function copy() {
		return self::$store;
	}
	
	/**
	* Gather data from source/_data/_data.json, source/_data/_listitems.json, and pattern-specific json files
	*
	* Reserved attributes:
	*    - Data::$store["listItems"] : listItems from listitems.json, duplicated into separate arrays for Data::$store["listItems"]["one"], Data::$store["listItems"]["two"]... etc.
	*    - Data::$store["link"] : the links to each pattern
	*    - Data::$store["cacheBuster"] : the cache buster value to be appended to URLs
	*    - Data::$store["patternSpecific"] : holds attributes from the pattern-specific data files
	*
	* @return {Array}        populates Data::$store
	*/
	public static function gather($options = array()) {
		
		// default vars
		$found         = false;
		$dataJSON      = array();
		$dataYAML      = array();
		$listItemsJSON = array();
		$listItemsYAML = array();
		
		// gather the data from the main source data.json
		if (file_exists(Config::$options["sourceDir"]."/_data/_data.json")) {
			$file     = file_get_contents(Config::$options["sourceDir"]."/_data/_data.json");
			$dataJSON = json_decode($file,true);
			if ($jsonErrorMessage = JSON::hasError()) {
				JSON::lastErrorMsg("_data/_data.json",$jsonErrorMessage,$data);
			}
			$found = true;
		}
		
		// gather the data from the main source data.yaml
		if (file_exists(Config::$options["sourceDir"]."/_data/_data.yaml")) {
			$file     = file_get_contents(Config::$options["sourceDir"]."/_data/_data.yaml");
			$dataYAML = Yaml::parse($file);
			$found = true;
		} 
		
		if (!$found) {
			print "Missing a required file, source/_data/_data.json. Aborting.\n";
			exit;
		}
		
		self::$store = array_replace_recursive($dataJSON,$dataYAML);
		
		if (is_array(self::$store)) {
			foreach (self::$reservedKeys as $reservedKey) {
				if (array_key_exists($reservedKey,self::$store)) {
					print "\"".$reservedKey."\" is a reserved key in Pattern Lab. The data using that key in _data.json will be overwritten. Please choose a new key.\n";
				}
			}
		}
		
		$listItemsJSON = self::getListItems("data/_listitems.json");
		$listItemsYAML = self::getListItems("data/_listitems.yaml","yaml");
		
		self::$store["listItems"]       = array_replace_recursive($listItemsJSON,$listItemsYAML);
		self::$store["cacheBuster"]     = Config::$options["cacheBuster"];
		self::$store["link"]            = array();
		self::$store["patternSpecific"] = array();
		
	}
	
	/**
	* Generate the listItems array
	* @param  {String}       the filename for the pattern to be parsed
	* @param  {String}       the extension so that a flag switch can be used to parse data
	*
	* @return {Array}        the final set of list items
	*/
	protected static function getListItems($filepath,$ext = "json") {
		
		$listItems     = array();
		$listItemsData = array();
		
		// add list item data, makes 'listItems' a reserved word
		if (file_exists(Config::$options["sourceDir"]."/".$filepath)) {
			
			$file = file_get_contents(Config::$options["sourceDir"]."/".$filepath);
			
			if ($ext == "json") {
				$listItemsData = json_decode($file, true);
				if ($jsonErrorMessage = JSON::hasError()) {
					JSON::lastErrorMsg($filepath,$jsonErrorMessage,$listItems);
				}
			} else {
				$listItemsData = Yaml::parse($file);
			}
			
			
			$numbers = array("one","two","three","four","five","six","seven","eight","nine","ten","eleven","twelve");
			
			$i = 0;
			$k = 1;
			$c = count($listItemsData)+1;
			
			while ($k < $c) {
				
				shuffle($listItemsData);
				$itemsArray = array();
				
				while ($i < $k) {
					$itemsArray[] = $listItemsData[$i];
					$i++;
				}
				
				$listItems[$numbers[$k-1]] = $itemsArray;
				
				$i = 0;
				$k++;
				
			}
			
		}
		
		return $listItems;
		
	}
	
	/**
	* Get the final data array specifically for a pattern
	* @param  {String}       the filename for the pattern to be parsed
	* @param  {Array}        any extra data that should be added to the pattern specific data that's being returned
	*
	* @return {Array}        the final set of list items
	*/
	public static function getPatternSpecificData($patternPartial,$extraData = array()) {
		
		// if there is pattern-specific data make sure to override the default in $this->d
		$d = self::copy();
		
		if (isset($d["patternSpecific"]) && array_key_exists($patternPartial,$d["patternSpecific"])) {
			
			if (!empty($d["patternSpecific"][$patternPartial]["data"])) {
				$d = array_replace_recursive($d, $d["patternSpecific"][$patternPartial]["data"]);
			}
			
			if (!empty($d["patternSpecific"][$patternPartial]["listItems"])) {
				
				$numbers = array("one","two","three","four","five","six","seven","eight","nine","ten","eleven","twelve");
				
				$k = 0;
				$c = count($d["patternSpecific"][$patternPartial]["listItems"]);
				
				while ($k < $c) {
					$section = $numbers[$k];
					$d["listItems"][$section] = array_replace_recursive( $d["listItems"][$section], $d["patternSpecific"][$patternPartial]["listItems"][$section]);
					$k++;
				}
				
			}
			
		}
		
		if (!empty($extraData)) {
			$d = array_replace_recursive($d, $extraData);
		}
		
		unset($d["patternSpecific"]);
		
		return $d;
		
	}
	
	/**
	* Print out the data var. For debugging purposes
	*
	* @return {String}       the formatted version of the d object
	*/
	public static function printData() {
		print_r(self::$store);
	}
	
}