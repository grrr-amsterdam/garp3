if (typeof Garp === 'undefined') {
	console.error('Garp is undefined. Please include Garp core.');
}

/**
 * FlashMessage
 * Shows a quick system message in an overlay or dialog box.
 * @param String msg The message
 * @param Int timeout How long the message will be displayed. Defaults to 5. (in seconds)
 */
Garp.FlashMessage = function(msg, timeout) {
	var shouldTimeout = -1 !== timeout;

	// assume seconds
	timeout = timeout || 5;
	if (shouldTimeout) {
		timeout *= 1000;
	}

	var timer,
		html = document.documentElement,
		FM_ACTIVE_CLASS = 'flash-message-active',
		FM_INACTIVE_CLASS = 'flash-message-inactive'
	;

	var hide = function() {
		clearInterval(timer);
		var fm = document.getElementById('flash-message');
		if (!fm) {
			return;
		}
		clearInterval(timer);

		var t = Garp.getStyle(fm, Garp.getTransitionProperty());
		var removeFm = function() {
			console.log('remove dat chit');
			fm.parentNode.removeChild(fm);
		};
		if (t) {
			// hmm fuck. can't figure out the right transitionEnd event...
			// maybe just listen for them all!
			// also: don't forget animationend
			fm.addEventListener('transitionEnd', function() {
				console.log('transition end');
				removeFm();
			}, false);
		}
		html.className = html.className.replace(FM_ACTIVE_CLASS, FM_INACTIVE_CLASS);

		if (!t) {
			removeFm();
		}
	};

	var show = function() {
		var fm = document.createElement('div');
		fm.setAttribute('id', 'flash-message');
		fm.className = 'flash-message';
		fm.innerHTML = '<p>' + msg + '</p>';
		html.appendChild(fm);
		html.className += ' ' + FM_ACTIVE_CLASS;

		if (shouldTimeout) {
			timer = setTimeout(hide, timeout);
		}
	};

	// public api
	this.show = show;
	this.hide = hide;

	return this;
};
