<?php
/**
 * Garp_Adobe_InDesign_SpreadSet
 * Wrapper around various InDesign related functionality.
 * Note: currently only works with pages that are horizontally laid out on the spread.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: david $
 * @version $Revision: 6502 $
 * @package Garp
 * @subpackage InDesign
 * @lastmodified $Date: 2012-10-01 19:03:54 +0200 (Mon, 01 Oct 2012) $
 */
class Garp_Adobe_InDesign_SpreadSet extends ArrayObject {
	protected $_workingDir;

	
	public function __construct($workingDir) {
		$this->_workingDir = $workingDir;
		$this->_loadSpreads();
	}
	
	
	/**
	 * @return Array 	List of Story IDs, divided into pages, where 777 is the page index, and a21 to a23 are Story IDs.
	 *					array(
	 *						777 => array(
	 *							a21, a22, a23
	 *						)
	 *					)
	 */
	public function getTaggedStoryIds() {
		$cumulativeStoriesPerPage = array();
		
		foreach($this as $spread) {
			$taggedStories = $spread->getStoriesWithTaggedTextFrames();
			$cumulativeStoriesPerPage += $taggedStories;
		}

		ksort($cumulativeStoriesPerPage);
		return $cumulativeStoriesPerPage;
	}
	
	
	public function replaceAttribsInSpread(array $newAttribs, array $storyIdsPerPage) {
		$contentIterator = 0;
		foreach ($storyIdsPerPage as $pageStories) {
			foreach($this as $spread) {
				if ($spread->usesStory($pageStories[0])) {
				
					if (array_key_exists('FillColor', $newAttribs)) {
						$fillColors = $newAttribs['FillColor'];
						$fillColorsForThisRow = $fillColors[$contentIterator];
						
						foreach ($pageStories as $storyId) {
							//	nu hier checken welke xmlTag in deze story staat.
							$story = new Garp_Adobe_InDesign_Story($storyId, $this->_workingDir);
							$tag = $story->getTag();
							if (array_key_exists($tag, $fillColorsForThisRow)) {
								$spread->setTextFrameAttribute($storyId, 'FillColor', $fillColorsForThisRow[$tag]);
							}
						}
					}
					break;
				}
			}

			$contentIterator++;
		}
	}
	
	
	protected function _loadSpreads() {
		foreach(glob($this->_workingDir . 'Spreads/*.xml') as $filepath) {
			$spreadId = Garp_Adobe_InDesign_Spread::getSpreadIdFromPath($filepath);
			$this[] = new Garp_Adobe_InDesign_Spread($spreadId, $this->_workingDir);
		}
	}
}