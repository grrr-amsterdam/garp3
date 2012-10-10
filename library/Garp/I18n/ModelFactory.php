<?php
/**
 * Garp_I18n_ModelFactory
 * Class responsible for the generation of i18n models.
 * These models are coupled to an internationalized view.
 * Take for instance a base table called 'posts'. This table
 * is managed via the Garp CMS and contains columns named in 
 * the following fashion;
 * - name_nl
 * - name_en
 * - description_nl
 * - description_en
 * To make the extraction of data on the frontend of your site
 * easier, you can create SQL views containing rules like this;
 * SELECT name_nl AS name...
 * And save this view as 'posts_nl' and make a similar one called
 * 'posts_en' containing all English columns.
 * Then from your rows you can extract data like this: $post->name
 * instead of having to prepend everything with the current 
 * locale.
 * This Factory will generate models for these views.
 * 
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage I18n
 * @lastmodified $Date: $
 */
class Garp_I18n_ModelFactory {
	/**
	 * Return a model for an internationalized SQL view.
	 * @param String $modelName The original model classname
	 * @param String $viewName The name of the i18n view
	 * @return Garp_Model
	 */
	public static function getModel($modelName, $viewName = null) {
		/**
		 * First, create an instance of the model in order
		 * to extract the primary key. We need this because
		 * i18n views don't have a primary key, so we have
		 * to explicitly set it.
		 */
		$originalModel	= new $modelName();
		$viewName		= $viewName ?: self::internationalizeName($originalModel->getName());
		$primaryKey		= $originalModel->info(Zend_Db_Table_Abstract::PRIMARY);
		
		return new $modelName(array(
			Zend_Db_Table_Abstract::PRIMARY => $primaryKey,
			Zend_Db_Table_Abstract::NAME	=> $viewName
		));
	}
	
	
	/**
	 * Create an internationalized name from a tablename.
	 * E.g. 'posts' becomes 'posts_nl', or 'posts_en'.
	 * @param String $tableName
	 * @param String $locale 
	 * @return String
	 */
	public static function internationalizeName($tableName, $locale = null) {
		$locale = $locale ?: (Garp_I18n::getCurrentLocale() ?: Garp_I18n::getDefaultLocale());
		$tableName .= '_'.$locale;
		return $tableName;
	}
}