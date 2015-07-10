<?php
/**
 * @group Spawn
 */
class Garp_Spawn_FieldTest extends Garp_Test_PHPUnit_TestCase {

	public function testRequiredCheckboxShouldNotBeNullable() {
		$field = new Garp_Spawn_Field('config', 'is_highlighted', array(
			'type' => 'checkbox',
			'default' => 0,
			'required' => true
		));
		$sql = Garp_Spawn_MySql_Column::renderFieldSql($field);
		$this->assertEquals(
			'  `is_highlighted` tinyint(1) NOT NULL DEFAULT 0',
			$sql);
		$this->assertEquals(0, $field->default);
	}

}
