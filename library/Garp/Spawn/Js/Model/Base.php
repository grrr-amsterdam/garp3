<?php
/**
 * Generated JS Base model
 *
 * @package Garp_Spawn_Js_Model
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Js_Model_Base extends Garp_Spawn_Js_Model_Abstract
    implements Garp_Spawn_Js_Model_Interface
{
    protected $_template = 'base_model.phtml';

    public function render() {
        $out = parent::render();
        return $this->_shouldMinifyModels() ? $this->_minify($out) : $out;
    }
}
