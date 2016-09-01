<?php
/**
 * Garp_Google
 * Wrapper around Google functionality
 *
 * @package Garp
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Google {
    const INVALID_CREDENTIALS_EXCEPTION = 'Invalid credentials: apiKey and appName are required';

    public static function getGoogleService($serviceName, array $credentials = null) {
        if (!$credentials) {
            $credentials = static::getCredentials();
        }
        if (!isset($credentials['apiKey'])) { // || !isset($credentials['appName'])) {
            throw new Garp_Google_Exception(static::INVALID_CREDENTIALS_EXCEPTION);
        }

        $client = new Google_Client();
        //$client->setApplicationName($credentials['appName']);
        $client->setDeveloperKey($credentials['apiKey']);

        $serviceClassName = "Google_Service_$serviceName";
        return new $serviceClassName($client);
    }

    public static function getCredentials() {
        $config = Zend_Registry::get('config');
        return array(
            'apiKey' => isset($config->google->apiKey) ? $config->google->apiKey : null,
                //'appName' => isset($config->google->appName) ? $config->google->appName : null,
        );
    }
}
