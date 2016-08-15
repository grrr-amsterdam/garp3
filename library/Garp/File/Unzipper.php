<?php
/**
 * Garp_File_Unzipper
 * Unzips a file, recursively if necessary.
 *
 * @package Garp_File_Unzipper
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_File_Unzipper {
    /**
     * Max amount of recursive tries to unpack double zipped data
     *
     * @var int
     */
    const MAX_TRIES = 10;

    /**
     * Original data
     *
     * @var string
     */
    protected $_original;

    /**
     * Class constructor
     *
     * @param string $obj The raw bytes
     * @return void
     */
    public function __construct($obj) {
        $this->_original = $obj;
    }

    /**
     * Get unzipped data. Will recursively unpack data until it looks like it's no longer gzipped
     *
     * @return string
     */
    public function getUnpacked() {
        $tries = 0;
        $obj = $this->_original;

        while ($this->_dataLooksZipped($obj)) {
            $unpacked = gzdecode($obj);
            if (null === $unpacked || false === $unpacked) {
                // If anything went wrong with decoding, return result of previous iteration
                return $obj;
            }
            $obj = $unpacked;

            $tries++;

            if ($this->_maxDepthReached($tries)) {
                // Zipped ridiculously deep? Screw that, return the original
                return $this->_original;
            }
        }
        return $obj;
    }

    /**
     * Check if data looks like it's been gzipped
     *
     * @param string $data
     * @return bool
     */
    protected function _dataLooksZipped($data) {
        // See RFC 1952, and also http://php.net/manual/en/function.gzdecode.php#82930
        return strlen($data) >= 18 && !strcmp(substr($data, 0, 2), "\x1f\x8b");
    }

    /**
     * Check if max recursion level is reached to prevent loop from hanging.
     *
     * @param int $tries
     * @return bool
     */
    protected function _maxDepthReached($tries) {
        return $tries > self::MAX_TRIES;
    }
}
