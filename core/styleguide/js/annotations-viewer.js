/*!
 * Annotations Support for the Viewer - v0.3
 *
 * Copyright (c) 2013 Brad Frost, http://bradfrostweb.com & Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

var annotationsViewer = {
	
	// set-up default sections
	commentsActive:          false,
	commentsViewAllActive:   false,
	targetOrigin:            (window.location.protocol === "file:") ? "*" : window.location.protocol+"//"+window.location.host,
	moveToOnInit:            0,
	
	/**
	* add the onclick handler to the annotations link in the main nav
	*/
	onReady: function() {
		
		// not sure this is used anymore...
		$('body').addClass('comments-ready');

		$(window).resize(function() {
			if(!annotationsViewer.commentsActive) {
				annotationsViewer.slideComment($('#sg-annotation-container').outerHeight());
			}
		});

		$('#sg-t-annotations').click(function(e) {
			
			e.preventDefault();
			
			// remove the class from the "eye" nav item
			$('#sg-t-toggle').removeClass('active');
			
			// turn the annotations section on and off
			annotationsViewer.toggleComments();
			
		});
		
		// initialize the annotations viewer
		annotationsViewer.commentContainerInit();
		
		// load the query strings in case code view has to show by default
		var queryStringVars = urlHandler.getRequestVars();
		if ((queryStringVars.view !== undefined) && ((queryStringVars.view === "annotations") || (queryStringVars.view === "a"))) {
			annotationsViewer.openComments();
			if (queryStringVars.number !== undefined) {
				annotationsViewer.moveToOnInit = queryStringVars.number;
			}
		}
		
	},
	
	/**
	* decide on if the annotations panel should be open or closed
	*/
	toggleComments: function() {
		
		if (!annotationsViewer.commentsActive) {
			annotationsViewer.openComments();
		} else {
			annotationsViewer.closeComments();
		}
		
	},
	
	/**
	* open the annotations panel
	*/
	openComments: function() {
		
		// make sure the code view overlay is off before showing the annotations view
		$('#sg-t-code').removeClass('active');
		codeViewer.codeActive = false;
		var obj = JSON.stringify({ "codeToggle": "off" });
		document.getElementById('sg-viewport').contentWindow.postMessage(obj,annotationsViewer.targetOrigin);
		codeViewer.slideCode(999);
		
		// tell the iframe annotation view has been turned on
		var obj = JSON.stringify({ "commentToggle": "on" });
		document.getElementById('sg-viewport').contentWindow.postMessage(obj,annotationsViewer.targetOrigin);
		
		// note that it's turned on in the viewer
		annotationsViewer.commentsActive = true;
		$('#sg-t-annotations').addClass('active');
	},
	
	/**
	* close the annotations panel
	*/
	closeComments: function() {
		annotationsViewer.commentsActive = false;
		var obj = JSON.stringify({ "commentToggle": "off" });
		document.getElementById('sg-viewport').contentWindow.postMessage(obj,annotationsViewer.targetOrigin);
		annotationsViewer.slideComment($('#sg-annotation-container').outerHeight());
		$('#sg-t-annotations').removeClass('active');
	},
	
	/**
	* add the basic mark-up and events for the annotations container
	*/
	commentContainerInit: function() {
		
		// the bulk of this template is in core/templates/index.mustache
		if (document.getElementById("sg-annotation-container") === null) {
			$('<div id="sg-annotation-container" class="sg-view-container"></div>').html($("#annotations-template").html()).appendTo('body').css('bottom',-$(document).outerHeight());
			setTimeout(function(){ $('#sg-annotation-container').addClass('anim-ready'); },50); //Add animation class once container is positioned out of frame
		}
		
		// make sure the close button handles the click
		$('body').delegate('#sg-annotation-close-btn','click',function() {
			annotationsViewer.commentsActive = false;
			$('#sg-t-annotations').removeClass('active');
			annotationsViewer.slideComment($('#sg-annotation-container').outerHeight());
			var obj = JSON.stringify({ "commentToggle": "off" });
			document.getElementById('sg-viewport').contentWindow.postMessage(obj,annotationsViewer.targetOrigin);
			return false;
		});
		
	},
	
	/**
	* slides the panel
	*/
	slideComment: function(pos) {
		$('#sg-annotation-container').css('bottom',-pos);
	},
	
	/**
	* moves to a particular item in the viewer
	*/
	moveTo: function(number) {
		if (document.getElementById("annotation-"+number) !== undefined) {
			var top = document.getElementById("annotation-"+number).offsetTop;
			$('#sg-annotation-container').animate({scrollTop: top - 10}, 600);
		}
	},
	
	/**
	* when turning on or switching between patterns with annotations view on make sure we get
	* the annotations from from the pattern via post message
	*/
	updateComments: function(comments) {
		
		var commentsContainer = document.getElementById("sg-comments-container");
		
		// clear out the comments container
		if (commentsContainer.innerHTML !== "") {
			commentsContainer.innerHTML = "";
		}
		
		// see how many comments this pattern might have. if more than zero write them out. if not alert the user to the fact their aren't any
		var count = Object.keys(comments).length;
		if (count > 0) {
			
			for (i = 1; i <= count; i++) {
				
				var displayNum = comments[i].number;
				
				var span = document.createElement("span");
				span.id = "annotation-state-" + displayNum;
				span.style.fontSize = "0.8em";
				span.style.color    = "#666";
				if (comments[i].state === false) {
					span.innerHTML  = " hidden";
				}
				
				var h2 = document.createElement("h2");
				h2.innerHTML  = displayNum + ". " + comments[i].title;
				h2.appendChild(span);
				
				var div = document.createElement("div");
				div.innerHTML = comments[i].comment;
				
				var commentDiv = document.createElement("div");
				commentDiv.classList.add("sg-comment-container");
				commentDiv.id = "annotation-" + displayNum;
				commentDiv.appendChild(h2);
				commentDiv.appendChild(div);
				
				commentsContainer.appendChild(commentDiv);
				
			}
			
		} else {
			
			var h2        = document.createElement("h2");
			h2.innerHTML  = "No Annotations";
			
			var div       = document.createElement("div");
			div.innerHTML = "There are no annotations for this pattern.";
			
			var commentDiv = document.createElement("div");
			commentDiv.classList.add("sg-comment-container");
			commentDiv.appendChild(h2);
			commentDiv.appendChild(div);
			
			commentsContainer.appendChild(commentDiv);
			
		}
		
		// slide the comment section into view
		annotationsViewer.slideComment(0);
		
		if (annotationsViewer.moveToOnInit != "0") {
			annotationsViewer.moveTo(annotationsViewer.moveToOnInit);
			annotationsViewer.moveToOnInit = "0";
		}
		
	},
	
	/**
	* toggle the comment pop-up based on a user clicking on the pattern
	* based on the great MDN docs at https://developer.mozilla.org/en-US/docs/Web/API/window.postMessage
	* @param  {Object}      event info
	*/
	receiveIframeMessage: function(event) {
		
		var data = (typeof event.data !== "string") ? event.data : JSON.parse(event.data);
		
		// does the origin sending the message match the current host? if not dev/null the request
		if ((window.location.protocol !== "file:") && (event.origin !== window.location.protocol+"//"+window.location.host)) {
			return;
		}
		
		if (data.commentOverlay !== undefined) {
			if (data.commentOverlay === "on") {
				annotationsViewer.updateComments(data.comments);
			} else {
				annotationsViewer.slideComment($('#sg-annotation-container').outerHeight());
			}
		} else if (data.annotationState !== undefined) {
			document.getElementById("annotation-state-"+data.displayNumber).innerHTML = (data.annotationState == true) ? "" : " hidden";
		} else if (data.displaynumber !== undefined) {
			annotationsViewer.moveTo(data.displaynumber);
		} else if (data.keyPress !== undefined) {
			if (data.keyPress == 'ctrl+shift+a') {
				annotationsViewer.toggleComments();
				return false;
			} else if (data.keyPress == 'esc') {
				if (annotationsViewer.commentsActive) {
					annotationsViewer.closeComments();
					return false;
				}
			}
		} else if (data.patternpartial !== undefined) {
			if (annotationsViewer.commentsViewAllActive && (data.patternpartial.indexOf("viewall-") != -1)) {
				var obj = JSON.stringify({ "commentToggle": "on" });
				document.getElementById('sg-viewport').contentWindow.postMessage(obj,annotationsViewer.targetOrigin);
			}
		}
		
	}
	
};

$(document).ready(function() { annotationsViewer.onReady(); });
window.addEventListener("message", annotationsViewer.receiveIframeMessage, false);

// make sure if a new pattern or view-all is loaded that comments are turned on as appropriate
$('#sg-viewport').load(function() {
	if (annotationsViewer.commentsActive) {
		var obj = JSON.stringify({ "commentToggle": "on" });
		document.getElementById('sg-viewport').contentWindow.postMessage(obj,annotationsViewer.targetOrigin);
	}
});

// no idea why this has to be outside. there's something funky going on with the JS pattern
$('#sg-view li a').click(function() {
	$(this).parent().parent().removeClass('active');
	$(this).parent().parent().parent().parent().removeClass('active');
});

// toggle the annotations panel
jwerty.key('ctrl+shift+a', function (e) {
	annotationsViewer.toggleComments();
	return false;
});

// close the annotations panel if using escape
jwerty.key('esc', function (e) {
	if (annotationsViewer.commentsActive) {
		annotationsViewer.closeComments();
		return false;
	}
});
