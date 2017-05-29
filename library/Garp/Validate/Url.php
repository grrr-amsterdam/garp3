<?php
/**
 * Garp_Validate_Url
 * class description
 *
 * @package Garp_Validate
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Validate_Url extends Zend_Validate_Abstract {

    const INVALID_INPUT = 'invalidInput';
    const INVALID_URL = 'invalidUrl';

    protected $_messageTemplates = array(
        self::INVALID_INPUT => "url validator invalid input",
        self::INVALID_URL   => "url validator invalid url",
    );

    public function isValid($value) {
        $value = trim($value);
        $this->_setValue($value);

        // Taken from @see http://nl.php.net/manual/en/function.preg-match.php#93824
        $regexp = "((https?|ftp)\:\/\/)?"; // SCHEME
        $regexp .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
        $regexp .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP
        $regexp .= "(\:[0-9]{2,5})?"; // Port
        $regexp .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path
        $regexp .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query
        $regexp .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor

        if (!is_string($value)) {
            $this->_error(self::INVALID_INPUT);
            return false;
        } elseif (!preg_match('/^' . $regexp . '$/i', $value)) {
            $this->_error(self::INVALID_URL);
            return false;
        }
        return true;
    }
}
