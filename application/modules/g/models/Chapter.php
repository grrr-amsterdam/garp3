<?php
/**
 * G_Model_Chapter
 * class description
 *
 * @author       Harmen Janssen | grrr.nl
 * @package      Garp
 * @subpackage   Model
 */
class G_Model_Chapter extends Model_Base_Chapter {
	/**
 	 * Content nodes. Saved here from beforeInsert til afterInsert
 	 * @var Array
 	 */
	protected $_contentNodeList = array();


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
		if (!empty($data['content'])) {
			// Save chapters until after the Article is inserted, because we need the primary key.
			if (is_string($data['content'])) {
				$data['content'] = Zend_Json::decode($data['content']);
			}
			$this->_contentNodeList = $data['content'];
		}
		unset($data['content']);
	}


	/**
 	 * Generic afterSave handler, called from afterInsert and afterUpdate
 	 * @param Int $id The id of the involved Article
 	 * @return Void
 	 */
	protected function _afterSave($id) {
		if (!empty($this->_contentNodeList)) {
			$this->relateContentNodes($this->_contentNodeList, $id);
			// Reset queue.
			$this->_contentNodeList = array();
		}
	}


	/**
	 * Relate ContentNodes to a chapter.
	 * @param Array $contentNodeList
	 * @param Int $chapterId
	 * @return Void
 	 */
	public function relateContentNodes($contentNodeList, $chapterId) {
		foreach ($contentNodeList as $contentNode) {
			$contentNode = $this->_getValidContentNodeData($contentNode);
			
			// Save ContentNode
			$contentNode['chapter_id'] = $chapterId;
			$contentNodeId = $this->_insertContentNode($contentNode);

			// @todo Move everything below here to G_Model_ContentNode::afterInsert()

			// Determine content type
			$contentTypeModelName = 'Model_'.$contentNode['model'];
			$contentTypeModel = new $contentTypeModelName();

			// Check for existing id
			$data = $contentNode['data'];
			if (empty($data['id'])) {
				// If no id is present, create a new subtype record
				$contentTypeId = $contentTypeModel->insert($data);
			} else {
				// Update the chapter subtype's content
				$contentTypeModel->update($data, 'id = '.$contentTypeModel->getAdapter()->quote($data['id']));
				$contentTypeId = $data['id'];
			}

			// Relate the ContentNode to the subtype record
			Garp_Content_Relation_Manager::relate(array(
				'modelA' => 'Model_ContentNode',
				'modelB' => $contentTypeModel,
				'keyA'   => $contentNodeId,
				'keyB'   => $contentTypeId,
			));
		}
	}


	/**
 	 * Insert new ContentNode record
 	 * @param Garp_Util_Configuration $contentNodeData
 	 * @return Int The primary key
 	 */
	protected function _insertContentNode(Garp_Util_Configuration $contentNodeData) {
		$contentNodeModel = new Model_ContentNode();
		$contentNodeId = $contentNodeModel->insert(array(
			'columns'    => $contentNodeData['columns'],
			'type'       => $contentNodeData['type'],
			'chapter_id' => $contentNodeData['chapter_id'],
		));
		return $contentNodeId;
	}


	/**
 	 * Validate content node
 	 * @param Array $contentNode
 	 * @return Array
 	 */
	protected function _getValidContentNodeData($contentNode) {
		$contentNode = $contentNode instanceof Garp_Util_Configuration ? $contentNode : new Garp_Util_Configuration($contentNode);
		$contentNode
			->obligate('model')
			->obligate('data')
			->obligate('columns')
			->setDefault('type', '')
		;
		return $contentNode;
	}
}
