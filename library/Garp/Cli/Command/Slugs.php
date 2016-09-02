<?php
/**
 * Garp_Cli_Command_Slugs
 * Generate slugs for a given table
 *
 * @package Garp_Cli_Command
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Slugs extends Garp_Cli_Command {
    /**
     * Generate the slugs
     *
     * @param array $args
     * @return bool
     */
    public function generate(array $args = array()) {
        if (empty($args)) {
            $this->help();
            return false;
        }
        $modelName = $args[0];
        if (strpos($modelName, '_') === false) {
            $modelName = 'Model_' . $modelName;
        }
        $overwrite = !empty($args[1]) ? $args[1] : false;

        $model = new $modelName();
        // No reason to cache queries. Use live data.
        $model->setCacheQueries(false);

        // Fetch Sluggable thru the model as to use the right slug-configuration
        list($sluggable, $model) = $this->_resolveSluggableBehavior($model);
        if (is_null($sluggable)) {
            Garp_Cli::errorOut('This model is not sluggable.');
            return false;
        }

        // Array to record fails
        $fails = array();
        $records = $model->fetchAll();
        foreach ($records as $record) {
            if (!$overwrite && $record->slug) {
                continue;
            }
            // Mimic a beforeInsert to create the slug in a separate array $data
            $args = array($model, $record->toArray());
            $sluggable->beforeInsert($args);

            // Since there might be more than one changed column, we use this loop
            // to append those columns to the record
            foreach ($args[1] as $key => $value) {
                if ($value != $record->{$key}) {
                    $record->{$key} = $value;
                }
            }

            // Save the record with its slug
            if (!$record->save()) {
                $pk = implode(', ', (array)$record->getPrimaryKey());
                $fails[] = $pk;
            }
        }

        Garp_Cli::lineOut('Done.');
        if (count($fails)) {
            Garp_Cli::errorOut(
                'There were some failures. ' .
                'Please perform a manual check on records with the following primary keys:'
            );
            Garp_Cli::lineOut(implode("\n-", $fails));
        }
        return true;
    }


    /**
     * Help
     *
     * @return bool
     */
    public function help() {
        Garp_Cli::lineOut('Generate slugs for existing records');
        Garp_Cli::lineOut('Usage:');
        Garp_Cli::lineOut('  g Slugs generate <model name> <overwrite>');
        Garp_Cli::lineOut('');
        return true;
    }

    protected function _resolveSluggableBehavior(Garp_Model_Db $model) {
        $sluggable = $model->getObserver('Sluggable');
        if (!is_null($sluggable)) {
            return array($sluggable, $model);
        }
        // Try on a derived model
        $translatable = $model->getObserver('Translatable');
        if (!is_null($translatable)) {
            $model = $translatable->getI18nModel($model->getUnilingualModel());
            return $this->_resolveSluggableBehavior($model);
        }
        return array(null, null);
    }
}
