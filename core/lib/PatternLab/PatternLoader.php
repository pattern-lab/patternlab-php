<?php

/*!
 * Pattern Lab Builder Class - v0.7.12
 *
 * Copyright (c) 2013-2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Does the vast majority of heavy lifting for the Generator and Watch classes
 *
 */

namespace PatternLab;

class PatternLoader {
	
	private $extension    = '.mustache';
	private $patternPaths = array();
	
	/**
	* Set-up the pattern paths var
	*/
	public function __construct($patternPaths) {
		
		$this->patternPaths = $patternPaths;
		
	}
	
	/**
	 * Helper function to return the pattern file name
	 * @param  {String}     the name of the pattern
	 *
	 * @return {String}     the file path to the pattern
	 */
	public function getPatternFileName($name) {
		
		$patternFileName = "";
		
		list($patternType,$pattern) = $this->getPatternInfo($name);
		
		// see if the pattern is an exact match for patternPaths. if not iterate over patternPaths to find a likely match
		if (isset($this->patternPaths[$patternType][$pattern])) {
			$patternFileName = $this->patternPaths[$patternType][$pattern]["patternSrcPath"];
		} else if (isset($this->patternPaths[$patternType])) {
			foreach($this->patternPaths[$patternType] as $patternMatchKey=>$patternMatchValue) {
				$pos = strpos($patternMatchKey,$pattern);
				if ($pos !== false) {
					$patternFileName = $patternMatchValue["patternSrcPath"];
					break;
				}
			}
		}
		
		return $patternFileName;
		
	}
	
	/**
	 * Helper function to return the parts of a partial name
	 * @param  {String}     the name of the partial
	 *
	 * @return {Array}      the pattern type and the name of the pattern
	 */
	public function getPatternInfo($name) {
		
		$patternBits = explode("-",$name);
		
		$i = 1;
		$k = 2;
		$c = count($patternBits);
		$patternType = $patternBits[0];
		while (!isset($this->patternPaths[$patternType]) && ($i < $c)) {
			$patternType .= "-".$patternBits[$i];
			$i++;
			$k++;
		}
		
		$patternBits = explode("-",$name,$k);
		$pattern = $patternBits[count($patternBits)-1];
		
		return array($patternType, $pattern);
		
	}
	
	/**
	 * Helper function for finding if a partial name has style modifier or parameters
	 * @param  {String}     the pattern name
	 *
	 * @return {Array}      an array containing just the partial name, a style modifier, and any parameters
	 */
	public function getPartialInfo($partial) {
		
		$styleModifier = array();
		$parameters	= array();
		
		if (strpos($partial, "(") !== false) {
			$partialBits      = explode("(",$partial,2);
			$partial          = trim($partialBits[0]);
			$parametersString = substr($partialBits[1],0,(strlen($partialBits[1]) - strlen(strrchr($partialBits[1],")"))));
			$parameters       = $this->parseParameters($parametersString);
		}
		
		if (strpos($partial, ":") !== false) {
			$partialBits      = explode(":",$partial,2);
			$partial          = $partialBits[0];
			$styleModifier    = array("styleModifier" => $partialBits[1]);
		}
		
		return array($partial,$styleModifier,$parameters);
		
	}
	
	/**
	 * Helper function to find and replace the given parameters in a particular partial before handing it back to Mustache
	 * @param  {String}    the file contents
	 * @param  {Array}     an array of paramters to match
	 *
	 * @return {String}    the modified file contents
	 */
	public function findReplaceParameters($fileData, $parameters) {
		foreach ($parameters as $k => $v) {
			if ($v == "true") {
				$fileData = preg_replace('/{{\#([\s]*'.$k .'[\s]*)}}(.*?){{\/([\s]*'.$k .'[\s]*)}}/s','$2',$fileData); // {{# asdf }}STUFF{{/ asdf}}
				$fileData = preg_replace('/{{\^([\s]*'.$k .'[\s]*)}}(.*?){{\/([\s]*'.$k .'[\s]*)}}/s','',$fileData); // {{^ asdf }}STUFF{{/ asdf}}
			} else if ($v == "false") {
				$fileData = preg_replace('/{{\^([\s]*'.$k .'[\s]*)}}(.*?){{\/([\s]*'.$k .'[\s]*)}}/s','$2',$fileData); // {{# asdf }}STUFF{{/ asdf}}
				$fileData = preg_replace('/{{\#([\s]*'.$k .'[\s]*)}}(.*?){{\/([\s]*'.$k .'[\s]*)}}/s','',$fileData); // {{^ asdf }}STUFF{{/ asdf}}
			} else {
				$fileData = preg_replace('/{{([\s]*'.$k .'[\s]*)}}/', $v, $fileData); // {{ asdf }}
			}
		}
		return $fileData;
	}
	
	/**
	 * Helper function to parse the parameters and return them as an array
	 * @param  {String}     the parameter string
	 *
	 * @return {Array}      the keys and values for the parameters
	 */
	private function parseParameters($string) {
		
		$parameters     = array();
		$betweenSQuotes = false;
		$betweenDQuotes = false;
		$inKey          = true;
		$inValue        = false;
		$char           = "";
		$buffer         = "";
		$keyBuffer      = "";
		$strLength      = strlen($string);
		
		for ($i = 0; $i < $strLength; $i++) {
			
			$previousChar = $char;
			$char = $string[$i];
			
			if ($inKey && !$betweenDQuotes && !$betweenSQuotes && (($char == "\"") || ($char == "'"))) {
				// if inKey, a quote, and betweenQuotes is false ignore quote, set betweenQuotes to true and empty buffer to kill spaces
				($char == "\"") ? ($betweenDQuotes = true) : ($betweenSQuotes = true);
			} else if ($inKey && (($betweenDQuotes && ($char == "\"")) || ($betweenSQuotes && ($char == "'"))) && ($previousChar == "\\")) {
				// if inKey, a quote, betweenQuotes is true, and previous character is \ add to buffer
				$buffer   .= $char;
			} else if ($inKey && (($betweenDQuotes && ($char == "\"")) || ($betweenSQuotes && ($char == "'")))) {
				// if inKey, a quote, betweenQuotes is true set betweenQuotes to false, save as key buffer, empty buffer set inKey false
				$keyBuffer      = $buffer;
				$buffer         = "";
				$inKey          = false;
				$betweenSQuotes = false;
				$betweenDQuotes = false;
			} else if ($inKey && !$betweenDQuotes && !$betweenSQuotes && ($char == ":")) {
				// if inKey, a colon, betweenQuotes is false, save as key buffer, empty buffer, set inKey false set inValue true
				$keyBuffer = $buffer;
				$buffer    = "";
				$inKey     = false;
				$inValue   = true;
			} else if ($inKey) {
				// if inKey add to buffer
				$buffer   .= $char;
			} else if (!$inKey && !$inValue && ($char == ":")) {
				// if inKey is false, inValue false, and a colon set inValue true
				$inValue   = true;
			} else if ($inValue && !$betweenDQuotes && !$betweenSQuotes && (($char == "\"") || ($char == "'"))) {
				// if inValue, a quote, and betweenQuote is false set betweenQuotes to true and empty buffer to kill spaces
				($char == "\"") ? ($betweenDQuotes = true) : ($betweenSQuotes = true);
			} else if ($inValue && (($betweenDQuotes && ($char == "\"")) || ($betweenSQuotes && ($char == "'"))) && ($previousChar == "\\")) {
				// if inValue, a quote, betweenQuotes is true, and previous character is \ add to buffer
				$buffer   .= $char;
			} else if ($inValue && (($betweenDQuotes && ($char == "\"")) || ($betweenSQuotes && ($char == "'")))) {
				// if inValue, a quote, betweenQuotes is true set betweenQuotes to false, save to parameters array, empty buffer, set inValue false
				$buffer    = str_replace("\\\"","\"",$buffer);
				$buffer    = str_replace('\\\'','\'',$buffer);
				$parameters[trim($keyBuffer)] = trim($buffer);
				$buffer    = "";
				$inValue   = false;
				$betweenSQuotes = false;
				$betweenDQuotes = false;
			} else if ($inValue && !$betweenDQuotes && !$betweenSQuotes && ($char == ",")) {
				// if inValue, a comman, betweenQuotes is false, save to parameters array, empty buffer, set inValue false, set inKey true
				$parameters[trim($keyBuffer)] = trim($buffer);
				$buffer    = "";
				$inValue   = false;
				$inKey     = true;
			} else if ($inValue && (($i + 1) == $strLength)) {
				// if inValue and end of the string add to buffer, save to parameters array
				$buffer   .= $char;
				$parameters[trim($keyBuffer)] = trim($buffer);
			} else if ($inValue) {
				// if inValue add to buffer
				$buffer   .= $char;
			} else if (!$inValue && !$inKey && ($char == ",")) {
				// if inValue is false, inKey false, and a comma set inKey true
				$inKey = true;
			}
		}
		
		return $parameters;
		
	}
	
}
