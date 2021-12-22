<?php
use Garp\Functional as f;

/**
 * @package Tests
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Db_FakerTest extends Garp_Test_PHPUnit_TestCase {

    /** @test */
    public function should_omit_primary_and_foreign_keys() {
        $fieldConfig = $this->_getFieldConfig();

        $faker = new Garp_Model_Db_Faker();
        $fakeRow = $faker->createFakeRow($fieldConfig, []);

        $this->assertFalse(array_key_exists('id', $fakeRow));
        $this->assertFalse(array_key_exists('user_id', $fakeRow));
    }

    /** @test */
    public function should_generate_a_random_row() {
        $fieldConfig = $this->_getFieldConfig();

        $isPrimaryOrForeign = f\either(f\equals('id'), f\equals('user_id'));
        $expectedKeys = f\filter(f\not($isPrimaryOrForeign), f\map(f\prop('name'), $fieldConfig));

        $faker = new Garp_Model_Db_Faker();
        $fakeRow = $faker->createFakeRow($fieldConfig);

        $this->assertEqualsCanonicalizing(
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

        $subset = f\pick(array_keys($defaults), $fakeRow);
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
            f\prop('foo', $fakeRow)
        );
    }

    protected function _getFieldConfig() {
        return array(
            array('name' => 'id', 'type' => 'numeric', 'primary' => true),
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
            ),
            array(
                'name' => 'user_id', 'type' => 'numeric',
                'origin' => 'relation', 'relationType' => 'hasOne'
            )
        );
    }

    public function setUp(): void {
        parent::setUp();
        $this->_helper->injectConfigValues(
            array(
                'app' => array('domain' => 'localhost.garp3.com')
            )
        );
    }
}

