<?php
/**
 * Garp_Service_Slack_Config
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Slack
 * @lastmodified $Date: $
 */
class Garp_Service_Slack_Config {
	protected $_token;
	protected $_channel;
	protected $_emoji;
	protected $_username;


	public function __construct(object $config) {

	}

	public function __call($function, $args) {
		//exit($function);
	}	
}
