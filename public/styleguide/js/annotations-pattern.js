/*!
 * Annotations Support for Patterns - v0.2
 *
 * Copyright (c) 2013 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

var annotationsPattern = {
	
	commentsActive: false,
	
	/**
	* add an onclick handler to each element in the pattern that has an annotation
	*/
	showComments: function() {
		
		for (comment in comments.comments) {
			var item = comments.comments[comment];
			var els  = document.querySelectorAll(item.el);
			if (els.length > 0) {
				for (el in els) {
					els[el].onclick = (function(item) {
						return function(e) {
							if (annotationsPattern.commentsActive) {
								e.preventDefault();
								e.stopPropagation();
								var obj = { "el": item.el, "title": item.title, "comment": item.comment };
								var targetOrigin = (window.location.protocol == "file:") ? "*" : window.location.protocol+"//"+window.location.host;
								parent.postMessage(obj,targetOrigin);
							}
					    }
					  })(item);
				}
			}
		}
		
	},	
	
	/**
	* toggle the has-comment class on/off based on user clicking in the viewer UI
	* based on the great MDN docs at https://developer.mozilla.org/en-US/docs/Web/API/window.postMessage
	* @param  {Object}      event info
	*/
	receiveIframeMessage: function(event) {
		
		// does the origin sending the message match the current host? if not dev/null the request
		if ((window.location.protocol != "file:") && (event.origin !== window.location.protocol+"//"+window.location.host)) {
			return;
		}
		
		if (event.data.commentToggle != undefined) {
			annotationsPattern.commentsActive = (event.data.commentToggle == "on") ? true : false;
			for (comment in comments.comments) {
				var item = comments.comments[comment];
				var els = document.querySelectorAll(item.el);
				if (els.length > 0) {
					for (el in els) {
						if (els[el].classList.length > 0) {
							if (event.data.commentToggle == "on") {
								els[el].classList.add("has-comment");
							} else {
								els[el].classList.remove("has-comment");
							}
						}
					}
				}
			}
		}
	}
	
};

annotationsPattern.showComments();
window.addEventListener("message", annotationsPattern.receiveIframeMessage, false);