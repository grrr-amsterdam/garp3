/**
 * @class inlineLabels
 * @package Garp
 */

 /**
 * Inline label module. For labels that look as if they are the value of an input field
 */
Garp.inlineLabels = {
	/**
	 * Find correct labels on the page and display 'em 'inline'
	 * @param Mixed $elements Optional elements, if none, "label.inline" will be used.
	 */
	init: function(elements) {
		var self = this;
		elements = elements || 'label.inline';
		$(elements).each(function() {
			var thisLabel = $(this);
			var input = $('#'+thisLabel.attr('for'));
			input.focus(function() {
				self.focus.call(input, thisLabel);
			}).blur(function() {
				self.blur.call(input, thisLabel);
			});
			
			// 'cause browsers remember certain form values, there needs to be a manual check.
			function check(){
				if ($(input).val()) {
					self.focus.call(input, thisLabel);
				}
			}
			setTimeout(check, 1000);
			setTimeout(check, 3000); // slow pages actually do benefit from this line
		});
	},
	/**
	 * Focus event handler on inputs
	 */
	focus: function(theLabel) {
		theLabel.addClass('hidden');
	},
	/**
	 * Blur event handler on inputs
	 */
	blur: function(theLabel) {
		if (!$(this).val()) {
			theLabel.removeClass('hidden');
		}
	}
};
Garp.inlineLabels.init();
