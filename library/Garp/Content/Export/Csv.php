<?php
use Garp\Functional as f;
use League\Csv\Reader;
use League\Csv\Writer;

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
    public function format(Garp_Model $model, array $rowset): string {
        $rowset = f\map(
            f\publish('_flatten', $this),
            $rowset
        );

        $writer = Writer::createFromString('');
        $writer->setDelimiter(';');
        $writer->insertOne(array_keys($rowset[0]));
        $writer->insertAll($rowset);
        $writer->setOutputBOM(Reader::BOM_UTF8);
        return strval($writer);
    }

    protected function _flatten(array $row): array {
        return f\map(
            function ($value) {
                return is_array($value)
                    ? implode(', ', $this->_flatten($value))
                    : $value;
            },
            $row
        );
    }

}
