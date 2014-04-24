/**
 * GoogleMaps
 * @package Garp
 * @TODO: refactor to use Garp class like structure 
 * 
 */

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