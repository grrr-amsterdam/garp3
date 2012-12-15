<?php
/**
 * Garp_Model_Behavior_Article
 * Wrapper around "article" style functionality. Provides rich magazine-type layouts.
 *
 * @author       Harmen Janssen
 * @version      1.0
 * @package      Garp
 * @subpackage   Behavior
 */
class Garp_Model_Behavior_Article extends Garp_Model_Behavior_Abstract {
	/**
 	 * Queue of chapters. These are queued beforeSave, 
 	 * and popped from the queue afterSave.
 	 * @var Array
 	 */
	protected $_queuedChapters = array();


	/**
 	 * Config values
 	 * @var Garp_Util_Configuration
 	 */
	protected $_config;


	/**
 	 * Configure the behavior
 	 * @param Array $config
 	 * @return Void
 	 */
	protected function _setup($config) {
		$config = new Garp_Util_Configuration($config);
		$config->obligate('contentTypes');
		$this->_config = $config;
	}


	/**
 	 * Bind all the Chapters and ContentNodes to fetch the complete Article.
 	 * @param Garp_Model_Db $model
 	 * @return Void
 	 */
	public function bindWithChapters(Garp_Model_Db &$model) {
		$model->bindModel('chapters', array(
			'modelClass' => 'Model_Chapter'
		));

		$chapterModel = new Model_Chapter();
		$chapterModel->bindModel('content', array(
			'modelClass' => 'Model_ContentNode'
		));

		$contentNodeModel = new Model_ContentNode();
		foreach ($this->_config['contentTypes'] as $chapterType) {
			$contentNodeModel->bindModel($chapterType);
		}
	}	


	/**
 	 * An article is nothing without its Chapters. Before every fetch
 	 * we make sure the chapters are fetched right along, at least in 
 	 * the CMS.
 	 * @param Array $args Event listener parameters
 	 * @return Void
 	 */
	public function beforeFetch(&$args) {
		if (Zend_Registry::isRegistered('CMS') && Zend_Registry::get('CMS')) {
			$model = &$args[0];
			$this->bindWithChapters($model);
		}
	}


	/**
 	 * AfterFetch callback, reforms the bound Chapters into a
 	 * more concise collection.
 	 * @param Array $args Event listener parameters
 	 * @return Void
 	 */
	public function afterFetch(&$args) {
		$results = &$args[1];
		$isArray = false;
		// Create a single, loopable interface
		if (!$results instanceof Garp_Db_Table_Rowset) {
			$results = array($results);
		}

		foreach ($results as $result) {
			if (!isset($result->chapters)) {
				continue;
			}
			$result->chapters = array_map(array($this, '_convertChapterLayout'), $result->chapters->toArray());
		}

		// return the pointer to 0
		if ($results instanceof Garp_Db_Table_Rowset) {
			$results->rewind();
		} else {
			// also, return results to the original format if it was no Rowset to begin with.
			$results = $results[0];
		}
	}


	/**
 	 * BeforeInsert event listener.
 	 * @param Array $args Event listener parameters
 	 * @return Void
 	 */
	public function beforeInsert(&$args) {
		$data = &$args[1];
		$this->_beforeSave($data);
	}


	/**
 	 * BeforeUpdate event listener.
 	 * @param Array $args Event listener parameters
 	 * @return Void
 	 */
	public function beforeUpdate(&$args) {
		$data = &$args[1];
		$this->_beforeSave($data);
	}


	/**
 	 * AfterInsert event listener.
 	 * @param Array $args Event listener parameters
 	 * @return Void
 	 */
	public function afterInsert(&$args) {
		$pk = $args[2];
		$this->_afterSave($pk);
	}


	/**
 	 * AfterUpdate event listener.
 	 * @param Array $args Event listener parameters
 	 * @return Void
 	 */
	public function afterUpdate(&$args) {
		$model = $args[0];
		$where = $args[3];
		$primaryKey = $model->extractPrimaryKey($where);
		$id = $primaryKey['id'];
		$this->_afterSave($id);
	}


	/**
 	 * Generic beforeSave handler, called by beforeInsert and beforeUpdate
 	 * @param Array $data
 	 * @return Void
 	 */
	protected function _beforeSave(&$data) {
		// Make sure chapters are not saved.
		if (!empty($data['chapters'])) {
			// Save chapters until after the Article is inserted, because we need the primary key.
			if (is_string($data['chapters'])) {
				$data['chapters'] = Zend_Json::decode($data['chapters']);
			}
			$this->_queuedChapters = $data['chapters'];
		}
		unset($data['chapters']);
	}


	/**
 	 * Generic afterSave handler, called from afterInsert and afterUpdate
 	 * @param Int $id The id of the involved Article
 	 * @return Void
 	 */
	protected function _afterSave($id) {
		if (!empty($this->_queuedChapters)) {
			$this->relateChapters($this->_queuedChapters, $id);
			// Reset queue.
			$this->_queuedChapters = array();
		}
	}


	/**
 	 * Convert bound Chapters to a more concise format.
 	 * @param Array $chapterRow
 	 * @return Array
 	 */
	protected function _convertChapterLayout(array $chapterRow) {
		$chapter = array();
		$chapter['type'] = $chapterRow['type'];
		if (!isset($chapterRow['content'])) {
 			return $chapter;
		}
		$chapter['content'] = array_map(array($this, '_convertContentNodeLayout'), $chapterRow['content']);
		return $chapter;
	}


	/**
 	 * Convert bound ContentNodes to a more concise format.
 	 * @param Array $contentNodeRow
 	 * @return Array
 	 */
	protected function _convertContentNodeLayout(array $contentNodeRow) {
		$contentTypes = $this->_config['contentTypes'];
		$contentNode = array();
		foreach ($contentTypes as $contentType) {
			if ($contentNodeRow[$contentType]) {
				$modelName = explode('_', $contentType);
				$modelName = array_pop($modelName);
				$contentNode['model'] = $modelName;
				$contentNode['type']  = $contentNodeRow['type'];
				$contentNode['data']  = $contentNodeRow[$contentType];
				$contentNode['columns'] = $contentNodeRow['columns'];
				break;
			}
		}
		return $contentNode;
	}


	/**
 	 * Relate Chapters.
 	 * Called after insert and after update.
 	 * @param Array $chapters
 	 * @param Int $articleId The id of the involved Article
 	 * @return Void
 	 */
	public function relateChapters(array $chapters, $articleId) {
		// Start by unrelating all chapters
		Garp_Content_Relation_Manager::unrelate(array(
			// @todo Model_Article should be dynamic! Could be Model_Project!
			'modelA' => 'Model_Article',
			'modelB' => 'Model_Chapter',
			'keyA'   => $articleId,
		));
		// Reverse order since the Weighable behavior sorts chapter by weight DESC,
		// giving each new chapter the highest weight.
		$chapters = array_reverse($chapters);
		foreach ($chapters as $chapterData) {
			$chapterData = $this->_getValidChapterData($chapterData);

			/**
 			 * Insert a new chapter.
 			 * The chapter will take care of storing and relating the 
 			 * content nodes.
 			 */
			$chapterModel = new Model_Chapter();
			$chapterId = $chapterModel->insert(array(
				// @todo article_id should be dynamic. Could be project_id for instance.
				'article_id' => $articleId,
				'type'       => $chapterData['type'],
				'content'    => $chapterData['content'],
			));
		}
	}


	/**
 	 * Convert chapter to Garp_Util_Configuration and validate its keys.
 	 * @param Array|Garp_Util_Configuration $chapterData
 	 * @return Garp_Util_Configuration
 	 */
	protected function _getValidChapterData($chapterData) {
		$chapterData = $chapterData instanceof Garp_Util_Configuration ? $chapterData : new Garp_Util_Configuration($chapterData);
		$chapterData
			->obligate('content')
			->setDefault('type', '')
		;
		return $chapterData;
	}
}
