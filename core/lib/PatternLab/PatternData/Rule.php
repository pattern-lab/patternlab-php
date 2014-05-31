<?php

/*!
 * Pattern Data Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\PatternData;

class Rule {
	
	protected $depthProp;
	protected $extProp;
	protected $isDirProp;
	protected $isFileProp;
	protected $searchProp;
	protected $ignoreProp;
	
	public function __construct($options) {
		
		// nothing here yet
		
	}
	
	/**
	* Test the Pattern Data Rules to see if a Rule matches the supplied values
	* @param  {Integer}      the depth of the item
	* @param  {String}       the extension of the item
	* @param  {Boolean}      if the item is a directory
	* @param  {Boolean}      if the item is a file
	* @param  {String}       the name of the item
	*
	* @return {Boolean}      whether the test was succesful or not
	*/
	public function test($depth, $ext, $isDir, $isFile, $name) {
		
		if (($this->depthProp != 3) && ($depth != $this->depthProp)) {
			return false;
		}
		
		if (($this->compareProp($ext,$this->extProp)) && ($isDir == $this->isDirProp) && ($isFile == $this->isFileProp)) {
			$result = true;
			if ($this->searchProp != "") {
				$result = $this->compareProp($name,$this->searchProp);
			}
			if ($this->ignoreProp != "") {
				$result = ($this->compareProp($name,$this->ignoreProp)) ? false : true;
			}
			return $result;
		}
		
		return false;
		
	}
	
	/**
	* Compare the search and ignore props against the name.
	* Can use && or || in the comparison
	* @param  {String}       the name of the item
	* @param  {String}       the value of the property to compare
	*
	* @return {Boolean}      whether the compare was successful or not
	*/
	protected function compareProp($name,$propCompare) {
		
		if (($name == "") && ($propCompare == "")) {
			$result = true;
		} else if ((($name == "") && ($propCompare != "")) || (($name != "") && ($propCompare == ""))) {
			$result = false;
		} else if (strpos($propCompare,"&&") !== false) {
			$result = true;
			$props  = explode("&&",$propCompare);
			foreach ($props as $prop) {
				$pos    = (strpos($name,$prop) !== false) ? true : false;
				$result = ($result && $pos);
			}
		} else if (strpos($propCompare,"||") !== false) {
			$result = false;
			$props  = explode("||",$propCompare);
			foreach ($props as $prop) {
				$pos    = (strpos($name,$prop) !== false) ? true : false;
				$result = ($result || $pos);
			}
		} else {
			$result = (strpos($name,$propCompare) !== false) ? true : false;
		}
		
		return $result;
		
	}
	
	/**
	* Get the name for a given pattern sans any possible digits used for reordering
	* @param  {String}       the pattern based on the filesystem name
	* @param  {Boolean}      whether or not to strip slashes from the pattern name
	*
	* @return {String}       a lower-cased version of the pattern name
	*/
	protected function getPatternName($pattern, $clean = true) {
		$patternBits = explode("-",$pattern,2);
		$patternName = (((int)$patternBits[0] != 0) || ($patternBits[0] == '00')) ? $patternBits[1] : $pattern;
		return ($clean) ? (str_replace("-"," ",$patternName)) : $patternName;
	}
	
}