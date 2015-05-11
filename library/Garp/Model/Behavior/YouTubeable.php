<?php
/**
 * Garp_Model_Behavior_Youtubeable
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Behavior
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_YouTubeable extends Garp_Model_Behavior_Abstract {
	const EXCEPTION_VIDEO_NOT_FOUND = 'Could not retrieve YouTube data for %s';

	/**
	 * Field translation table. Keys are internal names, values are the indexes of the output array.
	 * @var Array
	 */
	protected $_fields = array(
		//	internal name	=> database / form name
		'identifier'		=> 'identifier',
		'name'				=> 'name',
		'description'		=> 'description',
		'flash_player_url'	=> 'player',
		'watch_url'			=> 'url',
		'category'			=> 'category',
		'view_count'		=> 'view_count',
		'duration'			=> 'duration',
		'geo_location'		=> 'geo_location',
		'rating_info'		=> 'rating_info',
		'tags'				=> 'tags',
		'state'				=> 'state',
		'updated'			=> 'updated',
		'summary'			=> 'summary',
		'source'			=> 'source',
		'author'			=> 'video_author',
		'image_url'			=> 'image',
		'thumbnail_url'		=> 'thumbnail'
	);

	/**
	 * Setup fields. If certain fields are not provided,
	 * the defaults in $this->_fields are used.
	 * @param Array $config
	 * @return Void
	 */
	protected function _setup($config) {
		if (!empty($config)) {
			$this->_fields = $config + $this->_fields;
		}
	}

	/**
	 * Before insert callback. Manipulate the new data here. Set $data to FALSE to stop the insert.
	 * @param Array $options The new data is in $args[1]
	 */
	public function beforeInsert(Array &$args) {
		$data = &$args[1];
		if (!$output = $this->_fillFields($data)) {
			throw new Garp_Model_Behavior_Exception('Could not properly retrieve API data from YouTube.');
		}
		$data = $output + $data;
	}

	/**
	 * Before update callback. Manipulate the new data here.
	 * @param Array $data The new data is in $args[1]
	 * @return Void
	 */
	public function beforeUpdate(Array &$args) {
		$data = &$args[1];

		if (!$output = $this->_fillFields($data)) {
			throw new Garp_Model_Behavior_Exception('Could not properly retrieve API data from YouTube.');
		}
		$data = $output + $data;
	}

	/**
	 * Retrieves additional data about the video corresponding with given input url from YouTube, and returns new data structure.
	 */
	protected function _fillFields(Array $input) {
		if (!array_key_exists($this->_fields['watch_url'], $input)) {
			throw new Garp_Model_Behavior_Exception('Field '.$this->_fields['watch_url'].' is mandatory.');
		}
		$url = $input[$this->_fields['watch_url']];
		if (empty($url)) {
			return;
		}
		$entry = $this->_fetchEntryByUrl($url);
		$images = $entry->getSnippet()->getThumbnails();

		$data = array(
			'identifier'       => $entry->getId(),
			'name'             => $this->_getVideoName($entry, $input),
			'description'      => $this->_getVideoDescription($entry, $input),
			'flash_player_url' => $this->_getFlashPlayerUrl($entry),
			'watch_url'        => $url,
			'duration'         => $entry->getContentDetails()->getDuration(),
			'image_url'        => $images['high']['url'],
			'thumbnail_url'    => $images['default']['url'],
		);
		$out = array();
		foreach ($data as $ytKey => $value) {
			$garpKey = $this->_fields[$ytKey];
			$this->_populateOutput($out, $garpKey, $value);
		}
		return $out;
	}

	/**
 	 * Populate record with new data
 	 * @param Array $output
 	 * @param String $key
 	 * @param String $value
 	 * @return Void
 	 */
	protected function _populateOutput(array &$output, $key, $value) {
		if (strpos($key, '.') === false) {
			$output[$key] = $value;
			return;
		}
		$array = Garp_Util_String::toArray($key, '.', $value);
		$output += $array;
	}

	protected function _getFlashPlayerUrl(Google_Service_YouTube_Video $entry) {
		return 'http://www.youtube.com/embed/' . $entry->getId();
	}

	protected function _fetchEntryByUrl($watchUrl) {
		$yt = Garp_Google::getGoogleService('YouTube');
		$youTubeId = $this->_getId($watchUrl);
		$entries = $yt->videos->listVideos('id,snippet,contentDetails', array(
			'id' => $youTubeId
		));
		if (empty($entries['items'])) {
			throw new Garp_Model_Behavior_Exception(
				sprintf(self::EXCEPTION_VIDEO_NOT_FOUND, $watchUrl));
		}

		return $entries['items'][0];
	}

	/**
	 * Retrieves the id value of a YouTube url.
	 *
	 * @param String $youTubeUrl
	 * @return String
	 */
	protected function _getId($watchUrl) {
		$query = array();
		if (!$watchUrl) {
			throw new Garp_Model_Behavior_Exception('No YouTube url was received.');
		}

		$url = parse_url($watchUrl);
		if (empty($url['query']) && $url['host'] == 'youtu.be') {
			$videoId = substr($url['path'], 1);
		} elseif (isset($url['query'])) {
			parse_str($url['query'], $query);
			if (isset($query['v']) && $query['v']) {
				$videoId = $query['v'];
			}
		}

		if (!isset($videoId)) {
			throw new Garp_Model_Behavior_Exception('Not a valid YouTube url.');
		}
		return $videoId;
	}

	protected function _getVideoName($entry, $input) {
		if (!empty($input[$this->_fields['name']])) {
 		   	return $input[$this->_fields['name']];
 		}
		return $entry->getSnippet()->getTitle();
	}

	protected function _getVideoDescription($entry, $input) {
		if (!empty($input[$this->_fields['description']])) {
			return $input[$this->_fields['description']];
 		}
		return $entry->getSnippet()->getDescription();
	}
}
