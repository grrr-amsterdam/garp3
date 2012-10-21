<?php
/**
 * Garp_Adobe_InDesign
 * Handling boring InDesign functionality so you don't have to.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: david $
 * @version $Revision: 6526 $
 * @package Garp
 * @subpackage InDesign
 * @lastmodified $Date: 2012-10-04 06:20:22 +0200 (Thu, 04 Oct 2012) $
 */
class Garp_Adobe_InDesign {
	/**
	 * @var	String	$_workingDir The working directory where the .idml file is extracted to.
	 */
	protected $_workingDir;

	/**
	 * @var Garp_Adobe_InDesign_SpreadSet $_spreadSet The full collection of existing spreads.
	 */
	protected $_spreadSet;
	
	/**
	 * @var Garp_Adobe_InDesign_Storage $_storage
	 */
	protected $_storage;
	
	/**
	 * @param	String	$sourcePath		Name of the .idml file that will serve as a template for the dynamic .idml files
	 * @param	String	$targetPath		Location of the target .idml file.
	 * @param	Array	$newContent		Content parameters in the following format:
	 *									array (
	 *										[0] =>
	 *											'my_field_1' => 'contentNodeValue1',
	 *											'my_field_2' => array('contentNodeValue1', 'contentNodeValue2')
	 *									)
	 *									The story ID is derived from the .xml file in the .idml's Stories directory.
	 * @param	Array	[$newAttribs]	New attribute values to be changed for tagged TextFrames. Key name should be the
	 *									attribute that is to be changed. Can for now only be 'FillColor'.
	 *									array(
	 *										'FillColor' =>
	 *											array(
	 *												[0] =>
	 *													'my_field_3' => 'MyColorName'
	 *											)
	 *									)
	 */
	public function inject($sourcePath, $targetPath, array $newContent, $newAttribs = null) {
		$this->_workingDir 	= Garp_Adobe_InDesign_Storage::createTmpDir();
		$this->_storage		= new Garp_Adobe_InDesign_Storage($this->_workingDir, $sourcePath, $targetPath);
		$this->_storage->extract();
		$this->_spreadSet	= new Garp_Adobe_InDesign_SpreadSet($this->_workingDir);
		$storyIdsPerPage 	= $this->_spreadSet->getTaggedStoryIds();

		$newContentCount 	= count($newContent);
		$dynamicPageCount 	= count($storyIdsPerPage);

		if ($newContentCount !== $dynamicPageCount) {
			throw new Exception("The number of dynamic pages in the InDesign file is {$dynamicPageCount}, "
				. "but there are {$newContentCount} rows of data. Please "
				. ($newContentCount > $dynamicPageCount ? 'duplicate' : 'remove')
				. " some pages with tags to adjust this.");
		}

		$row = 0;
		foreach ($storyIdsPerPage as $pageStories) {
			foreach ($pageStories as $storyId) {
				$story = new Garp_Adobe_InDesign_Story($storyId, $this->_workingDir);
				$story->replaceContent($newContent[$row]);
			}
			$row++;
		}
		
		if ($newAttribs) {
			$this->_spreadSet->replaceAttribsInSpread($newAttribs, $storyIdsPerPage);
		}
		
		$this->_storage->zip();
		$this->_storage->removeWorkingDir();
	}
}