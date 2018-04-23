<?php
/**
 * Garp_Model_Db_Snippet
 * Snippet model. Snippets are small dynamic chunks of content.
 *
 * @package Garp_Model_Db
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Db_Snippet extends Model_Base_Snippet {
    /**
     * Fetch a snippet by its identifier
     *
     * @param string $identifier
     * @return Garp_Db_Table_Row
     */
    public function fetchByIdentifier($identifier) {
        if (!$identifier) {
            throw new InvalidArgumentException('Snippet identifier is required');
        }
        $select = $this->select()->where('identifier = ?', $identifier);
        if ($result = $this->fetchRow($select)) {
            return $result;
        }
        if (!Zend_Registry::get('config')->snippets->ignoreMissing) {
            throw new Exception('Snippet not found: ' . $identifier);
        }
        // Return fallback row, where text is set to $identifier, in order to provide some fallback.
        return $this->createRow([
            'has_text' => 1,
            'text' => $identifier,
        ]);
    }

    /**
     * BeforeFetch: filters out snippets where is_editable = 0 in the CMs.
     *
     * @param array $args
     * @return void
     */
    public function beforeFetch(&$args) {
        $model = &$args[0];
        $select = &$args[1];
        if (!$model->isCmsContext()) {
            return;
        }
        // Sanity check: this project might be spawned without the is_editable column,
        // it was added to Snippet at May 1 2013.
        if ($this->getFieldConfiguration('is_editable')) {
            $select->where('is_editable = ?', 1);
        }
    }
}
