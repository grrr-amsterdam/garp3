<?php 
/**
 * Class GoogleStaticMapHelper
 * 
 * provides a static image of a map with the help of the static-google map
 * garp.front.js will then convert the map to a dynamic variant
 * 
 * @author Peter
 *
 */
class G_View_Helper_GoogleStaticMap extends Zend_View_Helper_Abstract {

	protected $_defaults = array(
		'location' => array(		// center location
			'lat' => '52.090142', 
			'lng' => '5.109665'
		),
		'mapType' => 'roadmap', 	// roadmap / satellite / terain / hybrid  
		'zoomLevel' => 11, 			// 0 - 21 (earth - building)
		'width' => 320,
		'height' => 240,
		'altText' => 'Google Map',  // alt Text to display
		'sensor' => false,			// whether or not to get browser's location (prob. geoIP based)
		'markers' => array() 		// array(array('lat' => '52.090142', 'lng' => '5.109665'))
	);
	
	/**
	 * init
	 */
	public function googleStaticMap(array $config = null) {
		if (!is_null($config)) {
			return $this->render($config);
		}
		return $this;
	}
	
	/**
	 * walks through markers' array
	 * @TODO: implement other marker options
	 */
	public function getMarkersAsString($options){
		$markers = '';
		
		foreach($options['markers'] as $marker){
			$markers .= $marker['lat'] . ',' . $marker['lng'] . '|';
		}		
		
		return $markers ? substr($markers, 0, -1) : '';
	}
	
	
	/**
	 * render
	 */
	public function render($options = array()) {
		$options = array_merge($this->_defaults, $options);
		$markers = $this->getMarkersAsString($options);
		$img = '';
		
		$img .= '<img src="http://maps.google.com/maps/api/staticmap';
		$img .= '?center=' . $options['location']['lat'] . ',' . $options['location']['lng'];
		$img .= '&amp;zoom=' . $options['zoomLevel'];
		$img .= '&amp;size=' . $options['width'] . 'x' . $options['height'];
		$img .= '&amp;maptype=' . $options['mapType'];
		$img .= ($markers ? '&amp;markers=' . $markers : '');
		$img .= '&amp;sensor=' . ($options['sensor'] ? 'true' : 'false') . '" ';
		$img .= 'width="' . $options['width'] . '" height="' . $options['height'] . '" alt="' . $options['altText']. '" class="g-googlemap" />';
		
		$this->view->script()->src('http://www.google.com/maps/api/js?sensor=' . ($options['sensor'] ? 'true' : 'false'));
		return $img;
	}
	
}