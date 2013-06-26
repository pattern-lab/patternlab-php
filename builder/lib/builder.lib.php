<?php

/*!
 * Pattern Lab Builder Class - v0.1
 *
 * Copyright (c) 2013 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

class Builder {

	// i was lazy when i started this project & kept (mainly) to two letter vars. sorry.
	protected $m;  // mustache instance
	protected $d;  // data from data.json files
	protected $sp; // source patterns dir
	protected $pp; // public patterns dir
	protected $dp; // permissions for the public pattern dirs
	protected $fp; // permissions for the public pattern files
	protected $if; // directories/files to be ignored in source/patterns
	protected $wf; // files to be watched to see if they should be moved
	protected $mf; // where the files should be moved too
	protected $websocketAddress; // for populating the websockets template partial
	protected $contentSyncPort; // for populating the websockets template partial
	protected $navSyncPort; // for populating the websockets template partial
	protected $patternTypes; // a list of pattern types that match the directory structure
	
	/**
	* When initializing the Builder class or the sub-classes make sure the base properties are configured
	* Also, create the config if it doesn't already exist
	*/
	public function __construct() {
		
		// set-up the configuration options for patternlab
		if (!($config = @parse_ini_file(__DIR__."/../../config/config.ini"))) {
			// config.ini didn't exist so attempt to create it using the default file
			if (!@copy(__DIR__."/../../config/config.ini.default", __DIR__."/../../config/config.ini")) {
				print "Please make sure config.ini.default exists before trying to have Pattern Lab build the config.ini file automagically.";
				exit;
			} else {
				$config = parse_ini_file(__DIR__."/../../config/config.ini");
			}
		}
		
		// populate some standard variables out of the config
		foreach ($config as $key => $value) {
			
			// if the variables are array-like make sure the properties are validated/trimmed/lowercased before saving
			if (($key == "if") || ($key == "wf") || ($key == "mf") || ($key == "patternTypes")) {
				$values = explode(",",$value);
				array_walk($values,'Builder::trim');
				if ($key == "patternTypes") {
					array_walk($values,'Builder::strtolower');
				}
				$this->$key = $values;
			} else {
				$this->$key = $value;
			}
		}
		
	}
	
	/**
	* Simply returns a new Mustache instance
	*
	* @return {Object}       an instance of the Mustache engine
	*/
	protected function mustacheInstance() {
		return new Mustache_Engine(array(
			'loader' => new Mustache_Loader_PatternLoader(__DIR__.$this->sp),
			"partials_loader" => new Mustache_Loader_PatternLoader(__DIR__.$this->sp)
		));
	}
	
	/**
	* Renders a pattern within the context of spitting out a finished pattern w/ header & footer
	* @param  {String}       the filename of the file to be rendered
	* @param  {Object}       the instance of mustache to be used in the rendering
	*
	* @return {String}       the final rendered pattern including the standard header and footer for a pattern
	*/
	private function renderFile($f,$m) {
		$h  = file_get_contents(__DIR__.$this->sp."../templates/pattern-header-footer/header.html");
		$rf = $this->renderPattern($f,$m);
		$f  = file_get_contents(__DIR__.$this->sp."../templates/pattern-header-footer/footer.html");
		return $h."\n".$rf."\n".$f;
	}
	
	/**
	* Renders a given pattern file using Mustache and incorporating the provided data
	* @param  {String}       the filename of the file to be rendered
	* @param  {Object}       the instance of mustache to be used in the rendering
	*
	* @return {String}       the mark-up as rendered by Mustache
	*/
	protected function renderPattern($f,$m) {
		return $m->render($f,$this->d);
	}
	
	/**
	* Initiates a mustache instance, renders out a full pattern file and places it in the public directory
	*
	* @return {String}       the mark-up placed in it's appropriate location in the public directory
	*/
	protected function renderAndMove() {
		
		// initiate a mustache instance
		$m = $this->mustacheInstance();
		
		// scan the pattern source directory
		foreach ($this->patternTypes as $patternType) {
			
			foreach(glob(__DIR__.$this->sp.$patternType."/*/*.mustache") as $filename) {
				
				// render the file
				$entry = $this->getEntry($filename,"m");
				$r = $this->renderFile($entry.".mustache",$m);
				
				// if the pattern directory doesn't exist create it
				$entry = str_replace("/","-",$entry);
				if (!is_dir(__DIR__.$this->pp.$entry)) {
					mkdir(__DIR__.$this->pp.$entry);
					//chmod($this->pp.$entry,$this->dp);
					file_put_contents(__DIR__.$this->pp.$entry."/".$entry.".html",$r);
					//chmod($this->pp.$entry."/pattern.html",$this->fp);
				} else {
					file_put_contents(__DIR__.$this->pp.$entry."/".$entry.".html",$r);
				}
				
			}
			
		}
		
	}
	
	/**
	* Render the index page and style guide
	*
	* @return {String}        writes out the index and style guides
	*/
	protected function generateMainPages() {
		
		// render out the main pages and move them to public
		$nd = $this->gatherNavItems();
		$nd['contentsyncport'] = $this->contentSyncPort;
		$nd['navsyncport'] = $this->navSyncPort;
		
		// grab the partials into a data object for the style guide
		$sd = $this->gatherPartials();
		
		// render the "view all" pages
		$this->generateViewAllPages();
		
		// render the index page and the style guide
		$e = new Mustache_Engine(array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__."/../../source/templates/"),
			'partials_loader' => new Mustache_Loader_FilesystemLoader(__DIR__."/../../source/templates/partials/"),
		));
		$r = $e->render('index',$nd);
		file_put_contents(__DIR__."/../../public/index.html",$r);
		
		$s = $e->render('styleguide',$sd);
		file_put_contents(__DIR__."/../../public/styleguide.html",$s);
		
	}
	
	/**
	* Renders the view all pages
	*
	* @return {String}        writes out each view all page
	*/
	protected function generateViewAllPages() {
		
		// silly to do this again but makes sense in light of the fact that watcher needs to use this function too
		$nd = $this->gatherNavItems();
		
		// add view all to each list
		$i = 0; $k = 0;
		foreach ($nd['buckets'] as $bucket) {
			
			foreach ($bucket["navItems"] as $navItem) {
				
				foreach ($navItem["navSubItems"] as $subItem) {
					if ($subItem["patternName"] == "View All") {
						
						// get all the rendered partials that match
						$sid = $this->gatherPartialsByMatch($subItem["patternPath"]);
						
						// render the viewall template
						$e = new Mustache_Engine(array(
							'loader' => new Mustache_Loader_FilesystemLoader(__DIR__."/../../source/templates/"),
							'partials_loader' => new Mustache_Loader_FilesystemLoader(__DIR__."/../../source/templates/partials/"),
						));
						$v = $e->render('viewall',$sid);
						
						// if the pattern directory doesn't exist create it
						if (!is_dir(__DIR__.$this->pp.$subItem["patternPath"])) {
							mkdir(__DIR__.$this->pp.$subItem["patternPath"]);
							//chmod($this->pp.$entry,$this->dp);
							file_put_contents(__DIR__.$this->pp.$subItem["patternPath"]."/pattern.html",$v);
							//chmod($this->pp.$entry."/pattern.html",$this->fp);
						} else {
							file_put_contents(__DIR__.$this->pp.$subItem["patternPath"]."/pattern.html",$v);
						}
					}
				}
				
			}
			
			$i++;
			$k = 0;
			
		}
		
	}
	
	/**
	* Gather data from source/data/data.json and data.json files in pattern directories
	* Throws all the data into the Builder class scoped d var
	*/
	protected function gatherData() {
		
		// gather the data from the main source data.json
		if (file_exists(__DIR__."/../../source/data/data.json")) {
			$this->d = (object) array_merge(array(), (array) json_decode(file_get_contents(__DIR__."/../../source/data/data.json")));
		}
		
		// this makes link a reserved word but oh well...
		$this->d->link = new stdClass();
		
		// add the link names
		foreach ($this->patternTypes as $patternType) {
			
			foreach(glob(__DIR__.$this->sp.$patternType."/*/*.mustache") as $filename) {
				
				$entry = $this->getEntry($filename,"m");
				$this->d->link->$entry = "/patterns/".str_replace("/","-",$entry).".html";
				
			}
			
		}
		
	}	
	
	/**
	* Gathers the partials for the nav drop down in Pattern Lab
	*
	* @return {Array}        the nav items organized by type
	*/
	protected function gatherNavItems() {
		
		$b  = array(); // the array that will contain the items
		$t  = array(); // the array that will contain the english names for the types of buckets
		$cc = "";      // current class of the object we're looking at (e.g. atom)
		$cn = 0;       // track the number for the array
		$sc = "";      // current sub-class of the object we're looking at (e.g. block)
		$sn = 0;       // track the number for the array
		$n  = "";      // the name of the final object
		
		$b["buckets"] = array();
		$t   = array("a" => "Atoms", "m" => "Molecules", "o" => "Organisms", "p" => "Pages");
		$cco = $cc;    // prepopulate the "old" check of the previous current class
		$cno = $cn;    // prepopulate the "old" check of the previous current class
		$sco = $sc;    // prepopulate the "old" check of the previous current class
		$sno = $sn;
		
		// scan the pattern source directory
		$entries = scandir(__DIR__."/".$this->sp);
		foreach($entries as $entry) {
			
			// decide which files in the source directory might need to be ignored
			if (!in_array($entry,$this->if) && ($entry[0] != '_')) {
				$els = explode("-",$entry,3);
				$cc  = $els[0];
				$sc  = $els[1];
				$n   = ucwords(str_replace("-"," ",$els[2]));
				
				// place items in their buckets. i'm already confused looking back at this. it works tho...
				if ($cc == $cco) {
					if ($sc == $sco) {
						$b["buckets"][$cno]["navItems"][$sno]["navSubItems"][] = array(
																				"patternPath" => $entry,
																				"patternName"  => $n
																			   );
					} else {
						$sn++;
						$b["buckets"][$cno]["navItems"][$sn] = array(
																"sectionNameLC" => $sc,
																"sectionNameUC" => ucwords($sc),
																"navSubItems" => array(
																	array(
																		"patternPath" => $entry,
																		"patternName"  => $n
															  )));
						$sco = $sc;
						$sno = $sn;
					}
				} else {
					$b["buckets"][$cn] = array(
											   "bucketNameLC" => strtolower($t[$cc]),
											   "bucketNameUC" => $t[$cc], 
											   "navItems" => array( 
														array(
														"sectionNameLC" => $sc,
														"sectionNameUC" => ucwords($sc),
														"navSubItems" => array(
															array(
																"patternPath" => $entry,
																"patternName"  => $n
											    )))));
					$cco = $cc;
					$sco = $sc;
					$cno = $cn;
					$cn++;
					$sn = 0;
				}
			}
		}
		
		// add view all to each list
		$i = 0; $k = 0;
		foreach ($b['buckets'] as $bucket) {
			
			if ($bucket["bucketNameLC"] != "pages") {
				foreach ($bucket["navItems"] as $navItem) {
					
					$subItemsCount = count($navItem["navSubItems"]);
					$pathItems = explode("-",$navItem["navSubItems"][0]["patternPath"]);
					if (count($pathItems) > 0) {
						$viewAll = array("patternPath" => $pathItems[0]."-".$pathItems[1], "patternName" => "View All");
						$b['buckets'][$i]["navItems"][$k]["navSubItems"][$subItemsCount] = $viewAll;
					}
					
					$k++;
				}
				
			}
			
			$i++;
			$k = 0;
		}
		
		return $b;
		
	}
	
	/**
	* Renders the patterns in the source directory so they can be used in the default styleguide
	*
	* @return {Array}        an array of rendered partials
	*/
	protected function gatherPartials() {
		
		$m = $this->mustacheInstance();
		$p = array("partials" => array());
		
		// scan the pattern source directory
		foreach($this->patternTypes as $patternType) {
			
			foreach(glob(__DIR__.$this->sp.$patternType."/*/*.mustache") as $filename) {
				
				$entry = $this->getEntry($filename,"m");
				
				// make sure 'pages' get ignored. templates will have to be added to the ignore as well
				if ($entry[0] != "p") {
					
					if (file_exists(__DIR__."/".$this->sp.$entry.".mustache")) {
						
						// render the partial and stick it in the array
						$p["partials"][] = $this->renderPattern($entry.".mustache",$m);
						
					}
					
				}
				
			}
			
		}
		
		return $p;
		
	}
	
	/**
	* Renders the patterns that match a given string so they can be used in the view all styleguides
	* It's duplicative but I'm tired
	*
	* @return {Array}        an array of rendered partials that match the given path
	*/
	protected function gatherPartialsByMatch($pathMatch) {
		
		$m = $this->mustacheInstance();
		$p = array("partials" => array());
		
		// scan the pattern source directory
		foreach($this->patternTypes as $patternType) {
			
			foreach(glob(__DIR__.$this->sp.$patternType."/*/*.mustache") as $filename) {
			
				$entry = $this->getEntry($filename,"m");
			
				// decide which files in the source directory might need to be ignored
				if (($entry[0] != "p") && strstr($entry,$pathMatch)) {
					if (file_exists(__DIR__."/".$this->sp.$entry.".mustache")) {
					
						// render the partial and stick it in the array
						$p["partials"][] = $this->renderPattern($entry.".mustache",$m);
					
					}
				}
			
			}
			
		}
		
		return $p;
		
	}
	
	/**
	* Get the directory for a given pattern by parsing the file path
	* @param  {String}       the filepath for a directory that contained the match
	* @param  {String}       the the type of match for the pattern matching
	*
	* @return {String}       the directory for the pattern
	*/
	protected function getEntry($filepath,$type) {
		$file = ($type == 'm') ? '\.mustache' : 'data\.json';
		if (preg_match('/\/('.$this->getPatternTypesRegex().'\/([A-z0-9-]{1,})\/([A-z0-9-]{1,}))'.$file.'$/',$filepath,$matches)) {
			return $matches[1];
		}
	}
	
	/**
	* Get the directory for a given pattern by parsing the file path
	*
	* @return {String}       the final regex made up of pattern names
	*/
	protected function getPatternTypesRegex() {
		
		$i = 0;
		$regex = "(";
		foreach($this->patternTypes as $patternType) {
			$regex .= ($i != 0) ? "|".$patternType : $patternType;
			$i++;
		}
		$regex .= ")";
		
		return $regex;
		
	}
	
	/**
	* Write out the time tracking file so the content sync service will work. A holdover
	* from how I put together the original AJAX polling set-up.
	*
	* @return {String}       file containing a timestamp
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
	* Lowercase the given string. Used in the array_walk() function in __construct as a sanity check
	* @param  {String}       an entry from one of the list-based config entries
	*
	* @return {String}       lowercased version of the given $v var
	*/
	public function strtolower(&$v) {
		$v = strtolower($v);
	}

}
