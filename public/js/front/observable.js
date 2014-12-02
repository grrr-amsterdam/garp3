/**
 * @class Observable
 * @package Garp
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