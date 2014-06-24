<?php
class Garp_File_Storage_S3_Test extends PHPUnit_Framework_TestCase {
	public function testGetList() {
		///////////////
		// This test is disabled by default because of performance.
		return;
		///////////////
		
		
		if (!($cdnConfig = $this->_findFirstS3Config())) {
			return;
		}

		$s3 		= new Garp_File_Storage_S3($cdnConfig, $cdnConfig->path->upload->image);
		$list 		= $s3->getList();
		
		$this->assertTrue((bool)count($list));
	}
	
	protected function _findFirstS3Config() {
		$envs = array('production', 'staging', 'integration', 'development');
		
		foreach ($envs as $env) {
			$ini = new Garp_Config_Ini('application/configs/application.ini', $env);
			if ($ini->cdn->type === 's3') {
				return $ini->cdn;
			}
		}
	}
}