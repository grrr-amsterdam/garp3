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
		$config->obligate('chapterTypes');
		$this->_config = $config;
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
			$this->_convertChapterLayout($result);
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
 	 * Bind all the Chapter models to fetch the complete Article.
 	 * @param Garp_Model_Db $model
 	 * @return Void
 	 */
	public function bindWithChapters(Garp_Model_Db &$model) {
		$model->bindModel('chapters', array(
			'modelClass' => 'Model_Chapter'
		));

		$chapterModel = new Model_Chapter();
		foreach ($this->_config['chapterTypes'] as $chapterType) {
			$chapterModel->bindModel($chapterType);
		}
	}


	/**
 	 * Convert bound Chapters to a more concise format.
 	 * @param Garp_Db_Table_Row $row
 	 * @return Void
 	 */
	protected function _convertChapterLayout(Garp_Db_Table_Row &$row) {
		$chapters = array();
		$chapterTypes = $this->_config['chapterTypes'];
		if (isset($row->chapters) && count($row->chapters)) {
			foreach ($row->chapters as $chapterRow) {
				$chapter = array();
				$chapter['columns'] = $chapterRow->columns;
				$chapter['class'] = $chapterRow->class;
				foreach ($chapterTypes as $chapterType) {
					if ($chapterRow->{$chapterType}) {
						$modelName = explode('_', $chapterType);
						$modelName = array_pop($modelName);
						$chapter['model'] = $modelName;
						$chapter['data']  = $chapterRow->{$chapterType}->toArray();
						break;
					}
				}
				$chapters[] = $chapter;
			}
			$row->chapters = $chapters;
		}
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
			'modelA' => 'Model_Article',
			'modelB' => 'Model_Chapter',
			'keyA'   => $articleId,
		));
		// Reverse order since the Weighable behavior sorts chapter by weight DESC,
		// giving each new chapter the highest weight.
		$chapters = array_reverse($chapters);
		foreach ($chapters as $chapterData) {
			$chapterData = $this->_getValidatedChapterData($chapterData);

			// Create a new chapter
			$chapterModel = new Model_Chapter();
			$chapterId = $chapterModel->insert(array(
				'article_id' => $articleId,
				'columns'    => $chapterData['columns'],
				'class'      => $chapterData['class'],
			));

			// Determine Chapter subtype
			$chapterSubtypeModelName = 'Model_'.$chapterData['model'];
			$chapterSubtypeModelCls = new $chapterSubtypeModelName();
			// Check for existing id
			$data = $chapterData['data'];
			
			if (empty($data['id'])) {
				// If no id is present, create a new subtype record
				$chapterSubtypeId = $chapterSubtypeModelCls->insert($data);
			} else {
				// Update the chapter subtype's content
				$chapterSubtypeModelCls->update($data, 'id = '.$chapterSubtypeModelCls->getAdapter()->quote($data['id']));
				$chapterSubtypeId = $data['id'];
			}

			// Relate the subtype to the chapter
			Garp_Content_Relation_Manager::relate(array(
				'modelA' => 'Model_Chapter',
				'modelB' => $chapterSubtypeModelName,
				'keyA'   => $chapterId,
				'keyB'   => $chapterSubtypeId
			));
		}
	}


	/**
 	 * Convert chapter to Garp_Util_Configuration and validate its keys.
 	 * @param Array|Garp_Util_Configuration $chapterData
 	 * @return Garp_Util_Configuration
 	 */
	protected function _getValidatedChapterData($chapterData) {
		$chapterData = $chapterData instanceof Garp_Util_Configuration ? $chapterData : new Garp_Util_Configuration($chapterData);
		$chapterData->obligate('model')->obligate('data')->obligate('columns')->setDefault('class', '');
		return $chapterData;
	}
}
