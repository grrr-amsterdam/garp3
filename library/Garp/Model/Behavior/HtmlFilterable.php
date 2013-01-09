<?php
/**
 * Garp_Model_Behavior_HtmlFilterable
 * Filter unwanted HTML out of rich-text fields.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Behavior
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_HtmlFilterable extends Garp_Model_Behavior_Abstract {
	/**
	 * Fields to work on
	 * @var Array
	 */
	protected $_fields;


	/**
	 * Make sure the config array is at least filled with some default values to work with.
	 * @param Array $config Configuration values
	 * @return Array The modified array
	 */
	protected function _setup($config) {
		$this->_fields = $config;
	}


	/**
	 * Filter unwanted HTML out of a string
	 * @param String $string The string
	 * @param HTMLPurifier_Config $config
	 * @return String The filtered string
	 */
	public function filter($string, HTMLPurifier_Config $config = null) {
		if ($string) {
			require_once 'Garp/3rdParty/htmlpurifier/library/HTMLPurifier.auto.php';

			if (!$config) {
				$config = $this->_getConfig();
			}

			$purifier = new HTMLPurifier($config);
			$string = $purifier->purify($string);
		}
		return $string;
	}


	/**
	 * Create config.
	 * @return HTMLPurifier_Config
	 */
	protected function _getConfig() {
		$config = HTMLPurifier_Config::createDefault();
		$config->set('HTML.DefinitionID', 'Garp 3.0 CMS');
		$config->set('HTML.DefinitionRev', 5);
		$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
		$config->set('HTML.Trusted', true);
		$config->set('HTML.AllowedElements', array(
			'a', 'abbr', 'acronym', 'b', 'blockquote', 'br', 'caption', 'cite', 'code', 'dd', 'del', 'dfn', 'div', 'dl', 'dt', 'em', 'embed', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'i', 'iframe', 'img', 'ins', 'kbd', 'li', 'object', 'ol', 'p', 'param', 'pre', 's', 'span', 'strong', 'sub', 'sup', 'u', 'ul', 'var'
		));

		$config->set('AutoFormat.RemoveEmpty', true);
		$config->set('AutoFormat.RemoveSpansWithoutAttributes', true);
		$config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
		$config->set('Output.TidyFormat', true);
		$config->set('Attr.AllowedClasses', array('figure'));
		$config->set('CSS.AllowedProperties', array('font-weight', 'font-style', 'float', 'vertical-align', 'width', 'height'));
		$config->set('CSS.MaxImgLength', null);
    	$config->set('Cache.SerializerPath', APPLICATION_PATH.'/data/cache');
		$config->set('URI.MakeAbsolute', true);
		$config->set('URI.Base', 'http://'.$_SERVER['HTTP_HOST'].Zend_Controller_Front::getInstance()->getBaseUrl().'/');
		$config->set('Filter.Custom', array(
			new Garp_3rdParty_Ext_HTMLPurifier_Filter_MyIframe(),
			new Garp_3rdParty_Ext_HTMLPurifier_Filter_MyEmbed(),
		));

		// add proprietary elements
		if ($def = $config->maybeGetRawHTMLDefinition()) {
			$def->addAttribute('a', 'target', 'Enum#_blank,_self');
			
			$iframe = $def->addElement(
				'iframe',	// name
				'Inline',	// content set
				'Custom: #PCDATA',	// allowed children
				'Common',	// attribute collection
				array( 		// attributes
					'src*' => 'URI',
					'width*' => 'Number',
					'height*' => 'Number',
					'frameborder' => 'Text',
					'scrolling'	=> 'Text',
					'allowtransparency' => 'Text',
				)
			);
			$embed = $def->addElement(
				'embed',
				'Inline',
				'Custom: #PCDATA',
				'Common',
				array(
					'src*' => 'URI',
					'type*' => 'Text',
					'width*' => 'Number',
					'height*' => 'Number',
					'allowscriptaccess' => 'Text'
				)
			);
		}
		return $config;
	}


	/**
	 * Before insert callback. Manipulate the new data here. Set $data to FALSE to stop the insert.
	 * @param Array $options The new data is in $args[1]
	 * @return Array Or throw Exception if you wish to stop the insert
	 */
	public function beforeInsert(array &$args) {
		$data = &$args[1];
		foreach ($this->_fields as $field) {
			if (array_key_exists($field, $data)) {
				$data[$field] = $this->filter($data[$field]);
			}
		}
	}


	/**
	 * Before update callback. Manipulate the new data here.
	 * @param Array $data The new data is in $args[1]
	 * @return Void
	 */
	public function beforeUpdate(array &$args) {
		$data = &$args[1];
		foreach ($this->_fields as $field) {
			if (!empty($data[$field])) {
				$data[$field] = $this->filter($data[$field]);
			}
		}
	}
}
