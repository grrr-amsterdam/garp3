<?php
class Model_${modelName} extends Garp_Model_Db {
	protected $_name = '${tableName}';

	protected $_referenceMap = ${referenceMap};

	public function init() {
${observers}
		parent::init();
	}
}