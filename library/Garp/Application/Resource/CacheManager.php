<?php
class Garp_Application_Resource_CacheManager extends Zend_Application_Resource_Cachemanager {
	/**
 	 * Unfortunately copied from Zend_Application_Resource_Cachemanager, and
 	 * replaced Zend_Cache_Manager with Garp_Cache_Manager.
 	 */
	public function getCacheManager() {
        if (null === $this->_manager) {
            $this->_manager = new Zend_Cache_Manager;

            $options = $this->getOptions();
            foreach ($options as $key => $value) {
                if ($this->_manager->hasCacheTemplate($key)) {
                    $this->_manager->setTemplateOptions($key, $value);
                } else {
                    $this->_manager->setCacheTemplate($key, $value);
                }
            }
        }

        return $this->_manager;
	}
}
