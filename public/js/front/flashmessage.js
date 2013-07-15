Garp = Garp || {};

Garp.flashMessage = function(msg) {
	this.show = function() {
		var msg = document.createElement('div');
		msg.setAttribute('id', 'flash-message');
		msg.innerHTML = '<p>' + msg + '</p>';
		document.body.appendChild(msg);
	};
};
