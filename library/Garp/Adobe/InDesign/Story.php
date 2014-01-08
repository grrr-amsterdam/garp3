<?php
/**
 * Garp_Adobe_InDesign_Story
 * Wrapper around various InDesign related functionality.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: david $
 * @version $Revision: 6502 $
 * @package Garp
 * @subpackage InDesign
 * @lastmodified $Date: 2012-10-01 19:03:54 +0200 (Mon, 01 Oct 2012) $
 */
class Garp_Adobe_InDesign_Story {
	const PATH = 'Stories/Story_%s.xml';

	protected $_workingDir;
	
	protected $_path;
	
	protected $_xml;
	
	protected $_id;
	
	
	public function __construct($storyId, $workingDir) {
		$this->_id				= $storyId;
		$this->_workingDir		= $workingDir;
		$this->_path			= $this->_workingDir . sprintf(self::PATH, $storyId);
		$storyContent			= file_get_contents($this->_path);
		$this->_xml				= new SimpleXMLElement($storyContent);
	}
	

	/**
	 * @return mixed Returns the name of the (TextFrame) tag attached to this Story, or false if no tag is attached.
	 */
	public function getTag() {
		$tagElement = $this->_xml->xpath('//XMLElement');
		if ($tagElement) {
			$firstTagElement = $tagElement[0];
			$tag = $firstTagElement->attributes()->MarkupTag;
			$tag = str_replace('XMLTag/', '', $tag);
			return $tag;
		} else return false;
	}


	/**
	 * @param	Array	$newContent		Row data in the following format:
	 *									array(
	 *										'field_tag_1' => 'data',
	 *										'field_tag_2' => array('data1', 'data2')
	 *									)
	 */
	public function replaceContent(array $newContent) {
		$storyChildren		= $this->_xml->Story->children();
		$charStyleRanges 	= $this->_xml->Story->XMLElement->ParagraphStyleRange->CharacterStyleRange;

		if (
			$storyChildren->XMLElement &&
			$storyChildren->XMLElement->attributes()->MarkupTag
		) {
			$tagId = preg_replace('/XMLTag\/(\w+)/i', '$1', $storyChildren->XMLElement->attributes()->MarkupTag);

			if (array_key_exists($tagId, $newContent)) {
				if (is_scalar($newContent[$tagId])) {
					$newContent[$tagId] = (array)$newContent[$tagId];
				} elseif (is_null($newContent[$tagId])) {
					$newContent[$tagId] = (array)'';
				}

				foreach ($newContent[$tagId] as $newContentNodeIndex => $newContentNode) {
					if (array_key_exists(0, $charStyleRanges[$newContentNodeIndex]->Content)) {
						$charStyleRanges[$newContentNodeIndex]->Content[0] = $newContentNode;
					} else $charStyleRanges[$newContentNodeIndex]->Content = $newContentNode;
				}
			}
		}

		$this->_save();
	}
	
	
	protected function _save() {
		if (file_put_contents($this->_path, $this->_xml->asXml()) === false) {
			throw new Exception('Could not write to ' . $this->_path);
		}
	}
}