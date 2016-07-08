<?php
/**
 * Garp_Cli_Command_Composer
 * Update an old project so it requires Garp from Composer
 *
 * @package Garp_Cli_Command
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Composer extends Garp_Cli_Command {
    const OLD_INIT_PHP_REFERENCE = '../garp/application/init.php';
    const NEW_INIT_PHP_REFERENCE = '../vendor/grrr-amsterdam/garp3/application/init.php';
    const BASEPATH_DEFINITON = "define('BASE_PATH', realpath(dirname(__FILE__) . '/..'));";
    const INCLUDE_I18N_FILE = "include APPLICATION_PATH.'/../garp/application/data/i18n/%s.php';";
    const NEW_INCLUDE_I18N_FILE =  "include GARP_APPLICATION_PATH . '/data/i18n/%s.php';";
    const OLD_ROUTES_INCLUDE = 'APPLICATION_PATH "/../garp/application/configs/routes.ini"';
    const NEW_ROUTES_INCLUDE = 'GARP_APPLICATION_PATH "/configs/routes.ini"';
    const OLD_GARP_DEPLOY_PATH = 'garp/application/configs/deploy.rb';
    const NEW_GARP_DEPLOY_PATH = 'vendor/grrr-amsterdam/garp3/application/configs/deploy.rb';

    /**
     * Migrate garp to the composer version.
     *
     * @return bool
     */
    public function migrate() {
        $this->_requireGarpComposerPackage();
        $this->_updateSymlinks();
        $this->_updateIndexPhp();
        $this->_updateLocaleFiles();
        $this->_updateRoutesInclude();
        $this->_updateCapFile();

        Garp_Cli::lineOut('Done!');
        Garp_Cli::lineOut(
            'I\'m leaving the original garp folder in case you ' .
            'still have to push something from the subtree.', Garp_Cli::BLUE
        );

        return true;
    }

    protected function _requireGarpComposerPackage() {
        passthru('composer require grrr-amsterdam/garp3:^3.7.0');
    }

    protected function _updateSymlinks() {
        passthru('ln -shf ../../vendor/grrr-amsterdam/garp3/public/js public/js/garp');
        passthru('ln -shf ../../vendor/grrr-amsterdam/garp3/public/css public/css/garp');
        passthru(
            'ln -shf ../../../vendor/grrr-amsterdam/garp3/public/images ' .
            'public/media/images/garp'
        );
        passthru(
            'ln -shf ../vendor/grrr-amsterdam/garp3/library/Garp/3rdParty/PHPExcel ' .
            'library/PHPExcel'
        );
    }

    protected function _updateIndexPhp() {
        // Update include of init.php
        $indexPhp = file_get_contents('public/index.php');
        $indexPhp = str_replace(
            self::OLD_INIT_PHP_REFERENCE,
            self::NEW_INIT_PHP_REFERENCE, $indexPhp
        );

        // Put BASE_PATH definition in index.php
        if (strpos($indexPhp, 'BASE_PATH') === false) {
            $indexLines = explode("\n", $indexPhp);
            $indexOfInitPhpInclude = $this->_findIndexOfInitPhpInclude($indexLines);
            array_splice($indexLines, $indexOfInitPhpInclude, 0, self::BASEPATH_DEFINITON);
            $indexPhp = implode("\n", $indexLines);
        }

        file_put_contents('public/index.php', $indexPhp);
    }

    protected function _updateLocaleFiles() {
        foreach (array('nl', 'en') as $locale) {
            $file = "application/data/i18n/$locale.php";
            $contents = file_get_contents($file);
            $line = sprintf(self::INCLUDE_I18N_FILE, $locale);
            $updatedLine = sprintf(self::NEW_INCLUDE_I18N_FILE, $locale);
            $contents = str_replace($line, $updatedLine, $contents);
            file_put_contents($file, $contents);
        }
    }

    protected function _findIndexOfInitPhpInclude(array $lines) {
        foreach ($lines as $i => $line) {
            if (strpos($line, self::NEW_INIT_PHP_REFERENCE) !== false) {
                return $i;
            }
        }
        return 0;
    }

    protected function _updateRoutesInclude() {
        $file = 'application/configs/routes.ini';
        $routes = file_get_contents($file);
        $routes = str_replace(self::OLD_ROUTES_INCLUDE, self::NEW_ROUTES_INCLUDE, $routes);
        file_put_contents($file, $routes);
    }

    protected function _updateCapFile() {
        $file = 'Capfile';
        $contents = file_get_contents($file);
        $contents = str_replace(self::OLD_GARP_DEPLOY_PATH, self::NEW_GARP_DEPLOY_PATH, $contents);
        file_put_contents($file, $contents);
    }
}
