<?php
/**
 * @package Garp_Spawn_Model
 * @author  David Spreekmeester <david@grrr.nl>
 */
abstract class Garp_Spawn_Model_Abstract {
    public $id;
    public $order;
    public $label;
    public $description;
    public $route;
    public $creatable;
    public $deletable;
    public $quickAddable;
    public $comment;

    /**
     * $visible Whether this model shows up in the cms index.
     *
     * @var bool
     */
    public $visible;

    /**
     * Module for this model.
     *
     * @var string
     */
    public $module;

    /**
     * @var Garp_Spawn_Fields
     */
    public $fields;

    /**
     * @var Garp_Spawn_Behaviors
     */
    public $behaviors;

    /**
     * @var Garp_Spawn_Relation_Set
     */
    public $relations;

    /**
     * Column names that should jointly compose a unique key (optional)
     *
     * @var array
     */
    public $unique;

    /**
     * These properties cannot be configured directly from the configuration
     * because of their complexity.
     *
     * @var array
     */
    protected $_indirectlyConfigurableProperties = ['fields', 'listFields', 'behaviors', 'relations'];


    public function __construct(ArrayObject $config) {
        $this->_loadPropertiesFromConfig($config);

        $this->behaviors->onAfterSingularRelationsDefinition();
        $this->fields->onAfterSingularRelationsDefinition();
    }

    /**
     * Creates php models.
     *
     * @param  Garp_Spawn_Model_Abstract $model
     * @return void
     */
    public function materializePhpModels(Garp_Spawn_Model_Abstract $model) {
        $phpModel = new Garp_Spawn_Php_Renderer($model);
        $phpModel->save();
    }

    public function isMultilingual(): bool {
        return false;
    }

    public function isTranslated(): bool {
        return false;
    }

    public function getTableName(): string {
        return $this->id;
    }

    abstract public function getTableClassName(): string;

    protected function _loadPropertiesFromConfig(ArrayObject $config) {
        foreach ($config as $propName => $propValue) {
            $this->_loadProperty($propName, $propValue);
        }

        //  complex types
        $this->fields = new Garp_Spawn_Fields(
            $this,
            $config['inputs'],
            (array)$config['listFields']
        );
        $this->behaviors = new Garp_Spawn_Behavior_Set($this, $config['behaviors']);
        $this->relations = new Garp_Spawn_Relation_Set($this, $config['relations']);
    }

    protected function _loadProperty($name, $value) {
        $indirectlyConfigurable = in_array($name, $this->_indirectlyConfigurableProperties);
        $exists                 = property_exists($this, $name);

        if (!$exists && !$indirectlyConfigurable && $name !== 'inputs') {
            throw new Exception("The {$name} property is not a valid Spawn model property.");
        }

        if (!$indirectlyConfigurable && $exists) {
            $this->{$name} = $value;
        }
    }
}
