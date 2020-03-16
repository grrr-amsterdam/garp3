<?php
/**
 * Garp_Spawn_Relation
 * class description
 *
 * @package Garp_Spawn
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Relation {
    // @codingStandardsIgnoreStart
    const ERROR_RELATION_TYPE_NOT_AVAILABLE_YET_FOR_PLURAL =
        "Relation::type is not available yet, so you cannot use isPlural() at this point in your code.";
    const ERROR_RELATION_TYPE_NOT_AVAILABLE_YET_FOR_SINGULAR =
        "Relation::type is not available yet, so you cannot use isSingular() at this point in your code. Use self::_isSingularByArg() within the class.";
    const ERROR_RELATION_TYPE_MISSING =
        "The 'type' property is obligated in the definition of the %s relation.";
    const ERROR_RELATION_TYPE_INVALID =
        "The '%s' relation type for %s is invalid. Try: %s";
    const ERROR_RELATION_NAME_CANNOT_BE_PROPERTY =
        "The relation name cannot be defined as a property for the %s relation. Instead, it should be the key of the relation.";
    const ERROR_INVALID_RELATION_PROPERTY_VALUE =
        "'%s' is not a valid parameter for the %s > %s relation field configuration. Try: %s";
    const ERROR_INVALID_RELATION_TYPE_FOR_MULTILINGUAL =
        "'multilingual' is not a valid parameter for the %s > %s relation field configuration. It's only allowed on hasOne relations.";
    // @codingStandardsIgnoreEnd

    /**
     * The remote model which is referenced in this relation.
     *
     * @var string
     */
    public $model;

    public $name;
    public $type;
    public $label;
    public $limit;
    public $column;
    public $simpleSelect;
    public $max;
    public $paginated;
    public $multilingual;
    public $primary;
    public $info;
    public $visible = true;

    /**
     * Whether this relation field is editable in the cms.
     * For instance, hasMany relations of which the opposite side is belongsTo (instead of hasOne),
     * are not editable.
     *
     * @var bool
     */
    public $editable;

    /**
     * Whether a singular relation (hasOne / belongsTo) also implicates a hasMany relation
     * from the remote to the local model.
     *
     * @var bool
     */
    public $inverse;

    /**
     * In case of a plural relation, the rule name of the opposite (hasOne / belongsTo)
     * side of the relation.
     *
     * @var string
     */
    public $oppositeRule = null;

    public $inverseLabel = null;

    /**
     * Wether the hasAndBelongsToMany or hasMany relation can be weighable,
     * i.e. their relational order is defined by an end user.
     *
     * @var bool
     */
    public $weighable = false;

    /**
     * Normally, the relation type sets whether a relation is required.
     * A belongsTo relation makes it required, all other types do not.
     * However, this is overridable, so that belongsTo relations are not required.
     * Deletion of parents will still delete this record as well,
     * as with normal belongsTo relations, but the association field will not be required.
     *
     * @var bool
     */
    public $required;

    /**
     * In case of a hasAndBelongsToMany relation, extra columns can be added to the binding model.
     *
     * @var array
     */
    public $inputs;

    /**
     * Multiple relations that are defined as inline (not default behavior)
     * do not appear as a separate tab in the cms, but as a set of fields on the form.
     *
     * @var bool
     */
    public $inline;

    /**
     * Whether this relation was created by mirroring, i.e. a configured relation that lead to
     * this relation from the opposite model.
     *
     * @var bool
     */
    public $mirrored = false;

    /**
     * Contains the ID (string) of the bindingModel if this is a Habtm relation
     *
     * @var string
     */
    public $bindingModel = false;

    /**
     * The local model in which this relation is defined.
     *
     * @var Garp_Spawn_Model_Base
     */
    protected $_localModel;

    /**
     * Allowed relation types.
     *
     * @var Array
     */
    protected $_types = array('hasOne', 'belongsTo', 'hasMany', 'hasAndBelongsToMany');

    /**
     * @var Garp_Spawn_Model_Binding
     */
    protected $_bindingModel;


    /**
     * @param Garp_Spawn_Model_Abstract $localModel
     * @param string $name Name of the relation, such as 'User' or 'Author'
     * @param array $params
     * @return void
     */
    public function __construct(Garp_Spawn_Model_Abstract $localModel, $name, array $params) {
        $this->_setLocalModel($localModel);
        $this->name = $name;

        $this->_validate($name, $params);
        $this->_appendDefaults($name, $params);

        foreach ($params as $paramName => $paramValue) {
            $this->{$paramName} = $paramValue;
        }

        $this->_addRelationColumn($params);
        $this->_addRelationFieldInLocalModel();
        $this->_addOppositeRule();

        $this->_initBindingModelIdProp();
    }

    /**
     * @return Garp_Spawn_Model_Base
     */
    public function getLocalModel() {
        return $this->_localModel;
    }

    public function isPlural() {
        if (!$this->type) {
            throw new Exception(self::ERROR_RELATION_TYPE_NOT_AVAILABLE_YET_FOR_PLURAL);
        }
        return $this->type === 'hasAndBelongsToMany' || $this->type === 'hasMany';
    }

    public function isSingular() {
        if (!$this->type) {
            throw new Exception(self::ERROR_RELATION_TYPE_NOT_AVAILABLE_YET_FOR_SINGULAR);
        }
        return $this->_isSingularByArg($this->type);
    }

    public function getParams() {
        $out = new StdClass();
        $refl = new ReflectionObject($this);
        $reflProps = $refl->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($reflProps as $reflProp) {
            $out->{$reflProp->name} = $this->{$reflProp->name};
        }

        return $out;
    }

    /**
     * @param array $propNames  Numeric array of property names
     * @param array $propValues Corresponding numeric array of property values.
     *                          Can be nested to support multiple values by an OR operator.
     * @return bool
     */
    public function hasProperties(array $propNames, array $propValues) {
        foreach ($propNames as $propIndex => $propName) {
            $valuesForThisProp = (array)($propValues[$propIndex]);

            if (!in_array($this->{$propName}, $valuesForThisProp)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Garp_Spawn_Model_Base    A model object, representing the binding model
     *                                  between two hasAndBelongsToMany related models.
     */
    public function getBindingModel() {
        if (!$this->_bindingModel) {
            return $this->_createBindingModel();
        }

        return $this->_bindingModel;
    }

    /**
     * Mirror this relation so that it can be used as the opposite.
     * For instance: News has an Avatar relation (hasOne to Image).
     * After mirroring Avatar, it can be used as the relation Image hasMany AvatarNews.
     *
     * @param Garp_Spawn_Model_Base $model The model where this relation should stem from.
     * @return Garp_Spawn_Relation
     */
    public function mirror(Garp_Spawn_Model_Base $model) {
        $mirroredParams = $this->getMirroredParams();
        $relationName   = $mirroredParams->name;
        unset($mirroredParams->name);

        return new Garp_Spawn_Relation($model, $relationName, (array)$mirroredParams);
    }

    /**
     * @return StdClass
     */
    public function getMirroredParams() {
        $old = $this->getParams();
        $new = clone $old;

        $new->name          = $old->oppositeRule;
        $new->editable      = $old->type === 'belongsTo' ? false : $old->editable;
        $new->type          = $this->isSingular() ? 'hasMany' : 'hasAndBelongsToMany';
        $new->model         = $this->_localModel->id;
        // Mysterious! This property is set to "id", but as you can see in the constructor, after
        // setting `$this->column` in the foreach loop, `$this->_addRelationColumn` is executed,
        // which will immediately override the `$this->column`.
        // Since this appears to be useless and conflicts with the `if` statement meant for
        // overriding the foreign key column in the spawn config, I wil remove it.
        //$new->column        = 'id';
        $new->oppositeRule  = $old->name;
        $new->label         = $old->inverseLabel;
        $new->inverseLabel  = $old->label;
        $new->limit         = null;
        $new->mirrored      = true;

        return $new;
    }

    public function getNameKey($language) {
        return $this->multilingual ?
            '_' . $this->column . '_' . $language :
            $this->column
        ;
    }

    /**
     * Return the rules that make up this relation, as used in the referenceMap.
     *
     * @param string $subjectModel Name of the model you're viewing from. Determines the direction
     *                             in some cases.
     * @return array In case of 'hasMany', an array containing a single rule, in case of
     *               'hasAndBelongsToMany', an array containing two rules referencing two models.
     */
    public function getRules($subjectModel) {
        if ($this->type === 'hasMany') {
            return array($this->oppositeRule);
        }
        if ($this->type === 'hasAndBelongsToMany') {
            $bindingModel = $this->getBindingModel();
            $relations = $bindingModel->relations->getRelations();
            $rules = array_keys($relations);

            /**
             * Now sort egocentrically, because current sorting is alphabetical
             * (since $relations is an associative array)
             */
            $firstRel = current($relations);
            if ($firstRel->model !== $subjectModel) {
                $rules = array_reverse($rules);
            }

            if ($this->isHomo()
                && ($rules[0] !== $subjectModel && $rules[1] === $subjectModel)
            ) {
                // Make sure that the custom named relation always comes second.
                // This is part of egocentrism.
                $rules = array_reverse($rules);
            }
            return $rules;
        }
        return array($this->name);
    }

    /**
     * Wether a habtm relation between two identical models exists in both ways.
     *
     * @return bool
     */
    public function isBidirectional() {
        return !$this->isHomo() || $this->inverse;
    }

    /**
     * Wether a habtm relation is between two identical models.
     *
     * @return bool
     */
    public function isHomo() {
        return $this->model === $this->_localModel->id;
    }

    /**
     * @param Garp_Spawn_Model_Base $localModel
     * @return Garp_Spawn_Relation
     */
    protected function _setLocalModel($localModel) {
        $this->_localModel = $localModel;
        return $this;
    }

    /**
     * Check the provided relation type to see if this is a singular relation.
     *
     * @param string $relationType Type of the relation,
     *                             i.e. hasOne, belongsTo, hasMany or hasAndBelongsToMany.
     * @return bool
     */
    protected function _isSingularByArg($relationType) {
        return $relationType === 'hasOne' || $relationType === 'belongsTo';
    }


    protected function _validate($name, array $params) {
        if (!array_key_exists('type', $params)) {
            $error = sprintf(self::ERROR_RELATION_TYPE_MISSING, $name);
            throw new Exception($error);
        }
        foreach ($params as $paramName => $paramValue) {
            $this->_validateParam($paramName, $paramValue, $name);
        }

        if (isset($params['mirrored']) && $params['mirrored']) {
            unset($params['multilingual']);
        }
        $this->_validateMultilingual($params, $name);
    }

    protected function _validateParam($paramName, $paramValue, $relName) {
        switch ($paramName) {
        case 'type':
            $this->_validateType($paramValue, $relName);
            break;
        case 'name':
            $this->_validateName($paramValue, $relName);
            break;
        default:
            $this->_validateProp($paramName, $paramValue, $relName);
        }
    }

    protected function _validateType($paramValue, $relName) {
        if (in_array($paramValue, $this->_types)) {
            return true;
        }
        $error = sprintf(
            self::ERROR_RELATION_TYPE_INVALID,
            $param['type'],
            $relName,
            implode(', ', $this->_types)
        );
        throw new Exception($error);
    }

    protected function _validateName($paramValue, $relName) {
        $error = sprintf(self::ERROR_RELATION_NAME_CANNOT_BE_PROPERTY, $relName);
        throw new Exception($error);
    }

    protected function _validateProp($paramName, $paramValue, $relName) {
        if (property_exists($this, $paramName)) {
            return true;
        }
        $refl = new ReflectionObject($this);
        $reflProps = $refl->getProperties(ReflectionProperty::IS_PUBLIC);
        $publicProps = array();
        foreach ($reflProps as $reflProp) {
            if ($reflProp->name !== 'name') {
                $publicProps[] = $reflProp->name;
            }
        }

        $error = sprintf(
            self::ERROR_INVALID_RELATION_PROPERTY_VALUE,
            $paramName,
            $this->_localModel->id,
            $relName,
            implode(', ', $publicProps)
        );
        throw new Exception($error);
    }

    protected function _validateMultilingual($params, $relName) {
        if (isset($params['multilingual']) && $params['multilingual']
            && $params['type'] !== 'hasOne'
        ) {
            throw new Exception(
                sprintf(
                    self::ERROR_INVALID_RELATION_TYPE_FOR_MULTILINGUAL,
                    $this->_localModel->id, $relName
                )
            );
        }
    }

    protected function _appendDefaults($name, array &$params) {
        //  during execution of this method, self::isSingular() is not yet available.
        if (!array_key_exists('model', $params) || !$params['model']) {
            $params['model'] = $name;
        }

        if (!array_key_exists('label', $params) || !$params['label']) {
            $params['label'] = $name;
        }

        if (!array_key_exists('inverseLabel', $params) || !$params['inverseLabel']) {
            $params['inverseLabel'] = $this->getLocalModel()->id;
        }

        if (!array_key_exists('limit', $params) && $this->_isSingularByArg($params['type'])) {
            $params['limit'] = 1;
        }

        if (!array_key_exists('inverse', $params)) {
            $params['inverse'] = true;
            // $params['inverse'] = $this->_isSingularByArg($params['type']);
        }

        if (!array_key_exists('editable', $params)) {
            $params['editable'] = true;
        }

        if (!array_key_exists('required', $params)) {
            $params['required'] = $params['type'] === 'belongsTo';
        }

        if (!array_key_exists('inline', $params)) {
            $params['inline'] = false;
        }
    }

    protected function _addRelationColumn(array $params) {
        if (isset($params['column'])) {
            $this->column = $params['column'];
            return;
        }
        // Automagically determine foreign key column.
        $this->column = $this->isSingular() ?
            Garp_Spawn_Relation_Set::getRelationColumn($this->name) :
            Garp_Spawn_Relation_Set::getRelationColumn($this->_localModel->id);
    }

    /**
     * Registers this relation as a Field in the Model.
     *
     * @return void
     */
    protected function _addRelationFieldInLocalModel() {
        if (!$this->isSingular()) {
            return;
        }

        //$column = Garp_Spawn_Relation_Set::getRelationColumn($this->name);
        $column = $this->column;
        $fieldParams = array(
            'model' => $this->model,
            'type' => 'numeric',
            'editable' => true,
            'visible' => true,
            'primary' => $this->primary,
            'required' => $this->required,
            'relationAlias' => $this->name,
            'relationType' => $this->type
        );
        if ($this->multilingual && $this->_localModel->isMultilingual()) {
            // The relation is added to the i18n model by Garp_Spawn_Config_Model_I18n
            return;
        }
        $this->_localModel->fields->add('relation', $column, $fieldParams);
    }

    protected function _addOppositeRule() {
        if ($this->oppositeRule) {
            return;
        }

        $this->oppositeRule = $this->name !== $this->model
            ? $this->name
            : $this->_localModel->id
        ;
    }

    protected function _createBindingModel() {
        $factory = new Garp_Spawn_Model_Binding_Factory();
        return $factory->produceByRelation($this);
    }

    protected function _initBindingModelIdProp() {
        if ($this->type !== 'hasAndBelongsToMany') {
            return;
        }

        $bindingModel = $this->getBindingModel();
        $this->bindingModel = $bindingModel->id;
    }

}

