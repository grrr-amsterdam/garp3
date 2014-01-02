<?php
/**
 * Generated JS model
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Model
 * @lastmodified $Date: $
 */
class Garp_Model_Spawn_Js_Renderer {
	/** Maximum length of a textual field before it becomes a textarea. */
	const TEXTFIELD_MAX_LENGTH = 124;
	
	const _BASE_MODEL_PATH = '/../public/js/models/base/';
	const _EXTENDED_MODEL_PATH = '/../public/js/models/';

	protected $_model;
	protected $_modelsIncluder;
	protected $_excludeFromFormFields = array('published', 'online_status');



	public function __construct(Garp_Model_Spawn_Model $model) {
		$this->_model = $model;
	}
	
	
	public function save() {
		$baseModelPath = $this->_getBaseModelPath($this->_model->id);
		$baseModelContent = $this->_renderBaseModel();

		if ($this->_model->module) {
			p("  Javascript base model is in module {$this->_model->module}.");
		} elseif (file_put_contents($baseModelPath, $baseModelContent)) {
		 	p("√ Javascript base model generated.");
		} else {
		 	throw new Exception("Could not generate {$this->_model->id} Javascript base model.");
		}

		$extendedModelPath = $this->_getExtendedModelPath($this->_model->id);
		$extendedModelContent = $this->_renderExtendedModel();

		if (!file_exists($extendedModelPath)) {
			if (file_put_contents($extendedModelPath, $extendedModelContent) !== false) {
			 	p("√ Javascript extended model generated.");
			} else {
			 	throw new Exception("Could not generate {$this->_model->id} Javascript extended model.");
			}
		} else p("  Javascript extended model exists.");

		new Garp_Model_Spawn_Js_ModelsIncluder($this->_model);
	}

	
	protected static function _getBaseModelPath($modelId) {
		return APPLICATION_PATH.self::_BASE_MODEL_PATH.$modelId.'.js';
	}


	protected static function _getExtendedModelPath($modelId) {
		return APPLICATION_PATH.self::_EXTENDED_MODEL_PATH.$modelId.'.js';
	}


	protected function _getAclRolesThatAreNotAllowedModelPrivilege($privilege) {
		if (Zend_Registry::isRegistered('Zend_Acl')) {
			$acl = Zend_Registry::get('Zend_Acl');

			$modelResourceName = 
				($this->_model->module === 'garp' ? 'G_' : null)
				.'Model_'
				.$this->_model->id
			;

			if ($acl->has($modelResourceName)) {
				$auth = Garp_Auth::getInstance();
				$roles = $auth->getRoles();
				$rolesThatDontHaveThisPrivilege = array();

				foreach ($roles as $role) {
					if (!$acl->isAllowed($role, $modelResourceName, $privilege)) {
						$rolesThatDontHaveThisPrivilege[] = $role;
					}
				}

				return $rolesThatDontHaveThisPrivilege;
			}
		}
		
	}


	protected function _renderBaseModel() {
		$fields = $this->_model->fields->getFields();
		$relFields = $this->_model->fields->getFields('origin', 'relation');
		$editableFields = $this->_model->fields->getFields('editable', true);
		$singularRelations = $this->_model->relations->getRelations('type', array('hasOne', 'belongsTo'));
		$multipleRelations = $this->_model->relations->getRelations('type', array('hasMany', 'hasAndBelongsToMany'));


		$out = $this->_rl("Ext.ns('Garp.dataTypes');");
		$out.= $this->_rl("Garp.dataTypes.{$this->_model->id} = new Garp.DataType({", 0, 2);

		$out.= $this->_rl("text: __('{$this->_model->label}'),", 1, 1);
		$dashedId = Garp_Model_Spawn_Util::camelcased2dashed($this->_model->id);
		$out.= $this->_rl("iconCls: 'icon-{$dashedId}',", 1, 2);

		$quickAddableString = $this->_model->quickAddable ? 'true' : 'false';
		$out.= $this->_rl("quickCreatable: {$quickAddableString},", 1, 2);
		
		if (!$this->_model->visible) {
			$out.= $this->_rl("hidden: true,", 1, 2);
		} else {
			if ($rolesThatShouldntSeeThisModelInTheirMenu = $this->_getAclRolesThatAreNotAllowedModelPrivilege('menu')) {
				$out.= $this->_rl("hidden: ['".implode("','", $rolesThatShouldntSeeThisModelInTheirMenu)."'].indexOf(Garp.localUser.role) > -1,", 1, 2);
			}
		}

		if (!$this->_model->creatable) {
			$out.= $this->_rl("disableCreate: true,", 1, 2);
		}

		if (!$this->_model->deletable) {
			$out.= $this->_rl("disableDelete: true,", 1, 2);
		}


		/* _____DisplayField */
		$out.= $this->_rl("displayFieldRenderer: function(rec){", 1);
		if ($this->_model->fields->exists('name')) {
			$out.= $this->_rl("return rec.get('name') || __('New');", 2);
		} elseif (
			$this->_model->fields->exists('first_name') &&
			$this->_model->fields->exists('last_name_prefix') &&
			$this->_model->fields->exists('last_name')
		) {
			$out.= $this->_rl("return rec.get('last_name') ? (rec.get('first_name') + (rec.get('last_name_prefix') ? ' ' + rec.get('last_name_prefix') + ' ' : ' ') + rec.get('last_name')) : __('New');", 2);
		} else {
			$out.= $this->_rl('return [rec.get("'.implode($this->_model->fields->listFieldNames, '"), rec.get("').'")].join(" ").replace("  ", " ") || __("New");', 2);
		}
		$out.= $this->_rl("},", 1, 2);


		/* _____PreviewLink */
		if ($this->_model->route) {
			$out.= $this->_rl("previewLink: {", 1);

			$route = $this->_model->route;
			if ($route[0] === '/')
				$route = substr($route, 1);

			$urlParam = null;			
			$routeParts = explode("/", $route);

			foreach ($routeParts as &$part) {
				if (
					strlen($part) > 0 &&
					$part[0] === ':'
				) {
					$urlParam = substr($part, 1);
					$part = '{0}';
				}
			}

			$out .= $this->_rl("urlTpl: '".implode("/", $routeParts)."?preview',", 2);
			$out .= $this->_rl("param: '{$urlParam}'", 2);

			$out .= $this->_rl("},", 1, 2);
		}


		/* _____DefaultData */
		$i = 0;
		$out.= $this->_rl("defaultData: {", 1);

		foreach ($fields as $field) {
			$defaultValue = !empty($field->default) ?
				$this->_quoteIfNecessary($field->default) :
				($field->isTextual() ?
					"''" :
					($field->type === 'checkbox' ?
						0 :
						'null'
					)
				);
			$out.= $this->_rl("'{$field->name}': {$defaultValue}".($i < (count($fields) - 1) ? ',' : ''), 2);
			$i++;
		}
		$out.= $this->_rl("},", 1, 2);

		
		/* _____SortInfo */
		$sortParams = $this->_getSortParams();
		$out.= $this->_rl("sortInfo: {", 1);
		$out.= $this->_rl("field: '{$sortParams['field']}',", 2);
		$out.= $this->_rl("direction: '{$sortParams['direction']}'", 2);
		$out.= $this->_rl('},', 1, 2);


		/* _____ColumnModel */
		$out.= $this->_rl("columnModel: [{", 1);

		$columnModelNodes = array();

		/* __images in column model */
		foreach ($fields as $field) {
			if (
				in_array($field->name, $this->_model->fields->listFieldNames) &&
				self::_isImageField($field)
			) {
				$node = '';
				$node.= $this->_rl("dataIndex: '{$field->name}',", 2);

				$renderer = 'Garp.renderers.imageRelationRenderer';
				$node.= $this->_rl("renderer: {$renderer},", 2);
				$node.= $this->_rl("width: 74,", 2);
				$node.= $this->_rl("fixed: true,", 2);

				$label = $field->name == 'image_id' ? 'Image' : $field->label;
				$node.= $this->_rl("header: __('{$label}')", 2);

				$columnModelNodes[] = $node;
			}
		}


		/* __textual columns */
		foreach ($fields as $field) {
			if (
				!self::_isImageField($field) &&
				(
					in_array($field->name, $this->_model->fields->listFieldNames) ||
					$field->type === 'html'
				)
			) {
				$node = '';
				$node.= $this->_rl("dataIndex: '{$field->name}',", 2);

				$renderer = null;
				switch ($field->type ) {
					case 'datetime':
						$renderer = 'Garp.renderers.dateTimeRenderer';
					break;
					case 'date':
						$renderer = 'Garp.renderers.dateRenderer';
					break;
					case 'html':
						$renderer = 'Garp.renderers.htmlRenderer';
					break;
				}
				if ($renderer)
					$node.= $this->_rl("renderer: {$renderer},", 2);

				if (
					!in_array($field->name, $this->_model->fields->listFieldNames) ||
					!$field->visible ||
					(
						$this->_modelHasFirstAndLastName() &&
						(
							$field->name === 'first_name' ||
							$field->name === 'last_name_prefix' ||
							$field->name === 'last_name'
						)
						
					)
				) {
					$node.= $this->_rl("hidden: true,", 2);
				}
				
				$label = $field->label;
				$node.= $this->_rl("header: __('{$label}')", 2);

				$columnModelNodes[] = $node;
			}
		}

		if (
			$this->_modelHasFirstAndLastName()
		) {
			$node = $this->_rl("dataIndex: 'fullname',", 2);
			$node.= $this->_rl("header: __('Full name'),", 2);
			$node.= $this->_rl("hidden: false,", 2);
			$node.= $this->_rl("virtual: true,", 2);
			$node.= $this->_rl("convert: Garp.renderers.fullNameConverter", 2);
			$columnModelNodes[] = $node;
		}

		if ($columnModelNodes)
			$out.= implode($this->_rl("}, {", 1), $columnModelNodes);

		$out.= $this->_rl("}],", 1, 2);


		/* _____FormConfig */
		$out.= $this->_rl("formConfig: [{", 1);
		$out.= $this->_rl("layout: 'form',", 2);
		$out.= $this->_rl("defaults: {", 2);
		$out.= $this->_rl("defaultType: 'textfield'", 3);
		$out.= $this->_rl("},", 2);

		if (count($singularRelations)) {
			$singularImageRelations = array_filter($singularRelations, function ($n) {return $n->model === 'Image';});
			$out.= $this->_rl("listeners: {", 2);
			$out.= $this->_rl("loaddata: function(rec, formPanel){", 3);
			foreach ($singularImageRelations as $imageRelation) {
				$imagePreviewId = $this->_getImagePreviewId($imageRelation->column);
				$out.= $this->_rl("formPanel.{$imagePreviewId}.setText(Garp.renderers.imageRelationRenderer(rec.get('{$imageRelation->column}'), null, rec) || __('Add image'));", 4);
			}
			$out.= $this->_rl("}", 3);
			$out.= $this->_rl("},", 2);
		}

		$out.= $this->_rl("items: [{", 2);
		$out.= $this->_rl('xtype: "fieldset",', 3);
		$out.= $this->_rl('items: [{', 3);

		$formFields = array();


		/* _____ID field */
		$idField = $this->_model->fields->getFields('name', 'id');
		if (count($idField)) {
			$formFields[] = $this->_renderInputField(current($idField));
		}

		/* _____Image Fields */
		foreach ($singularRelations as $rel) {
			if ($rel->model === 'Image')
				$formFields[] = $this->_renderSingularRelationField($rel);
		}

		/* _____InputFields */
		foreach ($editableFields as $field) {
			if (!in_array($field->name, $this->_excludeFromFormFields))
				$formFields[] = $this->_renderInputField($field);
		}

		/* _____Single Relation Fields */
		foreach ($singularRelations as $rel) {
			if (
				$rel->model !== 'Image' &&
				$rel->column !== 'author_id' &&
				$rel->column !== 'modifier_id'
			) {
				$formFields[] = $this->_renderSingularRelationField($rel);
			}
		}

		$out.= implode("\n\t\t\t}, {\n", $formFields)."\n";

		$out.= $this->_rl("}]", 3);
		$out.= $this->_rl("}]", 2);
		
		if (count($multipleRelations)) {
			$out.= $this->_rl("}, {", 1);
		
			$multiRelFields = array();

			foreach ($multipleRelations as $rel) {
				$multiRelFields[] = $this->_renderMultipleRelationField($rel);
			}

			$out.= implode($this->_rl('}, {', 1), $multiRelFields);
		}

		$out.= $this->_rl("}]", 1);
		$out.= $this->_rl("});", 0);

		return $out;
	}


	protected function _modelHasFirstAndLastName() {
		return 
			in_array('first_name', $this->_model->fields->listFieldNames) &&
			in_array('last_name_prefix', $this->_model->fields->listFieldNames) &&
			in_array('last_name', $this->_model->fields->listFieldNames)
		;
	}


	protected static function _isImageField(Garp_Model_Spawn_Field $field) {
		return $field->name === 'image_id';
	}


	protected function _renderExtendedModel() {
		return <<<EOF
/**
 * Append listener to init event to override base class 
 * 
 */
Garp.dataTypes.{$this->_model->id}.on('init', function(){
	/*

	this.iconCls = 'icon-other-bogus';
	Ext.apply(this.getColumn('email'),{
		hidden: true,
		header: __('Thingmail')
	});
	this.getField('description').fieldLabel = __('Content:');
	this.removeField('year');

	*/
});
EOF;
	}


	protected function _quoteIfNecessary($value) {
		$decorator = null;

		if (
			!is_numeric($value) &&
			!is_bool($value) &&
			!is_null($value)
		) $decorator = "'";
		return $decorator.$value.$decorator;
	}


	protected function _renderInputField(Garp_Model_Spawn_Field $field) {
		$xType = $this->_getFieldXType($field);
		$vType = $this->_getFieldVType($field);
		$plugin = $this->_getFieldPlugin($field);

		$props = array();
		
		//$out = $this->_rl("name: '{$field->name}',", 4);
		
		
		$props[] = "name: '{$field->name}'";
		$props[] = "fieldLabel: __('{$field->label}')";
		$props[] = "disabled: ".($field->editable ? 'false' : 'true');
		$props[] = "hidden: ".($field->visible ? 'false' : 'true');

		switch ($field->type) {
			case 'enum':
				$props[] = "editable: false";
				$props[] = "mode: 'local'";
				$props[] = "store: ['".implode($field->options, "', '")."']";
			break;
		}

		if ($field->maxLength)
			$props[] = "maxLength: {$field->maxLength}";

		$props[] = "allowBlank: ".($field->required ? 'false' : 'true');

		$props[] = "xtype: '{$xType}'";
		if ($vType)
			$props[] = "vtype: '{$vType}'";
		if ($plugin)
			$props[] = "plugins: [{$plugin}]";
			
		if (
			$field->type === 'html'
		) {
			if ($field->rich) {
				$props[] = 'enableSourceEdit: false';
			} else {
				array_push($props,
					'enableMedia: false',      
					'enableHeading: false',
					'enableSourceEdit: false',
					'enableEmbed: false',
					'enableAlignments: false',
					'enableColors: false',
					'enableFont: false',
					'enableFontSize: false',
					'enableUnderline: false',
					'enableBlockQuote: false'
				);
			}
		}

		return "\t\t\t\t".implode(",\n\t\t\t\t", $props);
	}
	
	
	protected function _renderSingularRelationField(Garp_Model_Spawn_Relation $relation) {
		$t = 4;

		$out = $this->_rl("fieldLabel: __('{$relation->label}'),", $t);
		$out.= $this->_rl("allowBlank: ".($relation->type === 'belongsTo' ? 'false' : 'true').',', $t);

		if ($relation->model !== 'Image') {
			$out.= $this->_rl("name: '{$relation->column}',", $t);
			$out.= $this->_rl("xtype: 'relationfield',", $t);
			$out.= $this->_rl("model: '{$relation->model}',", $t);
			$isReadOnly = $relation->editable ? 'false' : 'true';
			$out.= $this->_rl("disabled: {$isReadOnly},", $t);
			$out.= $this->_rl("hidden: false,", $t);

			$remoteModel = new Garp_Model_Spawn_Model($relation->model);
			if (
				in_array('first_name', $remoteModel->fields->listFieldNames) &&
				in_array('last_name_prefix', $remoteModel->fields->listFieldNames) &&
				in_array('last_name', $remoteModel->fields->listFieldNames)
			) {
				$displayField = 'fullname';
			} elseif (in_array('name', $remoteModel->fields->listFieldNames)) {
				$displayField = 'name';
			} else $displayField = 'id';

			$out.= $this->_rl("displayField: '{$displayField}'", $t, 0);
		} else {
			$imageFieldId = $this->_getImageFieldId($relation->column);
			$imagePreviewId = $this->_getImagePreviewId($relation->column);
			$ini = Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini');
			if (!$ini->image->template->cms_list->w) {
				throw new Exception("Scaling template 'cms_list' is not defined, or lacks width.");
			}
			$w = $ini->image->template->cms_list->w;
			
			$out.= $this->_rl("xtype: 'button',", $t);
			$out.= $this->_rl("ref: '../../../{$imagePreviewId}',", $t);
			$out.= $this->_rl("tooltip: __('Click to change'),", $t);
			$out.= $this->_rl("boxMaxWidth: ".$w.",", $t);
			$out.= $this->_rl("listeners: {", $t);
			$out.= $this->_rl("'click': function(){", $t + 1);
			$out.= $this->_rl("this.refOwner.{$imageFieldId}.triggerFn();", $t + 2);
			$out.= $this->_rl("}", $t + 1);
			$out.= $this->_rl("}", $t);
			$out.= $this->_rl("}, {", $t - 1);

			$out.= $this->_rl("name: '{$relation->column}',", $t);
			$out.= $this->_rl("xtype: 'relationfield',", $t);
			$out.= $this->_rl("allowBlank: ".($relation->type === 'belongsTo' ? 'false' : 'true').',', $t);
			$out.= $this->_rl("autoLoad: false,", $t);
			$out.= $this->_rl("hidden: true,", $t);
			$out.= $this->_rl("displayField: 'filename',", $t);
			$out.= $this->_rl("ref: '../../../{$imageFieldId}',", $t);
			$out.= $this->_rl("model: '{$relation->model}',", $t);
			$out.= $this->_rl("allowCreate: true,", $t);

			$out.= $this->_rl("listeners: {", $t);
			$out.= $this->_rl("select: function(s){", $t + 1);
			$out.= $this->_rl("this.refOwner.{$imagePreviewId}.setText(s.selected ? Garp.renderers.imageRelationRenderer(s.selected.get('id'), null, s.selected) : __('Add image'));", $t + 2);
			$out.= $this->_rl("}", $t + 1);
			$out.= $this->_rl("}", $t, 0);
		}

		return $out;
	}


	protected function _renderMultipleRelationField(Garp_Model_Spawn_Relation $relation) {
		$out = $this->_rl("xtype: 'relationpanel',", 2);

		//TODO: foreignKey: 'id', // was alleen gezet bij Activity <-> Activity
		//$out.= $this->_rl("foreignKey: '{$relation->column}',", 2);
		
		if ($relation->limit)
			$out.= $this->_rl("maxItems: '{$relation->limit}',", 2);
		
		$out.= $this->_rl("model: '{$relation->model}',", 2);
		$out.= $this->_rl("rule: '{$relation->name}'", 2);
		return $out;
	}


	protected function _getImageFieldId($columnName) {
		return Garp_Model_Spawn_Util::underscored2camelcased($columnName);
	}
	
	
	protected function _getImagePreviewId($columnName) {
		return 'ImagePreview_'.$columnName;
		
		//return $this->_getImageFieldId($columnName).'Preview';
	}


	protected function _getFieldXType(Garp_Model_Spawn_Field $field) {
		switch ($field->type) {
			case 'numeric':
				return 'numberfield';
			case 'text':
				if (
					$field->maxLength <= Garp_Model_Spawn_Field::TEXTFIELD_MAX_LENGTH &&
					!is_null($field->maxLength)
				) {
					return 'textfield';
				} else
					return 'textarea';
			case 'url':
			case 'email':
				return 'textfield';
			case 'html':
				return 'richtexteditor';
			case 'checkbox':
				return 'checkbox';
			case 'datetime':
				return 'xdatetime';
			case 'date':
				return 'datefield';
			case 'enum':
				return 'combo';
			default:
				throw new Exception("The '{$field->type}' field type can't be translated to an ExtJS field type as of yet.");
		}
	}


	protected function _getFieldVType(Garp_Model_Spawn_Field $field) {
		switch ($field->type) {
			case 'email':
				return 'email';
			break;
			case 'url':
				return 'mailtoOrUrl';
			break;
		}
	}
	
	
	protected function _getFieldPlugin(Garp_Model_Spawn_Field $field) {
		switch ($field->type) {
			case 'url':
				return 'Garp.mailtoOrUrlPlugin';
		}
	}


	/**
	 * Render line with tabs and newlines
	 */
	protected function _rl($content, $tabs = 0, $newlines = 1) {
		return str_repeat("\t", $tabs).$content.str_repeat("\n", $newlines);
	}
	

	/**
	 * @return Array Associative array, containing the keys 'field' and 'direction'.
	 */
	protected function _getSortParams() {
		$out = array();

		if (strpos($this->_model->order, "(") === false) {
			$sortings = explode(",", $this->_model->order);
			$fieldAndDir = explode(" ", $sortings[0]);
			$out['field'] = $fieldAndDir[0];
			$out['direction'] = !empty($fieldAndDir[1]) ?
				$fieldAndDir[1] :
				'ASC'
			;
		} else {
			$out['direction'] = 'DESC';
			if ($this->_model->behaviors->displaysBehavior('Timestampable'))
				$out['field'] = 'created';
			else
				$out['field'] = 'id';
		}

		return $out;
	}
}