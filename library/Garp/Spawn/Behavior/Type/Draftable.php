<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Behavior_Type_Draftable extends Garp_Spawn_Behavior_Type_Abstract {
	protected $_fields = array(
		'published' => array(
			'type' => 'datetime',
			'editable' => true,
			'required' => false
		),
		'online_status' => array(
			'type' => 'checkbox',
			'editable' => true,
			'default' => 1,
			'required' => false
		)
	);

	public function __construct(Garp_Spawn_Model_Abstract $model, $origin, $name, $params = null, $type = null) {
		if (!empty($params['default'])) {
			$this->_setStatusDefault($params['default']);
		}

		// Use only the online_status part of Draftable, discard published
		if (!empty($params['draft_only'])) {
			unset($this->_fields['published']);
		}

		parent::__construct($model, $origin, $name, $params, $type);
	}

	public function needsPhpModelObserver() {
		$model = $this->getModel();
		return !$model->isTranslated();
	}


	/**
 	 * If default == 'draft', online_status default is 0.
 	 * If it's 'online', it is 1.
 	 * @param String $status
 	 * @return Void
 	 */
	public function _setStatusDefault($default) {
		if ('draft' === $default) {
			$this->_fields['online_status']['default'] = 0;
		}
	}
}
