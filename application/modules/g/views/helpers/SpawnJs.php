<?php
/**
 * G_View_Helper_SpawnJs
 * This helper is used by the Spawner when generating Javascript model files.
 *
 * @package G_View_Helper
 * @author  David Spreekmeester <david@grrr.nl>
 */
class G_View_Helper_SpawnJs extends Zend_View_Helper_Abstract {

    const UNKNOWN_TYPE_EXCEPTION
        = "The '%s' field type can't be translated to an ExtJS field type as of yet.";

    protected $_excludedFormFields = array('published', 'online_status');

    /**
     * Central interface for this helper.
     * like so (in the view):
     * $this->spawnJs()->getFieldType(...)
     *
     * @return G_View_Helper_SpawnJs $this
     */
    public function spawnJs() {
        return $this;
    }

    public function getFieldType(Garp_Spawn_Field $field) {
        switch ($field->type) {
        case 'numeric':
            return 'numberfield';
        case 'text':
        case 'set':
            if ((!is_null($field->multiline) && !$field->multiline)
                || ($field->maxLength <= Garp_Spawn_Field::TEXTFIELD_MAX_LENGTH
                && !is_null($field->maxLength))
            ) {
                return 'textfield';
            } else {
                return 'textarea';
            }
        case 'url':
        case 'email':
            return 'textfield';
        case 'html':
            // Enable CKEditor?
            if (!is_null($field->wysiwyg) && $field->wysiwyg) {
                // Enable image and video embeds?
                if ($field->wysiwyg === 'rich') {
                    return 'richwysiwygeditor';
                }
                return 'wysiwygeditor';
            }
            return 'richtexteditor';
        case 'checkbox':
            return 'checkbox';
        case 'datetime':
            return 'xdatetime';
        case 'date':
            return 'datefield';
        case 'time':
            return 'timefield';
        case 'enum':
            return 'combo';
        case 'document':
        case 'imagefile':
            return 'uploadfield';
        default:
            throw new Exception(sprintf(self::UNKNOWN_TYPE_EXCEPTION, $field->type));
        }
    }

    public function getFieldValidationType(Garp_Spawn_Field $field) {
        switch ($field->type) {
        case 'email':
            return 'garpEmail';
            break;
        case 'url':
            return 'mailtoOrUrl';
            break;
        }
    }

    public function getFieldPlugin(Garp_Spawn_Field $field) {
        switch ($field->type) {
        case 'url':
            return 'Garp.mailtoOrUrlPlugin';
        }
    }

    public function getAclRolesThatAreNotAllowedModelPrivilege(
        Garp_Spawn_Model_Base $model, $privilege
    ) {
        if (!Zend_Registry::isRegistered('Zend_Acl')) {
            return;
        }
        $acl = Zend_Registry::get('Zend_Acl');

        $modelResourceName = ($model->module === 'garp' ? 'G_' : null) . 'Model_' . $model->id;

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

    public function quoteIfNecessary($fieldType, $value) {
        $decorator = '';

        if ($fieldType === 'enum'
            || (!is_numeric($value) && !is_bool($value) && !is_null($value))
        ) {
            $decorator = "'";
        }
        return $decorator . $value . $decorator;
    }


    public function getSortParams($orderField) {
        $out = array();

        if (strpos($orderField, "(") === false) {
            $sortings = explode(",", $orderField);
            $fieldAndDir = explode(" ", $sortings[0]);
            $out['field'] = $fieldAndDir[0];
            $out['direction'] = !empty($fieldAndDir[1]) ?
                $fieldAndDir[1] :
                'ASC'
            ;
        } else {
            $out['direction'] = 'DESC';
            if ($this->_model->behaviors->displaysBehavior('Timestampable')) {
                $out['field'] = 'created';
            } else {
                $out['field'] = 'id';
            }
        }

        return $out;
    }

    public function isImageField(Garp_Spawn_Field $field, Garp_Spawn_Model_Base $model) {
        $rels = $model->relations->getRelations('column', $field->name);
        if (count($rels)) {
            $rel = current($rels);
            return $rel->model === 'Image';
        }

        return false;
    }

    /**
     * Whether this field is a singular relation to another record.
     *
     * @param Garp_Spawn_Field $field
     * @param Garp_Spawn_Model_Base $model
     * @return String Model name, or false if this is not a relation field.
     */
    public function isSingularRelationField(Garp_Spawn_Field $field, Garp_Spawn_Model_Base $model) {
        $rels = $model->relations->getRelations('column', $field->name);
        if (count($rels)) {
            $rel = current($rels);
            return $rel->model;
        }

        return false;
    }

    public function isListField($fieldName, Garp_Spawn_Model_Base $model) {
        return in_array($fieldName, $model->fields->listFieldNames);
    }

    public function modelHasFirstAndLastName(Garp_Spawn_Model_Base $model) {
        return
            $this->isListField('first_name', $model) &&
            $this->isListField('last_name_prefix', $model) &&
            $this->isListField('last_name', $model);
    }

    public function renderImagePreviewListener(Garp_Spawn_Relation $rel) {
        if (!$rel->multilingual) {
            return $this->renderImagePreviewListenerJs(
                $this->getImagePreviewId($rel->column),
                $rel->column
            );
        }
        $self = $this;
        return implode(
            "\n",
            array_map(
                function ($locale) use ($rel, $self) {
                    return $self->renderImagePreviewListenerJs(
                        $self->getImagePreviewId($rel->column, $locale),
                        $rel->column,
                        $locale
                    );
                },
                Garp_I18n::getLocales()
            )
        );
    }

    public function renderImagePreviewListenerJs($previewId, $column, $locale = false) {
        $out = 'formPanel.' . $previewId;
        $out .= '.setText(Garp.renderers.imageRelationRenderer(';
        $out .= "rec.get('{$column}'), null, rec, '{$locale}') || ";
        $out .= "__('Add image'));";
        return $out;
    }

    public function getImagePreviewId($columnName, $locale = false) {
        return 'ImagePreview_' . $columnName .
            ($locale ? '_' . $locale : '');
    }

    public function getImageRefDepth($multilingual) {
        return $multilingual ? '../../../../' : '../../../';
    }

    public function getImageFieldId($columnName, $locale = false) {
        return Garp_Spawn_Util::underscored2camelcased($columnName) .
            ($locale ? '_' . $locale : '');
    }

    public function getExcludedFormFields() {
        return $this->_excludedFormFields;
    }

    public function getDefaultValue(Garp_Spawn_Field $field) {
        if ($field->type === 'checkbox' && !$field->default) {
            return 0;
        }
        if ($field->isRelationField()) {
            return 'null';
        }
        if (isset($field->default)) {
            return $this->quoteIfNecessary($field->type, $field->default);
        }
        if (!$field->required
            || $field->primary
            || $field->type === 'datetime'
            || $field->type === 'date'
            || $field->type === 'time'
            || $field->type === 'enum'
            || $field->type === 'imagefile'
            || $field->type === 'document'
        ) {
            return 'null';
        }
        return $field->isTextual() ?  "''" : 0;
    }

    public function getDefaultValueForRelation(Garp_Spawn_Relation $rel) {
        return 'null';
    }
}

