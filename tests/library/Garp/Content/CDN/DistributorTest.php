<?php
/**
 * Garp_Content_Distribution
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @group CDN
 * @lastmodified $Date: $
 */
class Garp_Content_CDN_Distributor_Test extends PHPUnit_Framework_TestCase {
	const FILTER_STRING_MATCHING_MULTIPLE = 'css';
	const FILTER_STRING_MATCHING_ONE = 'css/garp/images/garp.png';
	const FILTER_STRING_NOT_MATCHING = 'l3$#j@[hdv%@u2w2a9g08u.e3#d@c';


	public function test_No_Assets_Should_Be_Selected_If_No_Match() {
		$distributor 		= new Garp_Content_CDN_Distributor();
		$assetList 			= $distributor->select(self::FILTER_STRING_NOT_MATCHING, false);

		$this->assertSame(count($assetList), 0);
	}


	public function test_Multiple_Assets_Should_Be_Selected_If_Match() {
		$distributor 		= new Garp_Content_CDN_Distributor();
		$assetList 			= $distributor->select(self::FILTER_STRING_MATCHING_MULTIPLE, false);

		$this->assertTrue((bool)count($assetList));
	}


	public function test_One_Asset_Should_Be_Selected_If_Specific_Match() {
		$distributor 		= new Garp_Content_CDN_Distributor();
		$assetList 			= $distributor->select(self::FILTER_STRING_MATCHING_ONE, false);

		$this->assertSame(count($assetList), 1);
	}

}