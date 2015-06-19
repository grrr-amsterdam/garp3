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

		// Is dit te makkelijk?
		// Op regels 162-164 van Garp/Spawn/Field.php wordt wel duidelijk een checkbox op
		// NIET required gezet, dat is niet zonder reden lijkt me.
		// Misschien moet 'default' erbij genomen worden in dat if statement?
		// Want het gaat er in ieder geval om dat ie nooit NULL moet krijgen als waarde, maar dat
		// er alleen 0 of 1 in komt te staan. In ieder geval wanneer de default waarde gezet is.
		// Anders zou je nog kunnen beargumenteren dat NULL een soort undetermined aangeeft, maar
		// voor onze projecten heb ik dat nog nooit nodig gehad.
		$this->assertEquals(true, $field->required);
	}

}
