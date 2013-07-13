/*!
 * jQuery Cookie Plugin v1.3
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2011, Klaus Hartl
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.opensource.org/licenses/GPL-2.0
 */
(function ($, document, undefined) {

	var pluses = /\+/g;

	function raw(s) {
		return s;
	}

	function decoded(s) {
		return decodeURIComponent(s.replace(pluses, ' '));
	}

	var config = $.cookie = function (key, value, options) {

		// write
		if (value !== undefined) {
			options = $.extend({}, config.defaults, options);

			if (value === null) {
				options.expires = -1;
			}

			if (typeof options.expires === 'number') {
				var days = options.expires, t = options.expires = new Date();
				t.setDate(t.getDate() + days);
			}

			value = config.json ? JSON.stringify(value) : String(value);

			return (document.cookie = [
				encodeURIComponent(key), '=', config.raw ? value : encodeURIComponent(value),
				options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
				options.path    ? '; path=' + options.path : '',
				options.domain  ? '; domain=' + options.domain : '',
				options.secure  ? '; secure' : ''
			].join(''));
		}

		// read
		var decode = config.raw ? raw : decoded;
		var cookies = document.cookie.split('; ');
		for (var i = 0, l = cookies.length; i < l; i++) {
			var parts = cookies[i].split('=');
			if (decode(parts.shift()) === key) {
				var cookie = decode(parts.join('='));
				return config.json ? JSON.parse(cookie) : cookie;
			}
		}

		return null;
	};

	config.defaults = {};

	$.removeCookie = function (key, options) {
		if ($.cookie(key) !== null) {
			$.cookie(key, null, options);
			return true;
		}
		return false;
	};

})(jQuery, document);

/*!
 * Data Saver - v0.1
 *
 * Copyright (c) 2013 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 */
var plCookieName = "patternlab";

function addValue(name,val) {
	var cookieVal = $.cookie(plCookieName);
	if ((cookieVal == null) || (cookieVal == "")) {
		cookieVal = name+"~"+val;
	} else {
		cookieVal = cookieVal+"|"+name+"~"+val;
	}
	$.cookie(plCookieName,cookieVal);
}

function updateValue(name,val) {
	var updateCookieVals = "";
	var cookieVals = $.cookie(plCookieName).split("|");
	for (var i = 0; i < cookieVals.length; i++) {
    	var fieldVals = cookieVals[i].split("~");
    	if (fieldVals[0] == name) {
			fieldVals[1] = val;
		}
		if (i > 0) {
				updateCookieVals += "|"+fieldVals[0]+"~"+fieldVals[1];
		} else {
				updateCookieVals += fieldVals[0]+"~"+fieldVals[1];
		}	
	}
	$.cookie(plCookieName,updateCookieVals);
}

function removeValue(name) {
	var updateCookieVals = "";
	var cookieVals = $.cookie(plCookieName).split("|");
	var k = 0;
	for (var i = 0; i < cookieVals.length; i++) {
    	var fieldVals = cookieVals[i].split("~");
    	if (fieldVals[0] != name) {
			if (k == 0) {
				updateCookieVals += fieldVals[0]+"~"+fieldVals[1];
			} else {
				updateCookieVals += "|"+fieldVals[0]+"~"+fieldVals[1];
			}
			k++;
		}
	}
	$.cookie(plCookieName,updateCookieVals);
}

function findValue(name) {
	if ($.cookie(plCookieName)) {
		var cookieVals = $.cookie(plCookieName).split("|");
		var k = 0;
		for (var i = 0; i < cookieVals.length; i++) {
	    	var fieldVals = cookieVals[i].split("~");
	    	if (fieldVals[0] == name) {
				return fieldVals[1];
			}
		}
	} 
	return false;
}
