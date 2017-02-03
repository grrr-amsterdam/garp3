<?php
/**
 * Garp_Cli_Command_Db
 * Contains various database related methods.
 *
 * @package Garp_Cli_Command
 * @author  Harmen Janssen <harmen@grrr.nl>
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Cli_Command_Db extends Garp_Cli_Command {

    /**
     * Show table info (DESCRIBE query) for given table
     *
     * @param array $args
     * @return void
     */
    public function info(array $args = array()) {
        if (empty($args)) {
            Garp_Cli::errorOut('Insufficient arguments');
            Garp_Cli::lineOut('Usage: garp Db info <tablename>');
            return;
        }
        $db = new Zend_Db_Table($args[0]);
        Garp_Cli::lineOut(
            Zend_Config_Writer_Yaml::encode($db->info())
        );
        Garp_Cli::lineOut('');
    }

    public function sync(array $args = array()) {
        $sourceEnv = $args ? current($args) : null;
        new Garp_Db_Synchronizer($sourceEnv);
    }

    /**
     * Walks over every text column of every record of every table
     * and replaces references to $subject with $replacement.
     * Especially useful since all images in Rich Text Editors are
     * referenced with absolute paths including the domain. This method
     * can be used to replace "old domain" with "new domain" in one go.
     *
     * @param array $args
     * @return bool
     */
    public function replace(array $args = array()) {
        $subject = !empty($args[0]) ? $args[0] :
            Garp_Cli::prompt('What is the string you wish to replace?');
        $replacement = !empty($args[1]) ? $args[1] :
            Garp_Cli::prompt('What is the new string you wish to insert?');
        $subject = trim($subject);
        $replacement = trim($replacement);

        $models = Garp_Content_Api::getAllModels();
        foreach ($models as $model) {
            if (is_subclass_of($model->class, 'Garp_Model_Db')) {
                $this->_replaceString($model->class, $subject, $replacement);
            }
        }
        return true;
    }

    /**
     * Start the interactive mysql client
     *
     * @return bool
     */
    public function connect() {
        $adapter = Zend_Db_Table::getDefaultAdapter();
        if (!$adapter) {
            Garp_Cli::errorOut('No database adapter found');
            return false;
        }
        $config = $adapter->getConfig();
        $params = array(
            '-u' . escapeshellarg($config['username']),
            '-p' . escapeshellarg($config['password']),
            '-h' . escapeshellarg($config['host']),
            ' ' . escapeshellarg($config['dbname'])
        );
        $cmd = 'mysql ' . implode(' ', $params);
        $process = proc_open($cmd, array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes);
        $proc_status = proc_get_status($process);
        $exit_code = proc_close($process);
        return ($proc_status["running"] ? $exit_code : $proc_status["exitcode"] ) == 0;
    }

    /**
     * Help
     *
     * @return bool
     */
    public function help() {
        Garp_Cli::lineOut('Usage:');
        Garp_Cli::lineOut('Show table info:');
        Garp_Cli::lineOut('  g Db info <tablename>');
        Garp_Cli::lineOut('');
        Garp_Cli::lineOut('Sync database with database of different environment:');
        Garp_Cli::lineOut('  g Db sync <environment>');
        Garp_Cli::lineOut('');
        Garp_Cli::lineOut('Replace a string in the database, across tables and columns:');
        Garp_Cli::lineOut('  g Db replace');
        Garp_Cli::lineOut('');
        Garp_Cli::lineOut('Connect to mysql interactively:');
        Garp_Cli::lineOut('  g Db connect');
        Garp_Cli::lineOut('');
        return true;
    }

    /**
     * Replace $subject with $replacement in all textual columns of the table.
     *
     * @param  string  $modelClass  The model classname
     * @param  string  $subject     The string that is to be replaced
     * @param  string  $replacement The string that will take its place
     * @return void
     */
    protected function _replaceString($modelClass, $subject, $replacement) {
        $model = new $modelClass();
        $columns = $this->_getTextualColumns($model);
        if ($columns) {
            $adapter = $model->getAdapter();
            $updateQuery = 'UPDATE ' . $adapter->quoteIdentifier($model->getName()) . ' SET ';
            foreach ($columns as $i => $column) {
                $updateQuery .= $adapter->quoteIdentifier($column) . ' = REPLACE(';
                $updateQuery .= $adapter->quoteIdentifier($column) . ', ';
                $updateQuery .= $adapter->quoteInto('?, ', $subject);
                $updateQuery .= $adapter->quoteInto('?)', $replacement);
                if ($i < (count($columns)-1)) {
                    $updateQuery .= ',';
                }
            }
            if ($response = $adapter->query($updateQuery)) {
                $affectedRows = $response->rowCount();
                Garp_Cli::lineOut('Model: ' . $model->getName());
                Garp_Cli::lineOut('Affected rows: ' . $affectedRows);
                Garp_Cli::lineOut('Involved columns: ' . implode(', ', $columns) . "\n");
            } else {
                Garp_Cli::errorOut('Error: update for table `' . $model->getName() . '` failed.');
            }
        }
    }

    /**
     * Get all textual columns from a table
     *
     * @param  Garp_Model_Db  $model  The model
     * @return array
     */
    protected function _getTextualColumns(Garp_Model_Db $model) {
        $columns = $model->info(Zend_Db_Table::METADATA);
        $textTypes = array('varchar', 'text', 'mediumtext', 'longtext', 'tinytext');
        foreach ($columns as $column => $meta) {
            if (!in_array($meta['DATA_TYPE'], $textTypes)) {
                unset($columns[$column]);
            }
        }
        return array_keys($columns);
    }
}
