<?php
/**
 * Garp_Content_Export_Excel
 * Export content in Excel XLS format
 *
 * @package Garp_Content_Export
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Content_Export_Excel extends Garp_Content_Export_Abstract {
    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = 'xls';


    /**
     * Format a recordset
     *
     * @param Garp_Model $model
     * @param array $rowset
     * @return string
     */
    public function format(Garp_Model $model, array $rowset) {
        $phpexcel = new PHPExcel();
        PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());

        // set metadata
        $props = $phpexcel->getProperties();
        if (Garp_Auth::getInstance()->isLoggedIn()) {
            $userData = Garp_Auth::getInstance()->getUserData();
            $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
            if ($bootstrap) {
                $view = $bootstrap->getResource('view');
                $userName = $view->fullName($userData);
                $props->setCreator($userName)
                    ->setLastModifiedBy($userName);
            }
        }
        $props->setTitle('Garp content export – ' . $model->getName());

        if (count($rowset)) {
            $this->_addContent($phpexcel, $model, $rowset);
        }

        /**
         * Hm, PHPExcel seems to only be able to write to a file (instead of returning
         * an XLS binary string). Therefore, we save a temporary file, read its contents
         * and return those, after which we unlink the temp file.
         */
        $tmpFileName = APPLICATION_PATH . '/data/logs/tmp.xls';
        $writer = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
        $writer->save($tmpFileName);
        $contents = file_get_contents($tmpFileName);
        unlink($tmpFileName);
        return $contents;
    }

    protected function _addContent(PHPExcel $phpexcel, Garp_Model $model, array $rowset) {
        // add header (row containing column names) at row 1
        $i = 1;
        $this->_addRow($phpexcel, array_keys($rowset[0]), $i);

        // add rows, from row 2
        foreach ($rowset as $row) {
            $this->_addRow($phpexcel, $row, ++$i);
        }
        // set alternate style for header cells
        $styleArray = array(
            'font' => array(
                'bold' => true,
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array(
                    'rgb' => 'CCCCCC',
                )
            ),
        );

        // add alternate styles to header cells
        for ($i = 0, $colCount = count(array_keys($rowset[0])), $char = 'A';
            $i < $colCount;
            $i++, $char++
        ) {
            $phpexcel->getActiveSheet()->getStyle($char . '1')->applyFromArray($styleArray);
        }

    }

    /**
     * Add row to spreadsheet
     *
     * @param PHPExcel $phpexcel
     * @param array $row
     * @param string $rowIndex Character describing the row index
     * @return void
     */
    protected function _addRow(PHPExcel $phpexcel, array $row, $rowIndex) {
        $col = 0;
        foreach ($row as $key => $value) {
            $colIndex = $col++;
            if (is_array($value)) {
                $rowset = $value;
                $value = array();
                foreach ($rowset as $row) {
                    if (is_array($row)) {
                        $values = array_values($row);
                        $values = implode(' : ', $values);
                    } else {
                        $values = $row;
                    }
                    $value[] = $values;
                }
                $value = implode("\n", $value);
            }
            $phpexcel->getActiveSheet()->setCellValueByColumnAndRow($colIndex, $rowIndex, $value);
        }
    }
}
