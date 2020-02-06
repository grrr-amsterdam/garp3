<?php
/**
 * Garp_Spawn_Field
 * Represents a single field in a model
 *
 * @package Garp_Spawn
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Field {
    const TEXTFIELD_MAX_LENGTH = 124;

    const INVALID_FIELD_TYPE = "'%s' is not a valid field type for the '%s' field. Try: %s";
    const INVALID_PARAMETER
        = "'%s' is not a valid parameter for the '%s' field configuration. Try: %s";
    // @codingStandardsIgnoreStart
    const UNSUPPORTED_PARAM_OPTIONS
        = "The 'options' parameter is only valid for the 'enum' fields.";
    const MISSING_OPTIONS_PARAM
        = "The 'options' parameter should contain an array with db enum values, or an object with db enum values as object keys, and labels as object values.";
    // @codingStandardsIgnoreEnd

    /**
     * Lowercase, underscored name of the field, as it appears in the database.
     *
     * @var string
     */
    public $name;

    public $required = true;
    public $type = 'text';
    public $maxLength;
    public $minLength;
    public $multiline;
    public $label;
    public $editable = true;
    public $visible = true;
    public $default;
    public $primary = false;
    public $unique = false;
    public $info;
    public $index;
    public $multilingual = false;
    public $comment;
    public $wysiwyg = false;
    public $searchable = null;

    /**
     * Used for relation foreign key fields. Defines the model related thru this foreign key field.
     *
     * @var string
     */
    public $model = false;

    /**
     * Used for relation foreign key fields. Defines the alias for this relation
     *
     * @var string
     */
    public $relationAlias = false;

    /**
     * Optional values for an enum field
     *
     * @var array
     */
    public $options = array();

    /**
     * Whether this is a floating point value, in case of a numeric field.
     *
     * @var bool
     */
    public $float = false;

    /**
     * Whether this is an unsigned value, in case of a numeric field.
     *
     * @var bool
     */
    public $unsigned = true;

    /**
     * Optional flag for an html field, allowing lists and media.
     *
     * @var bool
     */
    public $rich = false;

    /**
     * Context in which this field is added. Can be 'config', 'default', 'relation' or 'behavior'.
     *
     * @var string
     */
    public $origin;

    /**
     * Type of singular relation that this field references.
     * Only set in case of singular relation fields. Can be 'hasOne' or 'belongsTo'.
     *
     * @var string
     */
    public $relationType;

    protected $_types = array(
        'text', 'html', 'email', 'url', 'numeric', 'checkbox',
        'datetime', 'date', 'time', 'enum', 'set', 'document', 'imagefile'
    );

    protected $_defaultTypeByNamePattern = array(
        '/email$/'       => 'email',
        '/url$/'         => 'url',
        '/description$/' => 'html',
        '/(^|_)id$/'     => 'numeric',
        '/date$/'        => 'date',
        '/time$/'        => 'time'
    );

    /**
     * @param string $origin Context in which this field is added.
     *                       Can be 'config', 'default', 'relation' or 'behavior'.
     * @param string $name
     * @param array  $config
     * @return void
     */
    public function __construct($origin, $name, array $config) {
        $this->origin = $origin;
        $this->name = $name;
        $this->_loadParams($config);
        $this->_setConditionalDefaults($config);
    }

    public function isTextual() {
        $textualTypes = array('text', 'html', 'email', 'url', 'document');
        return in_array($this->type, $textualTypes);
    }

    public function isMultilingual() {
        return $this->multilingual;
    }

    public function isRelationField() {
        return $this->origin === 'relation';
    }

    public function isSuitableAsLabel() {
        $nonLabelFieldTypes = array('html', 'checkbox');
        $isSuitableType     = !in_array($this->type, $nonLabelFieldTypes);
        $isSuitableField    = $isSuitableType && !$this->isRelationField();

        return $isSuitableField;
    }

    protected function _loadParams(array $config) {
        foreach ($config as $paramName => $paramValue) {
            if (!property_exists($this, $paramName)) {
                $refl = new ReflectionObject($this);
                $reflProps = $refl->getProperties(ReflectionProperty::IS_PUBLIC);
                $publicProps = array();
                foreach ($reflProps as $reflProp) {
                    switch ($reflProp->name) {
                    case 'origin':
                    case 'name':
                        break;
                    default:
                        $publicProps[] = $reflProp->name;
                    }
                }
                throw new Exception(
                    sprintf(
                        self::INVALID_PARAMETER,
                        $paramName,
                        $this->name,
                        implode(', ', $publicProps)
                    )
                );
            } else {
                switch ($paramName) {
                case 'type':
                    if (!in_array($paramValue, $this->_types)) {
                        throw new Exception(
                            sprintf(
                                self::INVALID_FIELD_TYPE,
                                $paramValue,
                                $this->name,
                                implode(', ', $this->_types)
                            )
                        );
                    }
                    break;
                case 'options':
                    if ($config['type'] === 'enum' || $config['type'] === 'set') {
                        if ((!is_array($config['options']) && !is_object($config['options']))
                            || !($config['options'])
                        ) {
                            throw new Exception(self::MISSING_OPTIONS_PARAM);
                        }
                    } else {
                        throw new Exception(self::UNSUPPORTED_PARAM_OPTIONS);
                    }
                    break;
                case 'default':
                    // When "default" is given, but is NULL, we have to differentiate between an
                    // undefined value and actual purposeful NULL.
                    if (is_null($config['default'])) {
                        $paramValue = new Zend_Db_Expr('NULL');
                    }
                    break;
                }
            }

            $this->{$paramName} = $paramValue;
        }
    }

    protected function _setConditionalDefaults(array $config) {
        if (!array_key_exists('type', $config)) {
            foreach ($this->_defaultTypeByNamePattern as $pattern => $type) {
                if (preg_match($pattern, $this->name)) {
                    $this->type = $type;
                }
            }
        }

        if (!array_key_exists('maxLength', $config)) {
            switch ($this->name) {
            case 'name':
            case 'subtitle':
                $this->maxLength = self::TEXTFIELD_MAX_LENGTH;
                break;
            case 'id':
                $this->maxLength = 8;
                break;
            case 'email':
                $this->maxLength = 50;
                break;
            default:
                if (Garp_Spawn_Util::stringEndsIn('name', $this->name)) {
                    $this->maxLength = self::TEXTFIELD_MAX_LENGTH;
                }
            }
        }

        if (!array_key_exists('multiline', $config) && $this->isTextual()) {
            $this->multiline = !$this->maxLength || $this->maxLength > self::TEXTFIELD_MAX_LENGTH;
        }

        if ($this->type === 'checkbox') {
            $this->required = false;
        }

        if (!array_key_exists('label', $config)
            || !$config['label']
        ) {
            $this->label = Garp_Spawn_Util::underscored2readable(
                Garp_Spawn_Util::stringEndsIn('_id', $this->name) ?
                    substr($this->name, 0, -3) :
                    $this->name
            );
        } else {
            $this->label = ucfirst($this->label);
        }

        $this->required = (bool)$this->required;
    }

    public function getNameKey($language) {
        return $this->isMultilingual() ?
            '_' . $this->name . '_' . $language :
            $this->name
        ;
    }

    public function getNameProperty($name) {
    }
}


