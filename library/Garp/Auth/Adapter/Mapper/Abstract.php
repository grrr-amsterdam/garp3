<?php
/**
 * Garp_Auth_Adapter_Mapper_Abstract
 * Interface for column mappers. These mappers should be able to map properties from a social
 * network to properties in our own database.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_Auth_Adapter_Mapper
 */
interface Garp_Auth_Adapter_Mapper_Abstract {
	public function map(array $props);
}
