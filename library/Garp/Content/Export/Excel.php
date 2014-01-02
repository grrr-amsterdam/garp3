<?php
/**
 * Garp_Content_Export_Excel
 * Export content in Excel XLS format
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Export_Excel extends Garp_Content_Export_Abstract {
	/**
	 * File extension
	 * @var String
	 */
	protected $_extension = 'xls';
	
	
	/**
	 * Format a recordset
	 * @param Garp_Model $model
	 * @param Array $rowset
	 * @return String
	 */
	protected function _format(Garp_Model $model, array $rowset) {
		require APPLICATION_PATH.'/../garp/library/Garp/3rdParty/PHPExcel/Classes/PHPExcel.php';
		$phpexcel = new PHPExcel();
		PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );
		
		// set metadata
		$props = $phpexcel->getProperties();
		if (Garp_Auth::getInstance()->isLoggedIn()) {
			$userData = Garp_Auth::getInstance()->getUserData();
			$props->setCreator($userData['name'])
				  ->setLastModifiedBy($userData['name']);
		}
		$props->setTitle('Garp content export – '.$model->getName());

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
		
		// set autosize = true for every column, also add alternate styles to header cells
		for ($i = 0, $colCount = count(array_keys($rowset[0])), $char = 'A'; $i < $colCount; $i++, $char++) {
			$phpexcel->getActiveSheet()->getStyle($char.'1')->applyFromArray($styleArray);
			$phpexcel->getActiveSheet()->getColumnDimension($char)->setAutoSize(true);
		}
		
		/**
		 * Hm, PHPExcel seems to only be able to write to a file (instead of returning
		 * an XLS binary string). Therefore, we save a temporary file, read its contents
		 * and return those, after which we unlink the temp file.
		 */
		$tmpFileName = APPLICATION_PATH.'/data/logs/tmp.xls';
		$writer = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
		$writer->save($tmpFileName);
		$contents = file_get_contents($tmpFileName);
		unlink($tmpFileName);
		return $contents;
	}
	
	
	/**
	 * Add row to spreadsheet
	 * @param PHPExcel $phpexcel
	 * @param Array $row
	 * @param String $rowIndex Character describing the row index
	 * @return Void
	 */
	protected function _addRow(PHPExcel $phpexcel, array $row, $rowIndex) {
		$col = 0;
		foreach ($row as $key => $value) {
			$colIndex = $col++;
			$phpexcel->getActiveSheet()->setCellValueByColumnAndRow($colIndex, $rowIndex, $value);
		}
	}	
}