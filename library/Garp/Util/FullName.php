<?php
/**
 * Garp_Util_FullName
 * Represents a concatenated string, composed of separate name fields.
 *
 * @package Garp_Util
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Util_FullName {
    protected $_fullName;

    /**
     * Class constructor
     *
     * @param mixed $person
     * @return void
     */
    public function __construct($person) {
        if ($person instanceof Zend_Db_Table_Row_Abstract) {
            $person = $person->toArray();
        }

        $this->_fullName = $this->_getFullName($person);
    }

    /**
     * Get the value
     *
     * @return string
     */
    public function __toString() {
        return (string)$this->_fullName;
    }

    /**
     * Create full name
     *
     * @param Garp_Db_Table_Row|StdClass|array $person
     * @return string
     */
    protected function _getFullName($person) {
        if (array_key_exists('first_name', $person)
            && array_key_exists('last_name_prefix', $person)
            && array_key_exists('last_name', $person)
        ) {
            $first = $person['first_name'];
            $middle = $person['last_name_prefix'] ? ' ' . $person['last_name_prefix'] : '';
            $last = $person['last_name'] ? ' ' . $person['last_name'] : '';
            return $first . $middle . $last;
        } elseif (array_key_exists('name', $person)) {
            return $person['name'];
        } else {
            throw new Exception(
                'This model does not have a first name, last name prefix ' .
                'and last name. Nor does it have a singular name field.'
            );
        }
    }
}
