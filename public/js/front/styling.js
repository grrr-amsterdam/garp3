/**
 * Garp styling helpers
 */
if (typeof Garp === 'undefined') {
	var Garp = {};
}

Garp.getStyle = function(elm, rule) {
	if (document.defaultView && document.defaultView.getComputedStyle) {
		return document.defaultView.getComputedStyle(elm, '').getPropertyValue(rule);
	}
	if (elm.currentStyle) {
		rule = rule.replace(/\-(\w)/g, function(strMatch, p1) {
			return p1.toUpperCase();
		});
		return elm.currentStyle[rule];
	}
	return '';
};

Garp.getTransitionProperty = function() {
	var el = document.createElement('fakeelement');
	var transitions = [
		'transition',
		'OTransition',
		'MSTransition',
		'MozTransition',
		'WebkitTransition'
	];
	var getCurriedFunction = function(t) {
		return function() {
			return t;
		};
	};
	for (var i = 0, lt = transitions.length; i < lt; ++i) {
		if (el.style[transitions[i]] !== undefined) {
			// Speed up subsequent calls
			Garp.getTransitionProperty = getCurriedFunction(transitions[i]);
			return transitions[i];
		}
	}
	return null;
};

/**
 * Get cross-browser transitionEnd event
 * Inspiration: @see http://stackoverflow.com/questions/5023514/how-do-i-normalize-css3-transition-functions-across-browsers
 */
Garp.getTransitionEndEvent = function() {
	var transitions = {
		'transition':'transitionEnd',
		'OTransition':'oTransitionEnd',
		'MSTransition':'msTransitionEnd',
		'MozTransition':'transitionend',
		'WebkitTransition':'webkitTransitionEnd'
	};
	var t = Garp.getTransitionProperty();
	var getCurriedFunction = function(t) {
		return function() {
			return t;
		};
	};
	if (t && t in transitions) {
		Garp.getTransitionEndEvent = getCurriedFunction(transitions[t]);
		return transitions[t];
	}
	return null;
};
