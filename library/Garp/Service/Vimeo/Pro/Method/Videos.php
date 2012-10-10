<?php
/**
 * Garp_Service_Vimeo_Pro_Method_Videos
 * Vimeo Pro API wrapper around Videos methods.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Vimeo
 * @lastmodified $Date: $
 */
class Garp_Service_Vimeo_Pro_Method_Videos extends Garp_Service_Vimeo_Pro_Method_Abstract {
	/**
 	 * Add a specified user as a cast member to the video.
 	 * @param String $user_id The user to add as a cast member.
 	 * @param String $video_id The video to add the cast member to.
 	 * @param String $role The role of the user in the video.
 	 * @return Array
 	 */
	public function addCast($user_id, $video_id, $role = null) {
		if (!$this->getAccessToken()) {
			throw new Garp_Service_Vimeo_Exception('This method requires an authenticated user. '.
				'Please provide an access token.');
		}
		$params = array('user_id' => $user_id, 'video_id' => $video_id);
		if ($role) {
			$params['role'] = $role;
		}
		$response = $this->request('videos.addCast', $params);
		return $response;
	}


	/**
     * Get lots of information on a video.
 	 * @param String $video_id The ID of the video.
 	 * @return Array
 	 */
	public function getInfo($video_id) {
		$video = $this->request('videos.getInfo', array(
			'video_id' => $video_id
		));
		return $video['video'];
	}
}
