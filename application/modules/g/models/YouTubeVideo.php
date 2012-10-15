<?php
/**
 * G_Model_YouTubeVideo
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Model
 * @lastmodified $Date: $
 */
class G_Model_YouTubeVideo extends Garp_Model_Db {
	/**
	 * The table name
	 * @var String
	 */
	protected $_name = 'YoutubeVideo';
	
	
	/**
     * Initialize object
     * Called from {@link __construct()} as final step of object instantiation.
     * @return Void
     */
	public function init() {
		$this->registerObserver(new Garp_Model_Behavior_Timestampable())
			->registerObserver(new Garp_Model_Behavior_YouTubeable())
			->registerObserver(new Garp_Model_Behavior_Sluggable(array('baseField' => 'name')))
			->registerObserver(new Garp_Model_Behavior_HtmlFilterable(array('description')))
		;
		parent::init();
	}
}