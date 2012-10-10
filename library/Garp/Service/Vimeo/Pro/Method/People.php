<?php
/**
 * Garp_Service_Vimeo_Pro_Method_People
 * Vimeo Pro API wrapper around People methods.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Vimeo
 * @lastmodified $Date: $
 */
class Garp_Service_Vimeo_Pro_Method_People extends Garp_Service_Vimeo_Pro_Method_Abstract {
	/**
	 * Get information about a user.
 	 * @param String $user_id The ID number or username of the user. A token may be used instead.
 	 * @return Array
 	 */
	public function getInfo($user_id) {
		$person = $this->request('people.getInfo', array(
			'user_id' => $user_id
		));
		return $person['person'];
	}
}
