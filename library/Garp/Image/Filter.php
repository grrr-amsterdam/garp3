<?php
/**
 * Garp_Image_Scaler
 * Applies processing filters to images
 * @author David Spreekmeester | Grrr.nl
 * @package Garp
 */
class Garp_Image_Filter {
	static public function filter(&$image, $type) {
		switch ($type) {
			case 'grayscale':
				imagefilter($image, IMG_FILTER_GRAYSCALE);
			break;
			default:
				throw new Exception('Sorry, you provided a filter type unknown to me ('.$type.')');
		}
	}
}