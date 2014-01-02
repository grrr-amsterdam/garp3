<?php
/**
 * Garp_Browsebox
 * A browsebox is a simple interface to paging content.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Helper
 * @lastmodified $Date: $
 */
class G_View_Helper_Browsebox extends Zend_View_Helper_Abstract {
	/**
	 * Keep track of all browseboxes
	 * @var Array
	 */
	protected static $_store = array();
	
	
	/**
	 * Render a browsebox object
	 * @param Garp_Browsebox $browsebox
	 * @param Array $params Extra parameters sent to the partial
	 * @return String
	 */
	public function browsebox(Garp_Browsebox $browsebox = null, $params = array()) {
		if (is_null($browsebox)) {
			return $this;
		}
		
		static::$_store[$browsebox->getId()] = $browsebox;
		$params = array_merge($params, array(
			'results'	=> $browsebox->getResults(),
			'next'		=> $browsebox->getNextUrl(),
			'prev'		=> $browsebox->getPrevUrl()
		));
		return $this->view->partial(
			$browsebox->getViewPath(),
			'default',
			$params
		);
	}
	
	
	/**
	 * Create Javascript configuration for browsebox objects.
	 * @param String $id The browsebox id. If given, only config for this browsebox is returned.
	 * @return String 
	 */
	public function config($id = false) {
		$boxes = $this->_getAllBrowseboxes();
		$script = "Garp.browseboxes = [];\n";
		foreach ($boxes as $boxId => $box) {
			$jsConfig  = $this->_getJavascriptOptions($box);			
			$boxScript = '';
			$boxScript .= "new Garp.Browsebox({\n";
			$boxScript .= "\t\"id\": \"$boxId\",\n";
			$i = 0;
			$total = count($jsConfig);
			foreach ($jsConfig as $confKey => $confVal) {
				$boxScript .= "\t\"$confKey\": \"$confVal\"";
				if ($i < ($total-1)) {
					$boxScript .= ',';
				}
				$boxScript .= "\n";
				$i++;
			}
			$boxScript .= "})";
			
			if ($id && $boxId === $id) {
				return $this->_wrapInTags($boxScript);
			}
			$script .= "Garp.browseboxes.push($boxScript);\n";
		}
		return $this->_wrapInTags($script);
	}
	
	
	/**
	 * Return all currently initialized browseboxes.
	 * @return Array
	 */
	protected function _getAllBrowseboxes() {
		return static::$_store;
	}
	
	
	/**
	 * Wrap script in <script> tags.
	 * @param String $script
	 * @return String
	 */
	protected function _wrapInTags($script) {
		return sprintf("<script type=\"text/javascript\">\n%s\n</script>", $script);
	}
	
	
	/**
	 * Get JS options
	 * @param Garp_Browsebox $box
	 * @return Garp_Util_Configuration
	 */
	protected function _getJavascriptOptions(Garp_Browsebox $box) {
		$options = $box->getJavascriptOptions();
		$options = $options instanceof Garp_Util_Configuration ? $options : new Garp_Util_Configuration($options);
		return $options->setDefault('rememberState', false)
				   	   ->setDefault('transition', 'crossFade')
		;
	}
}
