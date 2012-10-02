<?php
/**
 * G_Model_VimeoVideo
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Model
 * @lastmodified $Date: $
 */
class G_Model_VimeoVideo extends Garp_Model_Db {
	/**
	 * The table name
	 * @var String
	 */
	protected $_name = 'VimeoVideo';
	
	
	/**
     * Initialize object
     * Called from {@link __construct()} as final step of object instantiation.
     * @return Void
     */
	public function init() {
		$this->registerObserver(new Garp_Model_Behavior_Timestampable())
			->registerObserver(new Garp_Model_Behavior_Vimeoable())
			->registerObserver(new Garp_Model_Behavior_Sluggable(array('baseField' => 'name')))
			->registerObserver(new Garp_Model_Behavior_HtmlFilterable(array('description')))
		;
		parent::init();
	}
	
	
	/**
	 * BeforeInsert callback
	 * @param Array $args
	 * @return Void
	 */
	public function beforeInsert(&$args) {
		$data = &$args[1];
		$this->_setPlayerUrl($data);
	}
	
	
	/**
	 * BeforeUpdate callback
	 * @param Array	$args
	 * @return Void
	 */
	public function beforeUpdate(&$args) {
		$data =&$args[1];
		$this->_setPlayerUrl($data);
	}
	
	
	/**
	 * Set player URL. This normalizes the Vimeo data with the YouTube data.
	 * @param Array $data The new record data
	 * @return Void
	 */
	protected function _setPlayerUrl(&$data) {
		if (!empty($data['id'])) {
			$data['player'] = 'http://player.vimeo.com/video/'.$data['id'];
		}
	}
}