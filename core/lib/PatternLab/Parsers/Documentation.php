<?php

/*!
 * Documentation Parser Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Parses any .md files the Pattern Data rules find. Returns the data
 *
 */

namespace PatternLab\Parsers;

use \Symfony\Component\Yaml\Yaml;
use \Michelf\MarkdownExtra;

class Documentation {
	
	protected static $lineEndingsSet = false;
	
	/**
	* Convert markdown data into proper mark-up
	* @param  {String}       the text to be converted
	*
	* @return {String}       the converted mark-up
	*/
	public static function convertMarkdown($text) {
		$markdown = MarkdownExtra::defaultTransform($text);
		return $markdown;
	}
	
	/**
	* Parse YAML data into an array
	* @param  {String}       the text to be parsed
	*
	* @return {Array}        the parsed content
	*/
	public static function convertYAML($text) {
		$yaml = Yaml::parse($text);
		return $yaml;
	}
	
	/**
	* Return only the relevant YAML from the given text
	* @param  {String}       the text to be parsed
	*
	* @return {Array}        the parsed content
	*/
	public static function getYAML($text) {
		list($yaml,$markdown) = self::parse($text);
		return $yaml;
	}
	
	/**
	* Return only the relevant converted markdown from the given text
	* @param  {String}       the text to be parsed
	*
	* @return {Array}        the parsed content
	*/
	public static function getMarkdown($text) {
		list($yaml,$markdown) = self::parse($text);
		return $markdown;
	}
	
	/**
	* Find and convert YAML and markdown in Pattern Lab documention files
	* @param  {String}       the text to be chunked for YAML and markdown
	*
	* @return {Array}        array containing both the YAML and converted markdown
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
	* Set the proper line endings so the text can be parsed properly
	*/
	protected static function setLineEndings() {
		if (!self::$lineEndingsSet) {
			ini_set("auto_detect_line_endings", true);
			self::$lineEndingsSet = true;
		}
	}
	
}