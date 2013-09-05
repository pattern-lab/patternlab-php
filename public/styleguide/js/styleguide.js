(function(w){
	var sw = document.body.clientWidth, //Viewport Width
		sh = document.body.clientHeight, //Viewport Height
		minViewportWidth = 240, //Minimum Size for Viewport
		maxViewportWidth = 2600, //Maxiumum Size for Viewport
		viewportResizeHandleWidth = 14, //Width of the viewport drag-to-resize handle
		$sgViewport = $('#sg-viewport'), //Viewport element
		$viewToggle = $('#sg-t-toggle'), //Toggle 
		$sizePx = $('.sg-size-px'), //Px size input element in toolbar
		$sizeEms = $('.sg-size-em'), //Em size input element in toolbar
		$bodySize = parseInt($('body').css('font-size')), //Body size of the document
		$vp = Object,
		$sgPattern = Object,
		discoID = false,
		discoMode = false,
		hayMode = false;
	
	
	$(w).resize(function(){ //Update dimensions on resize
		sw = document.body.clientWidth;
		sh = document.body.clientHeight;
	});

	/* Pattern Lab accordion dropdown */
	$('.sg-acc-handle').on("click", function(e){
		var $this = $(this),
			$panel = $this.next('.sg-acc-panel');
		e.preventDefault();
		$this.toggleClass('active');
		$panel.toggleClass('active');
	});

	$('.sg-nav-toggle').on("click", function(e){
		e.preventDefault();
		$('.sg-nav-container').toggleClass('active');
	});
	
	
	
	//View Trigger
	$viewToggle.on("click", function(e){
		e.preventDefault();
		$(this).parents('ul').toggleClass('active');
	});

	//Size Trigger
	$('#sg-size-toggle').on("click", function(e){
		e.preventDefault();
		$(this).parents('ul').toggleClass('active');
	});

	//Add Active States for size controls
	$('#sg-controls a').on("click", function(e){
		var $this = $(this);
		$('#sg-controls a').removeClass('active');
		$this.addClass('active');
	});
	
	//Phase View Events
	$('.sg-size[data-size]').on("click", function(e){
		e.preventDefault();
		killDisco();
		killHay();
		
		var val = $(this).attr('data-size');
		
		if (val.indexOf('px') > -1) {
			$bodySize = 1;
		}
		
		val = val.replace(/[^\d.-]/g,'')		
		sizeiframe(Math.floor(val*$bodySize));
	});
	
	//Size View Events

	//Click Size Small Button
	$('#sg-size-s').on("click", function(e){
		e.preventDefault();
		killDisco();
		killHay();
		sizeiframe(getRandom(minViewportWidth,500));
	});
	
	//Click Size Medium Button
	$('#sg-size-m').on("click", function(e){
		e.preventDefault();
		killDisco();
		killHay();
		sizeiframe(getRandom(500,800));
	});
	
	//Click Size Large Button
	$('#sg-size-l').on("click", function(e){
		e.preventDefault();
		killDisco();
		killHay();
		sizeiframe(getRandom(800,1200));
	});

	//Click Full Width Button
	$('#sg-size-full').on("click", function(e){ //Resets 
		e.preventDefault();
		killDisco();
		killHay();
		sizeiframe(sw);
	});
	
	//Click Random Size Button
	$('#sg-size-random').on("click", function(e){
		e.preventDefault();
		killDisco();
		killHay();
		sizeiframe(getRandom(minViewportWidth,sw));
	});
	
	//Click for Disco Mode, which resizes the viewport randomly
	$('#sg-size-disco').on("click", function(e){
		e.preventDefault();
		killHay();

		if (discoMode) {
			killDisco();

		} else {
			startDisco();
		}
	});

	/* Disco Mode */
	function disco() {
		sizeiframe(getRandom(minViewportWidth,sw));
	}
	
	function killDisco() {
		discoMode = false;
		clearInterval(discoID);
		discoID = false;
	}
	
	function startDisco() {
		discoMode = true;
		discoID = setInterval(disco, 800);
	}

	//Stephen Hay Mode - "Start with the small screen first, then expand until it looks like shit. Time for a breakpoint!"
	$('#sg-size-hay').on("click", function(e){
		e.preventDefault();
		killDisco();

		if (hayMode) {
			killHay();
		} else {
			startHay();	
		}
	});

	//Stop Hay! Mode
	function killHay() {
		var currentWidth = $sgViewport.width();
		hayMode = false;
		$sgViewport.removeClass('hay-mode');
		$('#sg-gen-container').removeClass('hay-mode');
		sizeiframe(Math.floor(currentWidth));
	}
	
	// start Hay! mode
	function startHay() {
		hayMode = true;
		$('#sg-gen-container').removeClass("vp-animate").width(minViewportWidth+viewportResizeHandleWidth);
		$sgViewport.removeClass("vp-animate").width(minViewportWidth);		
		var timeoutID = window.setTimeout(function(){
			$('#sg-gen-container').addClass('hay-mode').width(maxViewportWidth+viewportResizeHandleWidth);
			$sgViewport.addClass('hay-mode').width(maxViewportWidth);
		}, 200);
	}

	//Pixel input
	$sizePx.on('keydown', function(e){
		var val = Math.floor($(this).val());

		if(e.keyCode == 38) { //If the up arrow key is hit
			val++;
			sizeiframe(val,false);
		} else if(e.keyCode == 40) { //If the down arrow key is hit
			val--;
			sizeiframe(val,false);
		} else if(e.keyCode == 13) { //If the Enter key is hit
	    	e.preventDefault();
			sizeiframe(val); //Size Iframe to value of text box
			$(this).blur();
	    }
	    
	});

	$sizePx.on('keyup', function(e){
		var val = Math.floor($(this).val());
		updateSizeReading(val,'px','updateEmInput');
	});

	//Em input
	$sizeEms.on('keydown', function(e){
		var val = parseFloat($(this).val());

	    if(e.keyCode == 38) { //If the up arrow key is hit
			val++;
			sizeiframe(Math.floor(val*$bodySize),false);
		} else if(e.keyCode == 40) { //If the down arrow key is hit
			val--;
			sizeiframe(Math.floor(val*$bodySize),false);
		} else if(e.keyCode == 13) { //If the Enter key is hit
	    	e.preventDefault();
			sizeiframe(Math.floor(val*$bodySize)); //Size Iframe to value of text box
	    } 
	});

	$sizeEms.on('keyup', function(e){
		var val = parseFloat($(this).val());
		updateSizeReading(val,'em','updatePxInput');
	});
	
	//Resize the viewport
	//'size' is the target size of the viewport
	//'animate' is a boolean for switching the CSS animation on or off. 'animate' is true by default, but can be set to false for things like nudging and dragging
	function sizeiframe(size,animate) {
		var theSize;

		if(size>maxViewportWidth) { //If the entered size is larger than the max allowed viewport size, cap value at max vp size
			theSize = maxViewportWidth;
		} else if(size<minViewportWidth) { //If the entered size is less than the minimum allowed viewport size, cap value at min vp size
			theSize = minViewportWidth;
		} else {
			theSize = size;
		}

		//Conditionally remove CSS animation class from viewport
		if(animate==false) { 
			$('#sg-gen-container,#sg-viewport').removeClass("vp-animate"); //If aninate is set to false, remove animate class from viewport
		} else {
			$('#sg-gen-container,#sg-viewport').addClass("vp-animate");
		}

		$('#sg-gen-container').width(theSize+viewportResizeHandleWidth); //Resize viewport wrapper to desired size + size of drag resize handler
		$sgViewport.width(theSize); //Resize viewport to desired size

		updateSizeReading(theSize); //Update values in toolbar
		saveSize(theSize); //Save current viewport to cookie
	}
	
	function saveSize(size) {
		if (!DataSaver.findValue('vpWidth')) {
			DataSaver.addValue("vpWidth",size);
		} else {
			DataSaver.updateValue("vpWidth",size);
		}
	}
	
	
	//Update Pixel and Em inputs
	//'size' is the input number
	//'unit' is the type of unit: either px or em. Default is px. Accepted values are 'px' and 'em'
	//'target' is what inputs to update. Defaults to both
	function updateSizeReading(size,unit,target) {
		if(unit=='em') { //If size value is in em units
			emSize = size;
			pxSize = Math.floor(size*$bodySize);
		} else { //If value is px or absent
			pxSize = size;
			emSize = size/$bodySize;
		}
		
		if (target == 'updatePxInput') {
			$sizePx.val(pxSize);
		} else if (target == 'updateEmInput') {
			$sizeEms.val(emSize.toFixed(2));
		} else {
			$sizeEms.val(emSize.toFixed(2));
			$sizePx.val(pxSize);
		}	
	}
	
	/* Returns a random number between min and max */
	function getRandom (min, max) {
	    return Math.random() * (max - min) + min;
	}
	
	function updateViewportWidth(size) {
	
		$("#sg-viewport").width(size);
		$("#sg-gen-container").width(Math.floor(size) + 14);
		
		updateSizeReading(size);
	}

	// handles widening the "viewport"
	//   1. on "mousedown" store the click location
	//   2. make a hidden div visible so that it can track mouse movements and make sure the pointer doesn't get lost in the iframe
	//   3. on "mousemove" calculate the math, save the results to a cookie, and update the viewport
	$('#sg-rightpull').mousedown(function(event) {
		
		// capture default data
		var origClientX = event.clientX;
		var origViewportWidth = $sgViewport.width();
		
		// show the cover
		$("#sg-cover").css("display","block");
		
		// add the mouse move event and capture data. also update the viewport width
		$('#sg-cover').mousemove(function(event) {
			
			viewportWidth = (origClientX > event.clientX) ? origViewportWidth - ((origClientX - event.clientX)*2) : origViewportWidth + ((event.clientX - origClientX)*2);
			
			if (viewportWidth > minViewportWidth) {
				
				if (!DataSaver.findValue('vpWidth')) {
					DataSaver.addValue("vpWidth",viewportWidth);
				} else {
					DataSaver.updateValue("vpWidth",viewportWidth);
				}
				
				sizeiframe(viewportWidth,false);
			}
		});
	});

	// on "mouseup" we unbind the "mousemove" event and hide the cover again
	$('body').mouseup(function(event) {
		$('#sg-cover').unbind('mousemove');
		$('#sg-cover').css("display","none");
	});

	// capture the viewport width that was loaded and modify it so it fits with the pull bar
	var origViewportWidth = $("#sg-viewport").width();
	$("#sg-gen-container").width(origViewportWidth);
	$("#sg-viewport").width(origViewportWidth - 14);
	updateSizeReading($("#sg-viewport").width());

	// get the request vars
	var oGetVars = urlHandler.getRequestVars();
	
	// pre-load the viewport width
	var vpWidth = 0;
	var trackViewportWidth = true; // can toggle this feature on & off
	if ((oGetVars.h != undefined) || (oGetVars.hay != undefined)) {
		startHay();
	} else if ((oGetVars.d != undefined) || (oGetVars.disco != undefined)) {
		startDisco();
	} else if ((oGetVars.w != undefined) || (oGetVars.width != undefined)) {
		vpWidth = (oGetVars.w != undefined) ? oGetVars.w : oGetVars.width;
		vpWidth = (vpWidth.indexOf("em") != -1) ? Math.floor(Math.floor(vpWidth.replace("em",""))*$bodySize) : Math.floor(vpWidth.replace("px",""));
		DataSaver.updateValue("vpWidth",vpWidth);
		updateViewportWidth(vpWidth);
	} else if (trackViewportWidth && (vpWidth = DataSaver.findValue("vpWidth"))) {
		updateViewportWidth(vpWidth);
	}
	
	// load the iframe source
	var patternName = "";
	var patternPath = "";
	var iFramePath  = window.location.protocol+"//"+window.location.host+window.location.pathname.replace("index.html","")+"styleguide/html/styleguide.html";
	if ((oGetVars.p != undefined) || (oGetVars.pattern != undefined)) {
		patternName = (oGetVars.p != undefined) ? oGetVars.p : oGetVars.pattern;
		patternPath = urlHandler.getFileName(patternName);
		iFramePath  = (patternPath != "") ? window.location.protocol+"//"+window.location.host+window.location.pathname.replace("index.html","")+patternPath : iFramePath;
	}
	
	document.getElementById("sg-viewport").contentWindow.location.assign(iFramePath);
	
	history.replaceState({ "pattern": patternName }, null, null);
	
	//IFrame functionality

	//Scripts to run after the page has loaded into the iframe
	$sgViewport.load(function (){
		var $sgSrc = $sgViewport.attr('src'),
			$vp = $sgViewport.contents(),
			$sgPattern = $vp.find('.sg-pattern');

		//Clean View Trigger
		$('#sg-t-clean').on("click", function(e){
			e.preventDefault();
			$sgViewport.contents().hide();
			$vp.find('body').toggleClass('sg-clean');
			$vp.find('#intro, .sg-head, #about-sg').toggle();
			$vp.find('[role=main]').toggleClass('clean');
		});
		
		//Code View Trigger
		$('#sg-t-code').click(function(e){
			var $code = $vp.find('.sg-code');
			e.preventDefault();
			
			if($vp.find('.sg-code').length==0) {
				buildCodeView();
			} else {
				$code.toggle();
			}
		});

		//Annotation View Trigger
		$('#sg-t-annotations').click(function(e){
			var $annotations = $vp.find('.sg-annotations');
			e.preventDefault();
			
			if($vp.find('.sg-annotations').length==0) {
				buildAnnotationView();
			} else {
				$annotations.toggle();
			}
		});
		
		//Add code blocks after each pattern
		function buildCodeView() {
			$sgPattern.each(function(index) {
				$this = $(this),
				$thisHTML = $this.html().replace(/[<>]/g, function(m) { return {'<':'&lt;','>':'&gt;'}[m]}), 
				$thisCode = $( '<code></code>' ).html($thisHTML);
				
				$('<pre class="sg-code" />').html($thisCode).appendTo($this); //Create new node, fill it with the code text, then append it to the pattern
			});
			$vp.find('.sg-code').show();
		}

		//Add annotation blocks after each pattern
		function buildAnnotationView() {
			$sgPattern.each(function(index) { //Loop through each pattern
				$this = $(this),
				$thisAnnotation = "This is an example of an annotation. Eventually this annotation will be replaced by a real annotation defined in an external JSON file."; //Example Annotation
				
				$('<div class="sg-annotations" />').html($thisAnnotation).appendTo($this); //Create new node, fill it with the annotation text, then append it to the pattern
			});
			$vp.find('.sg-annotations').show();
		}
		
		// Pattern Click
		// this doesn't work because patternlab-php assumes the iframe is being refreshed. not the overall app
		/*
		$vp.find('.sg-head a').on("click", function(e){
			e.preventDefault();
			var thisHref = $(this).attr('href');
			window.location = thisHref;
		});
		*/
	});
	
})(this);

// reload the iframes initial state when clicking on home
$('.sg-nav-home').on('click', function(e){
	
	document.getElementById("sg-viewport").contentWindow.location.assign(iFramePath);

	//todo push state to maintain history.  not familiar with this yet.
	
	e.stopPropagation();

	return false;

});

// update the iframe with the source from clicked element in pull down menu. also close the menu
// having it outside fixes an auto-close bug i ran into
$('.sg-nav a').not('.sg-acc-handle').not('.sg-nav-home').on("click", function(e){
	
	// update the iframe via the history api handler
	urlHandler.pushPattern($(this).attr("data-patternpartial"));
	
	// close up the menu
	$(this).parents('.sg-acc-panel').toggleClass('active');
	$(this).parents('.sg-acc-panel').siblings('.sg-acc-handle').toggleClass('active');
	
	e.stopPropagation();
	
	return false;
	
});

// handle when someone clicks on the grey area of the viewport so it auto-closes the nav
function closePanels() {
	// close up the menu
	$('.sg-acc-panel').each(function() {
		if ($(this).hasClass('active')) {
			$(this).toggleClass('active');
		}
	});
	
	$('.sg-acc-handle').each(function() {
		if ($(this).hasClass('active')) {
			$(this).toggleClass('active');
		}
	});
}

$('#sg-vp-wrap').click(function(e) {
	
	closePanels();
	
});

// watch the iframe source so that it can be sent back to everyone else.
// based on the great MDN docs at https://developer.mozilla.org/en-US/docs/Web/API/window.postMessage
function receiveIframeMessage(event) {
	
	// does the origin sending the message match the current host? if not dev/null the request
	if ((window.location.protocol != "file:") && (event.origin !== window.location.protocol+"//"+window.location.host)) {
		return;
	}
	
	if (event.data.bodyclick != undefined) {
		
		closePanels();
		
	} else if (event.data.patternpartial != undefined) {
		
		if (!urlHandler.skipBack) {
			
			var iFramePath = urlHandler.getFileName(event.data.patternpartial);
			urlHandler.pushPattern(event.data.patternpartial, event.data.path);
			if (wsnConnected) {
				wsn.send( '{"url": "'+iFramePath+'", "patternpartial": "'+event.data.patternpartial+'" }' );
			}
			
		}
		
		// reset the defaults
		urlHandler.skipBack = false;
		
	}
	
}
window.addEventListener("message", receiveIframeMessage, false);