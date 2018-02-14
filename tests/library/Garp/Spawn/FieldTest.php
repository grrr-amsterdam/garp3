<?php
/**
 * @package Tests
 * @author  David Spreekmeester <david@grrr.nl>
 * @group   Spawn
 */
class Garp_Spawn_FieldTest extends Garp_Test_PHPUnit_TestCase {

    public function testRequiredCheckboxShouldNotBeNullable() {
        $field = new Garp_Spawn_Field(
            'config',
            'is_highlighted',
            array(
                'type' => 'checkbox',
                'default' => 0,
                'required' => true
            )
        );
        $sql = Garp_Spawn_Db_Column::renderFieldSql($field);
        $this->assertEquals(
            '  `is_highlighted` tinyint(1) NOT NULL DEFAULT 0',
            $sql
        );
        $this->assertEquals(0, $field->default);
    }

    public function testShouldFigureOutDefaultType() {
        $field = new Garp_Spawn_Field('config', 'id', array());
        $this->assertEquals('numeric', $field->type);

        $field = new Garp_Spawn_Field('config', 'active_tickets_id', array());
        $this->assertEquals('numeric', $field->type);

        $field = new Garp_Spawn_Field('config', 'beleid', array());
        $this->assertEquals('text', $field->type);

        $field = new Garp_Spawn_Field('config', 'contact_email', array());
        $this->assertEquals('email', $field->type);

        $field = new Garp_Spawn_Field('config', 'api_url', array());
        $this->assertEquals('url', $field->type);

        $field = new Garp_Spawn_Field('config', 'short_description', array());
        $this->assertEquals('html', $field->type);

        $field = new Garp_Spawn_Field('config', 'date', array());
        $this->assertEquals('date', $field->type);

        $field = new Garp_Spawn_Field('config', 'end_date', array());
        $this->assertEquals('date', $field->type);

        $field = new Garp_Spawn_Field('config', 'start_time', array());
        $this->assertEquals('time', $field->type);
    }
}
