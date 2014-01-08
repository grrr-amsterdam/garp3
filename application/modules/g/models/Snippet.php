<?php
/**
 * G_Model_Snippet
 * Snippet model. Snippets are small dynamic chunks of content.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Model
 * @lastmodified $Date: $
 */
class G_Model_Snippet extends Model_Base_Snippet {	
	/**
     * Initialize object
     * Called from {@link __construct()} as final step of object instantiation.
     * @return Void
     */
	public function init() {
		parent::init();
	}
	
	/**
 	 * Fetch a snippet by its identifier
 	 * @param String $identifier
 	 * @return Garp_Db_Table_Row
 	 */
	public function fetchByIdentifier($identifier) {
		$select = $this->select()->where('identifier = ?', $identifier);
		if ($result = $this->fetchRow($select)) {
			return $result;
		}
		throw new Exception('Snippet not found: '.$identifier);
	}

	/**
 	 * BeforeFetch: filters out snippets where is_editable = 0 in the CMs.
 	 * @param Array $args
 	 * @return Void
 	 */
	public function beforeFetch(&$args) {
		$model = &$args[0];
		$select = &$args[1];
		if (Zend_Registry::isRegistered('CMS') && Zend_Registry::get('CMS')) {
			// Sanity check: this project might be spawned without the is_editable column,
			// it was added to Snippet at May 1 2013.
			if ($this->getFieldConfiguration('is_editable')) {
				$select->where('is_editable = ?', 1);
			}
		}
	}
}
