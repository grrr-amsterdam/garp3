<?php
/**
 * Garp_Cli_Command_Spawn_Feedback
 *
 * @package Garp_Cli_Command_Spawn
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Cli_Command_Spawn_Feedback {

    /**
     * The command to dump a specific Javascript base model - since this
     * is normally concatenated and minified with the other base models.
     */
    const JS_BASE_MODEL_COMMAND = 'showJsBaseModel';
    const BATCH_MODE_COMMAND = 'b';

    protected $_args;


    public function __construct(array $args) {
        $this->setArgs($args);
    }

    public function setArgs(array $args) {
        $this->_args = $args;
    }

    public function getArgs() {
        return $this->_args;
    }

    /**
     * Whether the current setting should spawn any output, or just do a dry-run.
     *
     * @return bool
     */
    public function shouldSpawn() {
        return !array_key_exists(self::JS_BASE_MODEL_COMMAND, $this->getArgs());
    }

    /**
     * Returns whether the Spawner is in interactive or batch mode.
     * Interactive mode displays progress and asks questions.
     * Batch mode does not ask questions, is not careful with changes and displays minimal feedback.
     *
     * @return bool
     */
    public function isInteractive() {
        return !array_key_exists(self::BATCH_MODE_COMMAND, $this->getArgs());
    }
}
