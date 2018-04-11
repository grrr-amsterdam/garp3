<?php
use Garp\Functional as f;

/**
 * Garp_Cli_Command_Cdn
 * Distribute assets to the configured CDN.
 *
 * Note: since we're moving to dotenv implementations, configuration of other environments is no
 * longer shared in the codebase.
 * Therefore, settings should be piped into this script, for instance using the 12g utility:
 *
 * ```
 * $ 12g env list -e s -o json | g cdn distribute
 * ```
 *
 * If STDIN is empty, assets are distributed to whatever is currently configured (most likely
 * DEVELOPMENT).
 *
 * @package Garp_Cli_Command
 * @author David Spreekmeester <david@grrr.nl>
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Cdn extends Garp_Cli_Command {
    const FILTER_DATE_PARAM = 'since';
    const FILTER_DATE_VALUE_NEGATION = 'forever';

    const DRY_RUN_PARAM = 'dry';

    /**
     * Distributes the public assets on the local server to the configured CDN servers.
     *
     * @param array $args
     * @return void
     */
    public function distribute(array $args) {
        if (f\prop('to', $args)) {
            Garp_Cli::errorOut('"to" is a deprecated parameter.');
            Garp_Cli::lineOut(
                'Please use npm package 12g to pipe credentials of the target environment ' .
                "into Garp.\n" .
                " 👉  https://www.npmjs.com/package/12g\n\n" .
                "Usage:\n\n" .
                "12g env list -e {$args['to']} -o json | g cdn distribute\n"
            );
            return false;
        }
        $filterString = $this->_getFilterString($args);
        $filterDate = $this->_getFilterDate($args);
        $cdnConfig = $this->_gatherConfigVars();
        $isDryRun = $this->_getDryRunParam($args);

        $distributor = new Garp_Content_Cdn_Distributor();
        $assetList = $distributor->select($filterString, $filterDate);

        if (!$assetList) {
            Garp_Cli::errorOut("No files to distribute.");
            return false;
        }

        $summary = $this->_getReportSummary($assetList, $filterDate);
        Garp_Cli::lineOut("Distributing {$summary}\n");

        if ($isDryRun) {
            Garp_Cli::lineOut(implode("\n", (array)$assetList));
            return true;
        }

        $distributor->distribute(
            $cdnConfig,
            $assetList,
            function () {
                echo '.';
            },
            function ($asset) {
                Garp_Cli::errorOut("\nCould not upload {$asset}.");
            }
        );

        Garp_Cli::lineOut("\n√ Done");
        echo "\n\n";
        return true;
    }

    public function help() {
        Garp_Cli::lineOut("☞  U s a g e :\n");
        Garp_Cli::lineOut("Distributing all assets to the CDN servers:");
        Garp_Cli::lineOut("\tgarp.php Cdn distribute");
        Garp_Cli::lineOut("");

        Garp_Cli::lineOut("Examples of distributing a specific set of assets to the CDN servers:");
        Garp_Cli::lineOut("\tgarp.php Cdn distribute main.js");
        Garp_Cli::lineOut("\tgarp.php Cdn distribute css");
        Garp_Cli::lineOut("\tgarp.php Cdn distribute css/icons");
        Garp_Cli::lineOut("\tgarp.php Cdn distribute logos");
        Garp_Cli::lineOut("");

        Garp_Cli::lineOut("Distributing to a specific environment:");
        Garp_Cli::lineOut("\tgarp.php Cdn distribute --to=development");
        Garp_Cli::lineOut("\tgarp.php Cdn distribute main.js --to=staging");
        Garp_Cli::lineOut("");

        Garp_Cli::lineOut("Default only recently modified files will be distributed.");
        Garp_Cli::lineOut("To distribute all files:");
        Garp_Cli::lineOut("\tgarp.php Cdn distribute --since=forever");
        Garp_Cli::lineOut("");

        Garp_Cli::lineOut(
            "To distribute files modified since a specific date " .
            "(use a 'strtotime' compatible argument):"
        );
        Garp_Cli::lineOut("\tgarp.php Cdn distribute --since=yesterday");
        Garp_Cli::lineOut("");

        Garp_Cli::lineOut(
            "To see which files will be distributed without actually " .
            "distributing them, do a dry run:"
        );
        Garp_Cli::lineOut("\tgarp.php Cdn distribute --dry");
        Garp_Cli::lineOut("");

    }

    /**
     * Take the required variables necessary for distributing assets.
     *
     * @return array
     */
    protected function _gatherConfigVars() {
        $source = $this->_stdin ?
            $this->_parseStdin($this->_stdin) :
            Zend_Registry::get('config')->toArray();
        $cdnConfig = f\prop('cdn', $source);
        $s3Config = f\prop('s3', $cdnConfig);
        return array(
            'apikey'          => f\prop('apikey', $s3Config),
            'secret'          => f\prop('secret', $s3Config),
            'bucket'          => f\prop('bucket', $s3Config),
            'readonly'        => f\prop('readonly', $cdnConfig),
            'gzip'            => f\prop('gzip', $cdnConfig),
            'gzip_exceptions' => f\prop('gzip_exceptions', $cdnConfig)
        );
    }

    /**
     * Parse STDIN as JSON.
     * Output is formatted like the standard assets.ini.
     *
     * @param string $stdin
     * @return array
     */
    protected function _parseStdin($stdin) {
        try {
            $values = Zend_Json::decode($stdin);
            // Switch ENV vars to the given values.
            $currentEnv = $this->_updateEnvVars($values);
            // Note: we use "production" here because it's the highest level a config file can
            // support, making sure all required parameters are probably present.
            // Actual credentials will be provided from ENV.
            $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/assets.ini', 'production');
            // Reset ENV vars.
            $this->_updateEnvVars($currentEnv);
            return $config->toArray();
        } catch (Zend_Json_Exception $e) {
            throw new Exception(
                'Data passed thru STDIN needs to be in JSON-format'
            );
        }
    }

    /**
     * Update ENV vars with the given values.
     * Returns the _current_ values.
     *
     * @param  array $vars The new values
     * @return array       The old values
     */
    protected function _updateEnvVars(array $vars) {
        return f\reduce_assoc(
            function ($o, $var, $key) {
                // Store the current value
                $o[$key] = getenv($key);
                // Put the new value
                putenv("{$key}={$var}");
                return $o;
            },
            array(),
            $vars
        );
    }

    protected function _getFilterDateLabel($filterDate, $assetList) {
        if ($filterDate === false) {
            return 'forever';
        }
        if ($filterDate === null) {
            return date('j-n-Y', $assetList->getFilterDate());
        }
        return $filterDate;
    }

    protected function _getFilterString(array $args) {
        return array_key_exists(0, $args) ? $args[0] : null;
    }

    protected function _getFilterDate(array $args) {
        return array_key_exists(self::FILTER_DATE_PARAM, $args) ?
            ($args[self::FILTER_DATE_PARAM] === self::FILTER_DATE_VALUE_NEGATION ?
                false :
                $args[self::FILTER_DATE_PARAM]
            ) :
            null
        ;
    }

    protected function _getDryRunParam(array $args) {
        return array_key_exists(self::DRY_RUN_PARAM, $args);
    }

    protected function _getReportSummary(Garp_Content_Cdn_AssetList $assetList, $filterDate) {
        $assetCount = count($assetList);
        $summary = $assetCount === 1 ? $assetList[0] : $assetCount . ' assets';
        return $summary . ' since ' . $this->_getFilterDateLabel($filterDate, $assetList);
    }
}
