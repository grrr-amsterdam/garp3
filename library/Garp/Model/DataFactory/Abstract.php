<?php

use Garp\Functional as f;

/**
 * Class Garp_Model_DataFactory_Abstract
 *
 * @package Garp
 * @author Martijn Gastkemper <martijn@grrr.nl>
 */
abstract class Garp_Model_DataFactory_Abstract implements Garp_Model_DataFactory_Interface {

    protected $_faker;

    public function __construct() {
        $this->_faker = Faker\Factory::create();
        $this->_faker->addProvider(new Faker\Provider\Lorem($this->_faker));
    }

    abstract function define(\Faker\Generator $faker);

    public function make(array $defaults = []): array {
        return f\concat(
            $this->define($this->_faker),
            $defaults
        );
    }

    /**
     * There's a 10% change (more or less) the result of $fn will be returned. Otherwise it's NULL.
     *
     * @param callable $fn
     * @param int $change
     * @return mixed|null
     */
    protected function diceRoll($fn, int $change = 10) {
        $diceRoll = $this->_faker->numberBetween(1, 100);
        if ($diceRoll < $change) {
            return $fn();
        }
    }
}
