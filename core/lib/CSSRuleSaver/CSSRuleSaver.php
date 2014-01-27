<?php

/*!
 * CSS Rule Saver - v0.1.0
 *
 * Copyright (c) 2013 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace CSSRuleSaver;

class CSSRuleSaver {
	
	// default vars for cssRuleSaver
	protected $htmlData = "";
	protected $ruleSets = array();
	protected $atRules  = array();
	protected $dom;
	
	/**
	* Load the CSS data into the $this->ruleSets and $this->atRules arrays
	* @param  {String}       the filename of the CSS file
	*/
	public function loadCSS($file) {
		
		if (!file_exists($file)) {
			$this->error("The CSS file you supplied doesn't seem to exist. Check the path.");
		}
		
		$commentOpen          = false;
		$atRuleOpen           = false;
		$declarationBlockOpen = false;
		$fontFaceRuleOpen     = false;
		
		$atRule               = "";
		$declarationBlock     = "";
		$selectors            = "";
		
		// iterate over the given file and parse it into at-rules, selectors and their given declaration blocks
		$fp = fopen($file, "r");
		while(!feof($fp)) {
			$current_line = fgets($fp);
			if (!feof($fp)) {
				
				if ((strpos($current_line, "/*") !== false) && (strpos($current_line, "*/") === false)) {
					
					// matched a comment that *didn't* close on the same line
					$commentOpen = true;
					
				} else if (strpos($current_line, "*/") !== false) {
					
					// comment closed
					$commentOpen = false;
					
				} else if ($commentOpen) {
					
					// skip this line of the CSS file because we're inside a comment
					
				} else if (strpos($current_line, "@") !== false) {
					
					// matched an at-rule like a media query
					$atRuleOpen = true;
					$atRule     = trim(str_replace("{","",$current_line));
					
					// handle the weird case of the @font-face at-rule
					if (strpos($current_line, "@font-face") !== false) {
						$declarationBlock     = "";
						$declarationBlockOpen = true;
						$fontFaceRuleOpen     = true;
					}
					
				} else if (strpos($current_line, "{") !== false) {
					
					// matched the opening of a declaration block
					$declarationBlock     = "";
					$declarationBlockOpen = true;
					$selectors = trim(str_replace("{","",$current_line));
					
				} else if (strpos($current_line, "}") !== false) {
					
					// matched the closing of a declaration block or at-rule
					if ($atRuleOpen && !$declarationBlockOpen) {
						
						// it was an at-rule. close it up
						$atRuleOpen = false;
						
					} else {
						
						// it was a declaration block. close it up.
						$declarationBlockOpen = false;
						$declarationBlock    .= "\t".trim(str_replace("}","",$current_line));
						
						// if we're within an at-rule assign all the styles to it (e.g. styles under a media query)
						if ($atRuleOpen) {
							if (!array_key_exists($atRule,$this->atRules)) {
								$this->atRules[$atRule] = array();
							}
							$this->atRules[$atRule][$selectors] = !array_key_exists($selectors,$this->atRules[$atRule]) ? "\t".trim($declarationBlock) : $this->atRules[$atRule][$selectors]."\n\t".trim($declarationBlock);
						} else {
							$this->ruleSets[$selectors] = !array_key_exists($selectors,$this->ruleSets) ? "\t".trim($declarationBlock) : $this->ruleSets[$selectors]."\n\t".trim($declarationBlock);
						}
						
						// wait, a font-face rule was open. close it all up
						if ($fontFaceRuleOpen) {
							$fontFaceRuleOpen = false;
							$atRuleOpen = false;
						} else if (substr_count($current_line, "}") > 1) {
							
							// oops, someone closed the at-rule on the same line as the declaration block
							// *shakes fist at sass*
							$atRuleOpen = false;
						}
					}
				} else if ($declarationBlockOpen) {
					
					// declaration block is open so keep reading it in
					$declarationBlock .= "\t".ltrim($current_line);
					
				}
			}
		}
		fclose($fp);
		
	}
	
	/**
	* Load the HTML data
	* @param  {String}       the filename of the HTML file
	*/
	public function loadHTML($file,$load = true) {
		if ($load) {
			if (file_exists($file)) {
				$this->htmlData = file_get_contents($file);
			} else {
				$this->error("The HTML file you supplied doesn't seem to exist. Check the path.");
			}
		} else {
			$this->htmlData = $file;
		}
	}
	
	/**
	* Save the CSS rules that match between the given CSS file and the HTML file
	*
	* @return {String}       the rules that match between the given CSS file and HTML file
	*/
	public function saveRules() {
		
		// make sure data exists to compare
		if (($this->htmlData == "") || (count($this->ruleSets) == 0)) {
			$this->error("This would work better if there was CSS or HTML data.");
		}
		
		// set-up the selector DOM to compare
		$this->dom = new SelectorDOM($this->htmlData);
		
		// iterate over the default rule sets and test them against the given mark-up
		$statements = "";
		foreach ($this->ruleSets as $selector => $declarationBlock) {
			$statements .= $this->buildRuleSet($selector,$declarationBlock);
		}
		
		// iterate over the at-rules
		foreach ($this->atRules as $atRule => $ruleSets) {
			
			// iterate over the rule sets in the at-rules and test them against the given mark-up
			$atRuleSets = "";
			foreach ($ruleSets as $selector => $declarationBlock) {
				$atRuleSets .= $this->buildRuleSet($selector,$declarationBlock,"\t");
			}
			
			if ($atRuleSets != "") {
				
				// only write-out the at-rule if it contains at least one rule set
				$statements .= $atRule." {\n";
				$statements .= $atRuleSets."\n";
				$statements .= "}\n\n";
				
			} else if ($atRule == "@font-face") {
				
				// if the at-rule is a @font-face write it out no matter what
				foreach ($ruleSets as $selector => $ruleSet) {
					$statements .= $atRule." {\n";
					$statements .= $ruleSet."\n";
					$statements .= "}\n\n";
				}
				
			}
		}
		
		unset($this->dom);
		
		return $statements;
		
	}
	
	/**
	* Compare the given selector(s) against the DOM. Return the rule set if it matches
	* @param  {String}       the selector(s) to test against the xPath
	* @param  {String}       the declaration block that goes with the selector
	* @param  {String}       any indent characters that might need to be added to the final output
	*
	* @return {String}       if the selector(s) matched return the entire rule set with matches
	*/
	protected function buildRuleSet($selector,$declarationBlock,$indent = "") {
		
		// trap the selectors that are found
		$foundSelectors = array();
		
		// a given selector may have multiple parts (e.g. h1, h2, h3 ) break it up so each can be tested.
		$selectors      = explode(",",$selector);
		
		// iterate over each selector
		foreach ($selectors as $selector) {
			
			$selector     = trim($selector);
			
			// save the original selector and strip off bad pseudo-classes for matching purposes
			$selectorOrig = $selector;
			$badPseudoClasses = array(":first-child",":last-child",":after",":before",":nth-of",":visited",":hover",":focus","::");
			foreach ($badPseudoClasses as $badPseudoClass) {
				$pos = strpos($selector,$badPseudoClass);
				if ($pos !== false) {
					$selector = substr($selector,0,$pos-strlen($selector));
					break;
				}
			}
			
			// match the selector against the DOM. if result is found save the original selector format
			if (count($this->dom->select($selector)) > 0) {
				$foundSelectors[] = $selectorOrig;
			}
		}
		
		// if the given selectors matched against the DOM build the rule set
		if (count($foundSelectors) > 0) {
			
			// combine selectors that share a rule set
			$i = 0;
			$selectorList = "";
			foreach ($foundSelectors as $selector) {
				$selectorList .= ($i > 0) ? ", ".$selector : $selector;
				$i++;
			}
			
			// write out the rule set & return it
			$text  = $indent.$selectorList." { \n";
			$text .= str_replace($indent,$indent.$indent,$declarationBlock)."\n";
			$text .= $indent."}\n\n";
			return $text;
			
		}
		
	}
	
	/**
	* Print the error message. Yes, I should be using exception handling but I'm being lazy for now
	* @param  {String}       the message to spit out
	*/
	protected function error($msg) {
		print $msg."\n";
		exit;
	}
	
}
