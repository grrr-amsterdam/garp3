/**
 * Garp FlashMessage
 * API:
 * // Show message for 2 seconds
 * var fm = new Garp.FlashMessage('you have been logged out', 2);
 * fm.show();
 *
 * // Show message forever and ever:
 * var fm = new Garp.FlashMessage('you have been logged out', -1);
 * fm.show();
 *
 * // Hide manually
 * fm.hide();
 */
if (typeof Garp === 'undefined') {
	var Garp = {};
}

/**
 * FlashMessage
 * Shows a quick system message in an overlay or dialog box.
 * @param String|Array msg The message, or collection of messages
 * @param Int timeout How long the message will be displayed. Defaults to 5. Use -1 to never hide. (in seconds)
 */
Garp.FlashMessage = function(msg, timeout) {
	var shouldTimeout = -1 !== timeout,
		fm,
		timer,
		doc = document.documentElement,
		body = document.getElementsByTagName('body')[0],
		FM_ACTIVE_CLASS = 'flash-message-active',
		FM_INACTIVE_CLASS = 'flash-message-inactive'
	;

	// assume seconds
	timeout = timeout || 5;
	if (shouldTimeout) {
		timeout *= 1000;
	}

	// normalize msg to array
	if (typeof msg.push !== 'function') {
		msg = [msg];
	}
	
	var removeNode = function() {
		if (!fm) {
			return;
		}
		fm.parentNode.removeChild(fm);
		fm = null;
		doc.className = doc.className.replace(FM_INACTIVE_CLASS, '');
	};

	// Add event listeners that remove the node from the DOM 
	// after a transition or animation ends.
	var setRemoveHandler = function(transition) {
		var events = transition ? Garp.transitionEndEvents : Garp.animationEndEvents;
		for (var i = 0, el = events.length; i < el; ++i) {
			fm.addEventListener(events[i], removeNode, false);
		}
	};
	
	var hide = function() {
		clearInterval(timer);
		if (!fm) {
			return;
		}

		var t = Garp.getStyle(fm, Garp.getTransitionProperty()),
			a = Garp.getStyle(fm, Garp.getAnimationProperty());

		if (t || a) {
			setRemoveHandler(t);
		}
		doc.className = doc.className.replace(FM_ACTIVE_CLASS, FM_INACTIVE_CLASS);

		if (!t && !a) {
			removeNode();
		}
	};

	/**
	 * Show the message.
	 * A timer will be set that hides the it.
	 */
	var show = function() {
		fm = document.createElement('div');
		fm.setAttribute('id', 'flash-message');
		fm.className = 'flash-message';
		var html = '';
		for (var i = 0, ml = msg.length; i < ml; ++i) {
			html += '<p>' + msg[i] + '</p>';
		}
		fm.innerHTML = html;
		body.appendChild(fm);
		setTimeout(function() {
			doc.className += ' ' + FM_ACTIVE_CLASS;
		}, 0);

		// clicking on flash message hides it
		fm.onclick = hide;
		if (shouldTimeout) {
			timer = setTimeout(hide, timeout);
		}
	};

	// public api
	this.show = show;
	this.hide = hide;

	return this;
};

/**
 * Read the designated flashMessage cookie
 */
Garp.FlashMessage.parseCookie = function() {
	if (typeof JSON == 'undefined' || typeof JSON.parse !== 'function') {
		return '';
	}
	var FM_COOKIE = 'FlashMessenger',
		m = JSON.parse(unescape(Garp.Cookie.get(FM_COOKIE))),
		out = [];
	if (!m || !m.messages) {
		return '';
	}
	for (var i in m.messages) {
		var msg = m.messages[i];
		if (msg) {
			out.push(msg.replace(/\+/g, ' '));
		}
	}

	// Remove the cookie after parsing the flash message
	var exp = new Date();
	exp.setHours(exp.getHours() - 1);
	Garp.Cookie.set(FM_COOKIE, '', exp, (typeof COOKIEDOMAIN !== 'undefined') ? COOKIEDOMAIN : document.location.host);

	return out;
};
