<?php
/**
 * Garp_Browsebox
 * A browsebox is a simple interface to paging content.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @lastmodified $Date: $
 */
class Garp_Browsebox {
	/**
	 * The $_GET identifier for browsebox states
	 * @var String
	 */
	const BROWSEBOX_QUERY_IDENTIFIER = 'bb';
	
	
	/**
	 * Separator for browsebox state properties
	 * @var String
	 */
	const BROWSEBOX_QUERY_FILTER_PROP_SEPARATOR = '|';


	/**
	 * Separator for browsebox filters
	 * @var String
	 */
	const BROWSEBOX_QUERY_FILTER_SEPARATOR = '..';
	
		
	/**
	 * Configuration options
	 * @var Garp_Util_Configuration
	 */
	protected $_options;
	
	
	/**
	 * The fetched records
	 * @var Array|Zend_Db_Table_Rowset
	 */
	protected $_results = array();
	
	
	/**
	 * The SELECT object to generate results from.
	 * @var Zend_Db_Select
	 */
	protected $_select;
	
	
	/**
	 * Custom filters
	 * @var Array
	 */
	protected $_filters = array();
	
	
	/**
	 * The currently requested chunk
	 * @var Int
	 */
	protected $_chunk = 1;
	
	
	/**
	 * The max amount of chunks. Determined thru a COUNT() query
	 * @var Int
	 */
	protected $_max;
	
	
	/**
	 * Class constructor.
	 * @param Array|Garp_Util_Configuration $options Various configuration options.
	 * @return Void
	 */
	public function __construct($options) {
		$options = $options instanceof Garp_Util_Configuration ? $options : new Garp_Util_Configuration($options);
		$this->setConfig($options);
		$this->setSelect();
	}
	
	
	/**
	 * Factory method, creates browsebox objects based on App_Browsebox_Config.
	 * This method assumes its existence, so a fatal error will be thrown if 
	 * it can't be loaded.
	 * @param String $id The browsebox id
	 * @return Garp_Browsebox
	 */
	public static function factory($id) {
		$factory = new App_Browsebox_Factory();
		return $factory->create($id);
	}
	
	
	/**
	 * Initialize this browsebox - fetch content and attach to the view.
	 * @param Int $chunk What chunk to load. If null, the browsebox tries to find its state
	 * 					 in the current URL. If that doesn't work, default to chunk #1.
	 * @return Garp_Browsebox $this
	 */
	public function init($chunk = null) {
		if (is_null($chunk) && is_null($chunk = $this->_getCurrentStateFromRequest())) {
			$chunk = 1;
		}
		$this->_chunk	= $chunk;
		$this->_results = $this->_fetchContent($chunk);
		$this->_max	= $this->_fetchMaxChunks();
		return $this;
	}
	
	
	/**
	 * Set configuration options. This method makes sure there are usable defaults in place.
	 * @param Garp_Util_Configuration $options
	 * @return Garp_Browsebox $this
	 */
	public function setConfig(Garp_Util_Configuration $options) {
		$options->obligate('id')
				->obligate('model')
				->setDefault('pageSize', 10)
				->setDefault('chunkSize', 1)
				->setDefault('order', null)
				->setDefault('conditions', null)
				->setDefault('viewPath', 'partials/browsebox/'.$options['id'].'.phtml')
				->setDefault('bindings', array())
				->setDefault('filters', array())
				->setDefault('cycle', false)
				->setDefault('javascriptOptions', new Garp_Util_Configuration())
				->setDefault('select', null)
		;
		if (!empty($options['filters'])) {
			$options['filters'] = $this->_parseFilters($options['filters']);
		}
		$this->_options = $options;
		return $this;
	}
	

	/**
	 * Parse filter configuration
	 * @param Array $filters The given filter configuration
	 * @return Array
	 */
	protected function _parseFilters(array $filters) {
		$out = array();
		foreach ($filters as $key => $value) {
			// The deprecated way
			if (is_string($key) && is_array($value)) {
				$out[$key] = new Garp_Browsebox_Filter_Where($key, $value);
			} elseif ($value instanceof Garp_Browsebox_Filter_Abstract) {
				$out[$value->getId()] = $value;
			} else {
				throw new Garp_Browsebox_Exception('Invalid filter configuration encountered. Please use instances of Garp_Browsebox_Filter_Abstract to add filters.');
			}
		}
		return $out;
	}
	
	/**
	 * Add options to the configuration array
	 * @param String $key Config key
	 * @param Mixed  $val Config value
	 * @return $this
	 */
	public function setOption($key, $val) {
		$this->_options[$key] = $val;
		return $this;
	}


	/**
 	 * Get option
 	 * @return Mixed
 	 */
	public function getOption($key) {
		if (array_key_exists($key, $this->_options)) {
			return $this->_options[$key];
		}
		throw new Garp_Browsebox_Exception('Undefined key given: '.$key);
	}


	/**
	 * Create the Zend_Db_Select object that generates the results
	 * @return Garp_Browsebox $this
	 */
	public function setSelect() {
		$select = $this->_options['select'];
		if (!$select) {
			$select = $this->getModel()->select();
		}
		if ($this->_options['conditions']) {
			$select->where($this->_options['conditions']);
		}		
		if ($this->_options['order']) {
			$select->order($this->_options['order']);
		}
		$this->_select = $select;
		return $this;
	}
	
	
	/**
	 * Add custom filtering to WHERE clause
	 * @param String $filterId Key in $this->_options['filters']
	 * @param Array $params Filter values (by order of $this->_options['filters'][$filterId])
	 * @return Garp_Browsebox $this
	 */
	public function setFilter($filterId, array $params = array()) {
		if (!array_key_exists($filterId, $this->_options['filters'])) {
			throw new Garp_Browsebox_Exception('Filter "'.$filterId.'" not found in browsebox '.$this->getId().'.');
		}
		$filter = $this->_options['filters'][$filterId];
		$filter->init($params);
		
		try {
			$filter->modifySelect($this->_select);
		} catch (Garp_Browsebox_Filter_Exception_NotApplicable $e) {
			// It's okay, this filter makes no modifications to the Select object
		}

		// save the custom filters as a property so they can be sent along in the URL
		$this->_filters[$filterId] = $params;
		return $this;
	}
	
	
	/**
	 * Fetch records matching the given conditions
	 * @param Int $chunk The chunk index
	 * @return Zend_Db_Table_Rowset
	 */
	protected function _fetchContent($chunk) {
		$model = $this->getModel();
		$select = clone $this->getSelect();
		
		$select->limit(
			$this->_options['chunkSize'] * $this->_options['pageSize'], 
			(($chunk-1) * ($this->_options['pageSize'] * $this->_options['chunkSize']))
		);
		
		/**
		 * Filters may take over the fetching of results. This is neccessary for instance when you're filtering content on a HABTM related model. (in the same way Model Behaviors may return data in the beforeFetch callback)
		 */
		if ($content = $this->_fetchContentFromFilters($select)) {
			return $content;
		} else {
			return $model->fetchAll($select);
		}
	}


	/**
	 * Allow filters to fetch browsebox content.
	 * Note that there is no chaining. The first filter that returns content exits the loop.
	 * @param Zend_Db_Select $select
	 * @return Garp_Db_Table_Rowset
	 */
	protected function _fetchContentFromFilters(Zend_Db_Select $select) {
		foreach ($this->_options['filters'] as $filter) {
			try {
				$content = $filter->fetchResults($select, $this);
				return $content;
			} catch (Garp_Browsebox_Filter_Exception_NotApplicable $e) {
				// It's ok, this filter does not return content
			}
		}
		return null;
	}
	
	
	/**
	 * Determine what the max amount of chunks is for the given conditions.
	 * @return Int
	 */
	protected function _fetchMaxChunks() {
		$count = 'COUNT(*)';
		$model = $this->getModel();
		$select = clone $this->getSelect();
		$select->reset(Zend_Db_Select::COLUMNS)
			   ->reset(Zend_Db_Select::FROM)
			   ->reset(Zend_Db_Select::ORDER)
			   ->reset(Zend_Db_Select::GROUP)
		;
		$select->from($model->getName(), array('c' => $count));

		if ($max = $this->_fetchMaxChunksFromFilters($select)) {
			return $max;
		} else {
			$result = $model->fetchRow($select);
			return $result->c;
		}
	}


	/**
	 * Fetch max chunks from filters.
	 * Note that there is no chaining. The first filter that returns content exits the loop.
	 * @param Zend_Db_Select $select
	 * @return Garp_Db_Table_Rowset
	 */
	protected function _fetchMaxChunksFromFilters(Zend_Db_Select $select) {
		foreach ($this->_options['filters'] as $filter) {
			try {
				$content = $filter->fetchMaxChunks($select, $this);
				return $content;
			} catch (Garp_Browsebox_Filter_Exception_NotApplicable $e) {
				// It's ok, this filter does not return content
			}
		}
		return null;
	}


	/**
	 * Return results
	 * @return Zend_Db_Table_Rowset
	 */
	public function getResults() {
		return $this->_results;
	}
	
	
	/**
	 * Return the Select object
	 * @return Zend_Db_Select
	 */
	public function getSelect() {
		return $this->_select;
	}
	
	
	/**
	 * Retrieve a prepared model for this browsebox
	 * @return Garp_Model
	 */
	public function getModel() {
		$model = new $this->_options['model']();
		foreach ($this->_options['bindings'] as $binding => $bindingOptions) {
			if (is_numeric($binding)) {
				$model->bindModel($bindingOptions);
			} elseif (is_string($binding)) {
				$model->bindModel($binding, $bindingOptions);
			}
		}
		return $model;
	}
	
	
	/**
	 * Return id
	 * @return String
	 */
	public function getId() {
		return $this->_options['id'];
	}
	
	
	/**
	 * Return view path
	 * @return String
	 */
	public function getViewPath() {
		return $this->_options['viewPath'];
	}
	
	
	/**
	 * Get encoded custom filters for passing along to BrowseboxController.
	 * @param Boolean $encoded Pass FALSE if you wish to retrieve the raw array
	 * @return String
	 */
	public function getFilters($encoded = true) {
		$out = array();
		foreach ($this->_filters as $filterId => $params) {
			$out[] = $filterId.':'.implode(Garp_Browsebox::BROWSEBOX_QUERY_FILTER_PROP_SEPARATOR, $params);
		}
		$out = implode(Garp_Browsebox::BROWSEBOX_QUERY_FILTER_SEPARATOR, $out);
		return base64_encode($out);
	}
	
	
	/**
	 * Return config options relevant for the Javascript Browsebox implementation.
	 * @return Garp_Util_Configuration
	 */
	public function getJavascriptOptions() {
		$out = $this->_options['javascriptOptions'];
		$out['options'] = base64_encode(serialize(array(
			'pageSize' => $this->_options['pageSize'],
			'viewPath' => $this->_options['viewPath'],
			'filters'  => $this->getFilters()
		)));
		return $out;
	}
	
	
	/**
	 * Return the URL of the previous browsebox page.
	 * @return Int|Boolean False if this is the first page
	 */
	public function getPrevUrl() {
		if ($this->_chunk == 1) {
			return $this->_options['cycle'] ? $this->createStateUrl(ceil($this->_max / $this->_options['pageSize'])) : false;
		}
		return $this->createStateUrl($this->_chunk-1);
	}
	
	
	/**
	 * Return the URL of the next browsebox page.
	 * @return Int|Boolean False if this is the last page
	 */
	public function getNextUrl() {
		$lastChunk = ceil($this->_max / $this->_options['pageSize']);
		if ($this->_chunk == $lastChunk) {
			return $this->_options['cycle'] ? $this->createStateUrl(1) : false;
		}		
		return $this->createStateUrl($this->_chunk+1);
	}
	
	
	/**
	 * Find out wether the current browsebox has a state available in the current URL.
	 * @see self::createStateUrl() for an explanation on what the URL format looks like.
	 * @return Int The requested chunk if found, or NULL.
	 */
	protected function _getCurrentStateFromRequest() {
		$here = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
		$queryString = parse_url($here, PHP_URL_QUERY);
		parse_str($queryString, $queryComponents);		
		if (!empty($queryComponents[Garp_Browsebox::BROWSEBOX_QUERY_IDENTIFIER][$this->getId()])) {
			$chunk = $queryComponents[Garp_Browsebox::BROWSEBOX_QUERY_IDENTIFIER][$this->getId()];			
			return $chunk;
		}
		return null;
	}
	
	
	/**
	 * Create the URL preserving the state of this browsebox.
	 * The format of the query string is as follows;
	 * ?bb[<browsebox-id>]=<requested-chunk>,<dynamic-filters>&bb[<browsebox-id2>]=...
	 * Where "bb" is captured in the class constant Garp_Browsebox::BROWSEBOX_QUERY_IDENTIFIER
	 * and "," is captured in the class constant Garp_Browsebox::BROWSEBOX_QUERY_PROP_SEPARATOR.
	 * There may be multiple browsebox states in the URL and this function preserves them all.
	 * @param Int $chunk The chunk that's shown when requesting the returned URL.
	 * @return String
	 */
	public function createStateUrl($chunk) {
		$here = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
		$queryString = parse_url($here, PHP_URL_QUERY);
		parse_str($queryString, $queryComponents);
		$nextChunkState = $chunk;
		$queryComponents[Garp_Browsebox::BROWSEBOX_QUERY_IDENTIFIER][$this->getId()] = $nextChunkState;
		$newQueryString = '?'.http_build_query($queryComponents);
		return $newQueryString;
	}
}
