<?php
class Garp_Service_Sentry {
    static public function log(Exception $exception) {
        global $ravenClient;

        if (!$ravenClient) {
            return;
        }

        $debugVars = array(
            '_php_version' => phpversion(),
            '_garp_version' => Garp_Version::VERSION,
            'extensions' => get_loaded_extensions()
        );

        // Add user data to log
        $auth = Garp_Auth::getInstance();
        if ($auth->isLoggedIn()) {
            $debugVars['_user_data'] = $auth->getUserData();
        };

        $extra = array('extra' => $debugVars);

        $event_id = $ravenClient->getIdent(
            $ravenClient->captureException($exception, $extra)
        );
    }
        
}
