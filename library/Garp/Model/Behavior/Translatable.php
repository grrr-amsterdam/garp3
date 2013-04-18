<?php
/**
 * Garp_Model_Behavior_Translatable
 * Makes it easy to save content in different languages.
 * Allows for the following:
 *
 * array(
 *   "name" => array(
 *     "en" => "the quick brown fox",
 *     "nl" => "de snelle bruine vos"
 *   )
 * )
 *
 * The translated content will be extracted into an i18n record.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Model_Behavior
 */
class Garp_Model_Behavior_Translatable extends Garp_Model_Behavior_Abstract {
	/**
 	 * The columns that can be translated
 	 * @var Array
 	 */
	protected $_translatableFields;

	/**
 	 * Stores translatable fields from beforeSave til afterSave
 	 * @var Array
 	 */
	protected $_queue;

	/**
 	 * Configure this behavior
 	 * @param Array $config
 	 * @return Void
 	 */
	protected function _setup($config) {
		$this->_translatableFields = $config;
	}

	/**
 	 * Callback before inserting or updating.
 	 * Extracts translatable fields.
 	 * @param Array $data The submitted data
 	 * @return Void
 	 */
	protected function _beforeSave($data) {
		foreach ($this->_translatableFields as $field) {
			if (!empty($data[$field])) {
				$this->_queue[$field] = $data[$field];
				unset($data[$field]);
			}
		}
	}

	/**
 	 * Callback after inserting or updating.
 	 * @param Array $primaryKeys 
 	 */
	protected function _afterSave($primaryKeys) {
		$locales = Garp_I18n::getAllPossibleLocales();

	}

	/**
 	 * Before insert callback
 	 * @param Array $args 
 	 * @return Void
 	 */
	public function beforeInsert(&$args) {
		$model = &$args[0];
		$data  = &$args[1];
		$this->_beforeSave();
	}

	/**
 	 * Before update callback
 	 * @param Array $args
 	 * @return Void
 	 */
	public function beforeUpdate(&$args) {
		$model = &$args[0];
		$data  = &$args[1];
		$where = &$args[2];
		$this->_beforeSave();
	}

	/**
 	 * After insert callback
 	 * @param Array $args
 	 * @return Void
 	 */
	public function afterInsert(&$args) {
		$model      = &$args[0];
		$data       = &$args[1];
		$primaryKey = &$args[2];
		$this->_afterSave((array)$primaryKey);
	}

	/**
 	 * After update callback
 	 * @param Array $args 
 	 * @return Void
 	 */
	public function afterUpdate(&$args) {
		$model        = &$args[0];
		$affectedRows = &$args[1];
		$data         = &$args[2];
		$where        = &$args[3];

		$pkExtractor = new Garp_Db_PrimaryKeyExtractor($model, $where);
		$pks = $pkExtractor->extract();
		$this->_afterSave($pks);
	}
}
