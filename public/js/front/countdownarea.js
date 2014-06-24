/**
 * @class CountDownArea
 * @package Garp
 */

/** Garp.CountDownArea
 * twitter-style countdown for textareas
 * @param {string} formSelector
 * @param {number} maximumNumber of characters
 * @param {bool} allowBlank allow no comment (do not disable form submit)
 * @param {function} callback on every counter update
 */
Garp.CountDownArea = function(fieldSelector, counterSelector, maxCharacters, allowBlank, callback){
	var textarea = $(fieldSelector), submit = $('input[type="submit"], button', $(fieldSelector).parent('form'));
	if (!maxCharacters) {
		maxCharacters = 140;
	}
	
	allowBlank = allowBlank || false;
	
	function updateCounter(){
		var val = maxCharacters - textarea.val().length;
		if(textarea.hasClass('placeholder')){
			val = maxCharacters;
		}
		$(counterSelector).html(val + '');
		if (typeof callback === 'function') {
			callback(val);
		}
	}
	
	function checkLength(){
		if (typeof textarea.val() === 'undefined') {
			return;
		}
		// timeout construct: buffer this check. It might get called very often; that might cause slugish behavior:
		
		if (this.buffer) {
			clearTimeout(this.buffer);
		}
		this.buffer = setTimeout(function(){
			var len = textarea.val().length;
			if (len >= maxCharacters) {
				submit.attr({
					'disabled': 'disabled'
				});
				if (len > 0) {
					$(counterSelector).addClass('surplus');
				}
			} else {
				submit.removeAttr('disabled');
				$(counterSelector).removeClass('surplus');
			}
			if (!allowBlank && len === 0) {
				submit.attr({
					'disabled': 'disabled'
				});
			}
			updateCounter();
		}, 50);
	}
	
	textarea.keyup(checkLength).keypress(checkLength).blur(checkLength).click(checkLength);
	
	checkLength();
};