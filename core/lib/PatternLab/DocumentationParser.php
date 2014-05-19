<?php

/*!
 * Pattern Lab Pattern Documentation Parser Class - v0.7.12
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab;

use \Symfony\Component\Yaml\Yaml;
use \Michelf\MarkdownExtra;

class DocumentationParser {
	
	protected static $lineEndingsSet = false;
	
	/**
	* Returns the last error message when building a JSON file. Mimics json_last_error_msg() from PHP 5.5
	* @param  {String}       the file that generated the error
	*/
	public static function parse($text) {
		
		self::setLineEndings();
		
		// set-up defaults
		$yaml     = array();
		$markdown = "";
		
		// read in the content
		// based on: https://github.com/mnapoli/FrontYAML/blob/master/src/Parser.php
		$lines = explode(PHP_EOL, $text);
		
		if (count($lines) <= 1) {
			$markdown = self::convertMarkdown($text);
			return array($yaml,$markdown);
		}
		
		if (rtrim($lines[0]) !== '---') {
			$markdown = self::convertMarkdown($text);
			return array($yaml,$markdown);
		}
		
		$head = array();
		unset($lines[0]);
		$i = 1;
		foreach ($lines as $line) {
			if ($line === '---') {
				break;
			}
			$head[] = $line;
			$i++;
		}
		
		$head = implode(PHP_EOL, $head);
		$body = implode(PHP_EOL, array_slice($lines, $i));
		
		$yaml     = self::convertYAML($head);
		$markdown = self::convertMarkdown($body);
		
		return array($yaml,$markdown);
		
	}
	
	/**
	* Returns the last error message when building a JSON file. Mimics json_last_error_msg() from PHP 5.5
	* @param  {String}       the file that generated the error
	*/
	public static function convertMarkdown($text) {
		$markdown = MarkdownExtra::defaultTransform($text);
		return $markdown;
	}
	
	public static function convertYAML($text) {
		$yaml = Yaml::parse($text);
		return $yaml;
	}
	
	public static function getYAML($text) {
		list($yaml,$markdown) = self::parse($text);
		return $yaml;
	}
	
	public static function getMarkdown($text) {
		list($yaml,$markdown) = self::parse($text);
		return $markdown;
	}
	
	protected static function setLineEndings() {
		if (!self::$lineEndingsSet) {
			ini_set("auto_detect_line_endings", true);
			self::$lineEndingsSet = true;
		}
	}
	
}