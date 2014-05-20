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
		
		// set-up the source & public dirs
		$this->sp = "/../../../".$config["sourceDir"]."/_patterns".DIRECTORY_SEPARATOR;
		$this->pp = "/../../../".$config["publicDir"]."/patterns".DIRECTORY_SEPARATOR;
		$this->sd = __DIR__."/../../../".$config["sourceDir"];
		$this->pd = __DIR__."/../../../".$config["publicDir"];
		
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
				$toolssnapshot = "tools-snapshot"; // i was an idgit and used dashes
				if (!isset($this->$key->$toolssnapshot)) {
					if (!is_dir($this->pd."/snapshots")) {
						$this->$key->$toolssnapshot = true;
					}
				}
			} else {
				$this->$key = $value;
			}
			
		}
		
		// provide the default for enable CSS. performance hog so it should be run infrequently
		$this->enableCSS    = false;
		$this->patternCSS   = array();
		
		// find the pattern extension
		$this->patternExtension = $this->patternEngine;
		
	}
	
	/**
	* Renders a given pattern file using Mustache and incorporating the provided data
	* @param  {String}       the filename of the file to be rendered
	* @param  {String}       the pattern partial
	*
	* @return {String}       the mark-up as rendered by Mustache
	*/
	protected function renderPattern($f,$p,$k = array()) {
		
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
		
		if (count($k) > 0) {
			$d = array_merge($d, $k);
		}
		
		$pattern = $this->pl->render($f,$d);
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
		$templateLoader = new TemplateLoader();
		$this->mfs      = $templateLoader->fileSystem();
		$this->mv       = $templateLoader->vanilla();
		
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
		
		if (!is_dir($this->pd."/styleguide/html/")) {
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
		
		// make sure $this->pl & $this->mv are refreshed
		$patternLoader  = new PatternLoader($this->patternPaths);
		$this->pl       = $patternLoader->loadPatternLoaderInstance($this->patternEngine,__DIR__.$this->sp);
		
		$templateLoader = new TemplateLoader();
		$this->mv       = $templateLoader->vanilla();
		
		// loop over the pattern paths to generate patterns for each
		foreach($this->patternPaths as $patternType) {
			
			foreach($patternType as $pattern => $pathInfo) {
				
				// make sure this pattern should be rendered
				if ($pathInfo["render"]) {
					
					// get the rendered, escaped, and mustache pattern
					$this->generatePatternFile($pathInfo["patternSrcPath"].".".$this->patternExtension,$pathInfo["patternPartial"],$pathInfo["patternDestPath"],$pathInfo["patternState"]);
					
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
		file_put_contents(__DIR__.$this->pp.$path."/".$path.".".$this->patternExtension,$m);
		if ($this->enableCSS && isset($this->patternCSS[$p])) {
			file_put_contents(__DIR__.$this->pp.$path."/".$path.".css",htmlentities($this->patternCSS[$p]));
		}
		
	}
	
	/**
	* Generates the view all pages
	*/
	protected function generateViewAllPages() {
		
		// make sure $this->mfs & $this->mv are refreshed on each generation of view all
		$templateLoader = new TemplateLoader();
		$this->mfs      = $templateLoader->fileSystem();
		$this->mv       = $templateLoader->vanilla();
		
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
			$data    = file_get_contents($this->sd."/_data/_data.json");
			$this->d = json_decode($data,true);
			if ($jsonErrorMessage = JSON::hasError()) {
				JSON::lastErrorMsg("_data/_data.json",$jsonErrorMessage,$data);
			}
		} else {
			print "Missing a required file, source/_data/_data.json. Aborting.\n";
			exit;
		}
		
		if (is_array($this->d)) {
			$reservedKeys = array("listItems","cacheBuster","link","patternSpecific");
			foreach ($reservedKeys as $reservedKey) {
				if (array_key_exists($reservedKey,$this->d)) {
					print "\"".$reservedKey."\" is a reserved key in Pattern Lab. The data using that key in _data.json will be overwritten. Please choose a new key.\n";
				}
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
				if (file_exists(__DIR__.$this->sp.$filename.".".$this->patternExtension)) {
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
		
		usort($mqs, "strnatcmp");
		
		return $mqs;
		
	}
	
	/**
	* Refactoring the pattern path stuff
	*/
	protected function gatherPatternInfo() {
		
		// make sure the pattern header & footer aren't added
		$this->addPatternHF = false;
		
		// gather pattern info based on the supported rules
		$options = array("patternSourceDir" => __DIR__.$this->sp, "patternExtension" => $this->patternExtension);
		PatternInfo::loadRules($options);
		PatternInfo::gather($options);
		
		$kss = KSSParser::parse($this->sd);
		
		// initialize various arrays
		$this->navItems        = PatternInfo::$navItems;
		$this->patternPaths    = PatternInfo::$patternPaths;
		$this->patternTypes    = PatternInfo::$patternTypes;
		$this->patternLineages = array();
		$this->patternPartials = array();
		$this->viewAllPaths    = array();
		
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
		
		// walk across the data and update links
		array_walk_recursive($this->d,'PatternLab\Builder::compareReplace');
		
		// make sure $this->mpl is refreshed
		$patternLoader = new PatternLoader($this->patternPaths);
		$this->pl = $patternLoader->loadPatternLoaderInstance($this->patternEngine,__DIR__.$this->sp);
		
		// run through the nav items and generate pattern partials and the view all pages
		foreach ($this->navItems["patternTypes"] as $patternTypeKey => $patternTypeValues) {
			
			$patternType     = $patternTypeValues["patternType"];
			$patternTypeDash = $patternTypeValues["patternTypeDash"];
			
			// if this has a second level of patterns check them out (means we don't process pages & templates)
			if ((count($patternTypeValues["patternTypeItems"]) != 0) && (!in_array($patternType,$this->styleGuideExcludes))) {
				
				$arrayReset = false;
				
				foreach ($patternTypeValues["patternTypeItems"] as $patternSubtypeKey => $patternSubtypeValues) {
					
					// if there are no sub-items in a section remove it, else do a bunch of other stuff
					if (count($patternSubtypeValues["patternSubtypeItems"]) == 0) {
						
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
						
						// add the subtype info
						$patternSubtypeData = array("patternName" => $patternSubtypeValues["patternSubtypeUC"], "patternSectionSubtype" => true);
						if (isset($patternSubtypeValues["patternSubtypeDesc"])) {
							$patternSubtypeData["patternDesc"] = $patternSubtypeValues["patternSubtypeDesc"];
						}
						if (isset($patternSubtypeValues["patternSubtypeMeta"])) {
							$patternSubtypeData["patternDesc"] = $patternSubtypeValues["patternSubtypeDesc"];
						}
						$this->patternPartials[$patternTypeDash."-".$patternSubtypeDash][] = $patternSubtypeData;
						
						// add patterns to $this->patternPartials
						foreach ($patternSubtypeValues["patternSubtypeItems"] as $patternSubtypeItemKey => $patternSubtypeItem) {
							
							$patternSectionVanilla = true;
							$patternSectionKSS     = false;
							
							// see if this is in KSS
							$kssSection = $kss->getSection($patternSubtypeItem["patternPartial"]);
							if ($kssSection) {
								$patternSectionVanilla = false;
								$patternSectionKSS     = true;
							}
							
							$patternCode           = $this->renderPattern($patternSubtypeItem["patternSrcPath"],$patternSubtypeItem["patternPartial"]);
							$patternCodeRaw        = $patternCode[0];
							$patternCodeEncoded    = $patternCode[1];
							$patternLineageExists  = (count($this->patternLineages[$patternSubtypeItem["patternPartial"]]) > 0) ? true : false;
							$patternLineages       = $this->patternLineages[$patternSubtypeItem["patternPartial"]];
							$patternLineageRExists = (count($this->patternLineagesR[$patternSubtypeItem["patternPartial"]]) > 0) ? true : false;
							$patternLineagesR      = $this->patternLineagesR[$patternSubtypeItem["patternPartial"]];
							$patternLineageEExists = ($patternLineageExists || $patternLineageRExists) ? true : false;
							
							// set-up the mark-up for CSS Rule Saver so it can figure out which rules to save
							$patternCSSExists     = $this->enableCSS;
							$patternCSS           = "";
							if ($this->enableCSS) {
								$this->cssRuleSaver->loadHTML($patternCodeRaw,false);
								$patternCSS = $this->cssRuleSaver->saveRules();
								$this->patternCSS[$patternSubtypeItem["patternPartial"]] = $patternCSS;
							}
							
							$patternPartialData = array("patternSectionVanilla" => $patternSectionVanilla,
														"patternSectionKSS"     => $patternSectionKSS,
														"patternName"           => $patternSubtypeItem["patternName"],
														"patternLink"           => $patternSubtypeItem["patternPath"],
														"patternPartial"        => $patternSubtypeItem["patternPartial"],
														"patternPartialCode"    => $patternCodeRaw,
														"patternPartialCodeE"   => $patternCodeEncoded,
														"patternCSSExists"      => $patternCSSExists,
														"patternCSS"            => $patternCSS,
														"patternLineageExists"  => $patternLineageExists,
														"patternLineages"       => $patternLineages,
														"patternLineageRExists" => $patternLineageRExists,
														"patternLineagesR"      => $patternLineagesR,
														"patternLineageEExists" => $patternLineageEExists);
							
							if (isset($patternSubtypeItem["patternDesc"])) {
								$patternPartialData["patternDesc"] = $patternSubtypeItem["patternDesc"];
							}
							
							if (isset($patternSubtypeItem["patternMeta"])) {
								$patternPartialData["patternMeta"] = $patternSubtypeItem["patternMeta"];
							}
							
							if ($kssSection) {
								$patternPartialData["patternName"] = $kssSection->getTitle();
								$patternPartialData["patternDesc"] = $kssSection->getDescription();
								$modifiers = $kssSection->getModifiers();
								if (count($modifiers) > 0) {
									$patternPartialData["patternModifiersExist"] = true;
									$patternPartialData["patternModifiers"]      = array();
									foreach ($modifiers as $modifier) {
										$name  = $modifier->getName();
										$class = $modifier->getClassName();
										$desc  = $modifier->getDescription();
										$code  = "";
										$patternModifierCodeExists = false;
										if ($name[0] != ":") {
											list($code,$orig) = $this->renderPattern($patternSubtypeItem["patternSrcPath"],$patternSubtypeItem["patternPartial"],array("styleModifier" => $class));
											$patternModifierCodeExists = true;
										}
										$patternPartialData["patternModifiers"][] = array("patternModifierName"       => $name,
																						  "patternModifierDesc"       => $desc,
																						  "patternModifierCode"       => $code,
																						  "patternModifierCodeExists" => $patternModifierCodeExists);
									}
								}
							}
							
							$patternPartialData["patternDescExists"] = isset($patternPartialData["patternDesc"]);
							
							$this->patternPartials[$patternTypeDash."-".$patternSubtypeDash][] = $patternPartialData;
							
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
				
			} else {
				
				foreach ($patternTypeValues["patternItems"] as $patternSubtypeKey => $patternSubtypeItem) {
					// set the pattern state
					$patternBits = $this->getPatternInfo($patternSubtypeItem["patternPartial"]);
					if ($this->patternPaths[$patternBits[0]][$patternBits[1]]["patternState"] != "") {
						$this->navItems["patternTypes"][$patternTypeKey]["patternItems"][$patternSubtypeKey]["patternState"] = $this->patternPaths[$patternBits[0]][$patternBits[1]]["patternState"];
					}
				}
			}
			
		}
		
		// add pattern lab's resource to the user-defined files
		$templateHelper = new TemplateHelper($this->sp);
		$this->patternHead  = $templateHelper->patternHead;
		$this->patternFoot  = $templateHelper->patternFoot;
		$this->mainPageHead = $templateHelper->mainPageHead;
		$this->mainPageFoot = $templateHelper->mainPageFoot;
		
	}
	
	/**
	* Get the lineage for a given pattern by parsing it and matching mustache partials
	* @param  {String}       the filename for the pattern to be parsed
	*
	* @return {Array}        a list of patterns
	*/
	protected function getLineage($filename) {
		$data = file_get_contents(__DIR__.$this->sp.$filename.".".$this->patternExtension);
		//$data = file_get_contents($filename);
		if (preg_match_all('/{{>([ ]+)?([A-Za-z0-9-_]+)(?:\:[A-Za-z0-9-]+)?(?:(| )\(.*)?([ ]+)}}/',$data,$matches)) {
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
			
			$data          = file_get_contents($filepath);
			$listItemsJSON = json_decode($data, true);
			if ($jsonErrorMessage = JSON::hasError()) {
				JSON::lastErrorMsg(str_replace($this->sd."/","",$filepath),$jsonErrorMessage,$data);
			}
			
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
		$file = ($type == 'm') ? '\.'.$this->patternExtension : '\.json';
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
	
	/**
	* Sets the pattern state on other patterns based on the pattern state for a given partial
	* @param  {String}       the pattern state
	* @param  {String}       the pattern partial
	*/
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
		$ignoreDirs = array("styleguide","snapshots");
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
	* Print out the data var. For debugging purposes
	*
	* @return {String}       the formatted version of the d object
	*/
	public function printData() {
		print_r($this->d);
	}
	
	/**
	* Go through data and replace any values that match items from the link.array
	* @param  {String}       an entry from one of the list-based config entries
	*
	* @return {String}       trimmed version of the given $v var
	*/
	public function compareReplace(&$value) {
		if (is_string($value)) {
			$valueCheck = strtolower($value);
			$valueThin  = str_replace("link.","",$valueCheck);
			if ((strpos($valueCheck, 'link.') !== false) && array_key_exists($valueThin,$this->d["link"])) {
				$value = $this->d["link"][$valueThin];
			}
		}
		
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
