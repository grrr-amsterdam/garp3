<?php
/**
 * Garp_Content_Export_Csv
 * Export content in simple comma-separated-values format
 *
 * @package Garp_Content_Export
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Content_Export_Csv extends Garp_Content_Export_Abstract {
    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = 'csv';


    /**
     * Format a recordset
     *
     * @param  Garp_Model $model
     * @param  array $rowset
     * @return string
     */
    public function format(Garp_Model $model, array $rowset) {
        $out = '';
        // add fields
        $fields = array_keys($rowset[0]);
        $outFields = array();
        foreach ($fields as $field) {
            $outFields[] = $this->_escape($field);
        }
        $out .= implode(',', $outFields) . "\n";

        // values
        foreach ($rowset as $i => $row) {
            $outValues = array();
            foreach ($row as $key => $value) {
                // In the case of related hasMany or hasAndBelongsToMany
                // rowsets...
                if (is_array($value)) {
                    $rowset = $value;
                    $values = array();
                    foreach ($rowset as $row) {
                        $rowVals = array();
                        if (is_array($row)) {
                            foreach ($row as $key => $value) {
                                $rowVals[] = $value;
                            }
                            $rowVals = implode(', ', $rowVals);
                        } else {
                            $rowVals = $row;
                        }
                        $values[] = $rowVals;
                    }
                    $value = implode($values, "\n");
                }
                $outValues[] = $this->_escape($value);
            }
            $out .= implode(',', $outValues) . "\n";
        }
        return $out;
    }


    /**
     * Make sure a field is valid as per the CSV spec
     *
     * @param  string $str The string
     * @return string      The escaped string
     */
    protected function _escape($str) {
        $enclosed = false;
        // first, check for embedded double quotes
        if (strpos($str, '"') !== false) {
            $str = str_replace('"', '""', $str);
            $str = '"' . $str . '"';
            $enclosed = true;
        }
        // second, check all other conditions that require enclosing in double quotes
        if ((strpos($str, ',') !== false      // embedded commas
            || strpos($str, "\n") !== false   // embedded line-breaks
            || strpos($str, "\r") !== false   //  "           "
            || preg_match('~^\s|\s$~', $str)) && !$enclosed
        ) {
            $str = '"' . $str . '"';
        }
        return $str;
    }
}
