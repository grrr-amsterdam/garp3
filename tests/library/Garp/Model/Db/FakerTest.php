<?php
/**
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Db_FakerTest extends Garp_Test_PHPUnit_TestCase {
    /** @test */
    public function should_generate_a_random_row() {
        $fieldConfig = $this->_getFieldConfig();

        $expectedKeys = array_map(array_get('name'), $fieldConfig);
        $faker = new Garp_Model_Db_Faker();
        $fakeRow = $faker->createFakeRow($fieldConfig);

        $this->assertEquals(
            $expectedKeys,
            array_keys($fakeRow)
        );
    }

    /** @test */
    public function should_respect_default_values() {
        $fieldConfig = $this->_getFieldConfig();

        $defaults = array(
            'created' => '1975-04-11 12:40:12',
            'is_featured' => 0,
            'name' => 'Henk'
        );

        $faker = new Garp_Model_Db_Faker();
        $fakeRow = $faker->createFakeRow($fieldConfig, $defaults);

        $subset = array_get_subset($fakeRow, array_keys($defaults));
        $this->assertEquals(
            $defaults,
            $subset
        );
    }

    /** @test */
    public function should_leave_unknown_columns_intact() {
        $fieldConfig = $this->_getFieldConfig();

        $defaults = array(
            'foo' => 'bar'
        );

        $faker = new Garp_Model_Db_Faker();
        $fakeRow = $faker->createFakeRow($fieldConfig, $defaults);

        $this->assertEquals(
            'bar',
            array_get($fakeRow, 'foo')
        );
    }

    protected function _getFieldConfig() {
        return array(
            array('name' => 'name', 'type' => 'text'),
            array('name' => 'excerpt', 'type' => 'text', 'required' => false, 'maxLength' => 255),
            array('name' => 'body', 'type' => 'html'),
            array('name' => 'created', 'type' => 'datetime'),
            array('name' => 'is_featured', 'type' => 'checkbox'),
            array(
                'name' => 'status', 'type' => 'enum', 'options' => array(
                    'published',
                    'deleted',
                    'draft'
                )
            )
        );
    }

    public function setUp() {
        parent::setUp();
        $this->_helper->injectConfigValues(
            array(
                'app' => array('domain' => 'localhost.garp3.com')
            )
        );
    }
}
