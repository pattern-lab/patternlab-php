/*!
 * Annotations Support for Patterns - v0.1
 *
 * Copyright (c) 2013 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

var annotationsPattern = {
	
	commentsActive: false,
	
	showComments: function() {
		
		$.each(comments.comments, function(i, item) {
			$(item.el).bind('click',function(e) {
			    if (annotationsPattern.commentsActive) {
					e.preventDefault();
					var obj = { "el": item.el, "title": item.title, "comment": item.comment };
					parent.postMessage(obj,window.location.protocol+"//"+window.location.host);
					return false;
				}
			});
			
		});
		
	},	
	
	
	// watch the iframe source so that it can be sent back to everyone else.
	// based on the great MDN docs at https://developer.mozilla.org/en-US/docs/Web/API/window.postMessage
	receiveIframeMessage: function(event) {
		
		if (event.data == "on") {
			annotationsPattern.commentsActive = true;
			$.each(comments.comments, function(i, item) {
				$(item.el).addClass('has-comment');
			});
		} else if (event.data == "off") {
			annotationsPattern.commentsActive = false;
			$.each(comments.comments, function(i, item) {
				$(item.el).removeClass('has-comment');
			});
		}
		
	}
	
	
};

$(document).ready(function() { annotationsPattern.showComments(); });
window.addEventListener("message", annotationsPattern.receiveIframeMessage, false);