/*!
 * Annotations Support for Patterns - v0.3
 *
 * Copyright (c) 2013-2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

var annotationsPattern = {
	
	commentsOverlayActive:  false,
	commentsOverlay:        false,
	commentsEmbeddedActive: false,
	commentsEmbedded:       false,
	commentsGathered:       { "commentOverlay": "on", "comments": { } },
	trackedElements:        [ ],
	targetOrigin:           (window.location.protocol == "file:") ? "*" : window.location.protocol+"//"+window.location.host,
	
	/**
	* record which annotations are related to this pattern so they can be sent to the viewer when called
	*/
	gatherComments: function() {
		
		// make sure this only added when we're on a pattern specific view
		if (document.getElementById("sg-patterns") === null) {
			
			var count = 0;
			
			for (comment in comments.comments) {
				var item = comments.comments[comment];
				var els  = document.querySelectorAll(item.el);
				if (els.length > 0) {
					
					count++;
					item.displaynumber = count;
					
					for (var i = 0; i < els.length; ++i) {
						els[i].onclick = (function(item) {
							return function(e) {
								
								if (annotationsPattern.commentsOverlayActive) {
									
									e.preventDefault();
									e.stopPropagation();
									
									// if an element was clicked on while the overlay was already on swap it
									var obj = JSON.stringify({ "displaynumber": item.displaynumber, "el": item.el, "title": item.title, "comment": item.comment });
									parent.postMessage(obj,annotationsPattern.targetOrigin);
									
								}
								
							}
						})(item);
					}
				}
				
				
			}
			
		} else {
			
			var obj = JSON.stringify({ "commentOverlay": "off" });
			parent.postMessage(obj,annotationsPattern.targetOrigin);
			
		}
		
	},
	
	/**
	* embed a comment by building the sg-annotations div (if necessary) and building an sg-annotation div
	* @param  {Object}      element to check the parent node of
	* @param  {String}      the title of the comment
	* @param  {String}      the comment HTML
	*/
	embedComments: function (el,title,comment) {
		
		// build the annotation div and add the content to it
		var annotationDiv = document.createElement("div");
		annotationDiv.classList.add("sg-annotation");
		
		var h3       = document.createElement("h3");
		var p        = document.createElement("p");
		h3.innerHTML = title;
		p.innerHTML  = comment;
		
		annotationDiv.appendChild(h3);
		annotationDiv.appendChild(p);
		
		// find the parent element to attach things to
		var parentEl = annotationsPattern.findParent(el);
		
		// see if a child with the class annotations exists
		var els = parentEl.getElementsByClassName("sg-annotations");
		if (els.length > 0) {
			els[0].appendChild(annotationDiv);
		} else {
			var annotationsDiv = document.createElement("div");
			annotationsDiv.classList.add("sg-annotations");
			annotationsDiv.appendChild(annotationDiv);
			parentEl.appendChild(annotationsDiv);
		}
		
	},
	
	/**
	* recursively find the parent of an element to see if it contains the sg-pattern class
	* @param  {Object}      element to check the parent node of
	*/
	findParent: function(el) {
		
		var parentEl;
		
		if (el.classList.contains("sg-pattern")) {
			return el;
		} else if (el.parentNode.classList.contains("sg-pattern")) {
			return el.parentNode;
		} else {
			parentEl = annotationsPattern.findParent(el.parentNode);
		}
		
		return parentEl;
		
	},
	
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
		
		if ((data.resize !== undefined) && (annotationsPattern.commentsOverlayActive)) {
			
			for (var i = 0; i < annotationsPattern.trackedElements.length; ++i) {
				var el = annotationsPattern.trackedElements[i];
				if (window.getComputedStyle(el.element,null).getPropertyValue("max-height") == "0px") {
					el.element.firstChild.style.display = "none";
					var obj = JSON.stringify({"annotationState": false, "displayNumber": el.displayNumber });
					parent.postMessage(obj,annotationsPattern.targetOrigin);
				} else {
					el.element.firstChild.style.display = "block";
					var obj = JSON.stringify({"annotationState": true, "displayNumber": el.displayNumber });
					parent.postMessage(obj,annotationsPattern.targetOrigin);
				}
			}
			
		} else if (data.commentToggle !== undefined) {
			
			var i, els, item, displayNum;
			
			// if this is an overlay make sure it's active for the onclick event
			annotationsPattern.commentsOverlayActive  = false;
			annotationsPattern.commentsEmbeddedActive = false;
			
			// see which flag to toggle based on if this is a styleguide or view-all page
			if ((data.commentToggle === "on") && (document.getElementById("sg-patterns") !== null)) {
				annotationsPattern.commentsEmbeddedActive = true;
			} else if (data.commentToggle === "on") {
				annotationsPattern.commentsOverlayActive  = true;
			}
			
			// if comments overlay is turned off make sure to remove the has-annotation class and pointer
			if (!annotationsPattern.commentsOverlayActive) {
				els = document.querySelectorAll(".has-annotation");
				for (i = 0; i < els.length; i++) {
					els[i].classList.remove("has-annotation");
				}
				els = document.querySelectorAll(".annotation-tip");
				for (i = 0; i < els.length; i++) {
					els[i].style.display = "none";
				}
			}
			
			// if comments embedding is turned off make sure to hide the annotations div
			if (!annotationsPattern.commentsEmbeddedActive) {
				els = document.getElementsByClassName("sg-annotations");
				for (i = 0; i < els.length; i++) {
					els[i].style.display = "none";
				}
			}
			
			// if comments overlay is turned on add the has-annotation class and pointer
			if (annotationsPattern.commentsOverlayActive) {
				
				var count = 0;
				
				for (i = 0; i < comments.comments.length; i++) {
					item = comments.comments[i];
					els  = document.querySelectorAll(item.el);
					
					var state = true;
					
					if (els.length) {
						
						count++;
						
						//Loop through all items with annotations
						for (k = 0; k < els.length; k++) {
							
							els[k].classList.add("has-annotation");
							
							var span       = document.createElement("span");
							span.innerHTML = count;
							span.classList.add("annotation-tip");
							
							if (window.getComputedStyle(els[k],null).getPropertyValue("max-height") == "0px") {
								span.style.display = "none";
								state = false;
							}
							
							annotationsPattern.trackedElements.push({ "itemel": item.el, "element": els[k], "displayNumber": count, "state": state });
							
							els[k].insertBefore(span,els[k].firstChild);
							
						}
						
					}
					
				}
				
				// count elements so it can be used when displaying the results in the viewer
				var count = 0;
				
				// iterate over the comments in annotations.js
				for(i = 0; i < comments.comments.length; i++) {
					
					var state = true;
					
					var item  = comments.comments[i];
					var els   = document.querySelectorAll(item.el);
					
					// if an element is found in the given pattern add it to the overall object so it can be passed when the overlay is turned on
					if (els.length > 0) {
						count++;
						for (k = 0; k < els.length; k++) {
							if (window.getComputedStyle(els[k],null).getPropertyValue("max-height") == "0px") {
								state = false;
							}
						}
						annotationsPattern.commentsGathered.comments[count] = { "el": item.el, "title": item.title, "comment": item.comment, "number": count, "state": state };
					}
				
				}
				
				// send the list of annotations for the page back to the parent
				var obj = JSON.stringify(annotationsPattern.commentsGathered);
				parent.postMessage(obj,annotationsPattern.targetOrigin);
				
			} else if (annotationsPattern.commentsEmbeddedActive && !annotationsPattern.commentsEmbedded) {
				
				// if comment embedding is turned on and comments haven't been embedded yet do it
				for (i = 0; i < comments.comments.length; i++)  {
					item = comments.comments[i];
					els  = document.querySelectorAll(item.el);
					if (els.length > 0) {
						annotationsPattern.embedComments(els[0],item.title,item.comment); //Embed the comment
					}
					annotationsPattern.commentsEmbedded = true;
				}
				
			} else if (annotationsPattern.commentsEmbeddedActive && annotationsPattern.commentsEmbedded) {
				
				// if comment embedding is turned on and comments have been embedded simply display them
				els = document.getElementsByClassName("sg-annotations");
				for (i = 0; i < els.length; ++i) {
					els[i].style.display = "block";
				}
				
			}
			
		}
		
	}
	
};

// add the onclick handlers to the elements that have an annotations
annotationsPattern.gatherComments();
window.addEventListener("message", annotationsPattern.receiveIframeMessage, false);

// before unloading the iframe make sure any active overlay is turned off/closed
window.onbeforeunload = function() {
	var obj = JSON.stringify({ "commentOverlay": "off" });
	parent.postMessage(obj,annotationsPattern.targetOrigin);
};

// tell the parent iframe that keys were pressed

// toggle the annotations panel
jwerty.key('ctrl+shift+a', function (e) {
	var obj = JSON.stringify({ "keyPress": "ctrl+shift+a" });
	parent.postMessage(obj,codePattern.targetOrigin);
	return false;
});

// close the annotations panel if using escape
jwerty.key('esc', function (e) {
	var obj = JSON.stringify({ "keyPress": "esc" });
	parent.postMessage(obj,codePattern.targetOrigin);
	return false;
});
