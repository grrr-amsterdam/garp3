/**
 * scrollHandler
 * @package Garp 
 */
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