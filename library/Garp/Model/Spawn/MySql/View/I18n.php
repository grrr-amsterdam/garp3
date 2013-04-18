<?php
/**
 * A representation of a translated MySQL view.
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 * @group Spawn
 */
class Garp_Model_Spawn_MySql_View_I18n extends Garp_Model_Spawn_MySql_View_Abstract {

	public function getName() {
		// return $this->getModelId() . self::POSTFIX;
	}
	
	public function renderSql() {

		// SELECT
		// /* Language neutral columns */
		// id, created, modified,
		// /* Translatable columns */
		// COALESCE(ai_de.name, ai_en.name) AS name,
		// COALESCE(ai_de.description, ai_en.description) AS description
		// 
		// FROM animals a
		// 
		// LEFT OUTER JOIN animals_i18n ai_de ON ai_de.animal_id = a.id AND ai_de.lang = 'DE'
		// LEFT OUTER JOIN animals_i18n ai_en ON ai_en.animal_id = a.id AND ai_en.lang = 'EN'


	}
}