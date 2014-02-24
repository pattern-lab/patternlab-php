/*!
 * Code View Support for Patterns - v0.3
 *
 * Copyright (c) 2013-2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

var codePattern = {
	
	codeOverlayActive:  false,
	codeEmbeddedActive: false,
	targetOrigin: (window.location.protocol === "file:") ? "*" : window.location.protocol+"//"+window.location.host,
	
	/**
	* toggle the annotation feature on/off
	* based on the great MDN docs at https://developer.mozilla.org/en-US/docs/Web/API/window.postMessage
	* @param  {Object}      event info
	*/
	receiveIframeMessage: function(event) {
		
		var data = (typeof event.data !== "string") ? event.data : JSON.parse(event.data);
		
		// does the origin sending the message match the current host? if not dev/null the request
		if ((window.location.protocol != "file:") && (event.origin !== window.location.protocol+"//"+window.location.host)) {
			return;
		}
		
		if (data.codeToggle !== undefined) {
			
			var els, i;
			
			// if this is an overlay make sure it's active for the onclick event
			codePattern.codeOverlayActive  = false;
			codePattern.codeEmbeddedActive = false;
			
			// see which flag to toggle based on if this is a styleguide or view-all page
			if ((data.codeToggle == "on") && (document.getElementById("sg-patterns") !== null)) {
				codePattern.codeEmbeddedActive = true;
			} else if (data.codeToggle == "on") {
				codePattern.codeOverlayActive  = true;
			}
			
			// if comments embedding is turned off make sure to hide the annotations div
			if (!codePattern.codeEmbeddedActive && (document.getElementById("sg-patterns") !== null)) {
				els = document.getElementsByClassName("sg-code");
				for (i = 0; i < els.length; i++) {
					els[i].style.display = "none";
				}
			}
			
			// if comments overlay is turned on add the has-comment class and pointer
			if (codePattern.codeOverlayActive) {
				
				var obj = JSON.stringify({ "codeOverlay": "on", "lineage": lineage, "lineageR": lineageR, "patternPartial": patternPartial, "patternState": patternState, "cssEnabled": cssEnabled });
				parent.postMessage(obj,codePattern.targetOrigin);
				
			} else if (codePattern.codeEmbeddedActive) {
				
				// if code embedding is turned on simply display them
				els = document.getElementsByClassName("sg-code");
				for (i = 0; i < els.length; ++i) {
					els[i].style.display = "block";
				}
				
			}
			
		}
		
	}
	
};

// add the onclick handlers to the elements that have an annotations
window.addEventListener("message", codePattern.receiveIframeMessage, false);

// before unloading the iframe make sure any active overlay is turned off/closed
window.onbeforeunload = function() {
	var obj = JSON.stringify({ "codeOverlay": "off" });
	parent.postMessage(obj,codePattern.targetOrigin);
};

// tell the parent iframe that keys were pressed

// toggle the code panel
jwerty.key('ctrl+shift+c', function (e) {
	var obj = JSON.stringify({ "keyPress": "ctrl+shift+c" });
	parent.postMessage(obj,codePattern.targetOrigin);
	return false;
});

// when the code panel is open hijack cmd+a so that it only selects the code view
jwerty.key('cmd+a/ctrl+a', function (e) {
	var obj = JSON.stringify({ "keyPress": "cmd+a" });
	parent.postMessage(obj,codePattern.targetOrigin);
	return false;
});

// open the mustache panel
jwerty.key('ctrl+shift+u', function (e) {
	var obj = JSON.stringify({ "keyPress": "ctrl+shift+u" });
	parent.postMessage(obj,codePattern.targetOrigin);
	return false;
});

// open the html panel
jwerty.key('ctrl+shift+h', function (e) {
	var obj = JSON.stringify({ "keyPress": "ctrl+shift+h" });
	parent.postMessage(obj,codePattern.targetOrigin);
	return false;
});

// close the code panel if using escape
jwerty.key('esc', function (e) {
	var obj = JSON.stringify({ "keyPress": "esc" });
	parent.postMessage(obj,codePattern.targetOrigin);
	return false;
});