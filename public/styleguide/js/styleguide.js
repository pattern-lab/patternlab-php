(function(w){
	var sw = document.body.clientWidth,
		sh = document.body.clientHeight,
		bp = window.getComputedStyle(document.body,':after').getPropertyValue('content');
		$sgViewport = $('#sg-viewport'),
		$viewToggle = $('#sg-t-toggle'),
		$sizeToggle = $('#sg-size-toggle'),
		$tClean = $('#sg-t-clean'),
		$tAnnotations = $('#sg-t-annotations'),
		$tCode = $('#sg-t-code'),
		$tSidebar = $('#sg-t-sidebar'),
		$tFull = $('#sg-t-full'),
		$tSize = $('#sg-size'),
		$vp = Object,
		$sgPattern = Object,
		discoID = false,
		discoMode = false;
	
	
	$(w).resize(function(){ //Update dimensions on resize
		sw = document.body.clientWidth;
		sh = document.body.clientHeight;
		bp = window.getComputedStyle(document.body,':after').getPropertyValue('content');
		
		displayWidth();
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
	$sizeToggle.on("click", function(e){
		e.preventDefault();
		$(this).parents('ul').toggleClass('active');
	});
	
	//Size View Events
	$('#sg-size-s').on("click", function(e){
		e.preventDefault();
		killDisco();
		sizeiframe(getRandom(320,500));
	});
	
	$('#sg-size-m').on("click", function(e){
		e.preventDefault();
		killDisco();
		sizeiframe(getRandom(500,800));
	});
	
	$('#sg-size-l').on("click", function(e){
		e.preventDefault();
		killDisco();
		sizeiframe(getRandom(800,1200));
	});
	
	$('#sg-size-xl').on("click", function(e){
		e.preventDefault();
		killDisco();
		sizeiframe(getRandom(1200,1920));
	});
	
	$('#sg-size-random').on("click", function(e){
		e.preventDefault();
		killDisco();
		sizeiframe(getRandom(240,sw));
	});
	
	$('#sg-size-disco').on("click", function(e){
		e.preventDefault();
		if (discoMode) {
			killDisco();
		} else {
			discoMode = true;
			discoID = setInterval(disco, 800);
		}
		
	});
	
	$('#sg-size-enter').submit(function(){
		var val = $('#sg-size-num').val();
		sizeiframe(Math.floor(val));
		return false;
	});


	$sgViewport.load(function (){
		var $sgSrc = $sgViewport.attr('src'),
			$vp = $sgViewport.contents(),
			$sgPattern = $vp.find('.sg-pattern');
		
		

		//Clean View Trigger
		$tClean.on("click", function(e){
			e.preventDefault();
			$(this).toggleClass('active');
			$sgViewport.contents().hide();
			$vp.find('body').toggleClass('sg-clean');
			$vp.find('#intro, .sg-head, #about-sg').toggle();
			$vp.find('[role=main]').toggleClass('clean');
		});
		
		//Code View Trigger
		$tCode.on("click", function(e){
			var $code = $vp.find('.sg-code');
			e.preventDefault();
			$(this).toggleClass('active');
			
			if($vp.find('.sg-code').length==0) {
				buildCodeView();
			} else {
				$code.toggle();
			}
		});
		
		
		function buildCodeView() {
			$sgPattern.each(function(index) {
				$this = $(this),
				$thisHTML = $this.html().replace(/[<>]/g, function(m) { return {'<':'&lt;','>':'&gt;'}[m]}),
				$thisCode = $( '<code></code>' ).html($thisHTML);
				
				$('<pre class="sg-code" />').html($thisCode).appendTo($this);
			});
			$vp.find('.sg-code').show();
		}
		
		//Pattern Click
		$vp.find('.sg-head a').on("click", function(e){
			e.preventDefault();
			var thisHref = $(this).attr('href');
			//window.location = thisHref;
		});
	});
	
	//Resize the viewport
	function sizeiframe(size) {
		$('#sg-gen-container').addClass("vp-animate");
		$('#sg-viewport').addClass("vp-animate");
		$('#sg-gen-container').width(size+14);
		$('#sg-viewport').width(size);
		updateSizeReading(size);
		saveSize(size);
	}
	
	function saveSize(size) {
		if (!findValue('vpWidth')) {
			addValue("vpWidth",size);
		} else {
			updateValue("vpWidth",size);
		}
	}
	
	//Update Size Reading 
	var $sizePx = $('.sg-size-px');
	var $sizeEms = $('.sg-size-em');
	var $bodySize = parseInt($('body').css('font-size'));
	
	function displayWidth() {
		var vpWidth = $sgViewport.width() - 14;
		var emSize = vpWidth/$bodySize;
		$sizePx.text(vpWidth);
		$sizeEms.text(emSize.toFixed(2));
	}
	
	displayWidth();
	
	function updateSizeReading(size) {
		size = Math.floor(size);
		var emSize = size/$bodySize;
		$sizePx.text(size);
		$sizeEms.text(emSize.toFixed(2));
	}
	
	/* Disco Mode */
	function disco() {
		sizeiframe(getRandom(240,sw));
	}
	
	function killDisco() {
		discoMode = false;
		clearInterval(discoID);
		discoID = false;
	}
	
	/* Returns a random number between min and max */
	function getRandom (min, max) {
	    return Math.random() * (max - min) + min;
	}
	
	/* Accordion */
	$('.sg-acc-handle').on("click", function(e){
		var $this = $(this),
			$panel = $this.next('.sg-acc-panel');
		e.preventDefault();
		e.stopPropagation();
		$this.toggleClass('active');
		$panel.toggleClass('active');
		
	});

	
	$('.sg-control-trigger').on("click", function(e){
			var $this = $(this),
				$thisParent = $this.parents('.sg-control-container');
			e.preventDefault();
			e.stopPropagation();
			$thisParent.toggleClass('active');
			
		});
	
	/* load iframe */
	function loadIframe(iframeName, url) {
	    var $iframe = $('#' + iframeName);
	    if ( $iframe.length ) {
	        $iframe.attr('src',url);   
	        return false;
	    }
	    return true;
	}
	
})(this);

/************************
 * Dave's stuff... don't want to hack at the original styleguide too much
 ************************/

// update the viewportWidth and note difference in the toolbar
function updateViewportWidth(size) {
	
	var sizePx = $('.sg-size-px');
	var sizeEms = $('.sg-size-em');
	var bodySize = parseInt($('body').css('font-size'));
	
	$("#sg-viewport").width(size);
	$("#sg-gen-container").width(Math.floor(size) + 14);
	
	var emSize = (Math.floor(size))/bodySize;
	sizePx.text(Math.floor(size));
	sizeEms.text(emSize.toFixed(2));
}

// update the iframe with the source from clicked element in pull down menu. also close the menu
// having it outside fixes an auto-close bug i ran into
$('.sg-nav a').not('.sg-acc-handle').on("click", function(e){
	
	// update the iframe
	$("#sg-viewport").attr('src',this.href);
	
	// close up the menu
	$(this).parents('.sg-acc-panel').toggleClass('active');
	$(this).parents('.sg-acc-panel').siblings('.sg-acc-handle').toggleClass('active');
	
	e.stopPropagation();
	
	return false;
	
});

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

$('body').click(function(e) {
	
	closePanels();
	
});

// handles widening the "viewport"
//   1. on "mousedown" store the click location
//   2. make a hidden div visible so that it can track mouse movements and make sure the pointer doesn't get lost in the iframe
//   3. on "mousemove" calculate the math, save the results to a cookie, and update the viewport
$('#sg-rightpull').mousedown(function(event) {
	
	$('#sg-gen-container').removeClass("vp-animate");
	$('#sg-viewport').removeClass("vp-animate");
	
	// capture default data
	var origClientX = event.clientX;
	var origViewportWidth = $("#sg-viewport").width();
	
	// show the cover
	$("#sg-cover").css("display","block");
	
	// add the mouse move event and capture data. also update the viewport width
	$('#sg-cover').mousemove(function(event) {
		
		viewportWidth = (origClientX > event.clientX) ? origViewportWidth - ((origClientX - event.clientX)*2) : origViewportWidth + ((event.clientX - origClientX)*2);
		
		if (viewportWidth > 319) {
			
			if (!findValue('vpWidth')) {
				addValue("vpWidth",viewportWidth);
			} else {
				updateValue("vpWidth",viewportWidth);
			}
			
			updateViewportWidth(viewportWidth);
			
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

// pre-load the viewport width
var vpWidth = 0;
var trackViewportWidth = true; // can toggle this feature on & off
if (trackViewportWidth && (vpWidth = findValue("vpWidth"))) {
	updateViewportWidth(vpWidth);
}

// watch the iframe source so that it can be sent back to everyone else.
// based on the great MDN docs at https://developer.mozilla.org/en-US/docs/Web/API/window.postMessage
function receiveIframeMessage(event) {
		
	// does the origin sending the message match the current host? if not dev/null the request
	if (event.origin !== "http://"+window.location.host) {
		return;
	}
	
	if (event.data == 'body-click') {
		closePanels();
	} else if (wsnConnected) {
		wsn.send(event.data);
	}
	
}
window.addEventListener("message", receiveIframeMessage, false);