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
		
		if (u === false) return e.childNodes;
		
		var f = r.cloneNode(true), i = e.childNodes.length;
		while (i--) f.appendChild(e.firstChild);
		
		return f;
	}
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
		if (!receiverObj.hasOwnProperty(i)) {
			receiverObj[i] = senderObj[i];
		}
	}
	return receiverObj;
}

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
Garp.format = function(tpl){
	for (var res = tpl, i = 1, l = arguments.length; i < l; i++) {
		res = res.replace(new RegExp("\\${" + i +"\\}","g"), arguments[i]);
	}
	return res;
};

/**
 * Utility function each
 * Calls fn for each ownProperty of obj. fn(property, iterator, obj)
 * 
 * @param {Object} obj
 * @param {Function} fn 
 */
Garp.each = function(obj, fn){
	for(var i in obj){
		if (obj.hasOwnProperty(i)) {
			fn(obj[i], i, obj);
		}
	}
}

/**
 * Creates a Delegate function
 * @param {Function} function
 * @param {Object} scope
 */
Garp.createDelegate = function(fn, scope){
	return function(){
		fn.apply(scope, arguments);
	};
}

/**
 * 
 */
Garp.parseQueryString = function(str, decode) {
    var str = str || window.location.search;
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
Garp.Observable = function(){
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
		
		this.on.events = typeof this.on.events == 'undefined' ? [] : this.on.events;
		this.on.events[event] = typeof this.on.events[event] == 'undefined' ? [] : this.on.events[event];
		
		this.on.events[event].push({
			'eventName': event,
			handler: handler,
			scope: scope
		});
	};
	
	this.addListeners = function(eventsConfig){
		Garp.each(eventsConfig, function(event){
			this.on(event.event, event.fn, event.scope);
		});
	}
	
	/**
	 * Removes an event handler
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
		if(typeof this.on.events == 'undefined'){
			return;
		}
		if(!options){
			options = {};
		}
		Garp.each(this.on.events[eventName], function(obj){
			typeof obj.handler == 'function' ? obj.handler.call(obj.scope, options) : true;
		});
	};
	
};

/**
 * @class Transition
 * @param {Object} Browsebox reference
 * @param {String} Transition name (internal function reference)
 */
Garp.Transition = function(bb, transitionName){
	
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
		$('#' + this.id).html(Garp.innerShiv(data));
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
			var url = BASE + this.BROWSEBOX_URL;
			url += this.id + '/' + chunk + '/' +
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
			
			// 'cause browsers remember certain form values, there needs to be one manual check.
			setTimeout(function() {
				if ($(input).val()) {
					self.focus.call(input, thisLabel);
				}
			}, 1000);
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
			if (!email.test(elm.val())) {
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
}

/**
 * Stub i18n
 * @param {Object} s
 */
function __(s) { return s; }

/**
 * Garp.flashMessage
 */
Garp.flashMessage = (function(){
	
	/**
	 * Override Garp.flashMessage.delay to have a different delay
	 */
	this.delay = 6000; // 6 seconds
	this.elm = null;
	 
	/**
	 * Override Garp.flashMessage.animate to have a different animation:
	 * Eg:
	 *	Garp.flashMessage.animate = function(){
	 *		this.elm.fadeOut();
	 *	}
	 */
	this.animate = function(){
		this.elm.hide();
	};
	
	var flash = this;
	flash.init = function(){
		if (!flash.elm) {
			flash.elm = $('#flashMessage');
		}
		flash.elm.bind('click', function(){
			flash.animate();
		});
		setTimeout(function(){
			flash.animate();
		}, flash.delay);
	}
	$(window).bind('load',flash.init);
	
	return this;
})();

/**
 * cookie flashMessage things
 */
$(function(){
	var m = $.parseJSON(unescape(Garp.getCookie('FlashMessenger')));
	if (m && m.messages) {
		$('body').append('<div id="flashMessage"></div>');
		for (var i in m.messages) {
			var msg = m.messages[i];
			msg = msg.replace(/\+/g, ' ');
			$('#flashMessage').append('<p>' + msg + '</p>');
		}
		var exp = new Date();
		exp.setHours(exp.getHours() - 1);
// note; a global COOKIEDOMAIN can be set for this function.
		Garp.setCookie('FlashMessenger', '', exp, (typeof COOKIEDOMAIN !== 'undefined') ? COOKIEDOMAIN : '.' + document.location.host);
		Garp.flashMessage.init();
	}
});


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
		zoom: parseInt(config.zoom)
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
			}
		}
		
		$(this).wrap('<div class="g-googlemap-wrap"></div>');
		var wrap = $(this).parent('.g-googlemap-wrap').width(mapProperties.width).height(mapProperties.height);
		Garp.buildGoogleMap(wrap[0], mapProperties);	
	});
});

/**
 * YouTube ready: remove iframe with alternative YouTube rendering. Chrome renders both otherwise.
 */
function onYouTubePlayerReady(){
	$(function(){
		$('iframe.youtube-player').remove();
	});
}

/**
 * Grab a Cookie
 * @param {Object} name
 */
Garp.getCookie = function(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

/**
 * Give a Cookie
 * @param {Object} name
 * @param {Object} value
 * @param {Date} expiration date 
 * @param {String} domain
 */
Garp.setCookie = function(name, value, date, domain) {
	value = escape(value) + "; path=/" 
	if (domain) {
		value += "; domain="+escape(domain);
	}
	value += ((date==null) ? "" : "; expires="+date.toGMTString());
	document.cookie=name + "=" + value;
}

/**
 * Snippet edit links
 */
$(function(){
	var authCookie = unescape(Garp.getCookie('Garp_Auth'));
	authCookie = jQuery.parseJSON(authCookie);
	if (authCookie && 'userData' in authCookie && 'role' in authCookie['userData']) {
		var role = authCookie['userData']['role'];
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
									opacity: .5,
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
 * Garp relative Date
 * Returns the date or time difference in HRF™ (Human Readable Format)
 */
Garp.relativeDate = function(oldest, newest){
	oldest = new Date(oldest + '');
	newest = new Date(newest + '');
	
	var elapsed = Math.abs(oldest.getTime() - newest.getTime()); // milliseconds
	
	if(isNaN(elapsed)){
		return '';
	}
	
	var elapsed = elapsed / 60000; // minutes
	var result = '';
	
	switch (true) {
		case (elapsed < 1):
			result = __('less than a minute');
			break;
			
		case (elapsed < (60)):
			var minutes = Math.round(elapsed);
			result = minutes + ' ' + (minutes == 1 ? __('minute') : __('minutes'));
			break;
			
		case (elapsed < (60 * 24)):
		
			var hours = Math.round(elapsed / 60);
			result = hours + ' ' + (hours == 1 ? __('hour') : __('hours'));
			break;
			
		case (elapsed < (60 * 24 * 7)):
			var days = Math.round(elapsed / (60 * 24));
			result = days + ' ' + (days == 1 ? __('day') : __('days'));
			break;
			
		case (elapsed < (60 * 24 * 7 * 30)):
			var weeks = Math.round(elapsed / (60 * 24 * 7));
			result = weeks + ' ' + (weeks == 1 ? __('week') : __('weeks'));
			break;
			
		case (elapsed < (60 * 24 * 7 * 30 * 12)):
			var months = Math.round(elapsed / (60 * 24 * 7 * 30));
			result = months + ' ' + (months == 1 ? __('month') : __('months'));
			break;
			
		default:
			var years = Math.round(elapsed / (60 * 24 * 7 * 30 * 365));
			result = years + ' ' + (years == 1 ? __('year') : __('years'));
			break;
			
	}
	return result + ' ' +__('ago');
}

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
	}
	
	// private //
	this.parseResponse = function(response){
		var result = [];
		if (response) {
			if (response.results) { // search results
				for (var item in response.results) {
					if (response.results[item].text) {
						result.push(Garp.format(this.searchTpl, response.results[item].profile_image_url, response.results[item].from_user, response.results[item].text.replace(new RegExp('(http://[^ ]+)', "g"), '<a target="_blank" href="$1">$1</a>'), Garp.relativeDate(response.results[item].created_at, new Date()), response.results[item].from_user));
					}
				}
			} else { // list results
				var c = 1;
				for (var item in response) {
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
}

/*!
 * selectivizr v1.0.2 - (c) Keith Clark, freely distributable under the terms of the MIT license.
 * selectivizr.com
 */
(function(j){function A(a){return a.replace(B,h).replace(C,function(a,d,b){for(var a=b.split(","),b=0,e=a.length;b<e;b++){var s=D(a[b].replace(E,h).replace(F,h))+o,l=[];a[b]=s.replace(G,function(a,b,c,d,e){if(b){if(l.length>0){var a=l,f,e=s.substring(0,e).replace(H,i);if(e==i||e.charAt(e.length-1)==o)e+="*";try{f=t(e)}catch(k){}if(f){e=0;for(c=f.length;e<c;e++){for(var d=f[e],h=d.className,j=0,m=a.length;j<m;j++){var g=a[j];if(!RegExp("(^|\\s)"+g.className+"(\\s|$)").test(d.className)&&g.b&&(g.b===!0||g.b(d)===!0))h=u(h,g.className,!0)}d.className=h}}l=[]}return b}else{if(b=c?I(c):!v||v.test(d)?{className:w(d),b:!0}:null)return l.push(b),"."+b.className;return a}})}return d+a.join(",")})}function I(a){var c=!0,d=w(a.slice(1)),b=a.substring(0,5)==":not(",e,f;b&&(a=a.slice(5,-1));var l=a.indexOf("(");l>-1&&(a=a.substring(0,l));if(a.charAt(0)==":")switch(a.slice(1)){case "root":c=function(a){return b?a!=p:a==p};break;case "target":if(m==8){c=function(a){function c(){var d=location.hash,e=d.slice(1);return b?d==i||a.id!=e:d!=i&&a.id==e}k(j,"hashchange",function(){g(a,d,c())});return c()};break}return!1;case "checked":c=function(a){J.test(a.type)&&k(a,"propertychange",function(){event.propertyName=="checked"&&g(a,d,a.checked!==b)});return a.checked!==b};break;case "disabled":b=!b;case "enabled":c=function(c){if(K.test(c.tagName))return k(c,"propertychange",function(){event.propertyName=="$disabled"&&g(c,d,c.a===b)}),q.push(c),c.a=c.disabled,c.disabled===b;return a==":enabled"?b:!b};break;case "focus":e="focus",f="blur";case "hover":e||(e="mouseenter",f="mouseleave");c=function(a){k(a,b?f:e,function(){g(a,d,!0)});k(a,b?e:f,function(){g(a,d,!1)});return b};break;default:if(!L.test(a))return!1}return{className:d,b:c}}function w(a){return M+"-"+(m==6&&N?O++:a.replace(P,function(a){return a.charCodeAt(0)}))}function D(a){return a.replace(x,h).replace(Q,o)}function g(a,c,d){var b=a.className,c=u(b,c,d);if(c!=b)a.className=c,a.parentNode.className+=i}function u(a,c,d){var b=RegExp("(^|\\s)"+c+"(\\s|$)"),e=b.test(a);return d?e?a:a+o+c:e?a.replace(b,h).replace(x,h):a}function k(a,c,d){a.attachEvent("on"+c,d)}function r(a,c){if(/^https?:\/\//i.test(a))return c.substring(0,c.indexOf("/",8))==a.substring(0,a.indexOf("/",8))?a:null;if(a.charAt(0)=="/")return c.substring(0,c.indexOf("/",8))+a;var d=c.split(/[?#]/)[0];a.charAt(0)!="?"&&d.charAt(d.length-1)!="/"&&(d=d.substring(0,d.lastIndexOf("/")+1));return d+a}function y(a){if(a)return n.open("GET",a,!1),n.send(),(n.status==200?n.responseText:i).replace(R,i).replace(S,function(c,d,b,e,f){return y(r(b||f,a))}).replace(T,function(c,d,b){d=d||i;return" url("+d+r(b,a)+d+") "});return i}function U(){var a,c;a=f.getElementsByTagName("BASE");for(var d=a.length>0?a[0].href:f.location.href,b=0;b<f.styleSheets.length;b++)if(c=f.styleSheets[b],c.href!=i&&(a=r(c.href,d)))c.cssText=A(y(a));q.length>0&&setInterval(function(){for(var a=0,c=q.length;a<c;a++){var b=q[a];if(b.disabled!==b.a)b.disabled?(b.disabled=!1,b.a=!0,b.disabled=!0):b.a=b.disabled}},250)}if(!/*@cc_on!@*/true){var f=document,p=f.documentElement,n=function(){if(j.XMLHttpRequest)return new XMLHttpRequest;try{return new ActiveXObject("Microsoft.XMLHTTP")}catch(a){return null}}(),m=/MSIE (\d+)/.exec(navigator.userAgent)[1];if(!(f.compatMode!="CSS1Compat"||m<6||m>8||!n)){var z={NW:"*.Dom.select",MooTools:"$$",DOMAssistant:"*.$",Prototype:"$$",YAHOO:"*.util.Selector.query",Sizzle:"*",jQuery:"*",dojo:"*.query"},t,q=[],O=0,N=!0,M="slvzr",R=/(\/\*[^*]*\*+([^\/][^*]*\*+)*\/)\s*/g,S=/@import\s*(?:(?:(?:url\(\s*(['"]?)(.*)\1)\s*\))|(?:(['"])(.*)\3))[^;]*;/g,T=/\burl\(\s*(["']?)(?!data:)([^"')]+)\1\s*\)/g,L=/^:(empty|(first|last|only|nth(-last)?)-(child|of-type))$/,B=/:(:first-(?:line|letter))/g,C=/(^|})\s*([^\{]*?[\[:][^{]+)/g,G=/([ +~>])|(:[a-z-]+(?:\(.*?\)+)?)|(\[.*?\])/g,H=/(:not\()?:(hover|enabled|disabled|focus|checked|target|active|visited|first-line|first-letter)\)?/g,P=/[^\w-]/g,K=/^(INPUT|SELECT|TEXTAREA|BUTTON)$/,J=/^(checkbox|radio)$/,v=m>6?/[\$\^*]=(['"])\1/:null,E=/([(\[+~])\s+/g,F=/\s+([)\]+~])/g,Q=/\s+/g,x=/^\s*((?:[\S\s]*\S)?)\s*$/,i="",o=" ",h="$1";(function(a,c){function d(){try{p.doScroll("left")}catch(a){setTimeout(d,50);return}b("poll")}function b(d){if(!(d.type=="readystatechange"&&f.readyState!="complete")&&((d.type=="load"?a:f).detachEvent("on"+d.type,b,!1),!e&&(e=!0)))c.call(a,d.type||d)}var e=!1,g=!0;if(f.readyState=="complete")c.call(a,i);else{if(f.createEventObject&&p.doScroll){try{g=!a.frameElement}catch(h){}g&&d()}k(f,"readystatechange",b);k(a,"load",b)}})(j,function(){for(var a in z){var c,d,b=j;if(j[a]){for(c=z[a].replace("*",a).split(".");(d=c.shift())&&(b=b[d]););if(typeof b=="function"){t=b;U();break}}}})}}})(this);

(function(document){
	window.MBP = window.MBP || {}; 

	// Fix for iPhone viewport scale bug
	// http://www.blog.highub.com/mobile-2/a-fix-for-iphone-viewport-scale-bug/
	MBP.viewportmeta = document.querySelector && document.querySelector('meta[name="viewport"]');
	MBP.ua = navigator.userAgent;
 	 
	MBP.scaleFix = function () {
  	  if (MBP.viewportmeta && /iPhone|iPad/.test(MBP.ua) && !/Opera Mini/.test(MBP.ua)) {
    	MBP.viewportmeta.content = "width=device-width, minimum-scale=1.0, maximum-scale=1.0";
    	document.addEventListener("gesturestart", MBP.gestureStart, false);
  	  }
	};
	MBP.gestureStart = function () {
    	MBP.viewportmeta.content = "width=device-width, minimum-scale=0.25, maximum-scale=1.6";
	};

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
    	element.addEventListener ? element.addEventListener('keyup', handler, false) :
                               	   element.attachEvent('onkeyup', handler);
	};	
})(document);
