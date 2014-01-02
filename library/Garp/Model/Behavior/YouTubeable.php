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
		'author'			=> 'author',
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
		if ($output = $this->_fillFields($data)) {
			$data = $output + $data;
		} else throw new Garp_Model_Behavior_Exception('Could not properly retrieve API data from YouTube.');
	}
	
	
	/**
	 * Before update callback. Manipulate the new data here.
	 * @param Array $data The new data is in $args[1]
	 * @return Void
	 */
	public function beforeUpdate(Array &$args) {
		$data = &$args[1];

		if ($output = $this->_fillFields($data)) {
			$data = $output + $data;
		} else throw new Garp_Model_Behavior_Exception('Could not properly retrieve API data from YouTube.');
	}


	/**
	 * Retrieves additional data about the video corresponding with given input url from YouTube, and returns new data structure.
	 */
	protected function _fillFields(Array $input) {
		if (array_key_exists($this->_fields['watch_url'], $input)) {
			$url = $input[$this->_fields['watch_url']];
			
			if (!empty($url)) {
				$entry = $this->_fetchEntryByUrl($url);
				$images = $entry->mediaGroup->getThumbnail();

				return array(
					$this->_fields['identifier']		=> $entry->getVideoId(),
					$this->_fields['name']				=> !empty($input[$this->_fields['name']]) ?
						$input[$this->_fields['name']] :
						$entry->getVideoTitle(),
					$this->_fields['description']		=> !empty($input[$this->_fields['name']]) ?
						$input[$this->_fields['description']] :
						$entry->getVideoDescription(),
					$this->_fields['flash_player_url']	=> str_replace('/v/', '/embed/', $this->_getFlashPlayerUrl($entry)),
					$this->_fields['watch_url']			=> $this->_getWatchUrl($entry),
					$this->_fields['duration']			=> $entry->getVideoDuration(),
					$this->_fields['tags']				=> implode(' ', $entry->getVideoTags()),
					$this->_fields['author']			=> current($entry->getAuthor())->name->text,
					$this->_fields['image_url']			=> $images[3]->url,
					$this->_fields['thumbnail_url']		=> $images[0]->url
				);
			}
		} else throw new Garp_Model_Behavior_Exception('Field '.$this->_fields['watch_url'].' is mandatory.');
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
		} else return $entry->getFlashPlayerUrl();
	}
	
	
	protected function _fetchEntryByUrl($watchUrl) {
		$yt = new Zend_Gdata_YouTube();
		$youTubeId = $this->_getId($watchUrl);

		try {
			if ($entry = $yt->getVideoEntry($youTubeId)) {
				return $entry;
			} else throw new Garp_Model_Behavior_Exception('Could not retrieve YouTube data for '.$watchUrl);
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

		if (isset($videoId)) {
			return $videoId;
		} else {
			throw new Garp_Model_Behavior_Exception('Not a valid YouTube url.');
		}
	}
	
	
	protected function isEmbeddable(Zend_Gdata_YouTube_VideoEntry $entry) {
		return (bool)!$entry->getNoEmbed();
	}
}
