<?php 
/**
 * Garp Implementation of OAuth2 Storage
 * 
 * @package Garp
 * @author Ramiro Hammen <ramiro@grrr.nl>
 */
class Garp_OAuth2_Storage implements OAuth2_Storage_AccessTokenInterface,
OAuth2_Storage_ClientCredentialsInterface,
OAuth2_Storage_AuthorizationCodeInterface {

    protected $_accessTokenModel;

    protected $_clientModel;

    protected $_authorizationCodeModel;


    /**
     * Look up the supplied oauth_token from storage.
     *
     * We need to retrieve access token data as we create and verify tokens.
     *
     * @param String $access_token     Access token to be check with.
     *
     * @return Boolean
     * An associative array as below, and return NULL if the supplied oauth_token
     * is invalid:
     * - expires: Stored expiration in unix timestamp.
     * - client_id: (optional) Stored client identifier.
     * - user_id: (optional) Stored user identifier.
     * - scope: (optional) Stored scope values in space-separated string.
     * - id_token: (optional) Stored id_token (if "use_openid_connect" is true).
     *
     * @ingroup oauth2_section_7
     */
    public function getAccessToken($access_token) {
        $accessToken = $this->_getAccessTokenModel()->fetchByAccessToken($access_token);
        return $accessToken ? $accessToken->toArray() : null;
    }

    /**
     * Store the supplied access token values to storage.
     *
     * We need to store access token data as we create and verify tokens.
     *
     * @param String $access_token   access token  to be stored.
     * @param String $client_id      client identifier to be stored.
     * @param Integer $user_id        user identifier to be stored.
     * @param Timestamp $expires expiration to be stored as a Unix timestamp.
     * @param String $scope   OPTIONAL Scopes to be stored in space-separated string.
     *
     * @ingroup oauth2_section_4
     * @return void
     */
    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null) {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);

        $insertData = array(
            'access_token' => $access_token,
            'client_id' => $client_id,
            'expires' => $expires,
            'user_id' => $user_id,
            'scope' => $scope
        );

        if ($this->getAccessToken($access_token)) {
            $this->_getAccessTokenModel()->update($insertData, "access_token = '{$access_token}'");
            return;
        }

        $this->_getAccessTokenModel()->insert($insertData);
    }

    /**
     * Get client details corresponding client_id.
     *
     * OAuth says we should store request URIs for each registered client.
     * Implement this function to grab the stored URI for a given client id.
     *
     * @param String $client_id
     * Client identifier to be check with.
     *
     * @return array
     * Client details. The only mandatory key in the array is "redirect_uri".
     * This function MUST return FALSE if the given client does not exist or is
     * invalid. "redirect_uri" can be space-delimited to allow for multiple valid uris.
     * @code
     * return array(
     *     "redirect_uri" => REDIRECT_URI,      // REQUIRED redirect_uri registered for the client
     *     "client_id"    => CLIENT_ID,         // OPTIONAL the client id
     *     "grant_types"  => GRANT_TYPES,       // OPTIONAL an array of restricted grant types
     * );
     * @endcode
     *
     * @ingroup oauth2_section_4
     */
    public function getClientDetails($client_id) { 
        $client = $this->_getClientModel()->fetchByClientId($client_id);
        
        if (!$client) {
            return false;
        }

        return $client->toArray();
    }

    /**
     * Check restricted grant types of corresponding client identifier.
     *
     * If you want to restrict clients to certain grant types, override this
     * function.
     *
     * @param String $client_id
     * Client identifier to be check with.
     * @param String $grant_type
     * Grant type to be check with
     *
     * @return Boolean
     * TRUE if the grant type is supported by this client identifier, and
     * FALSE if it isn't.
     *
     * @ingroup oauth2_section_4
     */
    public function checkRestrictedGrantType($client_id, $grant_type) {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            return in_array($grant_type, (array) $details['grant_types']);
        }

        // if grant_types are not defined, then none are restricted
        return true;
    }

    /**
     * Make sure that the client credentials is valid.
     *
     * @param String $client_id
     * Client identifier to be check with.
     * @param String $client_secret
     * (optional) If a secret is required, check that they've given the right one.
     *
     * @return Boolean
     * TRUE if the client credentials are valid, and MUST return FALSE if it isn't.
     * @endcode
     *
     * @see http://tools.ietf.org/html/rfc6749#section-3.1
     *
     * @ingroup oauth2_section_3
     */
    public function checkClientCredentials($client_id, $client_secret = null) {
        $client = $this->_getClientModel()->fetchByClientId($client_id);

        return $client && $client->client_secret == $client_secret;
    }

    



    /**
     * Fetch authorization code data (probably the most common grant type).
     *
     * Retrieve the stored data for the given authorization code.
     *
     * Required for OAuth2::GRANT_TYPE_AUTH_CODE.
     *
     * @param String $code
     * Authorization code to be check with.
     *
     * @return Array
     * An associative array as below, and NULL if the code is invalid
     * @code
     * return array(
     *     "client_id"    => CLIENT_ID,      // REQUIRED Stored client identifier
     *     "user_id"      => USER_ID,        // REQUIRED Stored user identifier
     *     "expires"      => EXPIRES,        // REQUIRED Stored expiration in unix timestamp
     *     "redirect_uri" => REDIRECT_URI,   // REQUIRED Stored redirect URI
     *     "scope"        => SCOPE,          // OPTIONAL Stored scope values in 
     *                                          space-separated string
     * );
     * @endcode
     *
     * @see http://tools.ietf.org/html/rfc6749#section-4.1
     *
     * @ingroup oauth2_section_4
     */
    public function getAuthorizationCode($code) {
        $authorizationCode = $this->_getAuthorizationCodeModel()->fetchByCode($code);

        if (!$authorizationCode) {
            return null;
        }

        $authorizationCode = $authorizationCode->toArray();
        $authorizationCode['expires'] = strtotime($authorizationCode['expires']);

        return $authorizationCode;
    }

    /**
     * Take the provided authorization code values and store them somewhere.
     *
     * This function should be the storage counterpart to getAuthCode().
     *
     * If storage fails for some reason, we're not currently checking for
     * any sort of success/failure, so you should bail out of the script
     * and provide a descriptive fail message.
     *
     * Required for OAuth2::GRANT_TYPE_AUTH_CODE.
     *
     * @param string $code         Authorization code to be stored.
     * @param mixed  $client_id    Client identifier to be stored.
     * @param mixed  $user_id      User identifier to be stored.
     * @param string $redirect_uri Redirect URI(s) to be stored in a space-separated string.
     * @param int    $expires      Expiration to be stored as a Unix timestamp.
     * @param string $scope        OPTIONAL Scopes to be stored in space-separated string.
     *
     * @ingroup oauth2_section_4
     * @return void
     */
    public function setAuthorizationCode(
        $code, $client_id, $user_id, $redirect_uri, $expires, $scope = null
    ) {
         // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);
        // if it exists, update it. 
        if ($this->getAuthorizationCode($code)) {
            return $this->_getAuthorizationCodeModel()->update(
                array(
                'client_id' => $client_id,
                'user_id' => $user_id,
                'redirect_uri' => $redirect_uri,
                'expires' => $expires,
                'scope' => $scope
                ), 
                "where authorization_code = '{$code}'"
            );
        } else {
            return $this->_getAuthorizationCodeModel()->insert(
                array(
                'authorization_code' => $code,
                'client_id' => $client_id,
                'user_id' => $user_id,
                'redirect_uri' => $redirect_uri,
                'expires' => $expires,
                'scope' => $scope
                )
            );
        }
    }

    /**
     * Once an Authorization Code is used, it must be exipired
     *  
     * @param String $code
     * 
     * @return void
     * 
     * @see http://tools.ietf.org/html/rfc6749#section-4.1.2
     * 
     *    The client MUST NOT use the authorization code
     *    more than once.  If an authorization code is used more than
     *    once, the authorization server MUST deny the request and SHOULD
     *    revoke (when possible) all tokens previously issued based on
     *    that authorization code
     */
    public function expireAuthorizationCode($code) {
        return $this->_getAuthorizationCodeModel()->delete('authorization_code = ?', $code);
    }


    protected function _getAccessTokenModel() {
        if (!$this->_accessTokenModel) {
            $this->_accessTokenModel = new Model_OauthAccessToken;
        }

        return $this->_accessTokenModel;
    }

    protected function _getClientModel() {
        if (!$this->_clientModel) {
            $this->_clientModel = new Model_OauthClient;
        }

        return $this->_clientModel;
    }

    protected function _getAuthorizationCodeModel() {
        if (!$this->_authorizationCodeModel) {
            $this->_authorizationCodeModel = new Model_OauthAuthorizationCode;
        }

        return $this->_authorizationCodeModel;
    }




}