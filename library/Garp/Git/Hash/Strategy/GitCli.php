<?php
class Garp_Git_Hash_Strategy_GitCli 
    implements Garp_Git_Hash_Strategy_Protocol {
    const ERROR_NO_GIT_CLI =
        'The Git CLI command is not available.';


    public function getHash() {
        exec('git rev-parse master', $output, $returnCode);
        // @todo: de branch moet dynamisch worden!

        if (127 === $returnCode) {
            throw new Exception(self::ERROR_NO_GIT_CLI);
        }
        print_r($output);
        var_dump($returnCode);
    }        
}
