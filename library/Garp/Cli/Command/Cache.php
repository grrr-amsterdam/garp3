<?php
/**
 * Garp_Cli_Command_Cache
 *
 * @package Garp_Cli_Command
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Cache extends Garp_Cli_Command {
    protected $_allowedArguments = array(
        'clear' => '*',
        'info'  => array()
    );

    /**
     * Clear all the cache
     *
     * @param array $args Tags.
     * @return bool
     */
    public function clear(array $args = array()) {
        $app = Zend_Registry::get('application');
        $bootstrap = $app->getBootstrap();
        $cacheDir = false;
        if ($bootstrap && $bootstrap->getResource('cachemanager')) {
            $cacheManager = $bootstrap->getResource('cachemanager');
            $cache = $cacheManager->getCache(Zend_Cache_Manager::PAGECACHE);
            $cacheDir = $cache->getBackend()->getOption('public_dir');
        }

        Garp_Cache_Manager::purge($args, true, $cacheDir);
        Garp_Cli::lineOut('All cache purged.');
        return true;
    }

    public function info() {
        $backend = Garp_Cache_Manager::getCacheBackend();
        Garp_Cli::lineOut('# Server cache backend');
        $out = $backend ? 'Backend type: ' . get_class($backend) : 'No cache backend found';
        Garp_Cli::lineOut($out);
    }

    /**
     * Help
     *
     * @return bool
     */
    public function help() {
        Garp_Cli::lineOut('Usage:');
        Garp_Cli::lineOut('Clear all cache:');
        Garp_Cli::lineOut('  g Cache clear', Garp_Cli::BLUE);
        Garp_Cli::lineOut('');
        Garp_Cli::lineOut('Clear cache of model Foo and model Bar:');
        Garp_Cli::lineOut('  g Cache clear Model_Foo Model_Bar', Garp_Cli::BLUE);
        Garp_Cli::lineOut('');
        return true;
    }
}
