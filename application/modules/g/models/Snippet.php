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
	 * The table name
	 * @var String
	 */
	protected $_name = 'snippet';
	
	
	/**
     * Initialize object
     * Called from {@link __construct()} as final step of object instantiation.
     * @return Void
     */
	public function init() {
		$this->registerObserver(new Garp_Model_Behavior_Sluggable(array('baseField' => 'id')))
			 ->registerObserver(new Garp_Model_Behavior_HtmlFilterable(array('html')))
			 ->registerObserver(new Garp_Model_Behavior_Timestampable())
			 ->registerObserver(new Garp_Model_Validator_NotEmpty(array('uri', 'id')))
			 ;
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