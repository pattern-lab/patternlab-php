<?php

/*!
 * Mustache Pattern Loader Class - v0.7.12
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * The Mustache Pattern Loader has been modified from the FilesystemLoader
 * in Justin Hileman's implementation of Mustache
 *
 */

namespace PatternLab\PatternLoaders;

class Mustache implements \Mustache_Loader {
	
	private $baseDir;
	private $extension    = '.mustache';
	private $templates    = array();
	private $patternPaths = array();
	
	/**
	 * Mustache filesystem Loader constructor.
	 *
	 * Passing an $options array allows overriding certain Loader options during instantiation:
	 *
	 *	 $options = array(
	 *		 // The filename extension used for Mustache templates. Defaults to '.mustache'
	 *		 'extension' => '.ms',
	 *	 );
	 *
	 * @throws Mustache_Exception_RuntimeException if $baseDir does not exist.
	 *
	 * @param string $baseDir Base directory containing Mustache template files.
	 * @param array  $options Array of Loader options (default: array())
	 */
	public function __construct($baseDir, array $options = array()) {
		
		$this->baseDir = rtrim(realpath($baseDir), '/');
		
		if (!is_dir($this->baseDir)) {
			throw new \Mustache_Exception_RuntimeException(sprintf('FilesystemLoader baseDir must be a directory: %s', $baseDir));
		}
		
		if (array_key_exists('extension', $options)) {
			if (empty($options['extension'])) {
				$this->extension = '';
			} else {
				$this->extension = '.' . ltrim($options['extension'], '.');
			}
		}
		
		if (array_key_exists('patternPaths', $options)) {
			$this->patternPaths = $options['patternPaths'];
		}
		
		$this->patternLoader = new \PatternLab\PatternLoader($this->patternPaths);
		
	}
	
	/**
	 * Load a Template by name.
	 *
	 *	 $loader = new Mustache_Loader_FilesystemLoader(dirname(__FILE__).'/views');
	 *	 $loader->load('admin/dashboard'); // loads "./views/admin/dashboard.mustache";
	 *
	 * @param string $name
	 *
	 * @return string Mustache Template source
	 */
	public function load($name) {
		
		if (!isset($this->templates[$name])) {
			try {
				$this->templates[$name] = $this->loadFile($name);
			} catch (Exception $e) {
				print "The partial, ".$name.", wasn't found so a pattern failed to build.\n";
			}
		}
		
		return (isset($this->templates[$name])) ? $this->templates[$name] : false;
		
	}

	/**
	 * Helper function for loading a Mustache file by name.
	 *
	 * @throws Mustache_Exception_UnknownTemplateException If a template file is not found.
	 *
	 * @param string $name
	 *
	 * @return string Mustache Template source
	 */
	protected function loadFile($name) {
		
		// get pattern data
		list($partialName,$styleModifier,$parameters) = $this->patternLoader->getPartialInfo($name);
		
		// get the real file path for the pattern
		$fileName = $this->getFileName($partialName);
		
		// throw error if path is not found
		if (!file_exists($fileName)) {
			throw new \Mustache_Exception_UnknownTemplateException($fileName);
		}
		
		// get the file data
		$fileData = file_get_contents($fileName);
		
		// if the pattern name had a style modifier find & replace it
		if (count($styleModifier) > 0) {
			$fileData = $this->patternLoader->findReplaceParameters($fileData, $styleModifier);
		}
		
		// if the pattern name had parameters find & replace them
		if (count($parameters) > 0) {
			$fileData = $this->patternLoader->findReplaceParameters($fileData, $parameters);
		}
		
		return $fileData;
		
	}
	
	/**
	 * Helper function for getting a Mustache template file name.
	 * @param  {String}    the pattern type for the pattern
	 * @param  {String}    the pattern sub-type
	 *
	 * @return {Array}     an array of rendered partials that match the given path
	 */
	protected function getFileName($name) {
		
		// defaults
		$fileName = "";
		$dirSep   = DIRECTORY_SEPARATOR;
		
		// test to see what kind of path was supplied
		$posDash  = strpos($name,"-");
		$posSlash = strpos($name,$dirSep);
		
		if (($posSlash === false) && ($posDash !== false)) {
			$fileName = $this->baseDir.$dirSep.$this->patternLoader->getPatternFileName($name);
		} else {
			$fileName = $this->baseDir.$dirSep.$name;
		}
		
		if (substr($fileName, 0 - strlen($this->extension)) !== $this->extension) {
			$fileName .= $this->extension;
		}
		
		return $fileName;
		
	}
	
}
