/*!
 * QR Code Generator - v0.1
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Adds a QR code to the 
 *
 */

var qrCodeGenerator = {
	
	lastGenerated: "",
	liAdded: false,
	
	/**
	* get the qr code json object and add the qr code to the dom
	*/
	getQRCode: function () {
		
		var url = this.createURL();
		
		$.ajax({
			type: 'GET',
			url: 'http://miniqr.com/api/create.php?api=http&content='+url+'&size=150&rtype=json',
			async: false,
			jsonpCallback: 'plCallback',
			contentType: "application/json",
			dataType: 'jsonp',
			success: function(json) {
				
				var img               = document.createElement("img");
				img.src               = json.imageurl;
				img.alt               = "QR code for Pattern Lab";
				img.width             = "150";
				img.height            = "150";
				img.style.paddingTop  = "10px";
				
				var br                = document.createElement("br");
				var a                 = document.createElement("a");
				a.href                = qrCodeGenerator.createURL();
				a.innerHTML           = "[link]"
				a.style.textTransform = "lowercase";
				
				var li                = document.createElement("li");
				li.style.textAlign    = "center";
				li.appendChild(img);
				li.appendChild(br);
				li.appendChild(a);
				
				var ul = document.querySelector(".sg-tools ul");
				if (qrCodeGenerator.liAdded) {
					ul.removeChild(ul.lastChild);
				}
				ul.appendChild(li);
				qrCodeGenerator.liAdded = true;
				
			},
			error: function(e) {
				
				var a                 = document.createElement("a");
				a.href                = "#";
				a.innerHTML           = "the mini qr service is unavailable"
				a.style.textTransform = "lowercase";
				
				var li                = document.createElement("li");
				li.style.textAlign    = "center";
				li.appendChild(a);
				
				var ul = document.querySelector(".sg-tools ul");
				if (qrCodeGenerator.liAdded) {
					ul.removeChild(ul.lastChild);
				}
				ul.appendChild(li);
				qrCodeGenerator.liAdded = true;
				
			}
		});
		
		
	},
	
	/**
	* create the url that will be linked to from the qr code
	*/
	createURL: function() {
		var path = window.location.pathname;
		var search = window.location.search;
		var url = (xipHostname != "") ? xipHostname.replace("*", ipAddress)+path+search : window.location.toString();
		return url;
	}
	
};
