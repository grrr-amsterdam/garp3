<?php
/**
 * Garp_Adobe_InDesign_Spread
 * Wrapper around various InDesign related functionality.
 * Note: currently only works with pages that are horizontally laid out on the spread.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: david $
 * @version $Revision: 6498 $
 * @package Garp
 * @subpackage InDesign
 * @lastmodified $Date: 2012-10-01 17:07:46 +0200 (Mon, 01 Oct 2012) $
 */
class Garp_Adobe_InDesign_Spread {
	
	const PATH = 'Spreads/Spread_%s.xml';

	/**
	 * @param Array $pages 	The pages within this spread. Values are Garp_Adobe_InDesign_Page objects.
	 *						These are ordered by x-coordinate, where the most left page comes first.
	 */
	public $pages = array();
	
	public $textFrames = array();
	
	/**
	 * @var Array $stories 	An array of Story IDs, where the key is the Page index (number, not Page ID) where this Story is placed upon.
	 *						array(
	 *							2 => array('eu3f', 'p3fe'),
	 *							3 => array('3ri2')
	 *						)
	 */
	public $stories = array();

	protected $_id;	
	
	protected $_path;
	
	/**
	 * @var SimpleXMLElement $_xml
	 */
	protected $_xml;

	/**
	 * @var SimpleXMLElement $_spreadNodes
	 */
	protected $_spreadNodes;
	
	protected $_workingDir;



	/**
	 * @param String	$this->_xml 	The contents of an InDesign Spread xml configuration, as found in an .idml file.
	 */
	public function __construct($spreadId, $workingDir) {
		$this->_id				= $spreadId;
		$this->_workingDir		= $workingDir;
		$this->_path			= $this->_buildPath($spreadId);
		$spreadContent			= file_get_contents($this->_path);
		$this->_xml				= new SimpleXMLElement($spreadContent);
		$this->_spreadNodes		= $this->_xml->Spread->children();

		$this->pages 			= $this->_buildPages();
		$this->textFrames 		= $this->_buildTextFrames();
		$this->stories 			= $this->_buildStories();
	}
	

	public static function getSpreadIdFromPath($path) {
		$spreadId = preg_replace('/.+Spread_(\w+)\.xml/', '$1', $path);
		return $spreadId;
	}
	
	
	public function setTextFrameAttribute($storyId, $attribute, $newValue) {
		switch ($attribute) {
			case 'FillColor':
				$newValue = 'Color/' . $newValue;
			break;
			default:
				throw new Exception('Setting other Spread TextFrame attributes than FillColor is not supported at the time.');
		}
		
		$node = $this->_xml->xpath("//TextFrame[@ParentStory='{$storyId}']");
		if ($node) {
			$node[0]->attributes()->$attribute = $newValue;
		}
		$this->save();
	}
	
	
	public function save() {
		if (file_put_contents($this->_path, $this->_xml->asXml()) === false) {
			throw new Exception('Could not write to ' . $this->_path);
		}
	}


	/**
	 * Returns a list of stories with the same structure as $this->stories,
	 * but only including the stories that have an InDesign tag appended.
	 */
	public function getStoriesWithTaggedTextFrames() {
		$filteredStories = array();
		foreach ($this->stories as $pageIndex => $storyNodes) {
			$pageStories = array();

			foreach ($storyNodes as $storyIndex => $storyId) {
				if ($this->_storyHasTaggedTextFrame($storyId)) {
					$pageStories[$storyIndex] = $storyId;
				}
			}
			
			if ($pageStories) {
				$filteredStories[$pageIndex] = $pageStories;
			}
		}

		return $filteredStories;
	}
	
	
	public function usesStory($storyId) {
		foreach ($this->stories as $pageIndex => $stories) {
			if (in_array($storyId, $stories)) {
				return true;
			}
		}
		return false;
	}


	protected function _storyHasTaggedTextFrame($storyId) {
		$filepath 		= $this->_workingDir . "Stories/Story_{$storyId}.xml";
		$storyContent 	= file_get_contents($filepath);
		$xml 			= new SimpleXMLElement($storyContent);
		return (bool)$xml->xpath('//XMLElement');
	}
	
	
	protected function _buildPath($spreadId) {
		return $this->_workingDir . sprintf(self::PATH, $spreadId);
	}


	/**
	 * This method retrieves the Stories referenced by TextFrame entries on the current Spread, that geometrically map to this page.
	 * This has be calculated by geometry, since a TextFrame is not directly linked to a Page, but to a Spread.
	 */
	protected function _buildStories() {
		$storiesByPageNumber 	= array();
		$storiesByTag			= array();
		$pagesCount				= count($this->pages);

		//	build an array with all textframe positions, divided into the accompanying story XML tags
		foreach ($this->textFrames as $textFrame) {
			$story = new Garp_Adobe_InDesign_Story($textFrame->storyId, $this->_workingDir);
			if ($tag = $story->getTag()) {
				$storiesByTag[$tag][] = array(
					'storyId' => $textFrame->storyId,
					'x' => $textFrame->x
				);
			}
		}

		//	sort the stories by horizontal position
		$sortFunction = function($storyA, $storyB) {
			if ($storyA['x'] == $storyB['x']) {
				return 0;
			}
			return $storyA['x'] > $storyB['x'] ? 1 : -1;
		};

		foreach ($storiesByTag as &$stories) {
			usort($stories, $sortFunction);
		}

		//	now divide the stories in pages
		foreach ($storiesByTag as $tagStories) {
			$tagStoriesCount 	= count($tagStories);
			$tagStoriesPerPage 	= $tagStoriesCount / $pagesCount;
			
			foreach ($tagStories as $s => $tagStory) {
				$page 			= floor($s / $tagStoriesPerPage);
				$pageNumber 	= $this->pages[$page]->index;
				$storiesByPageNumber[$pageNumber][] = $tagStory['storyId'];
			}
		}

		return $storiesByPageNumber;
	}
	
	
	protected function _buildTextFrames() {
		$textFrames = array();

		foreach ($this->_spreadNodes as $tag => $nodeConfig) {
			switch ($tag) {
				case 'TextFrame':
					$textFrames[] = new Garp_Adobe_InDesign_TextFrame($this->_xml, $nodeConfig);
				break;
				case 'Group':
					foreach ($nodeConfig as $groupNodeTag => $groupNodeValue) {
						if ($groupNodeTag === 'TextFrame') {
							$textFrames[] = new Garp_Adobe_InDesign_TextFrame($this->_xml, $groupNodeValue);
						}
					}
				break;
			}
		}
		
		return $textFrames;
	}
	

	protected function _buildPages() {
		$pages = array();

		foreach ($this->_spreadNodes as $tag => $spreadNode) {
			if ($tag === 'Page') {
				$pages[] = new Garp_Adobe_InDesign_Page($this->_xml, $spreadNode);
			}
		}

		//	sort by x-coordinate
		usort($pages, function($a, $b) {
			if ($a->x === $b->x) return 0;
			return $a->x < $b->x ? -1 : 1;
		});

		return $pages;
	}
}
