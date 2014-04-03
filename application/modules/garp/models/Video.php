<?php
/**
 * G_Model_Video
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Model
 * @lastmodified $Date: $
 */
class G_Model_Video extends Garp_Model_Db {
	/**
	 * The table name
	 * @var String
	 */
	protected $_name = 'Video';
	
	
	/**
   * Initialize object
   * Called from {@link __construct()} as final step of object instantiation.
   * @return Void
   */
	public function init() {
		$this->registerObserver(new Garp_Model_Behavior_Timestampable())
			->registerObserver(new Garp_Model_Behavior_Videoable())
			->registerObserver(new Garp_Model_Behavior_Sluggable(array('baseField' => 'name')))
			->registerObserver(new Garp_Model_Behavior_HtmlFilterable(array('description')))
		;
		parent::init();
	}
}
