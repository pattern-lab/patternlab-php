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

use \Mustache_Engine as Engine;
use \Mustache_Loader_PatternLoader as PatternLoader;
use \Mustache_Loader_FilesystemLoader as FilesystemLoader;

class Builder {
	
	/**
	* When initializing the Builder class or the sub-classes make sure the base properties are configured
	* Also, create the config if it doesn't already exist
	*/
	public function __construct($config = array()) {
		
		// making sure the config isn't empty
		if (empty($config)) {
			print "A set of configuration options is required to use Pattern Lab.\n";
			exit;
		}
		
		// populate some standard variables out of the config
		foreach ($config as $key => $value) {
			
			// if the variables are array-like make sure the properties are validated/trimmed/lowercased before saving
			$arrayKeys = array("ie","id","patternStates","styleGuideExcludes");
			if (in_array($key,$arrayKeys)) {
				$values = explode(",",$value);
				array_walk($values,'PatternLab\Builder::trim');
				$this->$key = $values;
			} else if ($key == "ishControlsHide") {
				$this->$key = new \stdClass();
				if ($value != "") {
					$values = explode(",",$value);
					foreach($values as $value2) {
						$value2 = trim($value2);
						$this->$key->$value2 = true;
					}
				}
				if ($this->pageFollowNav == "false") {
					$value = "tools-follow";
					$this->$key->$value = true;
				}
				if ($this->autoReloadNav == "false") {
					$value = "tools-reload";
					$this->$key->$value = true;
				}
			} else {
				$this->$key = $value;
			}
			
		}
		
		// set-up the source & public dirs
		$this->sp = "/../../../source/_patterns".DIRECTORY_SEPARATOR;
		$this->pp = "/../../../public/patterns".DIRECTORY_SEPARATOR;
		$this->sd = __DIR__."/../../../source";
		$this->pd = __DIR__."/../../../public";
		
		// provide the default for enable CSS. performance hog so it should be run infrequently
		$this->enableCSS    = false;
		$this->patternCSS   = array();
		
	}
	
	/**
	* Load a new Mustache instance that uses the Pattern Loader
	*
	* @return {Object}       an instance of the Mustache engine
	*/
	protected function loadMustachePatternLoaderInstance() {
		$this->mpl = new Engine(array(
						"loader" => new PatternLoader(__DIR__.$this->sp,array("patternPaths" => $this->patternPaths)),
						"partials_loader" => new PatternLoader(__DIR__.$this->sp,array("patternPaths" => $this->patternPaths))
		));
	}
	
	/**
	* Load a new Mustache instance that uses the File System Loader
	*
	* @return {Object}       an instance of the Mustache engine
	*/
	protected function loadMustacheFileSystemLoaderInstance() {
		$this->mfs = new Engine(array(
						"loader" => new FilesystemLoader(__DIR__."/../../templates/"),
						"partials_loader" => new FilesystemLoader(__DIR__."/../../templates/partials/")
		));
	}
	
	/**
	* Load a new Mustache instance that uses the File System Loader
	*
	* @return {Object}       an instance of the Mustache engine
	*/
	protected function loadMustacheVanillaInstance() {
		$this->mv  = new Engine;
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
		
		$pattern = $this->mpl->render($f,$d);
		$escaped = htmlentities($pattern);
		
		if ($this->addPatternHF) {
			$patternHead = $this->mv->render($this->patternHead,$d);
			$patternFoot = $this->mv->render($this->patternFoot,$d);
			$pattern     = $patternHead.$pattern.$patternFoot;
		}
		
		return array($pattern,$escaped);
		
	}
	
	/**
	* Generates the index page and style guide
	*/
	protected function generateMainPages() {
		
		// make sure $this->mfs & $this->mv are refreshed
		$this->loadMustacheFileSystemLoaderInstance();
		$this->loadMustacheVanillaInstance();
		
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
		$this->navItems['autoreloadnav']     = $this->autoReloadNav;
		$this->navItems['autoreloadport']    = $this->autoReloadPort;
		$this->navItems['pagefollownav']     = $this->pageFollowNav;
		$this->navItems['pagefollowport']    = $this->pageFollowPort;
		$this->navItems['patternpaths']      = json_encode($patternPathDests);
		$this->navItems['viewallpaths']      = json_encode($this->viewAllPaths);
		$this->navItems['mqs']               = $this->gatherMQs();
		$this->navItems['qrcodegeneratoron'] = $this->qrCodeGeneratorOn;
		$this->navItems['ipaddress']         = getHostByName(getHostName());
		$this->navItems['xiphostname']       = $this->xipHostname;
		$this->navItems['ishminimum']        = $this->ishMinimum;
		$this->navItems['ishmaximum']        = $this->ishMaximum;
		$this->navItems['ishControlsHide']   = $this->ishControlsHide;
		
		// grab the partials into a data object for the style guide
		$sd = array("partials" => array());
		foreach ($this->patternPartials as $patternSubtypes) {
			foreach ($patternSubtypes as $patterns) {
				$sd["partials"][] = $patterns;
			}
		}
		
		// render the "view all" pages
		$this->generateViewAllPages();
		
		// add cacheBuster info
		$this->navItems['cacheBuster'] = $this->cacheBuster;
		$sd['cacheBuster']             = $this->cacheBuster;
		
		// render the index page
		$r = $this->mfs->render('index',$this->navItems);
		file_put_contents($this->pd."/index.html",$r);
		
		// render the style guide
		$sd             = array_replace_recursive($this->d,$sd);
		$styleGuideHead = $this->mv->render($this->mainPageHead,$sd);
		$styleGuideFoot = $this->mv->render($this->mainPageFoot,$sd);
		$styleGuidePage = $styleGuideHead.$this->mfs->render('viewall',$sd).$styleGuideFoot;
		
		if (!file_exists($this->pd."/styleguide/html/styleguide.html")) {
			print "ERROR: the main style guide wasn't written out. make sure public/styleguide exists. can copy core/styleguide\n";
		} else {
			file_put_contents($this->pd."/styleguide/html/styleguide.html",$styleGuidePage);
		}
		
	}
	
	/**
	* Generates all of the patterns and puts them in the public directory
	*/
	protected function generatePatterns() {
		
		// make sure patterns exists
		if (!is_dir($this->pd."/patterns")) {
			mkdir($this->pd."/patterns");
		}
		
		// make sure the pattern header & footer are added
		$this->addPatternHF = true;
		
		// make sure $this->mpl & $this->mv are refreshed
		$this->loadMustachePatternLoaderInstance();
		$this->loadMustacheVanillaInstance();
		
		// loop over the pattern paths to generate patterns for each
		foreach($this->patternPaths as $patternType) {
			
			foreach($patternType as $pattern => $pathInfo) {
				
				// make sure this pattern should be rendered
				if ($pathInfo["render"]) {
					
					// get the rendered, escaped, and mustache pattern
					$this->generatePatternFile($pathInfo["patternSrcPath"].".mustache",$pathInfo["patternPartial"],$pathInfo["patternDestPath"],$pathInfo["patternState"]);
					
				}
				
			}
			
		}
		
	}
	
	/**
	* Generates a pattern with a header & footer, the escaped version of a pattern, the msutache template, and the css if appropriate
	* @param  {String}       the filename of the file to be rendered
	* @param  {String}       the pattern partial
	* @param  {String}       path where the files need to be written too
	* @param  {String}       pattern state
	*/
	private function generatePatternFile($f,$p,$path,$state) {
		
		// render the pattern and return it as well as the encoded version
		list($rf,$e) = $this->renderPattern($f,$p);
		
		// the core footer isn't rendered as mustache but we have some variables there any way. find & replace.
		$rf = str_replace("{% patternPartial %}",$p,$rf);
		$rf = str_replace("{% lineage %}",json_encode($this->patternLineages[$p]),$rf);
		$rf = str_replace("{% lineageR %}",json_encode($this->patternLineagesR[$p]),$rf);
		$rf = str_replace("{% patternState %}",$state,$rf);
		
		// figure out what to put in the css section
		$c  = $this->enableCSS && isset($this->patternCSS[$p]) ? "true" : "false";
		$rf = str_replace("{% cssEnabled %}",$c,$rf);
		
		// get the original mustache template
		$m = htmlentities(file_get_contents(__DIR__.$this->sp.$f));
		
		// if the pattern directory doesn't exist create it
		if (!is_dir(__DIR__.$this->pp.$path)) {
			mkdir(__DIR__.$this->pp.$path);
		}
		
		// write out the various pattern files
		file_put_contents(__DIR__.$this->pp.$path."/".$path.".html",$rf);
		file_put_contents(__DIR__.$this->pp.$path."/".$path.".escaped.html",$e);
		file_put_contents(__DIR__.$this->pp.$path."/".$path.".mustache",$m);
		if ($this->enableCSS && isset($this->patternCSS[$p])) {
			file_put_contents(__DIR__.$this->pp.$path."/".$path.".css",htmlentities($this->patternCSS[$p]));
		}
		
	}
	
	/**
	* Generates the view all pages
	*/
	protected function generateViewAllPages() {
		
		// make sure $this->mfs & $this->mv are refreshed on each generation of view all
		$this->loadMustacheFileSystemLoaderInstance();
		$this->loadMustacheVanillaInstance();
		
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
								$sid                   = array_replace_recursive($this->d,$sid);
								
								// render the viewall template
								$viewAllHead = $this->mv->render($this->mainPageHead,$sid);
								$viewAllFoot = $this->mv->render($this->mainPageFoot,$sid);
								$viewAllPage = $viewAllHead.$this->mfs->render('viewall',$sid).$viewAllFoot;
								
								// if the pattern directory doesn't exist create it
								$patternPath = $patternType."-".$patternSubType;
								if (!is_dir(__DIR__.$this->pp.$patternPath)) {
									mkdir(__DIR__.$this->pp.$patternPath);
									file_put_contents(__DIR__.$this->pp.$patternPath."/index.html",$viewAllPage);
								} else {
									file_put_contents(__DIR__.$this->pp.$patternPath."/index.html",$viewAllPage);
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
	*    - $this->d["listItems"] : listItems from listitems.json, duplicated into separate arrays for $this->d->listItems->one, $this->d->listItems->two, $this->d->listItems->three... etc.
	*    - $this->d["link"] : the links to each pattern
	*    - $this->d["cacheBuster"] : the cache buster value to be appended to URLs
	*    - $this->d["patternSpecific"] : holds attributes from the pattern-specific data files
	*
	* @return {Array}        populates $this->d
	*/
	protected function gatherData() {
		
		// set the cacheBuster
		$this->cacheBuster = ($this->noCacheBuster || ($this->cacheBusterOn == "false")) ? 0 : time();
		
		// gather the data from the main source data.json
		if (file_exists($this->sd."/_data/_data.json")) {
			$this->d = json_decode(file_get_contents($this->sd."/_data/_data.json"),true);
			$this->jsonLastErrorMsg("_data/_data.json");
		} else {
			print "Missing a required file, source/_data/_data.json. Aborting.\n";
			exit;
		}
		
		$reservedKeys = array("listItems","cacheBuster","link","patternSpecific");
		foreach ($reservedKeys as $reservedKey) {
			if (array_key_exists($reservedKey,$this->d)) {
				print "\"".$reservedKey."\" is a reserved key in Pattern Lab. The data using that key in _data.json will be overwritten. Please choose a new key.\n";
			}
		}
		
		
		$this->d["listItems"]       = $this->getListItems($this->sd."/_data/_listitems.json");
		$this->d["cacheBuster"]     = $this->cacheBuster;
		$this->d["link"]            = array();
		$this->d["patternSpecific"] = array();
		
	}
	
	/**
	* Finds the regular and reverse lineages for the patterns
	*
	* @return {Array}        an array of patterns with their lineages
	*/
	protected function gatherLineages() {
		
		$this->patternLineages  = array();
		$this->patternLineagesR = array();
		$foundLineages          = array();
		
		// check for the regular lineages
		foreach($this->patternPaths as $patternType => $patterns) {
			
			foreach ($patterns as $pattern => $patternInfo) {
				
				$patternLineage = array();
				$filename       = $patternInfo["patternSrcPath"];
				
				// if a file doesn't exist it assumes it's a pseudo-pattern and will use the last lineage found
				if (file_exists(__DIR__.$this->sp.$filename.".mustache")) {
					$foundLineages = array_unique($this->getLineage($filename));
				}
				
				if (count($foundLineages) > 0) {
					foreach ($foundLineages as $lineage) {
						$patternBits = $this->getPatternInfo($lineage);
						if ((count($patternBits) == 2) && isset($this->patternPaths[$patternBits[0]][$patternBits[1]])) {
							$path = $this->patternPaths[$patternBits[0]][$patternBits[1]]["patternDestPath"];
							$patternLineage[] = array("lineagePattern" => $lineage,
													  "lineagePath"    => "../../patterns/".$path."/".$path.".html");
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
		
		// check for the reverse lineages
		foreach ($this->patternLineages as $needlePartial => $needleLineages) {
			
			$patternLineageR = array();
			
			foreach ($this->patternLineages as $haystackPartial => $haystackLineages) {
				
				foreach ($haystackLineages as $haystackLineage) {
					
					if ($haystackLineage["lineagePattern"] == $needlePartial) {
						
						$foundAlready = false;
						foreach ($patternLineageR as $patternCheck) {
							if ($patternCheck["lineagePattern"] == $haystackPartial) {
								$foundAlready = true;
								break;
							}
						}
						
						if (!$foundAlready) {
							$patternBits = $this->getPatternInfo($haystackPartial);
							if (isset($this->patternPaths[$patternBits[0]][$patternBits[1]])) {
								$path = $this->patternPaths[$patternBits[0]][$patternBits[1]]["patternDestPath"];
								$patternLineageR[] = array("lineagePattern" => $haystackPartial, 
														   "lineagePath"    => "../../patterns/".$path."/".$path.".html");
							}
						}
						
					}
					
				}
				
			}
			
			$this->patternLineagesR[$needlePartial] = $patternLineageR;
			
		}
		
	}
	
	/**
	* Finds Media Queries in CSS files in the source/css/ dir
	*
	* @return {Array}        an array of the appropriate MQs
	*/
	protected function gatherMQs() {
		
		$mqs = array();
		
		foreach(glob($this->sd."/css/*.css") as $filename) {
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
		
		// make sure the pattern header & footer aren't added
		$this->addPatternHF = false;
		
		// set-up the defaults
		$patternType        = "";
		$patternSubtype     = "";
		$patternSubtypeSet  = false;
		$dirSep             = DIRECTORY_SEPARATOR;
		
		// initialize various arrays
		$this->navItems                 = array();
		$this->navItems["patternTypes"] = array();
		$this->patternPaths             = array();
		$this->patternTypes             = array();
		$this->patternLineages          = array();
		$this->patternPartials          = array();
		$this->viewAllPaths             = array();
		
		// iterate over the patterns & related data and regenerate the entire site if they've changed
		$patternObjects  = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__.$this->sp), \RecursiveIteratorIterator::SELF_FIRST);
		$patternObjects->setFlags(\FilesystemIterator::SKIP_DOTS);
		
		$patternObjects = iterator_to_array($patternObjects);
		ksort($patternObjects);
		
		foreach($patternObjects as $name => $object) {
			
			$name  = str_replace(__DIR__.$this->sp,"",$name);
			$depth = substr_count($name,$dirSep);
			
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
				
				$patternFull  = $object->getFilename();                                        // 00-colors.mustache
				$pattern      = str_replace(".mustache","",$patternFull);                      // 00-colors
				
				// check for pattern state
				$patternState = "";
				if (strpos($pattern,"@") !== false) {
					$patternBits  = explode("@",$pattern,2);
					$pattern      = $patternBits[0];
					$patternState = $patternBits[1];
				}
				
				if ($patternSubtypeSet) {
					$patternPath     = $patternType.$dirSep.$patternSubtype.$dirSep.$pattern; // 00-atoms/01-global/00-colors
					$patternPathDash = str_replace($dirSep,"-",$patternPath);                 // 00-atoms-01-global-00-colors (file path)
				} else {
					$patternPath     = $patternType.$dirSep.$pattern;                         // 00-atoms/00-colors
					$patternPathDash = str_replace($dirSep,"-",$patternPath);                 // 00-atoms-00-colors (file path)
				}
				
				// track to see if this pattern should get rendered
				$render = false;
				
				// make sure the pattern isn't hidden
				if ($patternFull[0] != "_") {
					
					// set-up the names
					$patternDash    = $this->getPatternName($pattern,false);                  // colors
					$patternClean   = str_replace("-"," ",$patternDash);                      // colors (dashes replaced with spaces)
					$patternPartial = $patternTypeDash."-".$patternDash;                      // atoms-colors
					
					// set-up the info for the nav
					$patternInfo = array("patternPath"    => $patternPathDash."/".$patternPathDash.".html",
										 "patternSrcPath" => str_replace(__DIR__.$this->sp,"",$object->getPathname()),
										 "patternName"    => ucwords($patternClean),
										 "patternState"   => $patternState,
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
				$patternSrcPath  = str_replace(__DIR__.$this->sp,"",str_replace(".mustache","",$object->getPathname()));
				$patternDestPath = $patternPathDash;
				$this->patternPaths[$patternTypeDash][$patternDash] = array("patternSrcPath"  => $patternSrcPath,
																			"patternDestPath" => $patternDestPath,
																			"patternPartial"  => $patternPartial,
																			"patternState"    => $patternState,
																			"patternType"     => $patternTypeDash,
																			"render"          => $render);
				
			} else if ($object->isFile() && ($object->getExtension() == "json") && (strpos($object->getFilename(),"~") !== false)) {
				
				/*************************************
				 * This section is for:
				 *    JSON psuedo-patterns
				 *************************************/
				
				$patternSubtypeInclude = ($patternSubtypeSet) ? $patternSubtype."-" : "";
				$patternFull = $object->getFilename();
				
				if ($patternFull[0] != "_") {
					
					// check for a pattern state
					$patternState = "";
					$patternBits  = explode("@",$patternFull,2);
					if (isset($patternBits[1])) {
						$patternState = str_replace(".json","",$patternBits[1]);
						$patternFull  = preg_replace("/@(.*?)\./",".",$patternFull);
					}
					
					// set-up the names
					// $patternFull is defined above                                                     00-colors.mustache
					$patternBits     = explode("~",$patternFull);
					$patternBase     = $patternBits[0].".mustache";                                   // 00-homepage.mustache
					$patternBaseDash = $this->getPatternName($patternBits[0],false);                  // homepage
					$patternBaseJSON = $patternBits[0].".json";                                       // 00-homepage.json
					$stripJSON       = str_replace(".json","",$patternBits[1]);
					$patternBitClean = preg_replace("/@(.*?)/","",$patternBits[0]);
					$pattern         = $patternBitClean."-".$stripJSON;                               // 00-homepage-00-emergency
					$patternInt      = $patternBitClean."-".$this->getPatternName($stripJSON, false); // 00-homepage-emergency
					$patternDash     = $this->getPatternName($patternInt,false);                      // homepage-emergency
					$patternClean    = str_replace("-"," ",$patternDash);                             // homepage emergency
					$patternPartial  = $patternTypeDash."-".$patternDash;                             // pages-homepage-emergency
					
					// add to patternPaths
					if ($patternSubtypeSet) {
						$patternPath     = $patternType.$dirSep.$patternSubtype.$dirSep.$pattern;    // 00-atoms/01-global/00-colors
						$patternPathDash = str_replace($dirSep,"-",$patternPath);                    // 00-atoms-01-global-00-colors (file path)
					} else {
						$patternPath     = $patternType.$dirSep.$pattern;                            // 00-atoms/00-colors
						$patternPathDash = str_replace($dirSep,"-",$patternPath);                    // 00-atoms-00-colors (file path)
					}
					
					// add all patterns to patternPaths
					$patternSrcPath  = $this->patternPaths[$patternTypeDash][$patternBaseDash]["patternSrcPath"];
					$patternDestPath = $patternPathDash;
					$this->patternPaths[$patternTypeDash][$patternDash] = array("patternSrcPath"  => $patternSrcPath,
																				"patternDestPath" => $patternDestPath,
																				"patternPartial"  => $patternPartial,
																				"patternState"    => $patternState,
																				"patternType"     => $patternTypeDash,
																				"render"          => true);
					
					// set-up the info for the nav
					$patternInfo = array("patternPath"    => $patternPathDash."/".$patternPathDash.".html",
										 "patternSrcPath" => str_replace(__DIR__.$this->sp,"",preg_replace("/\~(.*)\.json/",".mustache",$object->getPathname())),
										 "patternName"    => ucwords($patternClean),
										 "patternState"   => $patternState,
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
					
					if (is_array($patternDataBase) && is_array($patternData)) {
						$this->d["patternSpecific"][$patternPartial]["data"] = array_merge($patternDataBase, $patternData);
					}
					
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
		
		// check on the states of the patterns
		$patternStateLast = count($this->patternStates) - 1;
		foreach($this->patternPaths as $patternType => $patterns) {
			
			foreach ($patterns as $pattern => $patternInfo) {
				
				$patternState = $this->patternPaths[$patternType][$pattern]["patternState"];
				
				// make sure the pattern has a given state
				if ($patternState != "") {
					
					$patternStateDigit = array_search($patternState, $this->patternStates);
					if ($patternStateDigit !== false) {
						// iterate over each of the reverse lineages for a given pattern to update their state
						foreach ($this->patternLineagesR[$patternType."-".$pattern] as $patternCheckInfo) {
							$patternBits = $this->getPatternInfo($patternCheckInfo["lineagePattern"]);
							if (($this->patternPaths[$patternBits[0]][$patternBits[1]]["patternState"] == "") && ($patternStateDigit != $patternStateLast)) {
								$this->patternPaths[$patternBits[0]][$patternBits[1]]["patternState"] = $patternState;
							} else {
								$patternStateCheck = array_search($this->patternPaths[$patternBits[0]][$patternBits[1]]["patternState"], $this->patternStates);
								if ($patternStateDigit < $patternStateCheck) {
									$this->patternPaths[$patternBits[0]][$patternBits[1]]["patternState"] = $patternState;
								}
							}
						}
						
					}
					
				}
				
			}
			
		}
		
		// make sure we update the lineages with the pattern state if appropriate
		foreach($this->patternLineages as $pattern => $patternLineages) {
			foreach($patternLineages as $key => $patternLineageInfo) {
				$patternBits  = $this->getPatternInfo($patternLineageInfo["lineagePattern"]);
				$patternState = $this->patternPaths[$patternBits[0]][$patternBits[1]]["patternState"];
				if (($patternState != "") && ($patternState != null)) {
					$this->patternLineages[$pattern][$key]["lineageState"] = $patternState;
				}
			}
		}
		
		foreach($this->patternLineagesR as $pattern => $patternLineages) {
			foreach($patternLineages as $key => $patternLineageInfo) {
				$patternBits  = $this->getPatternInfo($patternLineageInfo["lineagePattern"]);
				$patternState = $this->patternPaths[$patternBits[0]][$patternBits[1]]["patternState"];
				if (($patternState != "") && ($patternState != null)) {
					$this->patternLineagesR[$pattern][$key]["lineageState"] = $patternState;
				}
			}
		}
		
		// make sure $this->mpl is refreshed
		$this->loadMustachePatternLoaderInstance();
		
		// run through the nav items and generate pattern partials and the view all pages
		foreach ($this->navItems["patternTypes"] as $patternTypeKey => $patternTypeValues) {
			
			$patternType     = $patternTypeValues["patternType"];
			$patternTypeDash = $patternTypeValues["patternTypeDash"];
			
			// if this has a second level of patterns check them out (means we don't process pages & templates)
			if (isset($patternTypeValues["patternTypeItems"]) && (!in_array($patternType,$this->styleGuideExcludes))) {
				
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
						foreach ($patternSubtypeValues["patternSubtypeItems"] as $patternSubtypeItemKey => $patternSubtypeItem) {
							
							$patternCode          = $this->renderPattern($patternSubtypeItem["patternSrcPath"],$patternSubtypeItem["patternPartial"]);
							$patternCodeRaw       = $patternCode[0];
							$patternCodeEncoded   = $patternCode[1];
							$patternLineageExists = (count($this->patternLineages[$patternSubtypeItem["patternPartial"]]) > 0) ? true : false;
							$patternLineages      = $this->patternLineages[$patternSubtypeItem["patternPartial"]];
							
							// set-up the mark-up for CSS Rule Saver so it can figure out which rules to save
							$patternCSSExists     = $this->enableCSS;
							$patternCSS           = "";
							if ($this->enableCSS) {
								$this->cssRuleSaver->loadHTML($patternCodeRaw,false);
								$patternCSS = $this->cssRuleSaver->saveRules();
								$this->patternCSS[$patternSubtypeItem["patternPartial"]] = $patternCSS;
							}
							
							$this->patternPartials[$patternTypeDash."-".$patternSubtypeDash][] = array("patternName"          => $patternSubtypeItem["patternName"], 
																									   "patternLink"          => $patternSubtypeItem["patternPath"], 
																									   "patternPartial"       => $patternSubtypeItem["patternPartial"], 
																									   "patternPartialCode"   => $patternCodeRaw,
																									   "patternPartialCodeE"  => $patternCodeEncoded,
																									   "patternCSSExists"     => $patternCSSExists,
																									   "patternCSS"           => $patternCSS,
																									   "patternLineageExists" => $patternLineageExists,
																									   "patternLineages"      => $patternLineages
																									  );
							
							// set the pattern state
							$patternBits = $this->getPatternInfo($patternSubtypeItem["patternPartial"]);
							if (($this->patternPaths[$patternBits[0]][$patternBits[1]]["patternState"] != "") && (isset($this->navItems["patternTypes"][$patternTypeKey]["patternTypeItems"][$patternSubtypeKey]["patternSubtypeItems"]))) {
								$this->navItems["patternTypes"][$patternTypeKey]["patternTypeItems"][$patternSubtypeKey]["patternSubtypeItems"][$patternSubtypeItemKey]["patternState"] = $this->patternPaths[$patternBits[0]][$patternBits[1]]["patternState"];
							}
							
						}
						
					}
					
				}
				
				// reset the items to take into account removed items affecting the index
				if ($arrayReset) {
					$this->navItems["patternTypes"][$patternTypeKey]["patternTypeItems"] = array_values($this->navItems["patternTypes"][$patternTypeKey]["patternTypeItems"]);
					$arrayReset = false;
				}
				
			} elseif (isset($patternTypeValues["patternItems"])) {
				
				foreach ($patternTypeValues["patternItems"] as $patternSubtypeKey => $patternSubtypeItem) {
					// set the pattern state
					$patternBits = $this->getPatternInfo($patternSubtypeItem["patternPartial"]);
					if ($this->patternPaths[$patternBits[0]][$patternBits[1]]["patternState"] != "") {
						$this->navItems["patternTypes"][$patternTypeKey]["patternItems"][$patternSubtypeKey]["patternState"] = $this->patternPaths[$patternBits[0]][$patternBits[1]]["patternState"];
					}
				}
			}
			
		}
		
		// load pattern-lab's resources
		$htmlHead           = file_get_contents(__DIR__."/../../templates/pattern-header-footer/header.html");
		$htmlFoot           = file_get_contents(__DIR__."/../../templates/pattern-header-footer/footer.html");
		$extraFoot          = file_get_contents(__DIR__."/../../templates/pattern-header-footer/footer-pattern.html");
		
		// gather the user-defined header and footer information
		$patternHeadPath    = __DIR__.$this->sp."00-atoms/00-meta/_00-head.mustache";
		$patternFootPath    = __DIR__.$this->sp."00-atoms/00-meta/_01-foot.mustache";
		$patternHead        = (file_exists($patternHeadPath)) ? file_get_contents($patternHeadPath) : "";
		$patternFoot        = (file_exists($patternFootPath)) ? file_get_contents($patternFootPath) : "";
		
		// add pattern lab's resource to the user-defined files
		$this->patternHead  = str_replace("{% pattern-lab-head %}",$htmlHead,$patternHead);
		$this->patternFoot  = str_replace("{% pattern-lab-foot %}",$extraFoot.$htmlFoot,$patternFoot);
		$this->mainPageHead = $this->patternHead;
		$this->mainPageFoot = str_replace("{% pattern-lab-foot %}",$htmlFoot,$patternFoot);
		
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
			$this->jsonLastErrorMsg(str_replace($this->sd."/","",$filepath));
			
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
	* Helper function to return the parts of a partial name
	* @param  {String}       the name of the partial
	*
	* @return {Array}        the pattern type and the name of the pattern
	*/
	private function getPatternInfo($name) {
		
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
	
	protected function setPatternState($patternState, $patternPartial) {
		
		// set-up some defaults
		$patternState = array_search($patternState, $this->patternStates);
		
		// iterate over each of the reverse lineages for a given pattern to update their state
		foreach ($this->patternLineagesR[$patternPartial] as $patternCheckInfo) {
			
			// run through all of the navitems to find what pattern states match. this feels, and is, overkill
			foreach ($this->navItems["patternTypes"] as $patternTypeKey => $patternTypeValues) {
				
				if (isset($patternTypeValues["patternTypeItems"])) {
					
					foreach ($patternTypeValues["patternTypeItems"] as $patternSubtypeKey => $patternSubtypeValues) {
						
						// add patterns to $this->patternPartials
						foreach ($patternSubtypeValues["patternSubtypeItems"] as $patternSubtypeItemKey => $patternSubtypeItem) {
							
							if ($patternSubtypeItem["patternPartial"] == $patternPartial) {
								
								if ($this->navItems["patternTypes"][$patternTypeKey]["patternTypeItems"][$patternSubtypeKey]["patternSubtypeItems"][$patternSubtypeItemKey]["patternState"] == "") {
									 $f = $patternState;
								} else {
									$patternCheckState = array_search($this->navItems["patternTypes"][$patternTypeKey]["patternTypeItems"][$patternSubtypeKey]["patternSubtypeItems"][$patternSubtypeItemKey]["patternState"], $this->patternStates);
									if ($patternState < $patternCheckState) {
										$this->navItems["patternTypes"][$patternTypeKey]["patternTypeItems"][$patternSubtypeKey]["patternSubtypeItems"][$patternSubtypeItemKey]["patternState"] = $patternState;
									}
								}
								
							}
							
						}
						
					}
					
				} else {
					
					foreach ($patternTypeValues["patternItems"] as $patternSubtypeKey => $patternSubtypeItem) {
						
						if ($patternSubtypeItem["patternPartial"] == $patternPartial) {
							
							if ($this->navItems["patternTypes"][$patternTypeKey]["patternItems"][$patternSubtypeKey]["patternState"] == "") {
								$this->navItems["patternTypes"][$patternTypeKey]["patternItems"][$patternSubtypeKey]["patternState"] = $patternState;
							} else {
								$patternCheckState = array_search($this->navItems["patternTypes"][$patternTypeKey]["patternItems"][$patternSubtypeKey]["patternState"], $this->patternStates);
								if ($patternState < $patternCheckState) {
									$this->navItems["patternTypes"][$patternTypeKey]["patternItems"][$patternSubtypeKey]["patternState"] = $patternState;
								}
							}
							
						}
						
					}
					
				}
				
			}
			
		}
		
	}
	
	/**
	* Write out the time tracking file so the content sync service will work. A holdover
	* from how I put together the original AJAX polling set-up.
	*/
	protected function updateChangeTime() {
		
		if (is_dir($this->pd."/")) {
			file_put_contents($this->pd."/latest-change.txt",time());
		} else {
			print "Either the public directory for Pattern Lab doesn't exist or the builder is in the wrong location. Please fix.";
			exit;
		}
		
	}
	
	/**
	* Delete patterns and user-created directories and files in public/
	*/
	protected function cleanPublic() {
		
		// make sure patterns exists before trying to clean it
		if (is_dir($this->pd."/patterns")) {
			
			$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->pd."/patterns/"), \RecursiveIteratorIterator::CHILD_FIRST);
			
			// make sure dots are skipped
			$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
			
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
			
		}
		
		// scan source/ & public/ to figure out what directories might need to be cleaned up
		$sourceDirs = glob($this->sd."/*",GLOB_ONLYDIR);
		$publicDirs = glob($this->pd."/*",GLOB_ONLYDIR);
		
		// make sure some directories aren't deleted
		$ignoreDirs = array("styleguide");
		foreach ($ignoreDirs as $ignoreDir) {
			$key = array_search($this->pd."/".$ignoreDir,$publicDirs);
			if ($key !== false){
				unset($publicDirs[$key]);
			}
		}
		
		// compare source dirs against public. remove those dirs w/ an underscore in source/ from the public/ list
		foreach ($sourceDirs as $sourceDir) {
			$cleanDir = str_replace($this->sd."/","",$sourceDir);
			if ($cleanDir[0] == "_") {
				$key = array_search($this->pd."/".str_replace("_","",$cleanDir),$publicDirs);
				if ($key !== false){
					unset($publicDirs[$key]);
				}
			}
		}
		
		// for the remaining dirs in public delete them and their files
		foreach ($publicDirs as $dir) {
			
			$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir), \RecursiveIteratorIterator::CHILD_FIRST);
			
			// make sure dots are skipped
			$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
			
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
		if (file_exists($this->sd."/".$s)) {
			copy($this->sd."/".$s,$this->pd."/".$p);
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
		
		$loader = new \SplClassLoader('CSSRuleSaver', __DIR__.'/../../lib');
		$loader->register();
		
		$this->cssRuleSaver = new \CSSRuleSaver\CSSRuleSaver;
		
		foreach(glob($this->sd."/css/*.css") as $filename) {
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
	* Lowercase the given string. Used in the array_walk() function in __construct as a sanity check
	* @param  {String}       an entry from one of the list-based config entries
	*
	* @return {String}       lowercased version of the given $v var
	*/
	public function strtolower(&$v) {
		$v = strtolower($v);
	}

}
