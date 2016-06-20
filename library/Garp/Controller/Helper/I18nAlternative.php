<?php
/**
 * Garp_Controller_Helper_I18nAlternative
 * Grabs the slug of a record in different languages than the current. Used to construct
 * language-picker URLs.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_Controller_Helper
 */
class Garp_Controller_Helper_I18nAlternative extends Zend_Controller_Action_Helper_Abstract {

    /**
     * @param String $model_name (not prefixed)
     * @param Garp_Db_Table_Row|Int $record The record on which the alternative is based,
     *                                      or the id thereof.
     * @param String $foreign_key Foreign key column to the parent table
     * @return Array
     * @todo Make it work with various other parameters, not just 'slug'
     */
    public function direct($model_name, $foreign_key, $record, $default_to_same_slug = true) {
        $record_id = $record;
        if ($record instanceof Garp_Db_Table_Row) {
            $record_id = $record_id->id;
        }
        // Add the slugs of the group in alternate languages for the language picker
        $alt_param_slug = null;
        if ($default_to_same_slug && $record instanceof Garp_Db_Table_Row) {
            $alt_param_slug = $record->slug;
        }
        $alternate_url_params = array_fill_keys(Garp_I18n::getLocales(),
            array('slug' => $alt_param_slug));
        $model_name = 'Model_' . $model_name . 'I18n';
        $i18n_model = new $model_name;
        $select = $i18n_model
            ->select()
            ->from($i18n_model->getName(), array('slug', 'lang'))
            ->where("$foreign_key = ?", $record_id)
            ->where('lang != ?', Garp_I18n::getCurrentLocale())
        ;
        $localized_recordset = $i18n_model->fetchAll($select);
        foreach ($localized_recordset as $record) {
            $alternate_url_params[$record->lang] = array(
                'slug' => $record->slug
            );
        }
        return $alternate_url_params;
    }

}
