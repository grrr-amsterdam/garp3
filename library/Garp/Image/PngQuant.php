<?php

class Garp_Image_PngQuant {
	const LABEL_OPTIMIZED = '-optimized';
	

	/**
	 * Optimizes PNG data. If this is not a PNG, the original data is returned.
	 * @param	String	$data	Binary image data
	 * @return	String	$data	Optimized binary image data
	 */
	public function optimizeData($data) {
		if (!$this->isPngData($data)) {
			return $data;
		}
		
		//	store data in a temporary file to feed pngquant
		$sourcePath = $this->_getNewTempFilePath();
		$this->_store($sourcePath, $data);
		
		//	run pngquant on temporary file
		$this->_createOptimizedFile($sourcePath);
		
		//	retrieve data from optimized file
		$optimizedFilePath 	= $this->_getOptimizedFilePath($sourcePath);
		$optimizedData 		= $this->_fetchFileData($optimizedFilePath);
		
		//	verify data
		$this->_verifyData($optimizedData);

		unlink($sourcePath);
		unlink($optimizedFilePath);
		
		return $optimizedData;
	}
	
	public function isPngData($data) {
		$finfo = new finfo(FILEINFO_MIME);
		$mime = $finfo->buffer($data);
		$containsPngMime = strpos($mime, 'image/png') !== false;
		return $containsPngMime;
	}
	
	/**
	 * @return 	Bool 	Whether PNGQuant is available on this system.
	 */
	public function isAvailable() {
		$pngQuantIsAvailableCommand = new Garp_Shell_Command_PngQuantIsAvailable();
		$pngQuantIsAvailable = $pngQuantIsAvailableCommand->executeLocally();

		return $pngQuantIsAvailable;
	}
	
	/**
	 * @return	String	File path to the temporary file
	 */
	protected function _getNewTempFilePath() {
		$tempDir			= sys_get_temp_dir();
		$tempSourcePath 	= tempnam($tempDir, 'pngquant');
		return $tempSourcePath;
	}
	
	protected function _store($destinationPath, $data) {
		$bytes = file_put_contents($destinationPath, $data);
		
		if ($bytes === false) {
			throw new Exception('Could not store PNG at ' . $destinationPath);
		}
	}
	
	protected function _createOptimizedFile($sourcePath) {
		$pngQuantCommand = new Garp_Shell_Command_PngQuant($sourcePath, self::LABEL_OPTIMIZED);
		$pngQuantCommand->executeLocally();
	}
	
	protected function _getOptimizedFilePath($sourcePath) {
		$destinationPath = $sourcePath . self::LABEL_OPTIMIZED;
		return $destinationPath;
	}
	
	protected function _fetchFileData($path) {
		$data = file_get_contents($path);
		
		if ($data === false) {
			throw new Exception('Could open file ' . $path);
		}

		return $data;
	}
	
	protected function _verifyData($data) {
		if (!$data) {
			throw new Exception('PNG data was empty.');
		}
	}	
	
}