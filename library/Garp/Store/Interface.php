<?php
/**
 * Garp_Store_Interface
 * Blueprint for stores.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Store
 * @lastmodified $Date: $
 *
 * @todo It would be a good thing if Iterable and similar interfaces are added to these classes, 
 * so the stores can be accessed like an array with foreach loops and all that jazz.
 */
interface Garp_Store_Interface {
	/**
 	 * Class constructor
 	 * @param String $namespace Global namespace
 	 * @return Void
 	 */
	public function __construct($namespace);


	/**
 	 * Get value by key $key
 	 * @param String $key
 	 * @return Mixed
 	 */
	public function get($key);


	/**
 	 * Store $value by key $key
 	 * @param String $key
 	 * @param Mixed $value
 	 * @return $this
 	 */
	public function set($key, $value);


	/**
 	 * Magic getter
 	 * @param String $key
 	 * @return Mixed
 	 */
	public function __get($key);


	/**
 	 * Magic setter
 	 * @param String $key
 	 * @param Mixed $value
 	 * @return Void
 	 */
	public function __set($key, $value);


	/**
 	 * Magic isset
 	 * @param String $key
 	 * @return Boolean
 	 */
	public function __isset($key);


	/**
 	 * Magic unset
 	 * @param String $key
 	 * @return Void
 	 */
	public function __unset($key);


	/**
 	 * Remove a certain key from the store
 	 * @param String $key
 	 * @return $this
 	 */
	public function destroy($key = false);
}
