<?php
class Garp_Git_Hash_Strategy_Cap3 
    implements Garp_Git_Hash_Strategy_Protocol {

    const ERROR_NO_HASH_FOUND =
        'The Cap3 strategy could not find a Git hash.';
    const ERROR_HASH_EMPTY =
        'The Cap3 strategy resulted in an empty Git hash.';
    const DEFAULT_REPO_REF_HEADS_PATH =
        '../repo/refs/heads/';

    /**
     * @var String $repoRefHeadsPath Optionally injectable
     *          repository refs heads path.
     *          Should include a trailing slash.
     */
    public function __construct($repoRefHeadsPath = null) {
        if (null !== $repoRefHeadsPath) {
            $this->_repoRefHeadsPath = $repoRefHeadsPath;
            return;
        }

        $this->_repoRefHeadsPath = 
            self::DEFAULT_REPO_REF_HEADS_PATH
        ;
    }

    public function getHash() {
        // @todo Make branch dynamic
        $gitFile = $this->_repoRefHeadsPath . 'master';

        if (!file_exists($gitFile)) {
            throw new Exception(self::ERROR_NO_HASH_FOUND . ' ' . $gitFile);
        }

        $gitHash = file_get_contents($gitFile);
        if ($gitHash) {
            return $gitHash;
        }

        throw new Exception(self::ERROR_HASH_EMPTY);
    } 
}
