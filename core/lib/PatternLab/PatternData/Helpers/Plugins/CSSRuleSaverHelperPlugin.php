// set-up the mark-up for CSS Rule Saver so it can figure out which rules to save
$patternCSSExists     = $this->enableCSS;
$patternCSS           = "";
if ($this->enableCSS) {
	$this->cssRuleSaver->loadHTML($patternCodeRaw,false);
	$patternCSS = $this->cssRuleSaver->saveRules();
	$this->patternCSS[$patternSubtypeItem["patternPartial"]] = $patternCSS;
}