/*!
 * Basic postMessage Support - v0.1
 *
 * Copyright (c) 2013 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Handles the postMessage stuff in the pattern, view-all, and style guide templates.
 *
 */// alert the iframe parent that the pattern has loaded assuming this view was loaded in an iframe
function receiveIframeMessage(e){var t;if(window.location.protocol!="file:"&&e.origin!==window.location.protocol+"//"+window.location.host)return;if(e.data.path!==undefined)if(patternPartial!==""){var n=/patterns\/(.*)$/;t=window.location.protocol+"//"+window.location.host+window.location.pathname.replace(n,"")+e.data.path+"?"+Date.now();window.location.replace(t)}else{t=window.location.protocol+"//"+window.location.host+window.location.pathname.replace("styleguide/html/styleguide.html","")+e.data.path+"?"+Date.now();window.location.replace(t)}else e.data.reload!==undefined&&window.location.reload()}if(self!=top){var path=window.location.toString(),parts=path.split("?"),options={path:parts[0]};options.patternpartial=patternPartial!==""?patternPartial:"all";lineage!==""&&(options.lineage=lineage);var targetOrigin=window.location.protocol=="file:"?"*":window.location.protocol+"//"+window.location.host;parent.postMessage(options,targetOrigin);var aTags=document.getElementsByTagName("a");for(var i=0;i<aTags.length;i++)aTags[i].onclick=function(e){e.preventDefault();window.location.replace(this.getAttribute("href")+"?"+Date.now())}}var body=document.getElementsByTagName("body");body[0].onclick=function(){var e=window.location.protocol=="file:"?"*":window.location.protocol+"//"+window.location.host;parent.postMessage({bodyclick:"bodyclick"},e)};window.addEventListener("message",receiveIframeMessage,!1);