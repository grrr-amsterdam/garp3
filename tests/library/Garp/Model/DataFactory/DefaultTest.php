<?php

use Garp\Functional as f;

/**
 * Class Garp_Model_DataFactory_DefaultTest
 *
 * @author Martijn Gastkemper <martijn@grrr.nl>
 */
class Garp_Model_DataFactory_DefaultTest extends Garp_Test_PHPUnit_TestCase
{

    /** @test */
    public function should_fake_model_data()
    {
        $model = $this->createMock(Garp_Model_Db::class);
        $model->method('getFieldConfiguration')
            ->willReturn([
                'fieldA' => [
                    'name' => 'fieldA',
                    'type' => 'text',
                    'required' => true
                ]
            ]);

        $dataFactory = new Garp_Model_DataFactory_Default();
        $dataFactory->setModel($model);

        $data = $dataFactory->make(['fieldB' => 'default']);

        $this->assertArrayHasKey('fieldA', $data);
        $this->assertEquals('default', f\prop('fieldB', $data));
    }

    /**
     * @test
     * @expectedException Garp_Model_DataFactory_Exception
     */
    public function should_throw_exception_when_model_is_empty()
    {
        (new Garp_Model_DataFactory_Default())->make();
    }
}
