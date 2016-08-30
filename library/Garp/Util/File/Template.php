<?php
/**
 * Garp_Util_File_Template
 * Mini template-engine.
 * Used to generate files. Feed it a template, set some
 * variables and save the output.
 *
 * @package Garp_Util
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Util_File_Template {
    /**
     * The template contents.
     *
     * @var string
     */
    protected $_content;

    /**
     * Class constructor
     *
     * @param string $path Path to the template file
     * @return void
     */
    public function __construct($path) {
        $this->_content = file_get_contents($path);
    }

    /**
     * Set variable.
     *
     * @param string $key The variable key (template must contain an "#$key")
     * @param mixed $value The variable value
     * @return Garp_Util_File_Template $this
     */
    public function setVariable($key, $value) {
        $this->_content = str_replace('#' . $key . '#', (string)$value, $this->_content);
        return $this;
    }

    /**
     * Get output.
     *
     * @return string
     */
    public function getOutput() {
        return $this->_content;
    }

    /**
     * Save output.
     *
     * @param string $path
     * @return string
     */
    public function saveOutput($path) {
        return file_put_contents($path, $this->_content);
    }
}
