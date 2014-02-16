/*!
 * Pattern Finder
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

var patternFinder = {
	
	data: [],
	
	init: function() {
		
		for (var patternType in patternPaths) {
			if (patternPaths.hasOwnProperty(patternType)) {
				for (var pattern in patternPaths[patternType]) {
					var obj = {};
					obj.patternPartial = patternType+"-"+pattern;
					obj.patternPath    = patternPaths[patternType][pattern];
					this.data.push(obj);
				}
			}
		}
		
		// instantiate the bloodhound suggestion engine
		var patterns = new Bloodhound({
			datumTokenizer: function(d) { return Bloodhound.tokenizers.whitespace(d.patternPartial); },
			queryTokenizer: Bloodhound.tokenizers.whitespace,
			limit: 10,
			local: this.data
		});

		// initialize the bloodhound suggestion engine
		patterns.initialize();

		$('#sg-find .typeahead').typeahead({ highlight: true }, {
			displayKey: 'patternPartial',
			source: patterns.ttAdapter()
		}).on('typeahead:selected', patternFinder.onAutocompleted).on('typeahead:autocompleted', patternFinder.onSelected);
		
	},
	
	passPath: function(item) {
		// update the iframe via the history api handler
		$('#sg-find').removeClass('show-overflow');
		$('#sg-find .typeahead').val("");
		$('.sg-acc-handle, .sg-acc-panel').removeClass('active');
		var obj = JSON.stringify({ "path": urlHandler.getFileName(item.patternPartial) });
		document.getElementById("sg-viewport").contentWindow.postMessage(obj, urlHandler.targetOrigin);
	},
	
	onSelected: function(e,item) {
		patternFinder.passPath(item);
	},
	
	onAutocompleted: function(e,item) {
		patternFinder.passPath(item);
	},
	
	toggleFinder: function() {},
	
	openFinder: function() {},
	
	closeFinder: function() {}

}

patternFinder.init();

$('#sg-find').click(function(){ $(this).toggleClass('show-overflow') });

// jwerty stuff
// toggle the annotations panel
jwerty.key('cmd+shift+f/ctrl+shif+f', function (e) {
	$('.sg-find .sg-acc-handle, .sg-find .sg-acc-panel').addClass('active');
	$('#sg-find .typeahead').focus();
	return false;
});