<?php

/*!
 * Pattern Lab Builder Class - v0.6.2
 *
 * Copyright (c) 2013 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Does the vast majority of heavy lifting for the Generator and Watch classes
 *
 */

class Buildr {

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
	protected $patternLineages;   // the list of patterns that make up a particular pattern
	protected $patternTypesRegex; // the simple regex for the pattern types. used in getPath()
	protected $navItems;          // the items for the nav. includes view all links
	protected $viewAllPaths;      // the paths to the view all pages
	protected $enableCSS;         // decide if we'll enable CSS parsing
	protected $patternCSS;        // an array to hold the CSS generated for patterns
	protected $cssRuleSaver;      // where css rule saver will be initialized
	protected $cacheBuster;       // a timestamp used to bust the cache for static assets like CSS and JS
	protected $headHTML;          // the HTML for the header
	protected $footHTML;          // the HTML for the footer
	protected $headPattern;       // the pattern to be included in the <head>
	protected $footPattern;       // the pattern to be included in the foot
	
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
				array_walk($values,'Buildr::trim');
				$this->$key = $values;
			} else {
				$this->$key = $value;
			}
			
		}
		
		// provide the default for enable CSS. performance hog so it should be run infrequently
		$this->enableCSS  = false;
		$this->patternCSS = array();
		
		// set cache buster var
		$this->setCacheBuster();
		
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
	* @param  {String}       the pattern partial
	*
	* @return {String}       the mark-up as rendered by Mustache
	*/
	protected function renderPattern($f,$p) {
		
		// if there is pattern-specific data make sure to override the default in $this->d
		$d = $this->d;
		
		if (isset($d["patternSpecific"]) && array_key_exists($p,$d["patternSpecific"])) {
			
			if (!empty($d["patternSpecific"][$p]["data"])) {
				$d = array_replace_recursive($d, $d["patternSpecific"][$p]["data"]);
			}
			
			if (!empty($d["patternSpecific"][$p]["listItems"])) {
				
				$numbers = array("one","two","three","four","five","six","seven","eight","nine","ten","eleven","twelve");
				
				$k = 0;
				$c = count($d["patternSpecific"][$p]["listItems"]);
				
				while ($k < $c) {
					$section = $numbers[$k];
					$d["listItems"][$section] = array_replace_recursive( $d["listItems"][$section], $d["patternSpecific"][$p]["listItems"][$section]);
					$k++;
				}
				
			}
			
		}
		
		return $this->mpl->render($f,$d);
		
	}
	
	/**
	* Generates the index page and style guide
	*/
	protected function generateMainPages() {
		
		// make sure $this->mfs is refreshed
		$this->loadMustacheFileSystemLoaderInstance();
		
		// get the source pattern paths
		$patternPathDests = array();
		foreach($this->patternPaths as $patternType => $patterns) {
			$patternPathDests[$patternType] = array();
			foreach ($patterns as $pattern => $patternInfo) {
				if ($patternInfo["render"]) {
					$patternPathDests[$patternType][$pattern] = $patternInfo["patternDestPath"];
				}
			}
		}
		
		// render out the main pages and move them to public
		$this->navItems['contentsyncport'] = $this->contentSyncPort;
		$this->navItems['navsyncport']     = $this->navSyncPort;
		$this->navItems['patternpaths']    = json_encode($patternPathDests);
		$this->navItems['viewallpaths']    = json_encode($this->viewAllPaths);
		$this->navItems['mqs']             = $this->gatherMQs();
		
		// grab the partials into a data object for the style guide
		$sd = array("partials" => array());
		foreach ($this->patternPartials as $patternSubtypes) {
			foreach ($patternSubtypes as $patterns) {
				$sd["partials"][] = $patterns;
			}
		}
		
		// sort partials by patternLink
		usort($sd['partials'], "Buildr::sortPartials");
		
		// render the "view all" pages
		$this->generateViewAllPages();
		
		// add cacheBuster info
		$this->navItems['cacheBuster'] = $this->cacheBuster;
		$sd['cacheBuster']             = $this->cacheBuster;
		
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
		
		// load the overall header and footers
		$this->headHTML = file_get_contents(__DIR__.$this->sp."../_patternlab-files/pattern-header-footer/header.html");
		$this->footHTML = file_get_contents(__DIR__.$this->sp."../_patternlab-files/pattern-header-footer/footer.html");
		
		// gather the user-defined header and footer information
		$headPatternPath = __DIR__.$this->sp."00-atoms/00-meta/_00-head.mustache";
		$footPatternPath = __DIR__.$this->sp."00-atoms/00-meta/_01-foot.mustache";
		$this->headPattern = (file_exists($headPatternPath)) ? file_get_contents($headPatternPath) : "";
		$this->footPattern = (file_exists($footPatternPath)) ? file_get_contents($footPatternPath) : "";
		
		// loop over the pattern paths to generate patterns for each
		foreach($this->patternPaths as $patternType) {
			
			foreach($patternType as $pattern => $pathInfo) {
				
				// make sure this pattern should be rendered
				if ($pathInfo["render"]) {
					
					$r = $this->generatePatternFile($pathInfo["patternSrcPath"].".mustache",$pathInfo["patternPartial"]);
					
					// if the pattern directory doesn't exist create it
					$path = $pathInfo["patternDestPath"];
					if (!is_dir(__DIR__.$this->pp.$path)) {
						mkdir(__DIR__.$this->pp.$path);
						file_put_contents(__DIR__.$this->pp.$path."/".$path.".html",$r);
					} else {
						file_put_contents(__DIR__.$this->pp.$path."/".$path.".html",$r);
					}
				}
				
			}
			
		}
		
	}
	
	/**
	* Generates a pattern with a header & footer
	* @param  {String}       the filename of the file to be rendered
	* @param  {String}       the pattern partial
	*
	* @return {String}       the final rendered pattern including the standard header and footer for a pattern
	*/
	private function generatePatternFile($f,$p) {
		
		$hr = $this->headHTML;
		$rf = $this->renderPattern($f,$p);
		$fr = $this->footHTML;
		
		// replace the user-defined header and footer info
		$hr = str_replace("{{ headPattern }}",$this->headPattern,$hr);
		$fr = str_replace("{{ footPattern }}",$this->footPattern,$fr);
		
		// find & replace the cacheBuster var in header and footer
		$hr = str_replace("{{ cacheBuster }}",$this->cacheBuster,$hr);
		$fr = str_replace("{{ cacheBuster }}",$this->cacheBuster,$fr);
		
		// the footer isn't rendered as mustache but we have some variables there any way. find & replace.
		$fr = str_replace("{{ patternPartial }}",$p,$fr);
		$fr = str_replace("{{ lineage }}",json_encode($this->patternLineages[$p]),$fr);
		$fr = str_replace("{{ patternHTML }}",$rf,$fr);
		
		// set-up the mark-up for CSS Rule Saver so it can figure out which rules to save
		if ($this->enableCSS) {
			$this->cssRuleSaver->loadHTML($rf,false);
			$patternCSS = $this->cssRuleSaver->saveRules();
			$this->patternCSS[$p] = $patternCSS;
			$fr = str_replace("{{ patternCSS }}",$patternCSS,$fr);
		}
		
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
		foreach ($this->navItems['patternTypes'] as $bucket) {
			
			// make sure that the navItems index exists. catches issues with pages & templates
			if (isset($bucket["patternTypeItems"])) {
				
				foreach ($bucket["patternTypeItems"] as $navItem) {
					
					// make sure the navSubItems index exists. catches issues with empty folders
					if (isset($navItem["patternSubtypeItems"])) {
						
						foreach ($navItem["patternSubtypeItems"] as $subItem) {
							
							if ($subItem["patternName"] == "View All") {
								
								// get the pattern parts
								$patternType    = $subItem["patternType"];
								$patternSubType = $subItem["patternSubtype"];
								
								// get all the rendered partials that match
								$sid                   = array("partials" => $this->patternPartials[$this->getPatternName($patternType,false)."-".$this->getPatternName($patternSubType,false)]);
								$sid["patternPartial"] = $subItem["patternPartial"];
								$sid["cacheBuster"]    = $this->cacheBuster;
								
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
	* Gather data from source/_data/_data.json, source/_data/_listitems.json, and pattern-specific json files
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
		if (file_exists(__DIR__."/../../source/_data/_data.json")) {
			$this->d = array_merge(array(), json_decode(file_get_contents(__DIR__."/../../source/_data/_data.json"),true));
			$this->jsonLastErrorMsg("_data/_data.json");
		}
		
		$this->d["listItems"] = $this->getListItems(__DIR__."/../../source/_data/_listitems.json");
		
		//print_r($this->d->listItems);
		
		$this->d["link"]            = array();
		$this->d["patternSpecific"] = array();
		
	}
	
	/**
	* Finds the Lineages for the patterns
	*
	* @return {Array}        an array of patterns with their lineages
	*/
	protected function gatherLineages() {
		
		$this->patternLineages = array();
		$foundLineages         = array();
		
		foreach($this->patternPaths as $patternType => $patterns) {
			
			foreach ($patterns as $pattern => $patternInfo) {
				
				$patternLineage = array();
				$filename       = $patternInfo["patternSrcPath"];
				
				// if a file doesn't exist it assumes it's a pseudo-pattern and will use the last lineage found
				if (file_exists(__DIR__.$this->sp.$filename.".mustache")) {
					$foundLineages  = $this->getLineage($filename);
				}
				
				if (count($foundLineages) > 0) {
					foreach ($foundLineages as $lineage) {
						$patternBits  = explode("-",$lineage,2); // BUG: this is making an assumption
						if (isset($this->patternPaths[$patternBits[0]][$patternBits[1]])) {
							$path = $this->patternPaths[$patternBits[0]][$patternBits[1]]["patternDestPath"];
							$patternLineage[] = array("lineagePattern" => $lineage, "lineagePath" => "../../patterns/".$path."/".$path.".html");
						} else {
							if (strpos($lineage, '/') === false) {
								print "You may have a typo in ".$patternInfo["patternSrcPath"].". {{> ".$lineage." }} is not a valid pattern.\n";
							}
						}
					}
				}
				
				$this->patternLineages[$patternType."-".$pattern] = $patternLineage;
				
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
			preg_match_all("/(min|max)-width:([ ]+)?(([0-9]{1,5})(\.[0-9]{1,20}|)(px|em))/",$data,$matches);
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
	* Refactoring the pattern path stuff
	*/
	protected function gatherPatternInfo() {
		
		// set-up the defaults
		$patternType       = "";
		$patternSubtype    = "";
		$patternSubtypeSet = false;
		
		// initialize various arrays
		$this->navItems                 = array();
		$this->navItems["patternTypes"] = array();
		$this->patternPaths             = array();
		$this->patternTypes             = array();
		$this->patternLineages          = array();
		$this->patternPartials          = array();
		$this->viewAllPaths             = array();
		
		// iterate over the patterns & related data and regenerate the entire site if they've changed
		$patternObjects  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.$this->sp), RecursiveIteratorIterator::SELF_FIRST);
		$patternObjects->setFlags(FilesystemIterator::SKIP_DOTS);
		
		foreach($patternObjects as $name => $object) {
			
			$name  = str_replace(__DIR__.$this->sp,"",$name);
			$depth = substr_count($name,"/");
			
			// track old types and subtypes for increment purposes
			
			if ($object->isDir() && ($depth == 0)) {
				
				/*************************************
				 * This section is for:
				 *    The pattern type directory
				 *************************************/
				
				// is this the first bucket to be set?
				$bi = (count($this->navItems["patternTypes"]) == 0) ? 0 : $bi + 1;
				
				// set-up the names
				$patternType      = $name;                                 // 00-atoms
				$patternTypeDash  = $this->getPatternName($name,false);    // atoms
				$patternTypeClean = str_replace("-"," ",$patternTypeDash); // atoms (dashes replaced with spaces)
				
				// add to pattern types & pattern paths
				$this->patternTypes[]                 = $patternType;
				$this->patternPaths[$patternTypeDash] = array();
				
				// add a new patternType to the nav
				$this->navItems["patternTypes"][$bi] = array("patternTypeLC"   => strtolower($patternTypeClean),
															 "patternTypeUC"   => ucwords($patternTypeClean),
															 "patternType"     => $patternType,
															 "patternTypeDash" => $patternTypeDash);
				
				// starting a new set of pattern types. it might not have any pattern subtypes
				$patternSubtypeSet = false;
				
			} else if ($object->isDir() && ($depth == 1)) {
				
				/*************************************
				 * This section is for:
				 *    The pattern sub-type directory
				 *************************************/
				
				// is this the first bucket to be set?
				$ni = (!$patternSubtypeSet) ? 0 : $ni + 1;
				
				// set-up the names
				$patternSubtype      = $object->getFilename();                              // 02-blocks
				$patternSubtypeDash  = $this->getPatternName($object->getFilename(),false); // blocks
				$patternSubtypeClean = str_replace("-"," ",$patternSubtypeDash);            // blocks (dashes replaced with spaces)
				
				// add to patternPartials
				$this->patternPartials[$patternTypeDash."-".$patternSubtypeDash] = array();
				
				// add a new patternSubtype to the nav
				$this->navItems["patternTypes"][$bi]["patternTypeItems"][$ni] = array("patternSubtypeLC"   => strtolower($patternSubtypeClean),
																					  "patternSubtypeUC"   => ucwords($patternSubtypeClean),
																					  "patternSubtype"     => $patternSubtype,
																					  "patternSubtypeDash" => $patternSubtypeDash);
				
				// starting a new set of pattern types. it might not have any pattern subtypes
				$patternSubtypeSet = true;
				
			} else if ($object->isFile() && ($object->getExtension() == "mustache")) {
				
				/*************************************
				 * This section is for:
				 *    Mustache patterns
				 *************************************/
				
				$patternFull = $object->getFilename();                                // 00-colors.mustache
				$pattern     = str_replace(".mustache","",$patternFull);              // 00-colors
				
				if ($patternSubtypeSet) {
					$patternPath     = $patternType."/".$patternSubtype."/".$pattern; // 00-atoms/01-global/00-colors
					$patternPathDash = str_replace("/","-",$patternPath);             // 00-atoms-01-global-00-colors (file path)
				} else {
					$patternPath     = $patternType."/".$pattern;                     // 00-atoms/00-colors
					$patternPathDash = str_replace("/","-",$patternPath);             // 00-atoms-00-colors (file path)
				}
				
				// track to see if this pattern should get rendered
				$render = false;
				
				// make sure the pattern isn't hidden
				if ($patternFull[0] != "_") {
					
					// set-up the names                            
					$patternDash    = $this->getPatternName($pattern,false);             // colors
					$patternClean   = str_replace("-"," ",$patternDash);                 // colors (dashes replaced with spaces)
					$patternPartial = $patternTypeDash."-".$patternDash;                 // atoms-colors
					
					// set-up the info for the nav
					$patternInfo = array("patternPath"    => $patternPathDash."/".$patternPathDash.".html",
										 "patternSrcPath" => str_replace(__DIR__.$this->sp,"",$object->getPathname()),
										 "patternName"    => ucwords($patternClean),
										 "patternPartial" => $patternPartial);
					
					// add to the nav
					if ($depth == 1) {
						$this->navItems["patternTypes"][$bi]["patternItems"][] = $patternInfo;
					} else {
						$this->navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][] = $patternInfo;
					}
					
					// add to the link var for inclusion in patterns
					$this->d["link"][$patternPartial] = "../../patterns/".$patternPathDash."/".$patternPathDash.".html";
					
					// yup, this pattern should get rendered
					$render = true;
					
				} else {
					
					// replace the underscore to generate a good file pattern name
					$patternDash    = $this->getPatternName(str_replace("_","",$pattern),false); // colors
					$patternPartial = $patternTypeDash."-".$patternDash;                         // atoms-colors
					
				}
				
				// add all patterns to patternPaths
				$patternSrcPath  = $patternPath;
				$patternDestPath = $patternPathDash;
				$this->patternPaths[$patternTypeDash][$patternDash] = array("patternSrcPath" => $patternSrcPath, "patternDestPath" => $patternDestPath, "patternPartial" => $patternPartial, "render" => $render);
				
			} else if ($object->isFile() && ($object->getExtension() == "json") && (strpos($object->getFilename(),"~") !== false)) {
					
				/*************************************
				 * This section is for:
				 *    JSON psuedo-patterns
				 *************************************/
				
				$patternSubtypeInclude = ($patternSubtypeSet) ? $patternSubtype."-" : "";
				$patternFull = $object->getFilename();
				
				if ($patternFull[0] != "_") {
					
					// set-up the names
					// $patternFull is defined above                                                    00-colors.mustache
					$patternBits     = explode("~",$patternFull);
					$patternBase     = $patternBits[0].".mustache";                                  // 00-homepage.mustache
					$patternBaseJSON = $patternBits[0].".json";                                      // 00-homepage.json
					$stripJSON       = str_replace(".json","",$patternBits[1]);
					$pattern         = $patternBits[0]."-".$stripJSON;                               // 00-homepage-00-emergency
					$patternInt      = $patternBits[0]."-".$this->getPatternName($stripJSON, false); // 00-homepage-emergency
					$patternDash     = $this->getPatternName($patternInt,false);                     // homepage-emergency
					$patternClean    = str_replace("-"," ",$patternDash);                            // homepage emergency
					$patternPartial  = $patternTypeDash."-".$patternDash;                            // pages-homepage-emergency
					
					// add to patternPaths
					if ($patternSubtypeSet) {
						$patternPath     = $patternType."/".$patternSubtype."/".$pattern;            // 00-atoms/01-global/00-colors
						$patternPathDash = str_replace("/","-",$patternPath);                        // 00-atoms-01-global-00-colors (file path)
					} else {
						$patternPath     = $patternType."/".$pattern;                                // 00-atoms/00-colors
						$patternPathDash = str_replace("/","-",$patternPath);                        // 00-atoms-00-colors (file path)
					}
					
					// add all patterns to patternPaths
					$patternSrcPath  = str_replace(__DIR__.$this->sp,"",preg_replace("/\~(.*)\.json/","",$object->getPathname()));
					$patternDestPath = $patternPathDash;
					$this->patternPaths[$patternTypeDash][$patternDash] = array("patternSrcPath" => $patternSrcPath, "patternDestPath" => $patternDestPath, "patternPartial" => $patternPartial, "render" => true);
					
					// set-up the info for the nav
					$patternInfo = array("patternPath"    => $patternPathDash."/".$patternPathDash.".html",
										 "patternSrcPath" => str_replace(__DIR__.$this->sp,"",preg_replace("/\~(.*)\.json/",".mustache",$object->getPathname())),
										 "patternName"    => ucwords($patternClean),
										 "patternPartial" => $patternPartial);
					
					// add to the nav
					if ($depth == 1) {
						$this->navItems["patternTypes"][$bi]["patternItems"][] = $patternInfo;
					} else {
						$this->navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][] = $patternInfo;
					}
					
					// add to the link var for inclusion in patterns
					$this->d["link"][$patternPartial] = "../../patterns/".$patternPathDash."/".$patternPathDash.".html";
					
					// get the base data
					$patternDataBase = array();
					if (file_exists($object->getPath()."/".$patternBaseJSON)) {
						$patternDataBase = json_decode(file_get_contents($object->getPath()."/".$patternBaseJSON),true);
						$this->jsonLastErrorMsg($patternBaseJSON);
					}
					
					// get the special pattern data
					$patternData = (array) json_decode(file_get_contents($object->getPathname()));
					$this->jsonLastErrorMsg($object->getFilename());
					
					// merge them for the file
					if (!isset($this->d["patternSpecific"][$patternPartial])) {
						$this->d["patternSpecific"][$patternPartial]              = array();
						$this->d["patternSpecific"][$patternPartial]["data"]      = array();
						$this->d["patternSpecific"][$patternPartial]["listItems"] = array();
					}
					
					$this->d["patternSpecific"][$patternPartial]["data"] = array_merge($patternDataBase, $patternData);
					
				}
						
			} else if ($object->isFile() && ($object->getExtension() == "json")) {
					
				/*************************************
				 * This section is for:
				 *    JSON data
				 *************************************/
				$patternFull    = $object->getFilename();                                            // 00-colors.mustache
				$pattern        = str_replace(".listitems","",str_replace(".json","",$patternFull)); // 00-colors
				$patternDash    = $this->getPatternName($pattern,false);                             // colors
				$patternPartial = $patternTypeDash."-".$patternDash;                                 // atoms-colors
				
				if ($patternFull[0] != "_") {
					
					if (!isset($this->d["patternSpecific"][$patternPartial])) {
						$this->d["patternSpecific"][$patternPartial]              = array();
						$this->d["patternSpecific"][$patternPartial]["data"]      = array();
						$this->d["patternSpecific"][$patternPartial]["listItems"] = array();
					}
					
					if (strpos($object->getFilename(),".listitems.json") !== false) {
						$patternData = $this->getListItems($object->getPathname());
						$this->d["patternSpecific"][$patternPartial]["listItems"] = $patternData;
					} else {
						$patternData = json_decode(file_get_contents($object->getPathname()),true);
						$this->jsonLastErrorMsg($patternFull);
						$this->d["patternSpecific"][$patternPartial]["data"] = $patternData;
					}
					
				}
				
			}
			
		}
		

		
		// get all of the lineages
		$this->gatherLineages();
		
		// make sure $this->mpl is refreshed
		$this->loadMustachePatternLoaderInstance();
		
		// run through the nav items and generate pattern partials and the view all pages
		foreach ($this->navItems["patternTypes"] as $patternTypeKey => $patternTypeValues) {
			
			$patternType     = $patternTypeValues["patternType"];
			$patternTypeDash = $patternTypeValues["patternTypeDash"];
			
			// if this has a second level of patterns check them out (means we don't process pages & templates)
			if (isset($patternTypeValues["patternTypeItems"])) {
				
				$arrayReset = false;
				
				foreach ($patternTypeValues["patternTypeItems"] as $patternSubtypeKey => $patternSubtypeValues) {
					
					// if there are no sub-items in a section remove it, else do a bunch of other stuff
					if (!isset($patternSubtypeValues["patternSubtypeItems"])) {
						
						unset($this->navItems["patternTypes"][$patternTypeKey]["patternTypeItems"][$patternSubtypeKey]);
						$arrayReset = true;
						
					} else {
						
						$patternSubtype     = $patternSubtypeValues["patternSubtype"];
						$patternSubtypeDash = $patternSubtypeValues["patternSubtypeDash"];
						$subItemsCount      = count($patternSubtypeValues["patternSubtypeItems"]);
						
						// add a view all link
						$this->navItems["patternTypes"][$patternTypeKey]["patternTypeItems"][$patternSubtypeKey]["patternSubtypeItems"][$subItemsCount] = array(
																												"patternPath"    => $patternType."-".$patternSubtype."/index.html", 
																												"patternName"    => "View All",
																												"patternType"    => $patternType,
																												"patternSubtype" => $patternSubtype,
																												"patternPartial" => "viewall-".$patternTypeDash."-".$patternSubtypeDash);
						
						// add to the view all paths
						$this->viewAllPaths[$patternTypeDash][$patternSubtypeDash] = $patternType."-".$patternSubtype;
						
						// add patterns to $this->patternPartials
						foreach ($patternSubtypeValues["patternSubtypeItems"] as $patternSubtypeItem) {
							
							$patternCode          = $this->renderPattern($patternSubtypeItem["patternSrcPath"],$patternSubtypeItem["patternPartial"]);
							$patternCSSExists     = $this->enableCSS;
							$patternCSS           = ($this->enableCSS) ? $this->patternCSS[$patternSubtypeItem["patternPartial"]] : "";
							$patternLineageExists = (count($this->patternLineages[$patternSubtypeItem["patternPartial"]]) > 0) ? true : false;
							$patternLineages      = $this->patternLineages[$patternSubtypeItem["patternPartial"]];
							
							$this->patternPartials[$patternTypeDash."-".$patternSubtypeDash][] = array("patternName"          => $patternSubtypeItem["patternName"], 
																									   "patternLink"          => $patternSubtypeItem["patternPath"], 
																									   "patternPartial"       => $patternSubtypeItem["patternPartial"], 
																									   "patternPartialCode"   => $patternCode,
																									   "patternCSSExists"     => $patternCSSExists,
																									   "patternCSS"           => $patternCSS,
																									   "patternLineageExists" => $patternLineageExists,
																									   "patternLineages"      => $patternLineages
																									  );
							
						}
						
					}
					
				}
				
				// reset the items to take into account removed items affecting the index
				if ($arrayReset) {
					$this->navItems["patternTypes"][$patternTypeKey]["patternTypeItems"] = array_values($this->navItems["patternTypes"][$patternTypeKey]["patternTypeItems"]);
					$arrayReset = false;
				}
				
			}
			
		}
		
	}
	
	/**
	* Get the lineage for a given pattern by parsing it and matching mustache partials
	* @param  {String}       the filename for the pattern to be parsed
	*
	* @return {Array}        a list of patterns
	*/
	protected function getLineage($filename) {
		$data = file_get_contents(__DIR__.$this->sp.$filename.".mustache");
		//$data = file_get_contents($filename);
		if (preg_match_all('/{{>([ ]+)?([A-Za-z0-9-]+)(?:\:[A-Za-z0-9-]+)?(?:(| )\(.*)?([ ]+)}}/',$data,$matches)) {
			return $matches[2];
		}
		return array();
	}
	
	/**
	* Get the lineage for a given pattern by parsing it and matching mustache partials
	* @param  {String}       the filename for the pattern to be parsed
	*
	* @return {Array}        the final set of list items
	*/
	protected function getListItems($filepath) {
		
		$listItems = array();
		
		// add list item data, makes 'listItems' a reserved word
		if (file_exists($filepath)) {
			
			$listItemsJSON = json_decode(file_get_contents($filepath), true);
			$this->jsonLastErrorMsg(str_replace(__DIR__."/../../source/","",$filepath));
			
			$numbers = array("one","two","three","four","five","six","seven","eight","nine","ten","eleven","twelve");
			
			$i = 0;
			$k = 1;
			$c = count($listItemsJSON)+1;
			
			while ($k < $c) {
				
				shuffle($listItemsJSON);
				$itemsArray = array();
				//$listItems[$numbers[$k-1]] = array();
				
				while ($i < $k) {
					$itemsArray[] = $listItemsJSON[$i];
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
	* Set the cache buster var so it can be used on the query string for file requests
	*/
	protected function setCacheBuster() {
		$this->cacheBuster = time();
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
	* Delete patterns and user-created directories and files in public/
	*/
	protected function cleanPublic() {
		
		// find all of the patterns in public/. sort by the children first
		$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__."/../../public/patterns/"), RecursiveIteratorIterator::CHILD_FIRST);
		
		// make sure dots are skipped
		$objects->setFlags(FilesystemIterator::SKIP_DOTS);
		
		// for each file figure out what to do with it
		foreach($objects as $name => $object) {
			
			if ($object->isDir()) {
				// if this is a directory remove it
				rmdir($name);
			} else if ($object->isFile() && ($object->getFilename() != "README")) {
				// if this is a file remove it
				unlink($name);
			}
			
		}
		
		// scan source/ & public/ to figure out what directories might need to be cleaned up
		$sourceDirs = glob(__DIR__."/../../source/*",GLOB_ONLYDIR);
		$publicDirs = glob(__DIR__."/../../public/*",GLOB_ONLYDIR);
		
		// make sure some directories aren't deleted
		$ignoreDirs = array("styleguide");
		foreach ($ignoreDirs as $ignoreDir) {
			$key = array_search(__DIR__."/../../public/".$ignoreDir,$publicDirs);
			if ($key !== false){
				unset($publicDirs[$key]);
			}
		}
		
		// compare source dirs against public. remove those dirs w/ an underscore in source/ from the public/ list
		foreach ($sourceDirs as $sourceDir) {
			$cleanDir = str_replace(__DIR__."/../../source/","",$sourceDir);
			if ($cleanDir[0] == "_") {
				$key = array_search(__DIR__."/../../public/".str_replace("_","",$cleanDir),$publicDirs);
				if ($key !== false){
					unset($publicDirs[$key]);
				}
			}
		}
		
		// for the remaining dirs in public delete them and their files
		foreach ($publicDirs as $dir) {
			
			$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::CHILD_FIRST);
			
			// make sure dots are skipped
			$objects->setFlags(FilesystemIterator::SKIP_DOTS);
			
			foreach($objects as $name => $object) {
				
				if ($object->isDir()) {
					rmdir($name);
				} else if ($object->isFile()) {
					unlink($name);
				}
				
			}
			
			rmdir($dir);
			
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
	* @param  {String}       part of the file name to be found for replacement
	* @param  {String}       the replacement
	*/
	protected function moveStaticFile($fileName,$copy = "", $find = "", $replace = "") {
		$this->moveFile($fileName,str_replace($find, $replace, $fileName));
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
	* Loads the CSS from source/css/ into CSS Rule Saver to be used for code view
	*/
	protected function initializeCSSRuleSaver() {
		
		$this->cssRuleSaver = new cssRuleSaver;
		
		foreach(glob(__DIR__."/../../source/css/*.css") as $filename) {
			$this->cssRuleSaver->loadCSS($filename);
		}
		
	}
	
	/**
	* Returns the last error message when building a JSON file. Mimics json_last_error_msg() from PHP 5.5
	* @param  {String}       the file that generated the error
	*/
	protected function jsonLastErrorMsg($file) {
		$errors = array(
			JSON_ERROR_NONE             => null,
			JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
			JSON_ERROR_STATE_MISMATCH   => 'Underflow or the modes mismatch',
			JSON_ERROR_CTRL_CHAR        => 'Unexpected control character found',
			JSON_ERROR_SYNTAX           => 'Syntax error, malformed JSON',
			JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
		);
		$error        = json_last_error();
		$errorMessage = array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
		if ($errorMessage != null) {
			print "The JSON file, ".$file.", wasn't loaded. The error: ".$errorMessage."\n";
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
