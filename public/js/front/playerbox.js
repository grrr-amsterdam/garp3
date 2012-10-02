/**
 * playerBox
 * @package Garp
 */

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
