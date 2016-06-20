<?php
/**
 * Garp_File_Unzipper
 * Unzips a file, recursively if necessary.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_File_Unzipper
 */
class Garp_File_Unzipper {
    const MAX_TRIES = 10;

    public function __construct($obj) {
        $this->_original = $obj;
    }

    public function getUnpacked() {
        $tries = 0;
        $obj = $this->_original;
        while (true) {
            if ($tries > self::MAX_TRIES) {
                // Zipped ridiculously deep? Screw that, return the original
                return $this->_original;
            }
            // To account for re-zipped files, keep unpacking until there's a false value
            // returned.
            $unpacked = @gzdecode($obj);
            ++$tries;
            if (null === $unpacked || false === $unpacked) {
                // Can't unpack any more, return result of previous iteration
                return $obj;
            }
            $obj = $unpacked;
        }
    }
}
