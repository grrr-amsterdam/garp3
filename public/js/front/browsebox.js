/**
 * @class Transition
 *        &
 * @class Browsebox
 * 
 * @requires Garp.observable
 * @package Garp
 */

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