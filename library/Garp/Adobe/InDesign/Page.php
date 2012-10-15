<?php
/**
 * Garp_Adobe_InDesign_Page
 * Wrapper around various InDesign related functionality.
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: david $
 * @version $Revision: 6480 $
 * @package Garp
 * @subpackage InDesign
 * @lastmodified $Date: 2012-09-27 06:42:27 +0200 (Thu, 27 Sep 2012) $
 */
class Garp_Adobe_InDesign_Page extends Garp_Adobe_InDesign_SpreadNode {


	/**
	 * @param Int $index The page number within a document. One-based.
	 */
	public $index;
	
	
	/**
	 * @param String $color The color label of this page.
	 */
	public $colorLabel;



	/**
	 * @param SimpleXMLElement 	$spreadConfig 	The <Spread> node of an InDesign Spread configuration.
	 * @param SimpleXMLElement	$pageConfig		The <Page> node within the Spread configuration.
	 */
	public function __construct(SimpleXMLElement $spreadConfig, $pageConfig) {
		parent::__construct($spreadConfig, $pageConfig);

		$this->index = $this->_getPageIndex();
		$this->colorLabel = $this->_getColorLabel();
	}


	protected function _getPageIndex() {
		$descriptorNodes = $this->_nodeConfig->Properties->Descriptor->children();

		foreach ($descriptorNodes as $descriptorNode) {
			if ((string)$descriptorNode->attributes()->type === "long") {
				return (int)$descriptorNode;
			}
		}
	}
	
	
	protected function _getColorLabel() {
		return (string)$this->_nodeConfig->Properties->PageColor;
	}	
}