<?php

/**
 * Class Garp_Model_DataFactory_Default
 * This factory uses Garp_Model_Db_Faker. It creates a row based on the model field configuration. Use cases like
 * required if can't be generated this way. Use Garp_Model_DataFactory_Abstract to create a factory for a specific model.
 *
 * @package Garp
 * @author Martijn Gastkemper <martijn@grrr.nl>
 */
class Garp_Model_DataFactory_Default implements Garp_Model_DataFactory_Interface {

    /**
     * @var Garp_Model_Db
     */
    protected $model;

    function setModel(Garp_Model_Db $model) {
        $this->model = $model;
        return $this;
    }

    public function make(array $defaultData = []): array {
        if ($this->model === null) {
            throw new Garp_Model_DataFactory_Exception();
        }
        $faker = new Garp_Model_Db_Faker();
        return $faker->createFakeRow($this->model->getFieldConfiguration(), $defaultData);
    }
}
