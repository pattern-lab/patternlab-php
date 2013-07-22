/*!
 * URL Handler - v0.1
 *
 * Copyright (c) 2013 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Helps handle the initial iFrame source. Parses a string to see if it matches
 * an expected pattern in Pattern Lab. Supports Pattern Labs fuzzy pattern partial
 * matching style.
 *
 */

var urlHandler = {
	
	getFileName: function (name) {
	
		var baseDir     = "patterns";
		var fileName    = "";
	
		var bits        = this.getPatternInfo(name);
		var patternType = bits[0];
		var pattern     = bits[1];
	
		if ((patternPaths[patternType] != undefined) && (patternPaths[patternType][pattern] != undefined)) {
		
			fileName = patternPaths[patternType][pattern];
		
		} else if (patternPaths[patternType] != undefined) {
		
			for (patternMatchKey in patternPaths[patternType]) {
				if (patternMatchKey.indexOf(pattern) != -1) {
					fileName = patternPaths[patternType][patternMatchKey];
					break;
				}
			}
		
		}
	
		var regex = /\//g;
		return (fileName == "") ? fileName : baseDir+"/"+fileName.replace(regex,"-")+"/"+fileName.replace(regex,"-")+".html";
	
	},
	
	getPatternInfo: function (name) {
	
		var patternBits = name.split("-");
	
		var i = 1;
		var c = patternBits.length;
	
		var patternType = patternBits[0];
		while ((patternPaths[patternType] == undefined) && (i < c)) {
			patternType += "-"+patternBits[i];
			i++;
		}
	
		pattern = name.slice(patternType.length+1,name.length);
	
		return [patternType, pattern];
	
	}

}

// the following is taken from https://developer.mozilla.org/en-US/docs/Web/API/window.location
var oGetVars = new (function (sSearch) {
  if (sSearch.length > 1) {
    for (var aItKey, nKeyId = 0, aCouples = sSearch.substr(1).split("&"); nKeyId < aCouples.length; nKeyId++) {
      aItKey = aCouples[nKeyId].split("=");
      this[unescape(aItKey[0])] = aItKey.length > 1 ? unescape(aItKey[1]) : "";
    }
  }
})(window.location.search);

var iFramePath = "";
if ((oGetVars.p != undefined) || (oGetVars.pattern != undefined)) {
	patternName = (oGetVars.p != undefined) ? oGetVars.p : oGetVars.pattern;
	iFramePath = urlHandler.getFileName(patternName);
}

iFramePath = (iFramePath != "") ? iFramePath : "styleguide/html/styleguide.html";

$("#sg-viewport").attr("src",iFramePath);
