<?php
/**
 * Garp_Browsebox_Factory
 * class description
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Browsebox
 * @lastmodified $Date: $
 */
abstract class Garp_Browsebox_Factory {
	/**
	 * Return a Garp_Browsebox object based on App_Browsebox_Config.
	 * This factory assumes its existence, so an error will be thrown 
	 * if it cannot be loaded.
	 * @param String $id The browsebox id
	 * @return Garp_Browsebox
	 */
	public final function create($id) {
		$method = 'create'.ucfirst($id);
		if (method_exists($this, $method)) {
			return $this->$method();
		}
		throw new Garp_Browsebox_Exception('No initialization method for id "'.$id.'"');
	}
}