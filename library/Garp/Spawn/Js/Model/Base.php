<?php
/**
 * Generated JS Base model
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
class Garp_Spawn_Js_Model_Base extends Garp_Spawn_Js_Model_Abstract implements Garp_Spawn_Js_Model_Interface {
	protected $_template = 'base_model.phtml';


	public function render() {
		return $this->_minify(parent::render());
	}
}