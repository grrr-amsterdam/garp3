/**
 * Garp Frontend library
 * @author Peter Schilleman | Grrr
 * @author Harmen Janssen | Grrr
 * @package Garp
 */
Garp = typeof Garp == 'undefined' ? { } : Garp;
BASE = typeof BASE == 'undefined' ? '/' : BASE;

// http://jdbartlett.github.com/innershiv | WTFPL License
Garp.innerShiv = (function() {
	var d, r;
	return function(h, u) {
		if (!d) {
			d = document.createElement('div');
			r = document.createDocumentFragment();
			/*@cc_on d.style.display = 'none';@*/
		}
		
		var e = d.cloneNode(true);
		/*@cc_on document.body.appendChild(e);@*/
		e.innerHTML = h.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
		/*@cc_on document.body.removeChild(e);@*/
		
		if (u === false) {
			return e.childNodes;
		}
		
		var f = r.cloneNode(true), i = e.childNodes.length;
		while (i--) {
			f.appendChild(e.firstChild);
		}
		
		return f;
	};
}());

/**
 * Utility function. Binds receiverObj's properties to senderObj's properties 
 * @param {Object} receiverObj
 * @param {Object} senderObj
 * @return {Object} receiverObj
 */
Garp.apply = function(receiverObj, senderObj){
	for (var i in senderObj) {
		receiverObj[i] = senderObj[i];
	}
	return receiverObj;
};

/**
 * Utility function. Binds receiverObj's properties to senderObj's properties if not already present
 * @param {Object} receiverObj
 * @param {Object} senderObj
 * @return {Object} receiverObj
 */
Garp.applyIf = function(receiverObj, senderObj){
	for (var i in senderObj) {
		if (!receiverObj[i]) {
			receiverObj[i] = senderObj[i];
		}
	}
	return receiverObj;
};

/**
 * Utility string function: use a simple tpl string to format multiple arguments
 * 
 * example:
 * var html = Garp.format('<a href="${1}">${2}</a>"', 'http://www.grrr.nl/', 'Grrr Homepage');
 * 
 * @param {String} tpl  template
 * @param {String} ...n input string(s)
 * @return {String}
 */
Garp.format = function(tpl, o){
	var res = tpl;
	if (typeof o == 'object') {
		Garp.each(o, function(v, k){
			k = k.replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\-]', 'g'), '\\$&');
			res = res.replace(new RegExp('\\${' + k + '\\}', "g"),v);
		});
	} else {
		for (var i = 1, l = arguments.length; i < l; i++) {
			res = res.replace(new RegExp("\\${" + i + "\\}", "g"), arguments[i]);
		}
	}
	return res;
};

/**
 * Utility function each
 * Calls fn for each ownProperty of obj. fn(property, iterator, obj)
 * 
 * @param {Object} obj to iterate
 * @param {Function} fn to call with each property of obj, the property name and the object from within 'scope'
 * @param {Object} [scope] to execute within
 */
Garp.each = function(obj, fn, scope){
	for(var i in obj){
		if (obj.hasOwnProperty(i)) {
			if (scope) {
				fn.call(scope, obj[i], i, obj);
			} else {
				fn(obj[i], i, obj);
			}
		}
	}
};

/**
 * Creates a Delegate function
 * @param {Function} function
 * @param {Object} scope
 */
Garp.createDelegate = function(fn, scope){
	return function(){
		fn.apply(scope, arguments);
	};
};

/**
 * 
 */
Garp.parseQueryString = function(str, decode) {
    str = str || window.location.search;
	if (decode) {
		str = unescape(str);
	}
    var objURL = {};
    str.replace(
        new RegExp( "([^?=&]+)(=([^&]*))?", "g" ),
        function( $0, $1, $2, $3 ){
                objURL[ $1 ] = $3;
        }
    );
    return objURL;
};

/**
 * @class Observable
 */
Garp.Observable = function(cfg){
	
	Garp.apply(this, cfg);
	
	/**
	* Creates a global event handler
	*
	* @example Garp.on('onbeforeload', function(){}, scope);
	*
	* @param {String} event
	* @param {Function} handler
	* @param {Object} scope
	*/
	this.on = function(event, handler, scope){
		scope = typeof scope == 'undefined' ? this : scope;
		
		this.on.events = typeof this.on.events == 'undefined' ? {} : this.on.events;
		this.on.events[event] = typeof this.on.events[event] == 'undefined' ? [] : this.on.events[event];
		
		this.on.events[event].push({
			'eventName': event,
			handler: handler,
			scope: scope
		});
	};
	
	/**
	* Util to add listeners in bulk. Usefull when extending Garp.Observable  
	* @param {Object} eventsConfig
	*/
	this.addListeners = function(eventsConfig){
		Garp.each(eventsConfig, function(event, i){
			var fn, scope;
			if(typeof event == 'function'){
				fn = event;
			} else {
				fn = event.fn;
				scope = event.scope || this;
			}
			this.on(i, fn, scope);
		}, this);
	};
	
	/**
	* Removes an event handler
	* 
	* TODO: check / test this
	* 
	* @param {String} event
	* @param {Function} handler
	* @param {Object} scope
	*/
	this.un = function(event, handler, scope){
		scope = typeof scope == 'undefined' ? this : scope;
		this.on.events[event].pop({
			'eventName': event,
			handler: handler,
			scope: scope
		});
	};
	
	/**
	* Fires a global event
	* @param {String} eventName
	*/
	this.fireEvent = function(eventName, options){
		if (typeof this.on.events == 'undefined') {
		}
		Garp.each(this.on.events[eventName], function(obj){
			if (typeof obj.handler == 'function') {
				obj.handler.call(obj.scope, options || {});
			}
		});
	};
	
	this.init = function(){
		if (this.listeners) {
			this.addListeners(this.listeners);
		}
		return this;
	};
	return this.init();
};

/**
 * @class Transition
 * @param {Object} Browsebox reference
 * @param {String} Transition name (internal function reference)
 */
Garp.Transition = function(bb, transitionName){
	
	this.append = function(){
		bb.on('beforeload', function(){}, this);
		bb.on('afterload', function(){}, this);
	};
	
	this.crossFade = function(){
		bb.on('beforeload', function(){
			
			this.copy = this.elm.clone().addClass('crossfade');
			this.shim = this.elm.clone().addClass('shim');
			
			this.copy.insertAfter(this.elm);
			this.shim.insertAfter(this.elm);
			
			var pos = this.elm.position();
			this.copy.css({
				top: pos.top,
				left: pos.left,
				width: this.elm.width(),
				height: this.elm.height(),
				position: 'absolute'
			});
			this.elm.hide();
		}, this);
		bb.on('afterload', function(){
			this.shim.remove();
			this.elm.fadeIn(this.speed);
			var scope = this;
			this.copy.fadeOut(this.speed, function(){
				$(this).remove();
			});
		}, this);
	};
	
	this.fade = function(){
		bb.on('beforeload', function(){
			this.copy = this.elm.animate({
				opacity: 0
			});
		}, this);
		bb.on('afterload', function(){
			this.elm.animate({
				opacity: 1
			});
		}, this);
	};
	
	this.slideUp = function(){
		bb.on('beforeload', function(){
			this.elm.slideUp(this.speed);
		}, this);
		bb.on('afterload', function(){
			this.elm.slideDown(this.speed);
		}, this);
	};
	
	this.slideLeft = function(){
		bb.on('beforeload', function(options){
			this.elm.wrap('<div class="x-wrap" />');
			this.elm.parent('div.x-wrap').css({
				position: 'relative',
				overflow: 'hidden'
			});
			this.elm.css('position','relative').animate({
				left: options.direction * 900
			}, this.speed);
		}, this);
		bb.on('afterload', function(options){
			var scope = this;
			this.elm.stop().css('left', (1-options.direction) * 900).animate({
				left: 0
			}, this.speed, null, function(){
				scope.elm.unwrap();
			});
		}, this);
	};
	
	this.init = function(){
		//$('#' + bb.id).children().wrap('<div />');
		//this.elm = $('#' + bb.id, ' div');
		this.elm = $('#' + bb.id);
		this.speed = 'slow';
		this[transitionName].call(this);
	};
	this.init();
};
	

/**
 * @class Browsebox.
 * The browsebox is a simple interface to paging content.
 * 
 * @inherits Observable
 * @param {Object} config
 */
Garp.Browsebox = function(config){

	// Browsebox extends Garp.Observable
	Garp.apply(this, new Garp.Observable());

	// Defaults:
	this.cache = true;
	this.PRELOAD_DELAY = 850;
	this.BROWSEBOX_URL = 'g/browsebox/';
	this.append = false;
	
	// Apply config
	Garp.apply(this, config);
	
	// private
	this.timer = null;
	this.cacheArr = [];
	
	/**
	* Puts the processed data in the BB, sets up the links and fires afterload event
	* @param {String} data
	*/
	this.afterLoad = function(data, options){
		if (this.append) {
			$('.bb-next', $('#' + this.id)).replaceWith(Garp.innerShiv(data));
		} else {
			$('#' + this.id).html(Garp.innerShiv(data));
		}
		this.hijackLinks();
		this.fireEvent('afterload', options);
		this.preloadNext();
	};
		
	/**
	* Searches for images, and waits for them to load first
	* @param {String} data
	* @param {Bool} preloadOnly
	* @param {Number} direction
	*/
	this.processData = function(data, preloadOnly, dir){
		var imgs = $('img', data);
		var count = imgs.length;
		var scope = this;
		
		function checkStack(){
			count--;
			if (count <= 0 && !preloadOnly) {
				scope.afterLoad(data,{
					direction: dir
				});
			}
		}

		// See if there are any images. If so, wait for the load/error event on them.
		if (count > 0) {
			var I = [];
			$(imgs).each(function(i, img){
				I[i] = new Image();
				if (!preloadOnly) {
					$(I[i]).bind('load', checkStack).bind('error', checkStack);
				}
				I[i].src = $(img).attr('src');
			});
		} else{
			if (!preloadOnly) {
				this.afterLoad(data,{
					direction: dir
				});
			}
		}
		
	};
	
	/**
	* Loads a page. Fires beforeload and afterload events
	* @param {String} chunk
	* @param {String} [optionally] filters 
	* @param {Number} direction
	*/
	this.loadPage = function(chunk, filters, dir){
		var url = BASE + this.BROWSEBOX_URL;
		url += this.id + '/' + chunk + '/' + 
			(filters ? filters : 
				(this.filters  ? this.filters : '')
			) + (this.options ? this.options : '');
		
		this.fireEvent('beforeload', {
			direction: dir
		});
		
		var scope = this;
		setTimeout(function(){
			if (scope.cacheArr[url]) {
				scope.processData(scope.cacheArr[url], false, dir);
			} else {
				$.ajax({
					url: url,
					cache: scope.cache,
					success: function(data){
						scope.cacheArr[url] = data;
						scope.processData(data, false, dir);
					}
				});
			}
		}, 800);
	};
	
	this.preloadNext = function(){
		var url = $('.bb-next a', '#' + this.id).attr('href');
		
		if (url) {
			var queryComponents = Garp.parseQueryString(url, true);
			var chunk = queryComponents['bb[' + this.id + ']'];
			url = BASE + this.BROWSEBOX_URL + this.id + '/' + chunk + '/' +
			(this.filters ? this.filters : '');
			
			
			if (this.cacheArr[url]) {
				this.processData(this.cacheArr[url], true);
			} else {
				var scope = this;
				this.timer = setTimeout(function(){
					$.ajax({
						url: url,
						cache: scope.cache,
						success: function(data){
							scope.cacheArr[url] = data;
							scope.processData(data, true);
						}
					});
				}, this.PRELOAD_DELAY);
			}
		}
	};
	
	/**
	* Sets up previous & next buttons
	*/
	this.hijackLinks = function(){
		var scope = this;
		$('.bb-next a, .bb-prev a', '#' + this.id).unbind().bind('click', function(e){
			e.preventDefault();
			if(scope.timer){
				clearTimeout(scope.timer);
				scope.timer = false;
			}
			if (scope.rememberState) {
				scope.setHash($(this).attr('href'));
			}
			var queryComponents = Garp.parseQueryString($(this).attr('href'), true);
			var chunk = queryComponents['bb[' + scope.id + ']'];
			var dir = $(this).parent('.bb-next').length ? 1 : -1;
			scope.loadPage(chunk, null, dir);
			return false;
		});
	};
	
	/**
	* Sets up a new location.hash
	* @param {String} hash
	*/
	this.setHash = function(hash){
		hash = hash.substr(hash.indexOf('?') + 1, hash.length);
		window.location.hash = hash;
	};
	
	/**
	* Tries to find a previous location.hash state. Loads the according page
	* @TODO: expand this to find if we do not already have this state (location.hash v.s. loaction.search)
	*/
	this.getDejaVu = function(){
		var hashComponents = Garp.parseQueryString(window.location.hash.replace(/#/g,''), true);
		if(hashComponents['bb[' + this.id +']']){
			var chunk = hashComponents['bb[' + this.id + ']'];
			this.loadPage(chunk, null, null);		
		}
	};
	
	/**
	* Init
	*/
	this.init = function(){
		this.transition = new Garp.Transition(this, this.transition);
		this.hijackLinks();
		this.getDejaVu();
		var elm = $('#' + this.id);
		this.spinner = $('<div class="spinner"></div>').insertAfter('#' + this.id).css({			
			left: elm.position() ? elm.position().left : 0,
			top: elm.position() ? elm.position().top : 0,
			width: elm.width(),
			height: elm.height()
		});
		this.spinner.css({display:'none'});
		
		if (!this.hideSpinner) {
			this.on('beforeload', function(){
				var elm = $('#' + this.id);
				this.spinner.css({
					display: 'block',
					left: elm.position() ? elm.position().left : 0,
					top: elm.position() ? elm.position().top : 0,
					width: elm.width(),
					height: elm.height()
				});
			}, this);
			
			this.on('afterload', function(){
				this.spinner.css({display:'none'});
			}, this);
		}
		if(this.autoRun){
			var scope = this;
			var speed = typeof this.speed != 'undefined' ? this.speed : 800;
			this.interval = setInterval(function(){
				$('#' + this.id + '.bb-next a').click();
			}, speed);
		}
	};

	this.init();
};

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

/**
 * Validator object
 * 
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * !!                                                       !!
 * !!            THIS IS GOING TO BE PHASED OUT             !!
 * !!                                                       !!
 * !! We are going to use Garp.FormHelper instead           !!
 * !! @see https://projects.grrr.nl/projects/10/tickets/713 !!
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * 
 * @version 1.1
 */
Garp.Validator = (function() {
	/**
	* Private methods
	*/
	// validation functions. The key is the className that triggers the function
	var rules = {
		required: function(elm) {
			if (!elm.val()) {
				Garp.Validator.triggerError(elm.attr('id'), __('%s is een verplicht veld.'));
			}
		},
		noBMP: function(elm) {
			if (elm.val()) {
				var e = elm.val();
				e = e.substring(e.length-4, e.length);
				e = e.toUpperCase();
				if (e === '.BMP') {
					Garp.Validator.triggerError(elm.attr('id'), __('Geen geldig bestandsformaat.'));
				}
			}
		},
		email: function(elm) {
			var email = /([\w]+)(\.[\w]+)*@([\w\-]+\.){1,5}([A-Za-z]){2,4}$/;
			if (elm.val() && !email.test(elm.val())) {
				Garp.Validator.triggerError(elm.attr('id'), __('%s is geen geldig e-mailadres.'));
			}
		},
		password: function(elm) {
		},
		repeatPassword: function(elm) {
			if (elm.attr('rel') && $('#'+elm.attr('rel'))) {
				var theOtherPwdField = $('#'+elm.attr('rel'));
				if (theOtherPwdField.length) {
					if (theOtherPwdField.val() !== elm.val()) {
						Garp.Validator.triggerError(elm.attr('id'), __('De wachtwoorden komen niet overeen.'));
					}
				}
			}
		},
		requiredIf: function(elm) {
			if (elm.attr('rel') && $('#'+elm.attr('rel'))) {
				var otherField = $('#'+elm.attr('rel'));
				var otherFieldFilled = false;
				if (otherField.attr('type') == 'checkbox') {
					otherFieldFilled = otherField.is(':checked');
				} else {
					otherFieldFilled = otherField.val();
				}
				if (otherFieldFilled && !elm.val()) {
					var verb = otherField.attr('type') === 'checkbox' ? 'aangevinkt' : 'ingevuld';
					var str = __('Als ### is '+verb+', is %s verplicht.');
					str = str.replace('###', $('label[for="'+otherField.attr('id')+'"]').text());
					Garp.Validator.triggerError(elm.attr('id'), str);
				}
			}
		}
	};
	
	/**
	* Public methods
	*/
	return {
		// Validate the form according to the rules above
		validateForm: function(formId) {
			// loop thru all the different input types
			var fields = $('#'+formId+' input, #'+formId+' select, #'+formId+' textarea');
			$('#'+formId).submit(function(e) {
				// reset errorMessages to an empty array
				Garp.Validator.errorMessages = {};
				fields.each(function() {
					var self = $(this);
					for (var i in rules) {
						if (self.hasClass(i)) {
							rules[i](self);
						}
					}
				});
				var valid = true;
				for (var i in Garp.Validator.errorMessages) {
					valid = false;
					break;
				}
				if (valid) {
					return true;
				} else {
					var errorTxt = '';
					for (var j in Garp.Validator.errorMessages) {
						errorTxt += Garp.Validator.errorMessages[j]+'<br>';
					}
					$('#'+formId+' p.error').html(errorTxt);
					e.preventDefault();
					return false;
				}
			});
		},
		// add custom rules, with custom functions if required
		pushRule: function(rule, fn) {
			rules[rule] = fn ; //.push(rule);
		},
		// add errors
		triggerError: function(id, msg) {
			var label = $('label[for='+id+']');
			Garp.Validator.errorMessages[id] = msg.replace('%s', label.text());
		}
	};
})();

/**
 * Equalize the height of several items in a collection.
 * @param {jQuery} collection of items
 * @return {Void}
 */
Garp.equalizeHeight = function(collection) {
	var h = 0;
	collection.each(function() {
		h = Math.max(h, $(this).height());
	});
	collection.height(h);
};

/**
 * Retrieves an object housing in an array by looking for one of its keys and possibly it's value
 * @param {Array} array of objects
 * @param {String} key
 * @param {String} val [optional]
 * 
 * @return {Array | Object} 
 */
Garp.getBy = function(arr, key, val){
	var out = [];
	for(var k in arr){
		var item = arr[k];
		if(item[key]){
			if (!val || (val && item[key] == val)) {
				out.push(item);
			}
		}
	}
	return out.length == 1 ? out[0] : out;
};

/**
 * Garp Flash Messenger. Provides a method to show a 'popup' style message to the user.
 * Convenient for auto-fade out and such.
 * 
 * @param {Object} Config properties. See below for @cfg details:
 * 
 * @example usage: 
 *	var fm = new Garp.FlashMessage({
 *		msg: "Your Message might go here, or you can use... ",
 *		parseCookie: true // ... this option to grab the message from the server
 *	});
 * 
 */
Garp.FlashMessage = function(cfg){

	// Apply defaults:
	Garp.apply(this, {
	
		/**
		* @cfg: {String} Message to show
		*/
		msg: '',
		
		/**
		* @cfg: {Boolean} Whether or not too parse (and show) the Garp.FlashMessage cookie
		*/
		parseCookie: false,
		
		/**
		* @cfg: {String} Cookie name
		*/
		cookieName: 'FlashMessenger',
		
		/**
		* @cfg {Function} Callback, get's called with scope/this set to FlashMessage  
		*/
		afterShow: function(){
		},
		
		/**
		* @cfg {Function} Callback, get's called with scope/this set to FlashMessage
		*/
		afterClose: function(){
		},
		
		/**
		* @cfg {jQuery element}: Provide the element to use
		*/
		elm: null,
		createElm: function(){
			var id = 'flashMessage';
			this.elm = $('#' + id).length ? $('#' + id) : $('body').append('<div id="' + id + '"></div>').find('#' + id);
			return this.elm;
		},
		
		/**
		* @cfg {Boolean} Automatically hide?
		*/
		autoHide: true,
		
		/**
		* @cfg {Number} Delay for auto hide
		*/
		hideDelay: 6000,
		
		/**
		* @cfg {Function} Animation to use. Override to do someting else than just a simple fade:
		*/
		hideAnimation: function(){
			if (this.elm) {
				this.elm.fadeOut('slow', Garp.createDelegate(this.afterClose, this));
			} else {
				this.afterClose(this);
			}
		}
	});
	
	// Override with given config:
	Garp.apply(this, cfg);
	
	// Private functions:
	Garp.apply(this, {
	
		/**
		* Shows (the hidden) flashMessage. Should generally not be needed.
		*/
		show: function(){
			this.elm.show();
			return this;
		},
		
		/**
		* Hides the flashMessage element by using the possible animation
		*/
		hide: function(){
			if (this.hideAnimation) {
				this.hideAnimation();
			} else {
				this.close();
			}
			return this;
		},
		
		/**
		* Closes the flashMessage immediately
		*/
		close: function(){
			if (this.elm) {
				this.elm.hide();
			}
			if (this.afterClose) {
				this.afterClose(this);
			}
			return this;
		},
		
		/**
		* Parses a cookie. Nom,nom,nom.
		* note; a global COOKIEDOMAIN constant can be set for this function.
		*/
		cookieParser: function(){
			var m = $.parseJSON(unescape(Garp.getCookie(this.cookieName))), out = '';
			if (m && m.messages) {
				for (var i in m.messages) {
					var msg = m.messages[i];
					if (msg) {
						out += '<p>' + msg.replace(/\+/g, ' ') + '</p>';
					}
				}
				var exp = new Date();
				exp.setHours(exp.getHours() - 1);
				Garp.setCookie(this.cookieName, '', exp, (typeof COOKIEDOMAIN !== 'undefined') ? COOKIEDOMAIN : document.location.host);
				return out;
			}
			return '';
		},
		
		/**
		* Init
		*/
		init: function(){
			if (this.parseCookie) {
				this.msg += this.cookieParser();
			}
			if (!this.msg) {
				return this;
			}
			if (!this.elm) {
				this.createElm();
			}
			if (this.msg) {
				this.elm.html(this.msg);
			}
			if (this.autoHide) {
				setTimeout(Garp.createDelegate(this.hide, this), this.hideDelay);
			}
			this.elm.show();
			this.afterShow(this);
			return this;
		}
	});
		
	return this.init();
};



/**
 * Google Maps
 */
Garp.buildGoogleMap = function(elm, config){
	var map = new google.maps.Map(elm, {
		mapTypeId: google.maps.MapTypeId[config.maptype.toUpperCase()],
		navigationControl: true,
		navigationControlOptions: {
			style: google.maps.NavigationControlStyle.SMALL
		},
		mapTypeControlOptions: {
			mapTypeIds: ['']
		},
		scaleControl: true,
		center: new google.maps.LatLng(parseFloat(config.center.lat), parseFloat(config.center.lng)),
		zoom: parseInt(config.zoom, 10)
	});
	
	if(config.markers){
		for(var i in config.markers){
			var marker = config.markers[i];
			
			var gMarker = new google.maps.Marker({
				map: map,
				title: marker.title,
				position: new google.maps.LatLng(parseFloat(marker.lat), parseFloat(marker.lng))
			});
			
		}		
	}
};

$(function(){
	$('.g-googlemap').each(function(){

		if ($(this).parent('a').length) {
			$(this).unwrap();
		}
		
		var mapProperties = Garp.parseQueryString($(this).attr('src'));
		var center = mapProperties.center.split(',');
		Garp.apply(mapProperties,{
			width: $(this).attr('width'),
			height: $(this).attr('height'),
			center: {
				lat: center[0],
				lng: center[1]
			},
			markers: mapProperties.markers ? mapProperties.markers.split('|') : false
		});
		for (var i in mapProperties.markers){
			var m = mapProperties.markers[i].split(',');
			mapProperties.markers[i] = {
				lat: m[0],
				lng: m[1],
				title: m[2] ? m[2] : ''
			};
		}
		
		$(this).wrap('<div class="g-googlemap-wrap"></div>');
		var wrap = $(this).parent('.g-googlemap-wrap').width(mapProperties.width).height(mapProperties.height);
		Garp.buildGoogleMap(wrap[0], mapProperties);	
	});
});

/**
 * Grab a Cookie
 * @param {Object} name
 */
Garp.getCookie = function(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
			c = c.substring(1, c.length);
		}
        if (c.indexOf(nameEQ) === 0) {
			return c.substring(nameEQ.length, c.length);
		}
    }
    return null;
};

/**
 * Give a Cookie
 * @param {Object} name
 * @param {Object} value
 * @param {Date} expiration date 
 * @param {String} domain
 */
Garp.setCookie = function(name, value, date, domain){
	value = escape(value) + "; path=/";
	/*
	if (domain) {
		value += "; domain=" + escape(domain);
	} else {
		value += "; domain=" + escape((typeof COOKIEDOMAIN !== 'undefined') ? COOKIEDOMAIN : document.location.host);
	}
	*/
	value += (!date ? "" : "; expires=" + date.toGMTString());
	document.cookie = name + "=" + value;
};

Garp.removeCookie = function(name){
	Garp.setCookie(name,'',new Date('1900'));
};

/**
 * Snippet edit links
 */
$(function(){
	var authCookie = unescape(Garp.getCookie('Garp_Auth'));
	authCookie = jQuery.parseJSON(authCookie);
	if (authCookie && authCookie.userData && authCookie.userData.role) {
		var role = authCookie.userData.role;
		if (role && (role == 'admin' || role == 'developer')) {
			setTimeout(function(){
				var elms = [];
				(function walk(elm){
					if (elm.childNodes && elm.childNodes.length > 0) {
						for (var e in elm.childNodes) {
							walk(elm.childNodes[e]);
						}
					} else {
						elms.push(elm);
					}
				})($('body')[0]);
				for (var e in elms) {
					var elm = elms[e];
					var token = '//garp-snippet//';
					if (elm.nodeType == 8 && elm.nodeValue.indexOf(token) > -1) {
						var snippet = elm.nextSibling;
						var url = BASE + 'g/content/admin/?model=Snippet&id=' + elm.nodeValue.replace(token, '');
						//$(snippet).wrap('<div style="position:relative;">')
						var link = $('<a href="' + url + '" title="edit" target="garp_cms"><img src="' + BASE + 'media/images/garp/icons/pencil.png" /></a>').insertBefore(snippet).css({
							position: 'absolute',
							zIndex: '999999',
							padding: '4px',
							width: '20px',
							height: '20px',
							margin: '-14px 0 0 -14px',
							lineHeight: 0
						//border: '0'
						});
						(function(link){
							function mouseOut(){
								$(link).css({
									opacity: 0.5,
									border: '2px #fff outset',
									background: '#ddd'
								});
							}
							$(link).bind('mouseenter', function(){
								$(link).css({
									opacity: 1,
									border: '2px #fff outset',
									background: '#ddd'
								});
							}).bind('mouseleave', mouseOut);
							mouseOut();
						})(link);
					}
				}
			}, 1000);
		}
	}
});

/**
 * Is our dearest user logged in or what?
 */
Garp.isLoggedIn = function(){
	var c = Garp.getCookie('Garp_Auth');
	if(c){
		c = $.parseJSON(unescape(c));
		if(c.userData){
			return true;
		}
	}
	return false;
};

/**
 * Cookie acceptation stuff 'Cookiewet'
 */

Garp.hasCookiesAccepted = function(){
	return Garp.getCookie('Garp_Accept_Cookies') == 'Garp_Accept_Cookies';
};
Garp.acceptCookies = function(){
	var exp = new Date();
	exp.setYear(exp.getFullYear() + 1);
	Garp.setCookie('Garp_Accept_Cookies', 'Garp_Accept_Cookies', exp);
};

/**
 * Garp relative Date
 * Returns the date or time difference in HRF™ (Human Readable Format)
 */
Garp.relativeDate = function(oldest, newest){
	if (typeof oldest.getTime != 'function') {
		oldest = new Date(oldest + '');
	}
	if (typeof newest.getTime != 'function') {
		newest = new Date(newest + '');
	}
	var elapsed = Math.abs(oldest.getTime() - newest.getTime()); // milliseconds
	if (isNaN(elapsed)) {
		return '';
	}
	
	elapsed = elapsed / 60000; // minutes
	var result = '';
	// Date constants for readability
	var MINUTE = 1;
	var HOUR   = MINUTE*60;
	var DAY    = HOUR*24;
	var WEEK   = DAY*7;
	var MONTH  = DAY*30;
	var YEAR   = DAY*365;
	var days;
	
	switch (true) {
		case (elapsed < MINUTE):
			result = __('less than a minute');
			break;
			
		case (elapsed < HOUR):
			var minutes = Math.round(elapsed);
			result = minutes + ' ' + (minutes == 1 ? __('minute') : __('minutes'));
			break;
			
		case (elapsed < DAY):
			var hours = Math.round(elapsed / HOUR);
			result = hours + ' ' + (hours == 1 ? __('hour') : __('hours'));
			break;
			
		case (elapsed < WEEK):
			days = Math.round(elapsed / DAY);
			result = days + ' ' + (days == 1 ? __('day') : __('days'));
			break;
			
		case (elapsed < MONTH):
			/**
			 * Here we use Math.ceil because the scope is so small. It makes no sense when 
			 * it's 1 week and 2 days to say "1 week". It's more correct to say 2 weeks.
			 */
			var weeks = Math.ceil(elapsed / WEEK);
			/**
			 * And while we're at it: just say "days" when it's less than 2 weeks.
			 * Weeks are an inaccurate depiction of a time period when it's only a few of 'em.
			 * Better switch to days.
			 */
			if (weeks > 2) {
				result = weeks + ' ' + (weeks == 1 ? __('week') : __('weeks'));
			} else {
				days = Math.round(elapsed / DAY);
				result = days + ' ' + (days == 1 ? __('day') : __('days'));
			}
			break;
			
		case (elapsed < YEAR):
			var months = Math.round(elapsed / MONTH);
			result = months + ' ' + (months == 1 ? __('month') : __('months'));
			break;
			
		default:
			var years = Math.round(elapsed / YEAR);
			result = years + ' ' + (years == 1 ? __('year') : __('years'));
			break;
			
	}
	return result;
};

/***
 * Class Twitter
 * @param {Object} config
 * 
 * example usage:
 * var twitter = new Garp.Twitter({
 *		elm: $('#tweets'),
 *		afterFetch: function(result){
 *			this.elm.prepend(result);
 *		},
 *		beforeFetch: function(){
 *			this.elm.empty();
 *		}
 *	});
 *	twitter.search('garp');
 */
Garp.Twitter = function(config){

	// Default config: //
	Garp.apply(this, {
		query: '',
		resultsPerPage: 25, // max 100
		resultsAsArray: false,
		searchTpl: '<img src="${1}" alt="${2}">${2}: ${3}<hr>',
		listTpl: '<img src="${1}" alt="${2}">${2}: ${3}<hr>',
		beforeFetch: jQuery.noop,
		afterFetch: jQuery.noop,
		onError: jQuery.noop // Gets called when no results found or an error occurred
	});
	
	// Override config: //
	Garp.apply(this, config);
	
	/**
	* Searches Twitter for query and caches the query string for later re-use
	* @param {query} (optional) query
	*/
	this.search = function(query){
		if (query) {
			this.query = query;
		} else {
			query = this.query;
		}
		query = encodeURIComponent(query);
		var scope = this;
		scope.beforeFetch.call(this);
		$.getJSON(Garp.format('http://search.twitter.com/search.json?q=${1}&rpp=${2}&callback=?', query, this.resultsPerPage), function(response){
			scope.parseResponse.call(scope, response);
		});
	};
	
	
	/**
	* Gets Twitter Lists
	* @param {String} user
	* @param {String} listId
	*/
	this.getList = function(user, listId){
		var scope = this;
		scope.beforeFetch.call(this);
		$.getJSON(Garp.format('http://api.twitter.com/1/${1}/lists/${2}/statuses.json?callback=?', user, listId), function(response){
			scope.parseResponse.call(scope, response);
		});
	};
	
	// private //
	this.parseResponse = function(response){
		var item, result = [];
		if (response) {
			if (response.results) { // search results
				for (item in response.results) {
					if (response.results[item].text) {
						result.push(Garp.format(this.searchTpl, response.results[item].profile_image_url, response.results[item].from_user, response.results[item].text.replace(new RegExp('(http://[^ ]+)', "g"), '<a target="_blank" href="$1">$1</a>'), Garp.relativeDate(response.results[item].created_at, new Date()), response.results[item].from_user));
					}
				}
			} else { // list results
				var c = 1;
				for (item in response) {
					c++;
					if (response[item].text) {
						result.push(Garp.format(this.listTpl, response[item].user.profile_image_url, response[item].user.name, response[item].text.replace(new RegExp('(http://[^ ]+)', "g"), '<a href="$1">$1</a>'), Garp.relativeDate(response[item].created_at, new Date()), response[item].user.screen_name));
					}
					if(c > this.resultsPerPage){
						break;
					}
				}
			}
			if (result.length) {
				this.afterFetch(this.resultsAsArray ? result : result.join(''));
			} else {
				this.onError(response);
			}
		} else {
			this.onError(response);
		}
	};
	
	return this;
};

/**
 * Constrains number i between min and max. Rolls over.
 * @param {Number} i
 * @param {Number} {optional} min
 * @param {Number} max
 */
Garp.constrain = function(i){
	var max = arguments[2];
	var min = arguments[1];
	if(typeof max != 'Number'){
		min = 0;
		max = arguments[1];
	}
	if (i < min) {
		i = max;
	}
	if (i > max) {
		i = min;
	}
	return i;	
};

Garp.playerBox = function(config){

	$player = config.$player;
	$navigation = config.$navigation;
	
	var activeClass = config.activeClass || '';
	var width = config.width || 480;
	var height = config.height || 320;
	var navActiveClass = config.navActiveClass || '';
	
	$navigation.bind('click', function(e){
		if (e && e.target) {
			e.preventDefault();
			var $img = $(e.target);
			if ($img.is('img') || $img.is('span')) {
				var $a = $img.parent('a');
				var src = $a.attr('href');
				var video = false;
				$('img, iframe', $player).removeClass(activeClass);
				$('a', $navigation).removeClass(navActiveClass);
				$a.addClass(navActiveClass);
				if ($a.hasClass('video')) {
					video = true;
				}
				
				function cb(){
					if (video) {
						if ($a.hasClass('vimeo')) {
							$player.html('<iframe src="' + src + '" width="' + width + '" height="' + height + '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>');
						} else {
							$player.html('<iframe src="' + src + '" width="' + width + '" height="' + height + '" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>');
						}
					} else {
						$player.html('<img src="' + src + '">');
					}
					setTimeout(function(){
						$('img, iframe', $player).addClass(activeClass);
					}, 20);
				}
				if (video) {
					setTimeout(cb, 1000);
				} else {
					var i = new Image();
					i.onload = cb;
					i.src = src;
				}
			}
		}
	});
};

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

Garp.scrollHandler = (function(){

	var scrollBinded = false;
	var elms = [];
	
	function checkInView($elm){
		var et = $elm.offset().top - 200;
		if (et < 0) {
			et = 0;
		}
		var w = $(window).scrollTop();
		var wt = $(window).height() + w;
		return (et >= w) && et <= wt;
	}
	
	function handler(){
		if (elms.length) {
			$.each(elms, function(i, elm){
				if (elm) {
					var $elm = elm[0];
					var fn = elm[1];
					if (checkInView($elm)) {
						elms.splice(i, 1);
						fn();
					}
				}
			});
		} else {
			$(window).unbind('scroll', handler);
		}
	}
	
	return {
		elms: elms,
		register: function($elm, fn){
			if (checkInView($elm)) {
				fn();
				return;
			} else {
				elms.push([$elm, fn]);
			}
			if (!scrollBinded) {
				$(window).bind('scroll', handler);
				setInterval(handler, 2000);
				scrollBinded = true;
			}
		}
	};
})();


Garp.lazyLoader = {
	/**
	* Register a lazyLoad callback
	* @param {Object} cfg
	* @cfg id {String} id of the element
	* @cfg before {Function} beforeFetch fn. Gets called with scope &amp; fetch arguments
	* @cfg after {Function} afterFetch fn. Gets called with scope, respone &amp; fetch arguments 
	*/
	reg: function(cfg){
		if (cfg.id && !Garp.lazyLoader[cfg.id]) {
			if (typeof cfg.before == 'function' || typeof cfg.after == 'function') {
				Garp.lazyLoader[cfg.id] = cfg;
			} else {
				throw "No callback function defined in lazyLoader config.";
			}
		} else {
			throw "Can't register lazyLoader. No id property or duplicate entry.";
		}
	},
	
	/**
	* Deletes a lazyLoad callback
	* @param {Object} id
	*/
	unreg: function(id){
		delete Garp.lazyLoader[id];
	},


	/**
	* Collect all lazy-load elements and fetch!
	*/
	init: function(){
	
		var $elm = $('.lazy-load');
		if (!$elm.length) {
			return;
		}
		
		function fetch(e){
			if (e && e.preventDefault) {
				e.preventDefault();
			}
			var con = $(this).attr('data-con');
			if (typeof con == 'undefined') {
				con = '?';
			}
			var id = $(this).attr('id');
			var attributes = $(this).attr('data-attr') || '';
			if (!attributes) {
				con = '';
			}
			var before = $.noop;
			var after = $.noop;
			
			if (Garp.lazyLoader[id]) {
				if (Garp.lazyLoader[id].before) {
					before = Garp.lazyLoader[id].before;
				}
				if (Garp.lazyLoader[id].after) {
					after = Garp.lazyLoader[id].after;
				}
			}
			
			var fetchCB = Garp.createDelegate(fetch, this);
			before(this, fetchCB);
			
			var url = $(this).attr('data-href') + con + attributes;
			var scope = this;
			var isJson = $(this).hasClass('json');
			
			var replace = false;
			if ($(this).attr('data-replace-selector') && $($(this).attr('data-replace-selector')).length) {
				replace = true;
			}
			
			$.get(url, function(resp){
				if (isJson) {
					resp = resp.html;
				}
				if (replace) {
					$($(scope).attr('data-replace-selector')).replaceWith(resp);
				} else {
					$(scope).html(resp);
				}
				after.call(scope, scope, resp, fetchCB);
			}, isJson ? 'json' : 'html');
		}
		
		$elm.each(function(i, el){
			Garp.scrollHandler.register($(el), function(){
				fetch.call(el);
			});
		});
	}
};

/*!
 * selectivizr v1.0.2 - (c) Keith Clark, freely distributable under the terms of the MIT license.
 * selectivizr.com
 */
//(function(j){function A(a){return a.replace(B,h).replace(C,function(a,d,b){for(var a=b.split(","),b=0,e=a.length;b<e;b++){var s=D(a[b].replace(E,h).replace(F,h))+o,l=[];a[b]=s.replace(G,function(a,b,c,d,e){if(b){if(l.length>0){var a=l,f,e=s.substring(0,e).replace(H,i);if(e==i||e.charAt(e.length-1)==o)e+="*";try{f=t(e)}catch(k){}if(f){e=0;for(c=f.length;e<c;e++){for(var d=f[e],h=d.className,j=0,m=a.length;j<m;j++){var g=a[j];if(!RegExp("(^|\\s)"+g.className+"(\\s|$)").test(d.className)&&g.b&&(g.b===!0||g.b(d)===!0))h=u(h,g.className,!0)}d.className=h}}l=[]}return b}else{if(b=c?I(c):!v||v.test(d)?{className:w(d),b:!0}:null)return l.push(b),"."+b.className;return a}})}return d+a.join(",")})}function I(a){var c=!0,d=w(a.slice(1)),b=a.substring(0,5)==":not(",e,f;b&&(a=a.slice(5,-1));var l=a.indexOf("(");l>-1&&(a=a.substring(0,l));if(a.charAt(0)==":")switch(a.slice(1)){case "root":c=function(a){return b?a!=p:a==p};break;case "target":if(m==8){c=function(a){function c(){var d=location.hash,e=d.slice(1);return b?d==i||a.id!=e:d!=i&&a.id==e}k(j,"hashchange",function(){g(a,d,c())});return c()};break}return!1;case "checked":c=function(a){J.test(a.type)&&k(a,"propertychange",function(){event.propertyName=="checked"&&g(a,d,a.checked!==b)});return a.checked!==b};break;case "disabled":b=!b;case "enabled":c=function(c){if(K.test(c.tagName))return k(c,"propertychange",function(){event.propertyName=="$disabled"&&g(c,d,c.a===b)}),q.push(c),c.a=c.disabled,c.disabled===b;return a==":enabled"?b:!b};break;case "focus":e="focus",f="blur";case "hover":e||(e="mouseenter",f="mouseleave");c=function(a){k(a,b?f:e,function(){g(a,d,!0)});k(a,b?e:f,function(){g(a,d,!1)});return b};break;default:if(!L.test(a))return!1}return{className:d,b:c}}function w(a){return M+"-"+(m==6&&N?O++:a.replace(P,function(a){return a.charCodeAt(0)}))}function D(a){return a.replace(x,h).replace(Q,o)}function g(a,c,d){var b=a.className,c=u(b,c,d);if(c!=b)a.className=c,a.parentNode.className+=i}function u(a,c,d){var b=RegExp("(^|\\s)"+c+"(\\s|$)"),e=b.test(a);return d?e?a:a+o+c:e?a.replace(b,h).replace(x,h):a}function k(a,c,d){a.attachEvent("on"+c,d)}function r(a,c){if(/^https?:\/\//i.test(a))return c.substring(0,c.indexOf("/",8))==a.substring(0,a.indexOf("/",8))?a:null;if(a.charAt(0)=="/")return c.substring(0,c.indexOf("/",8))+a;var d=c.split(/[?#]/)[0];a.charAt(0)!="?"&&d.charAt(d.length-1)!="/"&&(d=d.substring(0,d.lastIndexOf("/")+1));return d+a}function y(a){if(a)return n.open("GET",a,!1),n.send(),(n.status==200?n.responseText:i).replace(R,i).replace(S,function(c,d,b,e,f){return y(r(b||f,a))}).replace(T,function(c,d,b){d=d||i;return" url("+d+r(b,a)+d+") "});return i}function U(){var a,c;a=f.getElementsByTagName("BASE");for(var d=a.length>0?a[0].href:f.location.href,b=0;b<f.styleSheets.length;b++)if(c=f.styleSheets[b],c.href!=i&&(a=r(c.href,d)))c.cssText=A(y(a));q.length>0&&setInterval(function(){for(var a=0,c=q.length;a<c;a++){var b=q[a];if(b.disabled!==b.a)b.disabled?(b.disabled=!1,b.a=!0,b.disabled=!0):b.a=b.disabled}},250)}if(!/*@cc_on!@*/true){var f=document,p=f.documentElement,n=function(){if(j.XMLHttpRequest)return new XMLHttpRequest;try{return new ActiveXObject("Microsoft.XMLHTTP")}catch(a){return null}}(),m=/MSIE (\d+)/.exec(navigator.userAgent)[1];if(!(f.compatMode!="CSS1Compat"||m<6||m>8||!n)){var z={NW:"*.Dom.select",MooTools:"$$",DOMAssistant:"*.$",Prototype:"$$",YAHOO:"*.util.Selector.query",Sizzle:"*",jQuery:"*",dojo:"*.query"},t,q=[],O=0,N=!0,M="slvzr",R=/(\/\*[^*]*\*+([^\/][^*]*\*+)*\/)\s*/g,S=/@import\s*(?:(?:(?:url\(\s*(['"]?)(.*)\1)\s*\))|(?:(['"])(.*)\3))[^;]*;/g,T=/\burl\(\s*(["']?)(?!data:)([^"')]+)\1\s*\)/g,L=/^:(empty|(first|last|only|nth(-last)?)-(child|of-type))$/,B=/:(:first-(?:line|letter))/g,C=/(^|})\s*([^\{]*?[\[:][^{]+)/g,G=/([ +~>])|(:[a-z-]+(?:\(.*?\)+)?)|(\[.*?\])/g,H=/(:not\()?:(hover|enabled|disabled|focus|checked|target|active|visited|first-line|first-letter)\)?/g,P=/[^\w-]/g,K=/^(INPUT|SELECT|TEXTAREA|BUTTON)$/,J=/^(checkbox|radio)$/,v=m>6?/[\$\^*]=(['"])\1/:null,E=/([(\[+~])\s+/g,F=/\s+([)\]+~])/g,Q=/\s+/g,x=/^\s*((?:[\S\s]*\S)?)\s*$/,i="",o=" ",h="$1";(function(a,c){function d(){try{p.doScroll("left")}catch(a){setTimeout(d,50);return}b("poll")}function b(d){if(!(d.type=="readystatechange"&&f.readyState!="complete")&&((d.type=="load"?a:f).detachEvent("on"+d.type,b,!1),!e&&(e=!0)))c.call(a,d.type||d)}var e=!1,g=!0;if(f.readyState=="complete")c.call(a,i);else{if(f.createEventObject&&p.doScroll){try{g=!a.frameElement}catch(h){}g&&d()}k(f,"readystatechange",b);k(a,"load",b)}})(j,function(){for(var a in z){var c,d,b=j;if(j[a]){for(c=z[a].replace("*",a).split(".");(d=c.shift())&&(b=b[d]););if(typeof b=="function"){t=b;U();break}}}})}}})(this);

(function(document){
	
	
	window.MBP = window.MBP || {}; 
	var a,b,c,d,e,f,g,h,i,j,k,l,m,n,o;
	/*! A fix for the iOS orientationchange zoom bug. Script by @scottjehl, rebound by @wilto.MIT / GPLv2 License.*/
	/*  JSLINT fix by Peter */
	(function(a){
		function m(){
			d.setAttribute("content", g); h = !0;
		}
		function n(){
			d.setAttribute("content", f); h = !1;
		}
		function o(b){
			l = b.accelerationIncludingGravity; i = Math.abs(l.x); j = Math.abs(l.y); k = Math.abs(l.z); 
			((!a.orientation || a.orientation === 180) && (i > 7 || (k > 6 && j < 8 || k < 8 && j > 6) && i > 5) ? h && n() : h || m());
		}
		var b = navigator.userAgent;
		if ((!(/iPhone|iPad|iPod/.test(navigator.platform) && (/OS [1-5]_[0-9_]* like Mac OS X/i.test(b)) && b.indexOf("AppleWebKit") > -1))) {
			return;
		}
		var c = a.document;
		if (!c.querySelector) {
			return;
		}
		var d = c.querySelector("meta[name=viewport]"), e = d && d.getAttribute("content"), f = e + ",maximum-scale=1", g = e + ",maximum-scale=10", h = !0, i, j, k, l;
		if (!d) {
			return;
		}
		a.addEventListener("orientationchange", m, !1);
		a.addEventListener("devicemotion", o, !1);
	})(this);

	// Autogrow
	// http://googlecode.blogspot.com/2009/07/gmail-for-mobile-html5-series.html
	MBP.autogrow = function (element, lh) {
 	 
    	function handler(e){
        	var newHeight = this.scrollHeight,
            	currentHeight = this.clientHeight;
        	if (newHeight > currentHeight) {
            	this.style.height = newHeight + 3 * textLineHeight + "px";
        	}
    	}
 	 
    	var setLineHeight = (lh) ? lh : 12,
        	textLineHeight = element.currentStyle ? element.currentStyle.lineHeight :
                         	 getComputedStyle(element, null).lineHeight;
 	 
    	textLineHeight = (textLineHeight.indexOf("px") == -1) ? setLineHeight :
                     	 parseInt(textLineHeight, 10);
 	 
    	element.style.overflow = "hidden";
		if(element.addEventListener){
			element.addEventListener('keyup', handler, false);
		} else {
			element.attachEvent('onkeyup', handler);
		}
	};	
})(document);

/**
 * Loads JS files async
 * @param {String} url
 * @param {String} type ('js' or 'css') (css not implemented yet!)
 * @param {Function} callback
 * @param {Object} scope to call callback from
 */
Garp.asyncLoad = function(url, type, cb, scope){
	if (type === 'js') {
		var s = document.createElement('script');
		if (s.addEventListener) {
			s.addEventListener('load', function(){
				cb.call(scope);
			}, false);
		}
		$('head')[0].appendChild(Garp.applyIf(s, {
			onreadystatechange: function(){
				if (this.readyState == 'loaded' || this.readyState == 'complete') {
					cb.call(scope);
				}
			},
			type: 'text/javascript',
			src: url
		}));
	} else if (type === 'css'){
		// TODO add support for CSS here
	} else {
		throw 'Unsupported type for asyncLoad';
	}
};

/**
 * Garp FormHelper Singleton.
 * Provides several goodies for forms:
 *  - validation
 *  - file upload
 *  - submit button disable to prevent duplicate entries
 *  - possible ajaxify (form element needs class 'ajax') 
 */
Garp.FormHelper = Garp.FormHelper || {};
Garp.apply(Garp.FormHelper, {

	/**
	 * @cfg {jQuery} The form(s) reference(s)
	 */
	form: $('.garp-form'),
	
	/**
	 * @cfg {String} class to put on the @cfg form element(s) when ajax calls are being made.
	 */
	ajaxBusyCls: 'loading',
	
	/**
	 * @cfg {Function} onAjaxComplete. Get's called on error, success or timeout. Passes: @param {Object} jqXhr @param {Object} status
	 */
	onAjaxComplete: function(jqXhr, status){
		var valid = false;
		var resp = false;
		if (status === 'success') {
			resp = $.parseJSON(jqXhr.responseText);
			if (resp && resp.html) {
				this.form.html(resp.html);
				valid = true;
			}
		}
		if (console && console.log && !valid) {
			console.log('Garp formHelper: server status ' + status);
		}
	},
	
	/**
	 * @cfg {Function} onBeforeAjax Possible to prevent ajax submission here; just return false.
	 */
	onBeforeAjax: function(){
		return true;
	},
	
	/**
	 * @cfg {Number} ajaxTimeout
	 */
	ajaxTimeout: 30000,
	
	/**
	 * @cfg {String} response type of the server
	 */
	ajaxDataType: 'json',
	
	
	// private
	/**
	 * Hijacks upload fields into nice AJAX things. Uses qq.FileUploader for this, but only includes it if necessary
	 * @param cb Callback after setup
	 * @param fh FormHelper reference
	 */
	hijackUploadFields: function(cb, fh){
		var fields = $('.hijack-upload', this.form);
		var scope = this;
		
		if (fields.length) {
			Garp.asyncLoad(BASE + 'js/fileuploader.js', 'js', function(){
				fields.each(function(i){
					var $target = $(this);
					var orig = $target;
					var name = $target.attr('name') || $target.data('name');
					$target.attr('name', name + '-filefield');
					var prepopulate = $target.data('prepopulate-filelist');
					var uploadType = $target.data('type') || 'image', imgTemplate = $target.data('image-template') || 'cms_list', url = BASE + 'g/content/upload/insert/1/mode/raw/type/' + uploadType, urlNonRaw = BASE + 'g/content/upload/insert/1/type/' + uploadType;
					
					$target = $target.parent();
					if (!$target[0]) {
						return;
					}
					var multiple = false;
					var maxItems = orig.data('max-files') || null;
					if (orig.data('max-files') && orig.data('max-files') > 1) {
						multiple = true;
					}
					var cfg = {
						element: $target[0],
						action: url,
						actionNonRawMode: urlNonRaw,
						debug: false,
						allowedExtensions: orig.attr('data-allowed-extensions') ? orig.attr('data-allowed-extensions').split(',') : null,
						sizeLimit: orig.attr('data-max-file-size') ? parseInt(orig.attr('data-max-file-size'), 10) * 1024 * 1024 : null,
						showMessage: function(msg){
							new Garp.FlashMessage({
								msg: msg,
								parseCookie: false
							});
						},
						template: '<div class="qq-uploader">' +
							'<div class="qq-upload-drop-area"><span>Sleep files hier om up te loaden</span></div>' +
							'<div class="qq-upload-button-container"><div class="qq-upload-button">uploaden</div></div>' +
							'<ul class="qq-upload-list"></ul>' +
							'</div>',
						fileTemplate: '<li>' +
							'<span class="qq-upload-file"></span>' +
							'<span class="qq-upload-spinner"></span>' +
							'<span class="qq-upload-size"></span>' +
							'<span class="qq-upload-failed-text">Mislukt</span>' +
							'<a class="qq-upload-cancel" href="#">Cancel</a>' +
							'<a class="remove">Verwijder</a>' +
							'</li>',
						multiple: multiple,
						maxItems: maxItems,
						onSubmit: function(id, filename){
							if(this.beforeUpload){
								this.beforeUpload($target, filename);
							}
							this.params = {
								'filename': filename
							};
							$target.parent('div').addClass('uploading');
						},
						onComplete: function(id, filename, responseJSON){
							var element = this.element;
							$(element).parent().removeClass('uploading').addClass('done-uploading');
							
							function checkCount(){
								var maxItems = $(element).data('max-items');
								var count = $('ul.qq-upload-list li', element).length;
								if (maxItems && maxItems == count) {
									$('.qq-upload-button-container', element).hide();
								} else {
									$('.qq-upload-button-container', element).show();
								}
							}
							checkCount();
							
							var ref = filename;
							
							if (responseJSON && responseJSON.id) {
								id = responseJSON.id;
								filename = responseJSON.filename;
								
								$('a.remove', element).unbind().bind('click', function(){
									var name = $(this).parent('li').find('span.qq-upload-file').text();
									$('input[data-filename="' + name + '"]', element).remove();
									$(this).parent('li').remove();
									checkCount();
								});
								
								$(element).append('<input type="hidden" value="' + id + '" name="' + name + '[]" data-filename="' + filename + '">');
								
								if(this.afterUpload){
									this.afterUpload(responseJSON, $target, ref);
								}
								
							} else {
								$('a.remove:last', element).click();
								Garp.FlashMessage({
									msg: __("Something went wrong while uploading. Please try again later")
								});
							}
						}
					};
					
					var uploaderConfig = Garp.apply(cfg, fh.uploaderConfig);
					var uploader = new qq.FileUploader(uploaderConfig);
					$(uploader._element).data('max-items', maxItems);
					if (cb && typeof cb === 'function') {
						cb($target);
					}
					if (fh.uploaderConfig.afterInit) {
						fh.uploaderConfig.afterInit(fh, $target);
					}
					
					// Add existing files to the list
					if (prepopulate) {
						for (var j = 0; j < prepopulate.length; j++) {
							var id = prepopulate[j].id;
							var filename = prepopulate[j].filename;
							// Create a fake onComplete call
							// @todo Is this really the way?
							var args = {};
							args = {
								id: id,
								filename: filename,
								fake: true
							};
							uploader._addToList(id, filename);
							uploaderConfig.onComplete.call({
								element: $target[0]
							}, args, args, args);
						}
					}
					if (fh.uploaderConfig.initComplete) {
						fh.uploaderConfig.initComplete(fh, $target);
					}
					
				});
			}, this);
		}
	},
	
	/**
	 * Turns the form into an Ajaxable thing
	 */
	ajaxify: function(){
		var fh = this, form = $(this.form);
		form.bind('submit', function(e){
			if (fh.validator && fh.validator.validateForm()) {
				e.preventDefault();
				if (fh.onBeforeAjax.call(fh)) {
					form.addClass(fh.ajaxBusyCls);
					$.ajax(form.attr('action'), {
						data: form.serializeArray(),
						type: form.attr('method'),
						dataType: fh.ajaxDataType,
						timeOut: fh.ajaxTimeout,
						complete: function(jqXhr, status){
							form.removeClass(fh.ajaxBusyCls);
							fh.onAjaxComplete.call(fh, jqXhr, status);
						}
					});
					return false;
				}
			}
		});
		return this;
	},
	
	/**
	 * Sets up duplicatable form elements
	 */
	setupDuplicators: function(){
		this._duplicators = [];
		var fh = this;
		$('.duplicatable', this.form).each(function(){
			fh._duplicators.push(new Garp.FormHelper.Duplicator(this, fh));
		});
	},

	/**
	 * Sets up validation
	 */	
	setupValidation: function(){
		
		var disabler = function(){
			this.disable(); // this == the submit button
		};
		
		this.validator = new Garp.FormHelper.Validator({
			form: this.form,
			listeners: {
				'formvalid': function(validator){
					$('button[type="submit"]', validator.form).bind('click.validator', disabler);
				},
				'forminvalid': function(validator){
					$('button[type="submit"]', validator.form).unbind('click.validator', disabler);
				},
				'fieldvalid': function(field){
					field.closest('div').removeClass('invalid').addClass('valid');
				},
				'fieldinvalid': function(field){
					field.closest('div').addClass('invalid').removeClass('valid');
				}
			}
		}).bind();
		
	},
	
	/**
	 * Placeholders aren't supported in older navigators 
	 */
	fixPlaceholdersIfNeeded: function(){
		var i = document.createElement('input');
		if (typeof i.placeholder === 'undefined') {
			Garp.asyncLoad(BASE + 'js/libs/jquery.addPlaceholder.js', 'js', function(){
				if ($('input[placeholder], textarea[placeholder]', this.form).length) {
					$('input[placeholder], textarea[placeholder]', this.form).addPlaceholder();
				}
				/*
				this.form.bind('submit', function(){
					$('input[placeholder], textarea[placeholder]', this.form).each(function(){
						if($(this).val() == $(this).attr('placeholder')){
							$(this).val('');
						}
					});
					return true;
				});*/
			}, this);
		}
	},

	/**
	 * Escape characters that would otherwise mess up a CSS selector
	 * ("[" and "]")
	 */
	formatName: function(name) {
		if (name) {
			name = name.replace('[', '\[').replace(']', '\]');
		}
		return name;
	},
	
	/**
	 * Init a.k.a go!
	 */
	init: function(cfg){
		Garp.apply(this, {
			uploaderConfig: {}
			// duplicatorConfig: {},  // not used at the moment.
			// validationConfig: {} // not used at the moment.
		});
		
		Garp.apply(this, cfg);
		
		this.setupDuplicators(this);
		this.hijackUploadFields(null, this);
		this.setupValidation(this);
		
		if(this.form.hasClass('ajax')){
			this.ajaxify();
		}
		this.fixPlaceholdersIfNeeded();
		return this;
	}
});


/**
 * Simple duplicate-a-field or fieldset
 * @param {Object} field the field to upgrade
 * @param {Object} fh FormHelper reference
 * @param {Object} cfg
 */
Garp.FormHelper.Duplicator = function(field, fh, cfg){

	Garp.apply(this, {
	
		/**
		 * @cfg {String} Text for the add button
		 */
		addText: __('Add'),
		
		/**
		 * @cfg {String} Text for the remove button
		 */
		removeText: __('Remove'),
		
		/**
		 * @cfg {String} extra ButtonClass
		 */
		buttonClass: '',
		buttonAddClass: '',
		buttonRemoveClass: '',

		/**
		 * @cfg {Function} Callback function
		 */
		afterDuplicate: null,
		
		// private:
		wrap: $(field).closest('div'),
		field: $(field),
		fh: fh,
		numOfFields: 1,
		maxItems: $(field).data('max-items') || false,
		newId: ($(field).attr('id') || 'garpfield-' + new Date().getTime()) + '-',
		skipElements: '',
		
		/**
		 * updateUI:
		 * Shows or hide add button based on data-max-items property of field
		 */
		updateUI: function(){
			if (this.maxItems) {
				if (this.numOfFields >= this.maxItems) {
					this.addButton.hide();
				} else {
					this.addButton.show();
				}
			}
		},
		
		/**
		 * Now do some duplication
		 * @param jQuery dupl This can be a DOM node acting as the duplicate
		 */
		duplicateField: function(dupl){
		
			if (this.field.attr('type') == 'file') {
				return;
			}
			var newId = this.newId + this.numOfFields;
			var newField;
			var isRuntimeDuplicate = typeof dupl == 'undefined';
			dupl = dupl || this.field.clone();
			dupl.addClass('duplicate');
			var scope = this;
			var numOfFields = this.numOfFields;
			
			// name="aap" -> name="aap[1]"
			function changeName(name){
				if (name[name.length - 1] == ']') {
					return name.replace(/\[*\d\]/, '[' + (parseInt(numOfFields, 10)) + ']');
				}
				return name;
			}
			
			// converts add button into remove button: 
			function setupRemoveBtn(dupl){
				var buttonClass = scope.buttonRemoveClass || scope.buttonClass;
				var removeBtn = $('<input class="remove ' + buttonClass + '" type="button" value="' + scope.removeText + '">');
				removeBtn.appendTo(dupl);
				removeBtn.bind('click', function(){
					if (dupl.is('fieldset')) {
						dupl.remove();
					} else {
						dupl.closest('div').prev('div').find('.duplicatable').focus();
						dupl.closest('div').remove();
					}
					scope.numOfFields--;
					scope.updateUI();
					if (scope.fh.validator) {
						scope.fh.validator.init();
					}
				});
			}
			
			if (this.field.is('fieldset')) {
				this.field.attr('id', newId);
			} else {
				dupl.find('[id]').attr('id', newId);
			}
			
			// file uploads:
			if (this.field.hasClass('file-input-wrapper')) {
				var name = $('input[type=text],input[type=hidden]', this.field).attr('name');
				dupl = $('<input type="file" />');
				dupl.insertAfter(this.field.parent('div'));
				dupl.addClass('hijack-upload duplicatable');
				dupl.wrap('<div></div>');
				newField = dupl;
				this.fh.hijackUploadFields(setupRemoveBtn, this.fh);
				
			} else {
				dupl.find('[for]').attr('for', newId);
				if (isRuntimeDuplicate) {
					dupl.find('.errors').remove();
				}
				if (dupl.attr('name')) {
					dupl.attr('name', changeName(dupl.attr('name')));
				}
				dupl.find('[name]').each(function(){
					$(this).attr('name', changeName($(this).attr('name')));
				});
				if (isRuntimeDuplicate) {
					dupl.find('input').not('[type="radio"], [type="checkbox"], [type="hidden"]').val('');
					var skipElements = this.skipElements.split(',');
					dupl.find('[name]').each(function() {
						var $this = $(this);
						var name  = $this.attr('name');
						for (var i = 0; i < skipElements.length; i++) {
							if (name == skipElements[i] || new RegExp('\\['+skipElements[i]+'\\]$').test(name)) {
								$this.remove();
								break;
							}
						}
					});
						
				}
				dupl.find('.invalid').removeClass('invalid');
				newField = dupl.find('.duplicatable').val('');
				
				setupRemoveBtn(dupl);
				
				dupl.insertBefore(this.addButton);

				if (this.fh.validator) {
					this.fh.validator.init();
				}
				
			}
			this.numOfFields++;
			this.updateUI();
			if (this.afterDuplicate) {
				var fnScope = this.afterDuplicate.split('.');
				for (var x = 0, fn = window, fnParent = window, fo; x < fnScope.length; x++) {
					fo = fnScope[x];
					// fnParent is always one step behind, ending up being the
					// "parent" object of the method, allowed for call() to be
					// used.
					fnParent = fn;
					if (typeof fn[fo] !== 'undefined') {
						fn = fn[fo];
					} else {
						throw new Error('Could not resolve callback path '+this.afterDuplicate);
					}
				}

				if (typeof fn == 'function') {
					fn.call(fnParent, dupl);
				} else {
					throw new Error('Given callback "'+this.afterDuplicate+'" is not a function.');
				}
			}
			if (isRuntimeDuplicate) {
				newField.focus();
			}
		},
		
		/**
		 * Create DOM & listeners
		 */
		createAddButton: function(){
			var that = this;
			var buttonClass = this.buttonAddClass || this.buttonClass;
			this.field.after('<input class="add ' + buttonClass + '" type="button" value="' + this.addText + '">');
			this.addButton = this.field.next('.add');
			this.addButton.bind('click', function(e) {
				that.duplicateField();
				e.preventDefault();
				return false;
			});
			return this;
		},
		
		/**
		 * Go !
		 */
		init: function(){
			// check if we're not already initialized
			if (this.field.attr('data-duplicator') == 'initialized') {
				return; // nothing to do!
			} 
			this.field.attr('data-duplicator', 'initialized');

			if (this.field.attr('data-button-class')) {
				this.buttonClass = this.field.attr('data-button-class');
			}
			if (this.field.attr('data-button-add-class')) {
				this.buttonAddClass = this.field.attr('data-button-add-class');
			}
			if (this.field.attr('data-button-add-text')) {
				this.addText = this.field.attr('data-button-add-text');
			}
			if (this.field.attr('data-button-remove-class')) {
				this.buttonRemoveClass = this.field.attr('data-button-remove-class');
			}
			if (this.field.attr('data-button-remove-text')) {
				this.removeText = this.field.attr('data-button-remove-text');
			}
			if (this.field.attr('data-skip-elements')) {
				this.skipElements = this.field.attr('data-skip-elements');
			}
			if (this.field.attr('data-after-duplicate')) {
				this.afterDuplicate = this.field.attr('data-after-duplicate');
			}
			this.createAddButton();
			var name = this.field.attr('name');
			var scope = this;
			if (!name) {
				// this is a fieldset. Find all elements in it to be renamed:
				$('[name]', this.field).each(function(){
					var name = $(this).attr('name');
					if (name[name.length - 1] !== ']') {
						$(this).attr('name', name + '[' + scope.numOfFields + ']');
					}
				});
				
				// find duplicates added by the server
				var classToLookFor = this.field.attr('class').split(' ')[0];
				this.wrap = this.field.wrap('<div class="fieldset-wrap"></div>').parent();
				this.addButton.detach().appendTo(this.wrap);
				// when the server adds duplicates, move these to the wrapper as
				// well
				var duplicate = this.wrap.next('.'+classToLookFor);
				while (duplicate.length) {
					this.duplicateField(duplicate);
					duplicate = this.wrap.next('.'+classToLookFor);
				}
			} else {
				// normal 'plain' field. Only change this name
				if (name[name.length - 1] !== ']') {
					this.field.attr('name', name + '[' + this.numOfFields + ']');
				}
			}
			return this;
		}
	});
	Garp.apply(this, cfg);
	return this.init();
};


/**
 * FormHelper Validator
 * @param {Object} cfg
 */
Garp.FormHelper.Validator = function(cfg){

	// Apply defaults:
	Garp.apply(this, {
		/**
		 * @cfg {jQuery} form, where to check for errors in:
		 */
		form: $('.garp-form'),
		
		/**
		 * @cfg {Array} tags to check. Might choose a subset via selectors e.g. input[type="checkbox"]
		 */
		elms: ['input', 'select', 'textarea'],
		
		/**
		 * @cfg {String}/{jQuery} msgTarget. "below" or jQuery selector where to put errors
		 */
		msgTarget: 'below',
		
		/**
		 * @cfg {String} If 'name' attr not set, use this text:
		 */
		missingNameText: __('This field'),
		
		/**
		 * @cfg {Bool} Whether or not to let the user know of validations error 'live'
		 */
		interactive: true
	});
	
	// Custom config:	
	Garp.apply(this, cfg);
	
	// Validator extends Garp.Observable
	Garp.apply(this, new Garp.Observable(this));
	
	// Internals:
	Garp.apply(this, {
	
		// global flag
		hasErrors: false,
		
		// Our collection of validation rules:
		rules: {
			
		// Required field validation:
			required: {
				init: function(field){
					if(field.parents('.multi-input.required').length){
						field.attr('required', 'required');
					}
				},
				fn: function(field){
					if (!field.attr('required')) {
						return true;
					}
					switch (field.attr('type')) {
						// grouped fields:
						case 'checkbox':
						case 'radio':
							var checked = false;
							var fields = $(this.elements).filter($('input[name="' + field.attr('name') + '"]'));
							fields.each(function(){
								if ($(this).attr('checked')) {
									checked = true;
								}
							});
							return checked;
						// single field:
						default:
							return $(field).val() !== '' && !$(field).hasClass('placeholder');
					}
				},
				errorMsg: __("Value is required and can't be empty")
			},
			
		// Simple email validation
			email: {
				fn: function(field){
					if (field.attr('type') == 'email' && field.val().length) {
						return (/^(\w+)([\-+.\'][\w]+)*@(\w[\-\w]*\.){1,5}([A-Za-z]){2,6}$/.test(field.val()));
					}
					return true;
				},
				errorMsg: __("'${1}' is not a valid email address in the basic format local-part@hostname")
			},
			
		// HTML5-pattern validation (RegExp)
			pattern: {
				RegExCache: {},
				fn: function(field){
					if (!field.attr('pattern') || !field.val().length) {
						return true;
					}
					var key = field.attr('pattern');
					var cache = this.rules.pattern.RegExCache;
					if(!cache[key]){ // compile regexes just once.
						cache[key] = new RegExp('^' + field.attr('pattern') + '$'); 
					}
					return cache[key].test(field.val());
				},
				errorMsg: __("'${1}' does not match against pattern '${2}'")
			},
		
		// URL
		url:{
			mailtoOrUrlRe: /(^mailto:(\w+)([\-+.][\w]+)*@(\w[\-\w]*))|((((^https?)|(^ftp)):\/\/)?([\-\w]+\.)+\w{2,3}(\/[%\-\w]+(\.\w{2,})?)*(([\w\-\.\?\\\/+@&#;`~=%!]*)(\.\w{2,})?)*\/?)/i,
			stricter: /(^mailto:(\w+)([\-+.][\w]+)*@(\w[\-\w]*))|(((^https?)|(^ftp)):\/\/([\-\w]+\.)+\w{2,3}(\/[%\-\w]+(\.\w{2,})?)*(([\w\-\.\?\\\/+@&#;`~=%!]*)(\.\w{2,})?)*\/?)/i,
			init: function(field){
				if(!field.attr('type') || field.attr('type') !== 'url'){
					return;
				}
				var scope = this;
				var mailtoOrUrlRe = this.rules.url.mailtoOrUrlRe;
				var stricter = this.rules.url.stricter;
				$(field).bind('blur.urlValidator', function(){
					if($(this).val() !== '' && !stricter.test($(this).val())){
						if ($.trim($(this).val()).substr(0, 7) !== 'http://') {
							$(this).val('http://' + $.trim($(this).val()));
						}
					}
				}).attr('data-force-validation-on-blur',field.attr('name'));
			}, fn: function(f){
				if(f.attr('type') == 'url' && f.val().length){
					return this.rules.url.mailtoOrUrlRe.test(f.val());
				}
				return true;
			},
			errorMsg: __("'${1}' is not a valid URL")
		},
		
		
		// Dutch postalcode and filter
			dutchPostalCode: {
				init: function(field){
					if(!field.hasClass('dutch-postal-code')){
						return;
					}
					// bind a 'filter' function before validation:
					$(field).attr('maxlength', 8).bind('blur.duthPostalcodeValidator', function(){
						var v = /(\d{4})(.*)(\w{2})/.exec($(this).val());
						if (v) {
							$(this).val(v[1] + ' ' + (v[3].toUpperCase()));
						}
					});
				},
				fn: function(field){
					if(!field.hasClass('dutch-postal-code') || !field.val().length){
						return true;
					}
					return (/^\d{4} \w{2}$/.test( field.val() ));
				},
				errorMsg: __("'${1}' is not a valid Dutch postcode")
			},
			
			identicalTo: {
				init: function(field){
					if (field.attr('data-identical-to')) {
						var name = Garp.FormHelper.formatName(field.attr('data-identical-to'));
						field.attr('data-force-validation-on-blur', name);
						var scope = this;
						$('[name="' + name + '"]').on('blur', function(){
							scope.validateField(field, scope.rules.identicalTo);
						});
					}
				},
				fn: function(field){
					if(!field.attr('data-identical-to') || !field.val().length){
						return true;
					}
					var name = field.attr('name');
					var theOtherName = Garp.FormHelper.formatName(field.attr('data-identical-to'));
					var theOtherField = $('[name="' + theOtherName + '"]');
					var oVal = theOtherField.val();
					
					return field.val() === oVal;
				},
				errorMsg: __("Value doesn't match")
			}
		},
		
		/**
		 * Rules might want to init; filter methods might be bound to various field events, for example
		 */
		initRules: function(){
			var elms = this.elements.toArray();
			var scope = this;
			Garp.each(this.rules, function(rule){
				if (rule.init) {
					Garp.each(elms, function(elm){
						rule.init.call(this, $(elm));
					}, this);
				}
			}, this);
		},
		
		/**
		 * Gives our rules a convenient number
		 */
		setRuleIds: function(){
			var c = 0;
			Garp.each(this.rules, function(rule){
				rule.id = c = c + 1;
			});
		},
		
		/**
		 * Returns the target element for error messages. It might create one first
		 * Possible to override and use a single msgTarget. Use @cfg msgTarget for this
		 * @param {DOM Element} field element
		 * @return {jQuery} Message Target ul
		 */
		getMsgTarget: function(field){
			var t;
			if (this.msgTarget === 'below') {
				if ($('ul.errors', $(field).closest('div')).length) {
					t = $('ul.errors', $(field).closest('div'));
				} else {
					t = $('<ul class="errors"></ul>').appendTo($(field).closest('div'));
				}
			} else {
				t = msgTarget;
			}
			return t;
		},
		
		/**
		 * Some fields need to be grouped: (One error for all 'related' fields)
		 * @param {DOM Element} field
		 * @param {Object} rule
		 * @return {jQuery} unique field
		 */
		getUniqueField: function(field, rule){
			var $field = $(field);
			// do group multi-input fields (radio / checkboxes)...
			if ($field.closest('div').find('.multi-input').length) { // fields are allways wrapped in <div> so we search for that one first.
				return Garp.FormHelper.formatName($field.attr('name')) + rule.id;
			}
			// ...but don't group duplicatable ones:
			return $field.attr('id') + rule.id;
		},
		
		/**
		 * Add an error message to a field
		 * @param {DOM Element} field
		 * @param {Object} rule
		 */
		setError: function(field, rule){
			var t = this.getMsgTarget(field);
			var uf = this.getUniqueField(field, rule);
			if (!$('li[data-error-id="' + uf + '"]').length) { // Don't we already have this message in place?
				var $field = $(field);
				var name = $field.attr('name') ? Garp.FormHelper.formatName($field.attr('name')) : this.missingNameText;
				var errorMsg = Garp.format(rule.errorMsg, $field.val() || '', name );
				t.first().append('<li data-error-id="' + uf + '">' + errorMsg.replace(/\[\]/, '') + '</li>');
			}
		},
		
		/**
		 * Clears errors
		 * @param {DOM Element} field
		 * @param {Object} rule
		 */
		clearError: function(field, rule){
			$('li[data-error-id="' + this.getUniqueField(field, rule) + '"]', this.form).remove();
		},
		
		// private
		_blurredElement: null,
		
		/**
		 * Live Validation events handlers:
		 */
		bindInteractiveHandlers: function(){
			var scope = this;
			this.elements.off('focus.validator blur.validator').on('focus.validator', function(){
				if (scope._blurredElement) {
					scope.validateField(scope._blurredElement);
				}
			}).on('blur.validator', function(){
				scope._blurredElement = this;
				if ($(this).attr('data-force-validation-on-blur')) {
					scope.validateField($(this).attr('data-force-validation-on-blur'));
				}
			});
		},
		
		/**
		 * Util: Adds a rule. A rule needs a fn propery {Function} and an errorMsg {String}
		 * @param {Object} ruleConfig
		 */
		addRule: function(ruleConfig){
			Garp.apply(this.rules, ruleConfig);
			this.setRuleIds();
			return this;
		},
		
		/**
		 * Util: Set a different message for a rule
		 * @param {Object} ruleName
		 * @param {Object} msg
		 */
		setRuleErrorMsg: function(ruleName, msg){
			if (this.rules[ruleName] && msg) {
				this.rules[ruleName].errorMsg = msg;
			}
			return this;
		},
		
		/**
		 * Validates a single field.
		 * @param {jQuery selector string} field
		 * @param {Validator Object} {optional} rule 
		 * @return {Bool} valid or not
		 */
		validateField: function(field, ruleObj){
			field = $(field);
			var valid = true;
			if (ruleObj && ruleObj.fn) {
				if(ruleObj.fn.call(this, field)){
					this.clearError(field, ruleObj);
				} else {
					this.setError(field, ruleObj);
					this.hasErrors = true;
					valid = false;
				}
			} else {
				Garp.each(this.rules, function(rule){
					if (rule.fn.call(this, field)) {
						this.clearError(field, rule);
					} else {
						this.setError(field, rule);
						this.hasErrors = true;
						valid = false;
					}
				}, this);
			}
			this.fireEvent(valid ? 'fieldvalid' : 'fieldinvalid', field);
			return valid;
		},
		
		/**
		 * Clear all error messages in bulk
		 */
		clearErrors: function(){
			$('ul.errors', this.form).remove();
			this.hasErrors = false;
			return this;
		},
		
		/**
		 * Validate the form!
		 * @return {Bool} valid or not
		 */
		validateForm: function(){
			this.hasErrors = false;
			Garp.each(this.elements.toArray(), this.validateField, this);
			this.fireEvent(this.hasErrors ? 'forminvalid' : 'formvalid', this);
			return !this.hasErrors;
		},
		
		/**
		 * Necessary for IE and placeholders:
		 * values might otherwise be sent to the server. yuck!
		 */
		cleanupPlaceholders: function(){
			Garp.each(this.elements.toArray(), function(f){
				f = $(f);
				if(f.attr('placeholder') && f.attr('placeholder') == f.val()){
					f.val('');
				}
			});
		},
		
		/**
		 * Necessary for IE and placeholders:
		 * Re-add the placeholders we just removed.
		 */
		resetPlaceholders: function(){
			Garp.each(this.elements.toArray(), function(f){
				$(f).addPlaceholder();
			});
		},
		
		/**
		 * Binds validateForm to submit or other event. Possible to bind this to a specific element
		 * @param {jQuery} element, defaults to @cfg form
		 * @param {String} event, defaults to 'submit'
		 */
		bind: function(elm, event){
			if (!elm) {
				elm = this.form;
			}
			var s = this;
			elm.on((event || 'submit') + '.validator', function(e){
				s.cleanupPlaceholders();
				if (!s.validateForm()) {
					if ($().addPlaceholder) {
						s.resetPlaceholders();
					}
					e.preventDefault();
					return false;
				}
				return true;
			});
			return s;
		},
		
		/**
		 * INITIALISE!
		 */
		init: function(){
			this.form.attr('novalidate', 'novalidate');
			this.elements = $(this.elms.join(', '), this.form);
			this.initRules();			
			this.setRuleIds();
			if (this.interactive) {
				this.bindInteractiveHandlers();
			}
			return this;
		}
	});
	return this.init();
};

/**
 * __ Utility function
 * @param {String} s
 * @return {String} s translated
 */ 
function __(s){ return Garp.locale[s] || s; }

// LOCALES:
Garp.locale = (typeof Garp.locale == 'undefined') ? {} : Garp.locale;
Garp.apply(Garp.locale, {

	// Garp.relativeDate
	'years': 'jaren',
	'year': 'jaar',
	'months': 'maanden',
	'month': 'maand',
	'weeks': 'weken',
	'week': 'week',
	'days': 'dagen',
	'day': 'dag',
	'hours': 'uur',
	'hour': 'uur',
	'minutes': 'minuten',
	'minute': 'minuut',
	'ago' : 'geleden',
	'less than a minute': 'minder dan een minuut',

	//Garp.FormHelper.Duplicator
	'Add': 'Toevoegen',
	'Remove': 'Verwijderen',

	//Garp.FormHelper.Validator
	// IMPORTANT: Keep synced with /garp/application/data/i18n/nl.php
	"'${1}' is not valid" : "'${1}' is niet geldig",
	"Value is required and can't be empty" : "Dit veld is verplicht",
	"'${1}' does not appear to be a postal code" : "'${1}' is geen geldige postcode",
	"'${1}' does not match against pattern '${2}'" : "Het ingevulde '${1}' komt niet overeen met het patroon voor veld '${2}",
	"'${1}' is not a valid email address in the basic format local-part@hostname" : "'${1}' is geen geldig e-mailadres in het formaat account@voorbeeld.nl",
	"'${1}' is not a valid Dutch postcode" : "'${1}' is geen geldige Nederlandse postcode",
	"'${1}' is not a valid URL" : "'${1}' is geen geldige URL",
	"Value doesn't match": "Waarde komt niet overeen",
	
	//qq.FileUploader
	"{file} has invalid extension. Only {extensions} are allowed.": "{file} heeft niet de juiste extensie. De volgende extensie(s) zijn toegestaan: {extensions}",
	"{file} is too large, maximum file size is {sizeLimit}.": "{file} is te groot. Maximum grootte is {sizeLimit}",
	"{file} is too small, minimum file size is {minSizeLimit}.": "{file} is te klein. Minimale grootte is {minSizeLimit}",
	"{file} is empty, please select files again without it.": "{file} is leeg.",
	"The files are being uploaded, if you leave now the upload will be cancelled.": "Bestanden worden geüpload. Het uploaden wordt onderbroken als u weggaat.",
	"Something went wrong while uploading. Please try again later" : "Er ging iets mis met uploaden. Probeer 't later opnieuw"
});


