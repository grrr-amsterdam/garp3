/**
 * Garp Frontend library Core functions
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
 * Parse a query string
 * @return {Object} query params as key/value pairs
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
		elm: (function(){
			var id = 'flashMessage';
			return $('#' + id).length ? $('#' + id) : $('body').append('<div id="' + id + '"></div>').find('#' + id);
		})(this),
		
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
				Garp.setCookie(this.cookieName, '', exp, (typeof COOKIEDOMAIN !== 'undefined') ? COOKIEDOMAIN : '.' + document.location.host);
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
			if (!this.elm.length) {
				$('body').append(this.elm);
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
	if (domain) {
		value += "; domain=" + escape(domain);
	} else {
		value += "; domain=" + escape((typeof COOKIEDOMAIN !== 'undefined') ? COOKIEDOMAIN : '.' + document.location.host);
	}
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
	Garp.setCookie('Garp_Accept_Cookies', 'Garp_Accept_Cookies', exp, false);
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
 * __ Utility function
 * @param {String} s
 * @return {String} s translated
 */ 
function __(s){ return Garp.locale[s] || s; }

// LOCALES:
Garp.locale = (typeof Garp.locale == 'undefined') ? {} : Garp.locale;

/**
 * Get computed styles
 * @param {Array}
 */
(function( $ ) {

	var getComputedStyle = document.defaultView && document.defaultView.getComputedStyle,
		// The following variables are used to convert camelcased attribute names
		// into dashed names, e.g. borderWidth to border-width
		rupper = /([A-Z])/g,
		rdashAlpha = /-([a-z])/ig,
		fcamelCase = function( all, letter ) {
			return letter.toUpperCase();
		},
		// Returns the computed style for an elementn
		getStyle = function( elem ) {
			if ( getComputedStyle ) {
				return getComputedStyle(elem, null);
			}
			else if ( elem.currentStyle ) {
				return elem.currentStyle;
			}
		},
		// Checks for float px and numeric values
		rfloat = /float/i,
		rnumpx = /^-?\d+(?:px)?$/i,
		rnum = /^-?\d/;

	// Returns a list of styles for a given element
	$.styles = function( el, styles ) {
		if (!el ) {
			return null;
		}
		var  currentS = getStyle(el),
			oldName, val, style = el.style,
			results = {},
			i = 0,
			left, rsLeft, camelCase, name;

		// Go through each style
		for (; i < styles.length; i++ ) {
			name = styles[i];
			oldName = name.replace(rdashAlpha, fcamelCase);

			if ( rfloat.test(name) ) {
				name = jQuery.support.cssFloat ? "float" : "styleFloat";
				oldName = "cssFloat";
			}

			// If we have getComputedStyle available
			if ( getComputedStyle ) {
				// convert camelcased property names to dashed name
				name = name.replace(rupper, "-$1").toLowerCase();
				// use getPropertyValue of the current style object
				val = currentS.getPropertyValue(name);
				// default opacity is 1
				if ( name === "opacity" && val === "" ) {
					val = "1";
				}
				results[oldName] = val;
			} else {
				// Without getComputedStyles
				camelCase = name.replace(rdashAlpha, fcamelCase);
				results[oldName] = currentS[name] || currentS[camelCase];

				// convert to px
				if (!rnumpx.test(results[oldName]) && rnum.test(results[oldName]) ) {
					// Remember the original values
					left = style.left;
					rsLeft = el.runtimeStyle.left;

					// Put in the new values to get a computed value out
					el.runtimeStyle.left = el.currentStyle.left;
					style.left = camelCase === "fontSize" ? "1em" : (results[oldName] || 0);
					results[oldName] = style.pixelLeft + "px";

					// Revert the changed values
					style.left = left;
					el.runtimeStyle.left = rsLeft;
				}

			}
		}

		return results;
	};

	/**
	* @function jQuery.fn.styles
	* @parent jQuery.styles
	* @plugin jQuery.styles
	*
	* Returns a set of computed styles. Pass the names of the styles you want to
	* retrieve as arguments:
	*
	*      $("div").styles('float','display')
	*      // -> { cssFloat: "left", display: "block" }
	*
	* @param {String} style pass the names of the styles to retrieve as the argument list
	* @return {Object} an object of `style` : `value` pairs
	*/
	$.fn.styles = function() {
		// Pass the arguments as an array to $.styles
		return $.styles(this[0], $.makeArray(arguments));
	};
})(jQuery);

/**
 * CSS3 animations if supoprted
 */
(function ($) {

	// Overwrites `jQuery.fn.animate` to use CSS 3 animations if possible

	var
		// The global animation counter
		animationNum = 0,
		// The stylesheet for our animations
		styleSheet = null,
		// The animation cache
		cache = [],
		// Stores the browser properties like transition end event name and prefix
		browser = null,
		// Store the original $.fn.animate
		oldanimate = $.fn.animate,

		// Return the stylesheet, create it if it doesn't exists
		getStyleSheet = function () {
			if(!styleSheet) {
				var style = document.createElement('style');
				style.setAttribute("type", "text/css");
				style.setAttribute("media", "screen");

				document.getElementsByTagName('head')[0].appendChild(style);
				if (!window.createPopup) { /* For Safari */
					style.appendChild(document.createTextNode(''));
				}

				styleSheet = style.sheet;
			}

			return styleSheet;
		},

		//removes an animation rule from a sheet
		removeAnimation = function (sheet, name) {
			for (var j = sheet.cssRules.length - 1; j >= 0; j--) {
				var rule = sheet.cssRules[j];
				// 7 means the keyframe rule
				if (rule.type === 7 && rule.name == name) {
					sheet.deleteRule(j);
					return;
				}
			}
		},

		// Returns whether the animation should be passed to the original $.fn.animate.
		passThrough = function (props, ops) {
			var nonElement = !(this[0] && this[0].nodeType),
				isInline = !nonElement && $(this).css("display") === "inline" && $(this).css("float") === "none";

			for (var name in props) {
				// jQuery does something with these values
				if (props[name] == 'show' || props[name] == 'hide' || props[name] == 'toggle' || $.isArray(props[name]) || props[name] < 0 || name == 'zIndex' || name == 'z-index') {
					return true;
				}
			}

			return props.jquery === true || getBrowser() === null ||
				// Animating empty properties
				$.isEmptyObject(props) ||
				// We can't do custom easing
				ops.length == 4 || typeof ops[2] == 'string' ||
				// Second parameter is an object - we can only handle primitives
				$.isPlainObject(ops) ||
				// Inline and non elements
				isInline || nonElement;
		},

		// Gets a CSS number (with px added as the default unit if the value is a number)
		cssValue = function(origName, value) {
			if (typeof value === "number" && !$.cssNumber[ origName ]) {
				value += "px";
			}
			return value;
		},

		// Feature detection borrowed by http://modernizr.com/
		getBrowser = function(){
			if(!browser) {
				var t,
					el = document.createElement('fakeelement'),
					transitions = {
						'transition': {
							transitionEnd : 'transitionEnd',
							prefix : ''
						},
//						'OTransition': {
//							transitionEnd : 'oTransitionEnd',
//							prefix : '-o-'
//						},
//						'MSTransition': {
//							transitionEnd : 'msTransitionEnd',
//							prefix : '-ms-'
//						},
						'MozTransition': {
							transitionEnd : 'animationend',
							prefix : '-moz-'
						},
						'WebkitTransition': {
							transitionEnd : 'webkitAnimationEnd',
							prefix : '-webkit-'
						}
					};

				for(t in transitions){
					if( el.style[t] !== undefined ){
						browser = transitions[t];
					}
				}
			}
			return browser;
		},

		// Properties that Firefox can't animate if set to 'auto':
		// https://bugzilla.mozilla.org/show_bug.cgi?id=571344
		// Provides a converter that returns the actual value
		ffProps = {
			top : function(el) {
				return el.position().top;
			},
			left : function(el) {
				return el.position().left;
			},
			width : function(el) {
				return el.width();
			},
			height : function(el) {
				return el.height();
			},
			fontSize : function(el) {
				return '1em';
			}
		},

		// Add browser specific prefix
		addPrefix = function(properties) {
			var result = {};
			$.each(properties, function(name, value) {
				result[getBrowser().prefix + name] = value;
			});
			return result;
		},

		// Returns the animation name for a given style. It either uses a cached
		// version or adds it to the stylesheet, removing the oldest style if the
		// cache has reached a certain size.
		getAnimation = function(style) {
			var sheet, name, last;

			// Look up the cached style, set it to that name and reset age if found
			// increment the age for any other animation
			$.each(cache, function(i, animation) {
				if(style === animation.style) {
					name = animation.name;
					animation.age = 0;
				} else {
					animation.age += 1;
				}
			});

			if(!name) { // Add a new style
				sheet = getStyleSheet();
				name = "jquerypp_animation_" + (animationNum++);
				// get the last sheet and insert this rule into it
				sheet.insertRule("@" + getBrowser().prefix + "keyframes " + name + ' ' + style,
					(sheet.cssRules && sheet.cssRules.length) || 0);
				cache.push({
					name : name,
					style : style,
					age : 0
				});

				// Sort the cache by age
				cache.sort(function(first, second) {
					return first.age - second.age;
				});

				// Remove the last (oldest) item from the cache if it has more than 20 items
				if(cache.length > 20) {
					last = cache.pop();
					removeAnimation(sheet, last.name);
				}
			}

			return name;
		};

	/**
	* @function $.fn.animate
	* @parent $.animate
	*
	* Animate CSS properties using native CSS animations, if possible.
	* Uses the original [$.fn.animate()](http://api.$.com/animate/) otherwise.
	*
	* @param {Object} props The CSS properties to animate
	* @param {Integer|String|Object} [speed=400] The animation duration in ms.
	* Will use $.fn.animate if a string or object is passed
	* @param {Function} [callback] A callback to execute once the animation is complete
	* @return {jQuery} The jQuery element
	*/
	$.fn.animate = function (props, speed, easing, callback) {
		//default to normal animations if browser doesn't support them
		if (passThrough.apply(this, arguments)) {
			return oldanimate.apply(this, arguments);
		}

		var optall = jQuery.speed(speed, easing, callback);

		// Add everything to the animation queue
		this.queue(optall.queue, function(done) {
			var
				//current CSS values
				current,
				// The list of properties passed
				properties = [],
				to = "",
				prop,
				self = $(this),
				duration = optall.duration,
				//the animation keyframe name
				animationName,
				// The key used to store the animation hook
				dataKey,
				//the text for the keyframe
				style = "{ from {",
				// The animation end event handler.
				// Will be called both on animation end and after calling .stop()
				animationEnd = function (currentCSS, exec) {
					self.css(currentCSS);
					
					self.css(addPrefix({
						"animation-duration" : "",
						"animation-name" : "",
						"animation-fill-mode" : "",
						"animation-play-state" : ""
					}));

					// Call the original callback
					if (optall.old && exec) {
						// Call success, pass the DOM element as the this reference
						optall.old.call(self[0], true);
					}

					$.removeData(self, dataKey, true);
				};

			for(prop in props) {
				properties.push(prop);
			}

			if(getBrowser().prefix === '-moz-') {
				// Normalize 'auto' properties in FF
				$.each(properties, function(i, prop) {
					var converter = ffProps[$.camelCase(prop)];
					if(converter && self.css(prop) == 'auto') {
						self.css(prop, converter(self));
					}
				});
			}

			// Use $.styles
			current = self.styles.apply(self, properties);
			$.each(properties, function(i, cur) {
				// Convert a camelcased property name
				var name = cur.replace(/([A-Z]|^ms)/g, "-$1" ).toLowerCase();
				style += name + " : " + cssValue(cur, current[cur]) + "; ";
				to += name + " : " + cssValue(cur, props[cur]) + "; ";
			});

			style += "} to {" + to + " }}";

			animationName = getAnimation(style);
			dataKey = animationName + '.run';

			// Add a hook which will be called when the animation stops
			$._data(this, dataKey, {
				stop : function(gotoEnd) {
					// Pause the animation
					self.css(addPrefix({
						'animation-play-state' : 'paused'
					}));
					// Unbind the animation end handler
					self.off(getBrowser().transitionEnd, animationEnd);
					if(!gotoEnd) {
						// We were told not to finish the animation
						// Call animationEnd but set the CSS to the current computed style
						animationEnd(self.styles.apply(self, properties), false);
					} else {
						// Finish animaion
						animationEnd(props, true);
					}
				}
			});

			// set this element to point to that animation
			self.css(addPrefix({
				"animation-duration" : duration + "ms",
				"animation-name" : animationName,
				"animation-fill-mode": "forwards"
			}));

			// Attach the transition end event handler to run only once
			self.one(getBrowser().transitionEnd, function() {
				// Call animationEnd using the passed properties
				animationEnd(props, true);
				done();
			});

		});

		return this;
	};
})(jQuery);