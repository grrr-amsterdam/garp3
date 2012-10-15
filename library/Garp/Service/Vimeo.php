<?php
/**
 * Garp_Service_Vimeo
 * Vimeo API wrapper. For the time being, only the Simple API is supported (@see http://www.vimeo.com/api/docs/simple-api)
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Vimeo
 * @lastmodified $Date: $
 */
class Garp_Service_Vimeo extends Zend_Service_Abstract {
	/**
	 * API Url
	 * @var String
	 */
	const VIMEO_API_URL = 'http://vimeo.com/api/v2/';
	
	
	/**
	 * Make a User request
	 * @param String $username
	 * @param String $request
	 * @return Array
	 */
	public function user($username, $request) {
		$options = array(
			'info', 'videos', 'likes', 'appears_in',
			'all_videos', 'subscriptions', 'albums',
			'channels', 'groups', 'contacts_videos',
			'contacts_like'
		);
		if (!in_array($request, $options)) {
			throw new Garp_Service_Vimeo_Exception('Invalid request. Available options are '.implode(', ', $options));
		}
		return $this->request($username.'/'.$request);
	}
	
	
	/**
	 * Make a Video request
	 * @param String $videoId Video id or Vimeo URL
	 * @return Array
	 */
	public function video($videoId) {
		// check if a Vimeo URL is given
		if (preg_match('~vimeo.com/([0-9]+)~', $videoId, $matches)) {
			$videoId = $matches[1];
		}
		return $this->request('video/'.$videoId);
	}
	
	
	/**
	 * Make an Activity request
	 * @param String $username
	 * @param String $request
	 * @return Array
	 */
	public function activity($username, $request) {
		$options = array(
			'user_did', 'happened_to_user', 'contacts_did', 'happened_to_contacts', 'everyone_did'
		);
		if (!in_array($request, $options)) {
			throw new Garp_Service_Vimeo_Exception('Invalid request. Available options are '.implode(', ', $options));
		}
		return $this->request('activity/'.$username.'/'.$request);
	}
	
	
	/**
	 * Make a Group request
	 * @param String $groupname
	 * @param String $request
	 * @return Array
	 */
	public function group($groupname, $request) {
		$options = array(
			'videos', 'users', 'info'
		);
		if (!in_array($request, $options)) {
			throw new Garp_Service_Vimeo_Exception('Invalid request. Available options are '.implode(', ', $options));
		}
		return $this->request('group/'.$groupname.'/'.$request);
	}
	
	
	/**
	 * Make a Channel request
	 * @param String $channel
	 * @param String $request
	 * @return Array
	 */
	public function channel($channel, $request) {
		$options = array(
			'videos', 'info'
		);
		if (!in_array($request, $options)) {
			throw new Garp_Service_Vimeo_Exception('Invalid request. Available options are '.implode(', ', $options));
		}
		return $this->request('channel/'.$channel.'/'.$request);
	}
	
	
	/**
	 * Make a Album request
	 * @param Int $albumId
	 * @param String $request
	 * @return Array
	 */
	public function album($albumId, $request) {
		$options = array(
			'videos', 'info'
		);
		if (!in_array($request, $options)) {
			throw new Garp_Service_Vimeo_Exception('Invalid request. Available options are '.implode(', ', $options));
		}
		return $this->request('album/'.$albumId.'/'.$request);
	}
	

	/**
	 * Send a request
	 * @param String $request
	 * @return String
	 */
	public function request($request) {
		$url  = self::VIMEO_API_URL.$request.'.json';
		$response = $this->getHttpClient()
						 ->setMethod(Zend_Http_Client::GET)
						 ->setUri($url)
						 ->request()
		;
		
		if ($response->getStatus() != 200) {
			switch ($response->getStatus()) {
				case 404:
					throw new Garp_Service_Vimeo_Exception('Error: item not found.');
				break;
				case 500:
					/**
					 * Hmm. Unfortunately, Vimeo is not very consistent when it comes to raising exception.
					 * In the case of status 500, I've seen some responses that said "Method not found" in plain text, 
					 * but I've also seen "We're experiencing trouble at the moment" messages containing a whole 
					 * bunch of HTML. Unfortunately this leaves me no choice but to throw a generic error message.
					 */
					throw new Garp_Service_Vimeo_Exception('An error occurred when fetching data from Vimeo.');
				break;
			}
		}
		
		return Zend_Json::decode($response->getBody());
	}
}
