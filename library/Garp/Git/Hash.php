<?php
/**
 * Garp_GitHash
 * Application Git hash
 * Note! This is a potentially expensive class,
 * requiring either disc access or the Git shell command.
 * @author David Spreekmeester <david@grrr.nl>
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @lastmodified $Date: $
 */
class Garp_Git_Hash {
    const ERROR_ALL_METHODS_FAILED = 
    'There was no method available to determine the Git hash.';
    //private static $hash;

    /**
     * @var Garp_GitHash_Strategy_Protocol $_strategy
     */
    protected $_strategy;


    public function __construct(
        Garp_Git_Hash_Strategy_Protocol $strategy = null
    ) {
        $this->_strategy = $strategy;
    }

    /**
     * Determine the method for retrieving the Git hash.
     * If possible, try to retrieve the Git hash from the
     * Capistrano 3 folder structure (in the 'repo' dir).
     * If not, try if the Git command is available on the CLI.
     */
    public function getHash(
    ) {
        // Use the optionally specified strategy.
        if (null !== $this->_strategy) {
            return $this->_strategy->getHash();
        }

        // First, try the Capistrano 3 strategy.
        $strategy = new Garp_Git_Hash_Strategy_Cap3();
        $hash = $strategy->getHash();

        if ($hash) {
            return $hash;
        }

        // Then, try the Git CLI strategy.
        $strategy = new Garp_Git_Hash_Strategy_GitCli();
        $hash = $strategy->getHash();

        if ($hash) {
            return $hash;
        }

        throw new Exception(self::ERROR_ALL_METHODS_FAILED);
    }
}
