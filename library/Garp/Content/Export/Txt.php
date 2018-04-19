<?php
/**
 * Garp_Content_Export_Txt
 * Export content in simple txt format
 *
 * @package Garp_Content_Export
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Content_Export_Txt extends Garp_Content_Export_Abstract {
    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = 'txt';


    /**
     * Format a recordset
     *
     * @param  Garp_Model $model
     * @param  array $rowset
     * @return string
     */
    public function format(Garp_Model $model, array $rowset) {
        $out = '';
        foreach ($rowset as $i => $row) {
            $out .= $this->_formatRow($row);
            if ($i < (count($rowset)-1)) {
                $out .= "\n\n";
            }
        }
        return $out;
    }


    /**
     * Format a single row
     *
     * @param  array $row
     * @return string
     */
    protected function _formatRow(array $row) {
        $out = '';
        foreach ($row as $key => $value) {
            if (is_array($value)) {
                // This is the case with hasMany and hasAndBelongsToMany related
                // rowsets.
                $value = $this->_formatRelatedRowset($value);
            }
            $out .= "$key: $value\n";
        }
        return $out;
    }


    /**
     * Format a related rowset (hasMany or hasAndBelongsToMany)
     *
     * @param array $rowset
     * @return string
     */
    protected function _formatRelatedRowset($rowset) {
        $out = array();
        foreach ($rowset as $row) {
            if (is_array($row)) {
                $values = array_values($row);
                $values = implode(' : ', $values);
                $out[] = $values;
                continue;
            }
            $out[] = $row;
        }
        $out = implode(' | ', $out);
        return $out;
    }
}
