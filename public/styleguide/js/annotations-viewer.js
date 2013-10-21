/*!
 * Annotations Support for the Viewer - v0.2
 *
 * Copyright (c) 2013 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

var annotationsViewer = {
	
	commentsActive: false,
	sw:             document.documentElement.clientWidth,
	breakpoint:     650,
	
	onReady: function() {
		
		$('body').addClass('comments-ready');
		$('#sg-t-annotations').click(function(){
			annotationsViewer.toggleComments();
			return false;
		});
		
		annotationsViewer.commentContainerInit();
		
	},
	
	toggleComments: function() {
		
		var targetOrigin = (window.location.protocol == "file:") ? "*" : window.location.protocol+"//"+window.location.host;
		
		if (!annotationsViewer.commentsActive) {
			
			annotationsViewer.commentsActive = true;
			document.getElementById('sg-viewport').contentWindow.postMessage({ "commentToggle": "on" },targetOrigin);
			$('#sg-t-annotations').text('Annotations On');
			
		} else {
			
			annotationsViewer.commentsActive = false;
			document.getElementById('sg-viewport').contentWindow.postMessage({ "commentToggle": "off" },targetOrigin);
			$('#sg-t-annotations').text('Annotations Off');
			annotationsViewer.slideComment(999);
			
		}
		
	},
	
	commentContainerInit: function() {
		
		$('<div id="comment-container" style="display: none;"></div>').html('<a href="#" id="close-comments">Close</a><h2 id="comment-title">Annotation Title</h2><div id="comment-text">Here is some comment text</div>').appendTo('body').css('bottom',-$(document).outerHeight());
		
		if (annotationsViewer.sw < annotationsViewer.breakpoint) {
			$('#comment-container').hide();
		} else {
			$('#comment-container').show();
		}
		
		$('body').delegate('#close-comments','click',function(e) {
			annotationsViewer.slideComment($('#comment-container').outerHeight());
			return false;
		});
		
	},
	
	slideComment: function(pos) {
		
		$('#comment-container').show();
		
		if (annotationsViewer.sw > annotationsViewer.breakpoint) {
			$('#comment-container').css('bottom',-pos);
		} else {
			var offset = $('#comment-container').offset().top;
			$('html,body').animate({scrollTop: offset}, 500);
		}
		
	},
	
	updateComment: function(el,title,msg) {
			var $container = $('#comment-container'),
				$title = $('#comment-title'),
				$text = $('#comment-text');
			$title.text(title);
			$text.html(msg);
			annotationsViewer.slideComment(0);
	},
	
	/**
	* toggle the comment pop-up based on a user clicking on the pattern
	* based on the great MDN docs at https://developer.mozilla.org/en-US/docs/Web/API/window.postMessage
	* @param  {Object}      event info
	*/
	receiveIframeMessage: function(event) {
		
		// does the origin sending the message match the current host? if not dev/null the request
		if ((window.location.protocol != "file:") && (event.origin !== window.location.protocol+"//"+window.location.host)) {
			return;
		}
		
		if (event.data.title != undefined) {
			annotationsViewer.updateComment(event.data.el,event.data.title,event.data.comment);
		}
		
	}
	
}

$(document).ready(function() { annotationsViewer.onReady(); });
window.addEventListener("message", annotationsViewer.receiveIframeMessage, false);

// no idea why this has to be outside. there's something funky going on with the JS pattern
$('#sg-view li a').click(function() {
	$(this).parent().parent().removeClass('active');
	$(this).parent().parent().parent().parent().removeClass('active');
});
