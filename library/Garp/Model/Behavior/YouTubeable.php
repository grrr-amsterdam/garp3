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

		if ($output = $this->_fillFields($data)) {
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
		$images = $entry->mediaGroup->getThumbnail();

		$data = array(
			'identifier'       => $entry->getVideoId(),
			'name'             => !empty($input[$this->_fields['name']]) ? $input[$this->_fields['name']] : $entry->getVideoTitle(),
			'description'      => !empty($input[$this->_fields['description']]) ? $input[$this->_fields['description']] : $entry->getVideoDescription(),
			'flash_player_url' => str_replace('/v/', '/embed/', $this->_getFlashPlayerUrl($entry)),
			'watch_url'        => $this->_getWatchUrl($entry),
			'duration'         => $entry->getVideoDuration(),
			'tags'             => implode(' ', $entry->getVideoTags()),
			'author'           => current($entry->getAuthor())->name->text,
			'image_url'        => $images[0]->url,
			'thumbnail_url'    => $images[3]->url
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

	/**
	 * Retrieves the watch URL from the API, and strips off redundant parameters.
	 * @param Zend_Gdata_YouTube_VideoEntry $entry The video entry, as retrieved from the YouTube API
	 * @return String The watch URL
	 */
	protected function _getWatchUrl(Zend_Gdata_YouTube_VideoEntry $entry) {
		$watchUrl = $entry->getVideoWatchPageUrl();
		$appendedString = '&feature=youtube_gdata_player';
		$pos = strpos($watchUrl, $appendedString);
		if ($pos !== false) {
			$watchUrl = substr($watchUrl, 0, $pos);
		}
		
		return $watchUrl;
	}
	
	/**
	 * Videos where embedding is disabled, do not return a Flash player url. Generate it anyway! :)
	 */
	protected function _getFlashPlayerUrl(Zend_Gdata_YouTube_VideoEntry $entry) {
		if (!$this->isEmbeddable($entry)) {
			return 'http://www.youtube.com/v/'.$entry->getVideoId().'?f=videos&app=youtube_gdata';
		}
		return $entry->getFlashPlayerUrl();
	}
	
	protected function _fetchEntryByUrl($watchUrl) {
		$yt = new Zend_Gdata_YouTube();
		$youTubeId = $this->_getId($watchUrl);

		try {
			if ($entry = $yt->getVideoEntry($youTubeId)) {
				return $entry;
			}
			throw new Garp_Model_Behavior_Exception('Could not retrieve YouTube data for ' . $watchUrl);
		} catch(Exception $e) {
			throw new Garp_Model_Behavior_Exception(
				strpos($e->getMessage(), '403') !== false ?
					'This video is not public' :
					$e->getMessage()
			);
		}
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
		} else {
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
	
	protected function isEmbeddable(Zend_Gdata_YouTube_VideoEntry $entry) {
		return (bool)!$entry->getNoEmbed();
	}
}
