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
	
	
	public function fetchByIdentifier($identifier) {
		if ($result = $this->fetchRow(
			$this->select()->where('identifier = ?', $identifier)
		)) {
			return $result;
		} else throw new Exception('Snippet not found: '.$identifier);
	}
}
