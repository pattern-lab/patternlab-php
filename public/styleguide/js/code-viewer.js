/*!
 * Code View Support for the Viewer - v0.1
 *
 * Copyright (c) 2013 Brad Frost, http://bradfrostweb.com & Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

var codeViewer = {
	
	codeActive:     false,
	tabActive:        "e",
	encoded:           "",
	mustache:          "",
	css:               "",
	
	onReady: function() {
		
		$('body').addClass('code-ready');
		$('#sg-t-code').click(function(e) {
			e.preventDefault();
			
			// make sure the annotations overlay is off
			$('#sg-t-annotations').removeClass('active');
			annotationsViewer.commentsActive = false;
			var targetOrigin = (window.location.protocol === "file:") ? "*" : window.location.protocol+"//"+window.location.host;
			document.getElementById('sg-viewport').contentWindow.postMessage({ "commentToggle": "off" },targetOrigin);
			annotationsViewer.slideComment(999);
			
			$('#sg-t-toggle').removeClass('active');
			
			if ($(this).hasClass('active')) {
				codeViewer.closeCode();
			} else {
				codeViewer.openCode();
			}
			
			codeViewer.codeContainerInit();
			
		});
	},
	
	openCode: function() {
		var targetOrigin = (window.location.protocol === "file:") ? "*" : window.location.protocol+"//"+window.location.host;
		codeViewer.codeActive = true;
		document.getElementById('sg-viewport').contentWindow.postMessage({ "codeToggle": "on" },targetOrigin);
		$('#sg-t-code').addClass('active');
	},
	
	closeCode: function() {
		var targetOrigin = (window.location.protocol === "file:") ? "*" : window.location.protocol+"//"+window.location.host;
		codeViewer.codeActive = false;
		document.getElementById('sg-viewport').contentWindow.postMessage({ "codeToggle": "off" },targetOrigin);
		codeViewer.slideCode($('#sg-code-container').outerHeight());
		$('#sg-t-code').removeClass('active');
	},
	
	codeContainerInit: function() {
		
		if (document.getElementById("sg-code-container") === null) {
			$('<div id="sg-code-container" class="sg-view-container"></div>').html($("#code-template").html()).appendTo('body').css('bottom',-$(document).outerHeight());
		}
		
		//Close Code View Button
		$('body').delegate('#sg-code-close-btn','click',function() {
			codeViewer.closeCode();
			return false;
		});
		
		//make sure the click events are handled
		$('#sg-code-title-html').click(function() {
			$('.sg-code-title-active').removeClass('sg-code-title-active');
			$(this).toggleClass("sg-code-title-active");
			codeViewer.swapCode("e");
		});
		
		$('#sg-code-title-mustache').click(function() {
			$('.sg-code-title-active').removeClass('sg-code-title-active');
			$(this).toggleClass("sg-code-title-active");
			codeViewer.swapCode("m");
		});
		
		$('#sg-code-title-css').click(function() {
			$('.sg-code-title-active').removeClass('sg-code-title-active');
			$(this).toggleClass("sg-code-title-active");
			codeViewer.swapCode("c");
		});
		

		
	},
	
	slideCode: function(pos) {
		$('#sg-code-container').css('bottom',-pos);
	},
	
	saveEncoded: function() {
		codeViewer.encoded = this.responseText;
		if (codeViewer.tabActive == "e") {
			codeViewer.activateDefaultTab("e",this.responseText);
		}
	},
	
	saveMustache: function() {
		codeViewer.mustache = this.responseText;
		if (codeViewer.tabActive == "m") {
			codeViewer.activateDefaultTab("m",this.responseText);
		}
	},
	
	saveCSS: function() {
		$('#sg-code-title-css').css("display","block");
		codeViewer.css = this.responseText;
		if (codeViewer.tabActive == "c") {
			codeViewer.activateDefaultTab("c",this.responseText);
		}
	},
	
	swapCode: function(type) {
		var fill      = "";
		var className = (type == "c") ? "css" : "markup";
		$("#sg-code-fill").removeClass().addClass("language-"+className);
		if (type == "m") {
			fill = codeViewer.mustache;
		} else if (type == "e") {
			fill = codeViewer.encoded;
		} else if (type == "c") {
			fill = codeViewer.css;
		}
		$("#sg-code-fill").html(fill).text();
		codeViewer.tabActive = type;
		Prism.highlightElement(document.getElementById("sg-code-fill"));
	},
	
	activateDefaultTab: function(type,code) {
		var typeName  = "";
		var className = (type == "c") ? "css" : "markup";
		if (type == "m") {
			typeName = "mustache";
		} else if (type == "e") {
			typeName = "html";
		} else if (type == "c") {
			typeName = "css";
		}
		$('.sg-code-title-active').removeClass('sg-code-title-active');
		$('#sg-code-title-'+typeName).addClass('sg-code-title-active');
		$("#sg-code-fill").removeClass().addClass("language-"+className);
		$("#sg-code-fill").html(code).text();
		Prism.highlightElement(document.getElementById("sg-code-fill"));
	},
	
	updateCode: function(lineage,lineageR,patternPartial,cssEnabled) {
		
		// draw lineage
		var lineageList = "";
		$("#sg-code-lineage").css("display","none");
		
		if (lineage.length !== 0) {
			$("#sg-code-lineage").css("display","block");
			
			for (var i = 0; i < lineage.length; i++) {
				lineageList += (i === 0) ? "" : ", ";
				lineageList += "<a href='"+lineage[i].lineagePath+"' data-patternPartial='"+lineage[i].lineagePattern+"'>"+lineage[i].lineagePattern+"</a>";
				i++;
			}
			
		}
		
		$("#sg-code-lineage-fill").html(lineageList);
		
		$('#sg-code-lineage-fill a').on("click", function(e){
			e.preventDefault();
			document.getElementById("sg-viewport").contentWindow.postMessage( { "path": urlHandler.getFileName($(this).attr("data-patternpartial")) }, urlHandler.targetOrigin);
		});
		
		// draw reverse lineage
		var lineageRList = "";
		$("#sg-code-lineager").css("display","none");
		
		if (lineageR.length !== 0) {
			$("#sg-code-lineager").css("display","block");
			
			for (var i = 0; i < lineageR.length; i++) {
				lineageRList += (i === 0) ? "" : ", ";
				lineageRList += "<a href='"+lineageR[i].lineagePath+"' data-patternPartial='"+lineageR[i].lineagePattern+"'>"+lineageR[i].lineagePattern+"</a>";
				i++;
			}
			
		}
		
		$("#sg-code-lineager-fill").html(lineageRList);
		
		$('#sg-code-lineager-fill a').on("click", function(e){
			e.preventDefault();
			document.getElementById("sg-viewport").contentWindow.postMessage( { "path": urlHandler.getFileName($(this).attr("data-patternpartial")) }, urlHandler.targetOrigin);
		});
		
		var fileName = urlHandler.getFileName(patternPartial);
		
		var e = new XMLHttpRequest();
		e.onload = this.saveEncoded;
		e.open("GET", fileName.replace(/\.html/,".escaped.html") + "?" + (new Date()).getTime(), true);
		e.send();
		
		var m = new XMLHttpRequest();
		m.onload = this.saveMustache;
		m.open("GET", fileName.replace(/\.html/,".mustache") + "?" + (new Date()).getTime(), true);
		m.send();
		
		if (cssEnabled) {
			var c = new XMLHttpRequest();
			c.onload = this.saveCSS;
			c.open("GET", fileName.replace(/\.html/,".css") + "?" + (new Date()).getTime(), true);
			c.send();
		}
		
		codeViewer.slideCode(0);
			
	},
	
	/**
	* toggle the comment pop-up based on a user clicking on the pattern
	* based on the great MDN docs at https://developer.mozilla.org/en-US/docs/Web/API/window.postMessage
	* @param  {Object}      event info
	*/
	receiveIframeMessage: function(event) {
		
		// does the origin sending the message match the current host? if not dev/null the request
		if ((window.location.protocol !== "file:") && (event.origin !== window.location.protocol+"//"+window.location.host)) {
			return;
		}
		
		if (event.data.codeOverlay !== undefined) {
			if (event.data.codeOverlay === "on") {
				codeViewer.updateCode(event.data.lineage,event.data.lineageR,event.data.codePatternPartial,event.data.cssEnabled);
			} else {
				codeViewer.slideCode($('#sg-code-container').outerHeight());
			}
		}
		
	}
	
};

$(document).ready(function() { codeViewer.onReady(); });
window.addEventListener("message", codeViewer.receiveIframeMessage, false);

// make sure if a new pattern or view-all is loaded that comments are turned on as appropriate
$('#sg-viewport').load(function() {
	if (codeViewer.codeActive) {
		var targetOrigin = (window.location.protocol == "file:") ? "*" : window.location.protocol+"//"+window.location.host;
		document.getElementById('sg-viewport').contentWindow.postMessage({ "codeToggle": "on" },targetOrigin);
	}
});
