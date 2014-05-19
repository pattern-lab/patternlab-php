<?php

/*!
 * Pattern Lab Pattern Info Rules Class - v0.7.12
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab;

class PatternInfoRule {
	
	public $depthProp;
	public $extProp;
	public $isDirProp;
	public $isFileProp;
	public $searchProp;
	protected $sourceDir;
	protected $dirSep;
	
	public function __construct($options) {
		
		$this->patternSourceDir = $options["patternSourceDir"];
		
	}
	
	public function testRule($depth, $ext, $isDir, $isFile, $name) {
		
		if (($this->depthProp != 3) && ($depth != $this->depthProp)) {
			return false;
		}
		
		if (($ext == $this->extProp) && ($isDir == $this->isDirProp) && ($isFile == $this->isFileProp)) {
			if ($this->searchProp != "") {
				return (strpos($name,$this->searchProp) !== false) ? true : false;
			} else {
				return true;
			}
		}
		
		return false;
		
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
	
	protected function findKey($haystack,$needle,$attribute) {
		$key = false;
		foreach ($haystack as $strawKey => $strawValues) {
			if ($strawValues[$attribute] == $needle) {
				$key = $strawKey;
				break;
			}
		}
		return $key;
	}
}