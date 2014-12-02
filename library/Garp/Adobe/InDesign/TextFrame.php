<?php
/**
 * Garp_Adobe_InDesign_TextFrame
 * Wrapper around various InDesign related functionality.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: david $
 * @version $Revision: 6480 $
 * @package Garp
 * @subpackage InDesign
 * @lastmodified $Date: 2012-09-27 06:42:27 +0200 (Thu, 27 Sep 2012) $
 */
class Garp_Adobe_InDesign_TextFrame extends Garp_Adobe_InDesign_SpreadNode {
	
	/**
	 * @var	String	$pageId		The ID of the page this TextFrame is placed upon.
	 */
	// public $pageId;

	/**
	 * @var String	$storyId	The Story that this TextFrame belongs to.
	 */
	public $storyId;


 	/**
 	 * @param SimpleXMLElement 	$spreadConfig 		The <Spread> node of an InDesign Spread configuration.
 	 * @param String			$textFrameConfig	The <TextFrame> node within the Spread configuration.
 	 */
	public function __construct(SimpleXMLElement $spreadConfig, $textFrameConfig) {
		parent::__construct($spreadConfig, $textFrameConfig);

		$this->storyId = $this->_getStoryId();
	}
	
	
	protected function _getStoryId() {
		return (string)$this->_nodeConfig->attributes()->ParentStory;
	}
	

}