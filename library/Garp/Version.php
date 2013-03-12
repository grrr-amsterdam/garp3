<?php
/**
 * Garp_Version
 * class description
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp
 */
final class Garp_Version {
    /**
 	 * Garp version identification - see compareVersion()
 	 * @var String
     */
    const VERSION = '3.7';


    /**
     * (taken from Zend_Version)
     *
     * Compare the specified Garp version string $version
     * with the current Garp_Version::VERSION of Garp.
     *
     * @param  string  $version  A version string (e.g. "0.7.1").
     * @return int           -1 if the $version is older,
     *                           0 if they are the same,
     *                           and +1 if $version is newer.
     *
     */
    public static function compareVersion($version) {
		$version = strtolower($version);
		$version = preg_replace('/(\d)pr(\d?)/', '$1a$2', $version);
		return version_compare($version, strtolower(static::VERSION));
	}
}
