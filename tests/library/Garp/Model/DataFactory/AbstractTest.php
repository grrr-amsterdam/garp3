<?php

use Garp\Functional as f;

/**
 * Class Garp_Model_DataFactory_AbstractTest
 *
 * @author Martijn Gastkemper <martijn@grrr.nl>
 */
class Garp_Model_DataFactory_AbstractTest extends Garp_Test_PHPUnit_TestCase
{
    /** @test */
    public function should_fake_model_data() {
        $dataFactory = new class extends Garp_Model_DataFactory_Abstract {
            function define(\Faker\Generator $faker) {
                return [
                    'fieldA' => $faker->text,
                    'fieldB' => $faker->text
                ];
            }
        };

        $data = $dataFactory->make(['fieldB' => 'default']);

        $this->assertInternalType('string', f\prop('fieldA', $data));
        $this->assertEquals('default', f\prop('fieldB', $data));
        $this->assertArrayNotHasKey('fieldC', $data);
    }
}
