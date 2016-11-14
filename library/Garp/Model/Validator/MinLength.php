<?php
/**
 * Garp_Model_Velidator_MinLength
 * Validates a minimum length of a value
 *
 * @package Garp_Model_Validator
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Validator_MinLength extends Garp_Model_Validator_Abstract {
    const ERROR_MESSAGE = "'%value%' is less than %min% characters long";

    /**
     * Columns to check for min length
     *
     * @var array
     */
    protected $_fields = array();

    /**
     * Setup the validation environment
     *
     * @param array $config Configuration options
     * @return void
     */
    protected function _setup($config) {
        $this->_fields = $config;
    }

    /**
     * Validate wether the given columns are long enough
     *
     * @param array $data The data to validate
     * @param Garp_Model_Db $model
     * @param bool $onlyIfAvailable Wether to skip validation on fields that are not in the array
     * @return void
     * @throws Garp_Model_Validator_Exception
     */
    public function validate(array $data, Garp_Model_Db $model, $onlyIfAvailable = true) {
        $theFields = $this->_fields;
        $applicableFields = array_keys(array_get_subset($data, array_keys($theFields)));

        $tooShortFields = array_filter(
            $applicableFields,
            function ($field) use ($theFields, $data) {
                return strlen($data[$field]) < $theFields[$field];
            }
        );

        if (count($tooShortFields)) {
            $first = current($tooShortFields);
            throw new Garp_Model_Validator_Exception(
                Garp_Util_String::interpolate(
                    __(self::ERROR_MESSAGE),
                    array(
                        'value' => $first,
                        'min' => $theFields[$first]
                    )
                )
            );
        }
    }

}
