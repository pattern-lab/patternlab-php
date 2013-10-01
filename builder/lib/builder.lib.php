<?php

/*!
 * Pattern Lab Builder Class - v0.3.3
 *
 * Copyright (c) 2013 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Does the vast majority of heavy lifting for the Generator and Watch classes
 *
 */

class Builder {

	// i was lazy when i started this project & kept (mainly) to two letter vars. sorry.
	protected $mpl;               // mustache pattern loader instance
	protected $mfs;               // mustache file system loader instance
	protected $d;                 // data from data.json files
	protected $sp;                // source patterns dir
	protected $pp;                // public patterns dir
	protected $ie;                // extensions to ignore
	protected $id;                // directories to ignore
	protected $contentSyncPort;   // for populating the websockets template partial
	protected $navSyncPort;       // for populating the websockets template partial
	protected $patternTypes;      // a list of pattern types that match the directory structure
	protected $patternPaths;      // the paths to patterns for use with mustache partials
	protected $patternTypesRegex; // the simple regex for the pattern types. used in getPath()
	protected $navItems;          // the items for the nav. includes view all links
	protected $viewAllPaths;      // the paths to the view all pages
	
	/**
	* When initializing the Builder class or the sub-classes make sure the base properties are configured
	* Also, create the config if it doesn't already exist
	*/
	public function __construct() {
		
		// set-up the configuration options for patternlab
		if (!($config = @parse_ini_file(__DIR__."/../../config/config.ini"))) {
			// config.ini didn't exist so attempt to create it using the default file
			if (!@copy(__DIR__."/../../config/config.ini.default", __DIR__."/../../config/config.ini")) {
				print "Please make sure config.ini.default exists before trying to have Pattern Lab build the config.ini file automagically. Check permissions of config/.";
				exit;
			} else {
				$config = parse_ini_file(__DIR__."/../../config/config.ini");
			}
		}
		
		// populate some standard variables out of the config
		foreach ($config as $key => $value) {
			
			// if the variables are array-like make sure the properties are validated/trimmed/lowercased before saving
			if (($key == "ie") || ($key == "id")) {
				$values = explode(",",$value);
				array_walk($values,'Builder::trim');
				$this->$key = $values;
			} else {
				$this->$key = $value;
			}
		}
		
		// generate patternTypes as well as patternPaths
		$this->gatherPatternPaths();
		
		// get nav items
		$this->gatherNavItems();
		
	}
	
	/**
	* Load a new Mustache instance that uses the Pattern Loader
	*
	* @return {Object}       an instance of the Mustache engine
	*/
	protected function loadMustachePatternLoaderInstance() {
		$this->mpl = new Mustache_Engine(array(
						"loader" => new Mustache_Loader_PatternLoader(__DIR__.$this->sp,array("patternPaths" => $this->patternPaths)),
						"partials_loader" => new Mustache_Loader_PatternLoader(__DIR__.$this->sp,array("patternPaths" => $this->patternPaths))
		));
	}
	
	/**
	* Load a new Mustache instance that uses the File System Loader
	*
	* @return {Object}       an instance of the Mustache engine
	*/
	protected function loadMustacheFileSystemLoaderInstance() {
		$this->mfs = new Mustache_Engine(array(
						"loader" => new Mustache_Loader_FilesystemLoader(__DIR__."/../../source/_patternlab-files/"),
						"partials_loader" => new Mustache_Loader_FilesystemLoader(__DIR__."/../../source/_patternlab-files/partials/")
		));
	}
	
	/**
	* Renders a given pattern file using Mustache and incorporating the provided data
	* @param  {String}       the filename of the file to be rendered
	* @param  {Object}       the instance of mustache to be used in the rendering
	*
	* @return {String}       the mark-up as rendered by Mustache
	*/
	protected function renderPattern($f) {
		
		// if there is pattern-specific data make sure to override the default in $this->d
		$d = $this->d;
		if (isset($d->patternSpecific) && array_key_exists($f,$d->patternSpecific)) {
			$d = (object) array_merge((array) $d, (array) $d->patternSpecific->$f);
		}
		
		return $this->mpl->render($f,$d);
		
	}
	
	/**
	* Generates the index page and style guide
	*/
	protected function generateMainPages() {
		
		// make sure $this->mfs is refreshed
		$this->loadMustacheFileSystemLoaderInstance();
		
		// render out the main pages and move them to public
		$this->navItems['contentsyncport'] = $this->contentSyncPort;
		$this->navItems['navsyncport']     = $this->navSyncPort;
		$this->navItems['patternpaths']    = json_encode($this->patternPaths);
		$this->navItems['viewallpaths']    = json_encode($this->viewAllPaths);
		$this->navItems['mqs']             = $this->gatherMQs();
		
		// grab the partials into a data object for the style guide
		$sd = $this->gatherPartials();
		
		// sort partials by patternLink
		usort($sd['partials'], "Builder::sortPartials");
		
		// render the "view all" pages
		$this->generateViewAllPages();
		
		// render the index page and the style guide
		$r = $this->mfs->render('index',$this->navItems);
		file_put_contents(__DIR__."/../../public/index.html",$r);
		$s = $this->mfs->render('styleguide',$sd);
		file_put_contents(__DIR__."/../../public/styleguide/html/styleguide.html",$s);
		
	}
	
	/**
	* Generates all of the patterns and puts them in the public directory
	*/
	protected function generatePatterns() {
		
		// make sure $this->mpl is refreshed
		$this->loadMustachePatternLoaderInstance();
		
		// loop over the pattern paths to generate patterns for each
		foreach($this->patternPaths as $patternType) {
			
			foreach($patternType as $pattern => $path) {
				
				$r = $this->generatePatternFile($path.".mustache");
				
				// if the pattern directory doesn't exist create it
				$path = str_replace("/","-",$path);
				if (!is_dir(__DIR__.$this->pp.$path)) {
					mkdir(__DIR__.$this->pp.$path);
					file_put_contents(__DIR__.$this->pp.$path."/".$path.".html",$r);
				} else {
					file_put_contents(__DIR__.$this->pp.$path."/".$path.".html",$r);
				}
				
			}
			
		}
		
	}
	
	/**
	* Generates a pattern with a header & footer
	* @param  {String}       the filename of the file to be rendered
	*
	* @return {String}       the final rendered pattern including the standard header and footer for a pattern
	*/
	private function generatePatternFile($f) {
		$hr = file_get_contents(__DIR__.$this->sp."../_patternlab-files/pattern-header-footer/header.html");
		$rf = $this->renderPattern($f);
		$fr = file_get_contents(__DIR__.$this->sp."../_patternlab-files/pattern-header-footer/footer.html");
		$fr = str_replace("{{ patternPartial }}",$this->getPatternPartial($f),$fr);
		return $hr."\n".$rf."\n".$fr;
	}
	
	/**
	* Generates the view all pages
	*/
	protected function generateViewAllPages() {
		
		// make sure $this->mfs is refreshed on each generation of view all. for some reason the mustache instance dies
		$this->loadMustacheFileSystemLoaderInstance();
		
		// add view all to each list
		$i = 0; $k = 0;
		foreach ($this->navItems['buckets'] as $bucket) {
			
			// make sure that the navItems index exists. catches issues with pages & templates
			if (isset($bucket["navItems"])) {
				
				foreach ($bucket["navItems"] as $navItem) {
					
					// make sure the navSubItems index exists. catches issues with empty folders
					if (isset($navItem["navSubItems"])) {
						
						foreach ($navItem["navSubItems"] as $subItem) {
							
							if ($subItem["patternName"] == "View All") {
								
								// get the pattern parts
								$patternType    = $subItem["patternType"];
								$patternSubType = $subItem["patternSubType"];
								
								// get all the rendered partials that match
								$sid = $this->gatherPartialsByMatch($patternType, $patternSubType);
								$sid["patternPartial"] = $subItem["patternPartial"];
								
								// render the viewall template
								$v = $this->mfs->render('viewall',$sid);
								
								// if the pattern directory doesn't exist create it
								$patternPath = $patternType."-".$patternSubType;
								if (!is_dir(__DIR__.$this->pp.$patternPath)) {
									mkdir(__DIR__.$this->pp.$patternPath);
									file_put_contents(__DIR__.$this->pp.$patternPath."/index.html",$v);
								} else {
									file_put_contents(__DIR__.$this->pp.$patternPath."/index.html",$v);
								}
								
							}
							
						}
						
					}
					
				}
				
			}
			
			$i++;
			$k = 0;
			
		}
		
	}
	
	/**
	* Gather data from source/_data/data.json, source/_data/listitems.json, and pattern-specific json files
	*
	* Reserved attributes: 
	*    - $this->d->listItems : listItems from listitems.json, duplicated into separate arrays for $this->d->listItems->one, $this->d->listItems->two, $this->d->listItems->three... etc.
	*    - $this->d->link : the links to each pattern
	*    - $this->d->patternSpecific : holds attributes from the pattern-specific data files
	*
	* @return {Array}        populates $this->d
	*/
	protected function gatherData() {
		
		// gather the data from the main source data.json
		if (file_exists(__DIR__."/../../source/_data/data.json")) {
			$this->d = (object) array_merge(array(), (array) json_decode(file_get_contents(__DIR__."/../../source/_data/data.json")));
		}
		
		// add list item data, makes 'listItems' a reserved word
		if (file_exists(__DIR__."/../../source/_data/listitems.json")) {
			
			$listItems = (array) json_decode(file_get_contents(__DIR__."/../../source/_data/listitems.json"));
			$numbers   = array("one","two","three","four","five","six","seven","eight","nine","ten","eleven","twelve");
			
			$i = 0;
			$k = 1;
			$c = count($listItems)+1;
			
			$this->d->listItems = new stdClass();
			
			while ($k < $c) {
				
				shuffle($listItems);
				$itemsArray = array();
				$this->d->listItems->$numbers[$k-1] = new stdClass();
				
				while ($i < $k) {
					$itemsArray[] = $listItems[$i];
					$i++;
				}
				
				$this->d->listItems->$numbers[$k-1] = $itemsArray;
				
				$i = 0;
				$k++;
				
			}
			
		}
		
		// add the link names for easy reference, makes 'link' a reserved word
		$this->d->link = new stdClass();
		foreach($this->patternPaths as $patternTypeName => $patterns) {
			
			foreach($patterns as $pattern => $path) {
				$patternName = $patternTypeName."-".$pattern;
				$path = str_replace("/","-",$path);
				$this->d->link->$patternName = "../../patterns/".$path."/".$path.".html";
			}
			
		}
		
		// add pattern specific data so it can override when a pattern (not partial!) is rendered
		// makes 'patternSpecific' a reserved word
		$this->d->patternSpecific = new stdClass();
		foreach($this->patternTypes as $patternType) {
			
			// $this->d->patternSpecific["pattern-name-that-matches-render.mustache"] = array of data;
			$patternTypeClean = $this->getPatternName($patternType,false);
			
			// find pattern data for pattern subtypes*/
			foreach(glob(__DIR__.$this->sp.$patternType."/*/*.json") as $filename) {
				$path = $this->getPath($filename,"j");
				if (in_array($path,$this->patternPaths[$patternTypeClean])) {
					$patternName = $path.".mustache";
					$this->d->patternSpecific->$patternName = (array) json_decode(file_get_contents(__DIR__."/../../source/_patterns/".$path.".json"));
				}
			}
			
			// find pattern data for pattern types that are flat (e.g. pages & templates)
			foreach(glob(__DIR__.$this->sp.$patternType."/*.json") as $filename) {
				$path = $this->getPath($filename,"j");
				if (in_array($path,$this->patternPaths[$patternTypeClean])) {
					$patternName = $path.".mustache";
					$this->d->patternSpecific->$patternName = (array) json_decode(file_get_contents(__DIR__."/../../source/_patterns/".$path.".json"));
				}
			}
			
		}
		
	}	
	
	/**
	* Finds Media Queries in CSS files in the source/css/ dir
	*
	* @return {Array}        an array of the appropriate MQs
	*/
	protected function gatherMQs() {
		
		$mqs = array();
		
		foreach(glob(__DIR__."/../../source/css/*.css") as $filename) {
			$data    = file_get_contents($filename);
			preg_match_all("/(min|max)-width:( |)(([0-9]{1,5})(\.[0-9]{1,20}|)(px|em))/",$data,$matches);
			foreach ($matches[3] as $match) {
				if (!in_array($match,$mqs)) {
					$mqs[] = $match;
				}
			}	
		}
		
		sort($mqs);
		return $mqs;
		
	}
	
	/**
	* Gathers the partials for the nav drop down in Pattern Lab
	*
	* @return {Array}        populates $this->navItems
	*/
	protected function gatherNavItems() {
		
		$b  = array("buckets" => array()); // the array that will contain the items
		$bi = 0;                           // track the number for the bucket array
		$ni = 0;                           // track the number for the nav items array
		$incrementNavItem = true;          // track nav item regeneration so we avoid rebuilding view all pages
		
		// iterate through each pattern type and add them as buckets
		foreach($this->patternTypes as $patternType) {
			
			// get the bits for a bucket and check to see if the first bit is a number
			$bucket = $this->getPatternName($patternType); // ok, it's not a pattern name but same functionality
			
			// add a new bucket
			$b["buckets"][$bi] = array("bucketNameLC" => strtolower($bucket),
									   "bucketNameUC" => ucwords($bucket)); 
			
			// see if a pattern has subtypes
			$patternSubTypes = glob(__DIR__.$this->sp.$patternType."/*",GLOB_ONLYDIR);
			if (count($patternSubTypes) == 0) {
				
				// find the patterns and add them to the nav
				foreach(glob(__DIR__.$this->sp.$patternType."/*.mustache") as $pattern) {
					
					$patternPathBits = explode("/",$pattern);
					if ($patternPathBits[count($patternPathBits) - 1][0] != "_") {
						
						// get the bits for a pattern and check to see if the first bit is a number
						$patternClean = substr($pattern,strlen(__DIR__.$this->sp.$patternType."/"));
						$patternClean = str_replace(".mustache","",$patternClean);
						$patternFinal = $this->getPatternName($patternClean);
						
						// add a new pattern
						$b["buckets"][$bi]["patternItems"][] = array("patternPath"    => $patternType."-".$patternClean."/".$patternType."-".$patternClean.".html",
																	 "patternName"    => ucwords($patternFinal),
																	 "patternPartial" => str_replace(" ","-",$bucket)."-".str_replace(" ","-",$patternFinal));
					}
					
				}
				
				// if all of the patterns for a given pattern type (e.g. atoms) were commented out we need to unset it
				if (!isset($b["buckets"][$bi]["patternItems"])) {
					unset($b["buckets"][$bi]);
					$bi--;
				}
				
			} else {
				
				// iterate over pattern sub-types
				foreach($patternSubTypes as $dir) {
					
					// NOTE: clean items still include directory numbers, final items DO NOT. made sense at the time i originally wrote it
					
					// get the bits for a directory and check to see if the first bit is a number
					$dirClean = substr($dir,strlen(__DIR__.$this->sp.$patternType."/"));
					$dirFinal = $this->getPatternName($dirClean); // ok, it's not a pattern name but same functionality
					
					// add a new section
					$b["buckets"][$bi]["navItems"][$ni] = array("sectionNameLC" => strtolower($dirFinal),
																"sectionNameUC" => ucwords($dirFinal));
					
					// iterate over patterns
					foreach(glob(__DIR__.$this->sp.$patternType."/".$dirClean."/*.mustache") as $pattern) {
						
						$patternPathBits = explode("/",$pattern);
						if ($patternPathBits[count($patternPathBits) - 1][0] != "_") {
							
							// get the bits for a pattern and check to see if the first bit is a number
							$patternClean = substr($pattern,strlen(__DIR__.$this->sp.$patternType."/".$dirClean."/"));
							$patternClean = str_replace(".mustache","",$patternClean);
							$patternFinal = $this->getPatternName($patternClean);
							
							// add a new pattern
							$b["buckets"][$bi]["navItems"][$ni]["navSubItems"][] = array("patternPath"    => $patternType."-".$dirClean."-".$patternClean."/".$patternType."-".$dirClean."-".$patternClean.".html",
																						 "patternName"    => ucwords($patternFinal),
																						 "patternPartial" => str_replace(" ","-",$bucket)."-".str_replace(" ","-",$patternFinal));
						
						}
						
						// if all of the patterns for a given sub-type were commented out we need to unset it
						if (!isset($b["buckets"][$bi]["navItems"][$ni]["navSubItems"])) {
							unset($b["buckets"][$bi]["navItems"][$ni]);
							$incrementNavItem = false;
						}
						
					}
				
					// add a view all for the section
					if (isset($b["buckets"][$bi]["navItems"][$ni]["navSubItems"])) {
						$subItemsCount = count($b["buckets"][$bi]["navItems"][$ni]["navSubItems"]);
						$vaBucket   = str_replace(" ","-",$bucket);
						$vaDirFinal = str_replace(" ","-",$dirFinal);
						$b["buckets"][$bi]["navItems"][$ni]["navSubItems"][$subItemsCount] = array("patternPath" => $patternType."-".$dirClean."/index.html", 
																								   "patternName" => "View All",
																								   "patternType" => $patternType,
																								   "patternSubType" => $dirClean,
																								   "patternPartial" => "viewall-".$vaBucket."-".$vaDirFinal);
						$this->viewAllPaths[$vaBucket][$vaDirFinal] = $patternType."-".$dirClean;
					}
					
					// this feels like such a hacky way of doing it
					if (!$incrementNavItem) {
						$incrementNavItem = true;
					} else {
						$ni++;
					}
					
				}
			}
			
			$bi++;
			$ni = 0;
			
		}
		
		$this->navItems = $b;
		
	}
	
	/**
	* Pulls together all of the pattern paths for use with mustache and the simplified partial matching
	*
	* @return {Array}        populates $this->patternPaths
	* @return {Array}        populates $this->patternTypes
	*/
	protected function gatherPatternPaths() {
		
		// set-up vars
		$this->patternPaths = array();
		$this->patternTypes = array();
		
		// get the pattern types
		foreach(glob(__DIR__.$this->sp."/*",GLOB_ONLYDIR) as $patternType) {
			$this->patternTypes[] = substr($patternType,strlen(__DIR__.$this->sp)+1);
		}
		
		// set-up the regex for getPath()
		$this->getPatternTypesRegex();
		
		// find the patterns for the types
		foreach($this->patternTypes as $patternType) {
			$patternTypePaths = array();
			
			// find pattern paths for pattern subtypes
			foreach(glob(__DIR__.$this->sp.$patternType."/*/*.mustache") as $filename) {
				preg_match('/\/([A-z0-9-_]{1,})\.mustache$/',$filename,$matches);
				$pattern = $this->getPatternName($matches[1], false);
				if (($pattern[0] != "_") && (!isset($patternTypePaths[$pattern]))) {
					$patternTypePaths[$pattern] = $this->getPath($filename);
				}
			}
			
			// find pattern paths for pattern types that are flat (e.g. pages & templates)
			foreach(glob(__DIR__.$this->sp.$patternType."/*.mustache") as $filename) {
				preg_match('/\/([A-z0-9-_]{1,})\.mustache$/',$filename,$matches);
				$pattern = $this->getPatternName($matches[1], false);
				if (($pattern[0] != "_") && (!isset($patternTypePaths[$pattern]))) {
					$patternTypePaths[$pattern] = $this->getPath($filename);
				}
			}
			
			$patternTypeClean = $this->getPatternName($patternType, false);
			$this->patternPaths[$patternTypeClean] = $patternTypePaths;
			
		}
		
	}
	
	/**
	* Renders the patterns in the source directory so they can be used in the default styleguide
	*
	* @return {Array}        an array of rendered partials
	*/
	protected function gatherPartials() {
		
		// make sure $this->mpl is refreshed
		$this->loadMustachePatternLoaderInstance();
		
		$p = array("partials" => array());
		
		// loop through pattern paths
		foreach($this->patternPaths as $patternType => $patternTypeValues) {
			
			foreach($patternTypeValues as $pattern => $path) {
				
				if (substr_count($path,"/") == 2) {
					
					// double-check the file exists
					if (file_exists(__DIR__."/".$this->sp.$path.".mustache")) {
						
						// create the pattern name & link, render the partial, and stick it all into the pattern array
						$patternParts    = explode("/",$path);
						$patternName     = $this->getPatternName($patternParts[2]);
						$patternLink     = str_replace("/","-",$path)."/".str_replace("/","-",$path).".html";
						$patternPartial  = $this->renderPattern($path.".mustache");
						$p["partials"][] = array("patternName" => ucwords($patternName), "patternLink" => $patternLink, "patternPartialPath" => $patternType."-".$pattern, "patternPartial" => $patternPartial);
						
					}
					
				}
				
			}
			
		}
		
		return $p;
		
	}
	
	/**
	* Renders the patterns that match a given string so they can be used in the view all styleguides
	* @param  {String}       the pattern type for the pattern
	* @param  {String}       the pattern sub-type
	*
	* @return {Array}        an array of rendered partials that match the given path
	*/
	protected function gatherPartialsByMatch($patternType, $patternSubType) {
		
		// make sure $this->mpl is refreshed
		$this->loadMustachePatternLoaderInstance();
		
		$p = array("partials" => array());
		
		$patternTypeClean = $this->getPatternName($patternType);
			
		// get matches based on pattern type and pattern sub-type
		foreach(glob(__DIR__.$this->sp.$patternType."/".$patternSubType."/*.mustache") as $filename) {
			
			// get the directory match of the pattern
			$path = $this->getPath($filename);
				
			// because we're globbing we need to check again to see if the pattern should be ignored
			$patternParts = explode("/",$path);
			if ($patternParts[2][0] != "_") {
				
				// create the pattern name & link, render the partial, and stick it all into the pattern array
				$patternName     = $this->getPatternName($patternParts[2]);
				$patternLink     = str_replace("/","-",$path)."/".str_replace("/","-",$path).".html";
				$patternPartial  = $this->renderPattern($path.".mustache");
				$p["partials"][] = array("patternName" => ucwords($patternName), "patternLink" => $patternLink, "patternPartialPath" => str_replace(" ","-",$patternTypeClean)."-".str_replace(" ","-",$patternName), "patternPartial" => $patternPartial);
				
			}
			
		}
		
		return $p;
		
	}
	
	/**
	* Get the directory path for a given pattern or json file by parsing the file path
	* @param  {String}       the filepath for a directory that contained the match
	* @param  {String}       the type of match for the pattern matching, defaults to mustache
	*
	* @return {String}       the directory for the pattern
	*/
	protected function getPath($filepath,$type = "m") {
		$file = ($type == 'm') ? '\.mustache' : '\.json';
		if (preg_match('/\/('.$this->patternTypesRegex.'\/(([A-z0-9-]{1,})\/|)([A-z0-9-]{1,}))'.$file.'$/',$filepath,$matches)) {
			return $matches[1];
		}
	}
	
	/**
	* Get the name for a given pattern sans any possible digits used for reordering
	* @param  {String}       the pattern based on the filesystem name
	* @param  {Boolean}      whether or not to strip slashes from the pattern name
	*
	* @return {String}       a lower-cased version of the pattern name
	*/
	protected function getPatternName($pattern, $clean = true) {
		$patternBits  = explode("-",$pattern,2);
		$patternName = (((int)$patternBits[0] != 0) || ($patternBits[0] == '00')) ? $patternBits[1] : $pattern;
		return ($clean) ? (str_replace("-"," ",$patternName)) : $patternName;
	}
	
	/**
	* Get the pattern partial for a given file
	* @param  {String}       the file name for a pattern
	*
	* @return {String}       the pattern partial
	*/
	protected function getPatternPartial($fileName) {
		
		$fileName = str_replace(".mustache","",$fileName);
		
		foreach($this->patternPaths as $patternTypeKey => $patternTypeVals) {
			
			foreach($patternTypeVals as $pattern => $path) {
				
				if ($path == $fileName) {
					return $patternTypeKey."-".$pattern;
				}
				
			}
			
		}
		
		return false;
		
	}
	
	/**
	* Create a regex based on $this->patternTypes
	*
	* @return {String}       populates $this->patternTypesRegex
	*/
	protected function getPatternTypesRegex() {
		
		$i = 0;
		$regex = "(";
		foreach($this->patternTypes as $patternType) {
			$regex .= ($i != 0) ? "|".$patternType : $patternType;
			$i++;
		}
		$regex .= ")";
		
		$this->patternTypesRegex = $regex;
		
	}
	
	/**
	* Write out the time tracking file so the content sync service will work. A holdover
	* from how I put together the original AJAX polling set-up.
	*/
	protected function updateChangeTime() {
		
		if (is_dir(__DIR__."/../../public/")) {
			file_put_contents(__DIR__."/../../public/latest-change.txt",time());
		} else {
			print "Either the public directory for Pattern Lab doesn't exist or the builder is in the wrong location. Please fix.";
			exit;
		}
		
	}
	
	/**
	* Copies a file from the given source path to the given public path.
	* THIS IS NOT FOR PATTERNS 
	* @param  {String}       the source file
	* @param  {String}       the public file
	*/
	protected function moveFile($s,$p) {
		if (file_exists(__DIR__."/../../source/".$s)) {
			copy(__DIR__."/../../source/".$s,__DIR__."/../../public/".$p);
		}
	}
	
	/**
	* Moves static files that aren't directly related to Pattern Lab
	* @param  {String}       file name to be moved
	* @param  {String}       copy for the message to be printed out
	*/
	protected function moveStaticFile($fileName,$copy = "") {
		$this->moveFile($fileName,$fileName);
		$this->updateChangeTime();
		if ($copy != "") {
			print $fileName." ".$copy."...\n";
		}
	}
	
	/**
	* Check to see if a given filename is in a directory that should be ignored
	* @param  {String}       file name to be checked
	*
	* @return {Boolean}      whether the directory should be ignored
	*/
	protected function ignoreDir($fileName) {
		foreach($this->id as $dir) {
			$pos = strpos(DIRECTORY_SEPARATOR.$fileName,DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR);
			if ($pos !== false) {
				return true;
			}
		}
		return false;
	}
	
	/**
	* Print out the data var. For debugging purposes
	*
	* @return {String}       the formatted version of the d object
	*/
	public function printData() {
		print_r($this->d);
	}
	
	/**
	* Trim a given string. Used in the array_walk() function in __construct as a sanity check
	* @param  {String}       an entry from one of the list-based config entries
	*
	* @return {String}       trimmed version of the given $v var
	*/
	public function trim(&$v) {
		$v = trim($v);
	}
	
	/**
	* Sort the partials generated for the styleguide so that any new ones show up in the correct place
	* @param  {Array}        items from from one pattern to compare
	* @param  {Array}        items from another pattern to compare
	*
	* @return {Integer}      the result of the string comparison
	*/
	public function sortPartials($a,$b) {
		return strcmp($a["patternLink"],$b["patternLink"]);
	}
	
	/**
	* Lowercase the given string. Used in the array_walk() function in __construct as a sanity check
	* @param  {String}       an entry from one of the list-based config entries
	*
	* @return {String}       lowercased version of the given $v var
	*/
	public function strtolower(&$v) {
		$v = strtolower($v);
	}

}
