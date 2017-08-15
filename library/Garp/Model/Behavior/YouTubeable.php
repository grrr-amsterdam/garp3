<?php
/**
 * Garp_Model_Behavior_Youtubeable
 *
 * @package Garp_Model_Behavior
 * @author  David Spreekmeester <david@grrr.nl>
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Behavior_YouTubeable extends Garp_Model_Behavior_Abstract {
    const EXCEPTION_VIDEO_NOT_FOUND = 'Could not retrieve YouTube data for %s';
    const EXCEPTION_NO_URL = 'No YouTube url was received.';
    const EXCEPTION_INVALID_YOUTUBE_URL = 'Not a valid YouTube url: %s';
    const EXCEPTION_NO_API_RESPONSE = 'Could not retrieve API data from YouTube.';
    const EXCEPTION_MISSING_FIELD = 'Field %s is mandatory.';

    /**
     * Field translation table. Keys are internal names, values are the indexes of the output array.
     *
     * @var array
     */
    protected $_fields = array(
        //  internal name   => database / form name
        'identifier'        => 'identifier',
        'name'              => 'name',
        'description'       => 'description',
        'flash_player_url'  => 'player',
        'watch_url'         => 'url',
        'category'          => 'category',
        'view_count'        => 'view_count',
        'duration'          => 'duration',
        'geo_location'      => 'geo_location',
        'rating_info'       => 'rating_info',
        'tags'              => 'tags',
        'state'             => 'state',
        'updated'           => 'updated',
        'summary'           => 'summary',
        'source'            => 'source',
        'author'            => 'video_author',
        'image_url'         => 'image',
        'thumbnail_url'     => 'thumbnail'
    );

    /**
     * Setup fields. If certain fields are not provided,
     * the defaults in $this->_fields are used.
     *
     * @param array $config
     * @return void
     */
    protected function _setup($config) {
        if (!empty($config)) {
            $this->_fields = $config + $this->_fields;
        }
    }

    /**
     * Before insert callback. Manipulate the new data here. Set $data to FALSE to stop the insert.
     *
     * @param array $args The new data is in $args[1]
     * @return void
     */
    public function beforeInsert(Array &$args) {
        $data = &$args[1];
        if (!$output = $this->_fillFields($data)) {
            throw new Garp_Model_Behavior_YouTubeable_Exception_NoApiResponse(
                self::EXCEPTION_NO_API_RESPONSE
            );
        }
        $data = $output + $data;
    }

    /**
     * Before update callback. Manipulate the new data here.
     *
     * @param array $args The new data is in $args[1]
     * @return void
     */
    public function beforeUpdate(Array &$args) {
        $data = &$args[1];

        if (!$output = $this->_fillFields($data)) {
            throw new Garp_Model_Behavior_YouTubeable_Exception_NoApiResponse(
                self::EXCEPTION_NO_API_RESPONSE
            );
        }
        $data = $output + $data;
    }

    /**
     * Retrieves additional data about the video corresponding with given input url from YouTube,
     * and returns new data structure.
     *
     * @param array $input
     * @return array
     */
    protected function _fillFields(Array $input) {
        if (!array_key_exists($this->_fields['watch_url'], $input)) {
            throw new Garp_Model_Behavior_YouTubeable_Exception_MissingField(
                sprintf(self::EXCEPTION_MISSING_FIELD, $this->_fields['watch_url'])
            );
        }
        $url = $input[$this->_fields['watch_url']];
        if (empty($url)) {
            return;
        }
        $entry = $this->_fetchEntryByUrl($url);
        $images = $entry->getSnippet()->getThumbnails();
        $duration = $entry->getContentDetails()->getDuration();

        $data = array(
            'identifier'       => $entry->getId(),
            'name'             => $this->_getVideoName($entry, $input),
            'description'      => $this->_getVideoDescription($entry, $input),
            'flash_player_url' => $this->_getFlashPlayerUrl($entry),
            'watch_url'        => $url,
            'duration'         => $this->_getDurationInSeconds($duration),
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
     *
     * @param array $output
     * @param string $key
     * @param string $value
     * @return void
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
        return 'https://www.youtube.com/embed/' . $entry->getId();
    }

    protected function _fetchEntryByUrl($watchUrl) {
        $yt = Garp_Google::getGoogleService('YouTube');
        $youTubeId = $this->_getId($watchUrl);
        $entries = $yt->videos->listVideos(
            'id,snippet,contentDetails', array(
            'id' => $youTubeId
            )
        );
        if (empty($entries['items'])) {
            throw new Garp_Model_Behavior_YouTubeable_Exception_VideoNotFound(
                sprintf(self::EXCEPTION_VIDEO_NOT_FOUND, $watchUrl)
            );
        }

        return $entries['items'][0];
    }

    /**
     * Retrieves the id value of a YouTube url.
     *
     * @param string $watchUrl
     * @return string
     */
    protected function _getId($watchUrl) {
        $query = array();
        if (!$watchUrl) {
            throw new Garp_Model_Behavior_YouTubeable_Exception_NoUrl(self::EXCEPTION_NO_URL);
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
            throw new Garp_Model_Behavior_YouTubeable_Exception_InvalidUrl(
                sprintf(self::EXCEPTION_INVALID_YOUTUBE_URL, $watchUrl)
            );
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

    /**
     * Convert ISO 8601 duration to seconds
     *
     * @param  string $duration
     * @return integer
     */
    protected function _getDurationInSeconds($duration) {
        $interval = new \DateInterval($duration);
        return ($interval->d * 24 * 60 * 60) +
            ($interval->h * 60 * 60) +
            ($interval->i * 60) +
            $interval->s;
    }

}
