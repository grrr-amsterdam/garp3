<?php
/**
 * Garp_Cli_Command_Image
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Image extends Garp_Cli_Command {
	/**
	 * Generate scaled versions of uploaded images
	 * @param Array $args
	 * @return Boolean success
	 */
	public function generateScaled($args) {
		$overwrite = array_key_exists('overwrite', $args);
		if (
			array_key_exists('template', $args) &&
			!empty($args['template'])
		) {
			return $this->_generateScaledImagesForTemplate($args['template'], $overwrite);
		} elseif(
			array_key_exists('filename', $args) &&
			!empty($args['filename'])
		) {
			return $this->_generateScaledImagesForFilename($args['filename'], $overwrite);
		}
		return $this->_generateAllScaledImages($overwrite);
	}

	/**
 	 * Optimize images
 	 * @return Boolean success
 	 */
	public function optimize($args) {
		$imageModel = new Model_Image();
		$pngs = $imageModel->fetchAll(
			$imageModel->select()
				->from($imageModel->getName(), array('filename'))
				->where('filename LIKE ?', '%.png')
		);
		$pngQuant = new Garp_Image_PngQuant();
		if (!$pngQuant->isAvailable()) {
			Garp_Cli::errorOut('I have no business here: pngquant is not available.');
		}
		$gif = new Garp_Image_File();
		
		foreach ($pngs as $png) {
			$data = $gif->fetch($png->filename);
			$data = $pngQuant->optimizeData($data);
			$gif->store($png->filename, $data, true);
			Garp_Cli::lineOut('Optimized '.$png->filename);
		}
		Garp_Cli::lineOut('Done.');
	}

	/**
 	 * Generate scaled versions of all images in all templates.
 	 * @param Boolean $overwrite
 	 * @return Boolean
 	 */
	protected function _generateAllScaledImages($overwrite = false) {
		$imageScaler 	= new Garp_Image_Scaler();
		$templates 		= $imageScaler->getTemplateNames();
		$success 		= 0;

		foreach ($templates as $t) {
			$success += (int)$this->_generateScaledImagesForTemplate($t, $overwrite);
		}

		return $success == count($templates);
	}

	/**
	 * Generate scaled versions of a specific source file, along all configured templates.
	 * @param String $filename Filename of the source file in need of scaling
	 * @param Boolean $overwrite
	 * @return Void
	 */
	protected function _generateScaledImagesForFilename($filename, $overwrite = false) {
		Garp_Cli::lineOut('Generating scaled images for file "'.$filename.'".');

		if (!$filename) {
			return;
		}
		
		$scaler 	= new Garp_Image_Scaler();
		$templates 	= $scaler->getTemplateNames();
		$imageModel = new G_Model_Image();
		$select 	= $imageModel->getAdapter()->quoteInto('filename = ?', $filename);
		$record 	= $imageModel->fetchRow($select);
		$file 		= new Garp_Image_File();
		$success 	= 0;

		if (!$record) {
			Garp_Cli::errorOut('I couldn\'t find any records in the database, containing "'.$filename.'" as filename');	
			return;
		}

		foreach ($templates as $template) {
			$success += (int)$this->_scaleDatabaseImage($record, $file, $scaler, $template, $overwrite);
		}

		return $success == count($templates);		
	}
	
	/**
	 * Generate scaled versions of all source files, along given template.
	 * @param String $template Template name, as it appears in configuration.
	 * @param Boolean $overwrite
	 * @return Void
	 */
	protected function _generateScaledImagesForTemplate($template, $overwrite = false) {
		Garp_Cli::lineOut('Generating scaled images for template "' . $template . '".');
	
		$imageModel = new G_Model_Image();
		$records 	= $imageModel->fetchAll();
		$file 		= new Garp_Image_File('upload');
		$scaler 	= new Garp_Image_Scaler();
		$success 	= 0;

		foreach ($records as $record) {
			$success += (int)$this->_scaleDatabaseImage($record, $file, $scaler, $template, $overwrite);
		}
		
		Garp_Cli::lineOut('Done scaling images for template "'.$template.'".');
		return $success == count($records);
	}

	/**
 	 * Show help
 	 */
	public function help() {
		Garp_Cli::lineOut(
			"Usage:\n".
			"php garp.php Image [command] [options]\n\n".
			"Commands:\n".
			"to generate scaled versions of all files for all existing templates:\n".
			"  generateScaled\n".
			"to generate scaled versions of all files for a specific template:\n".
			"  generateScaled --template=[template name]\n".
			"to generate scaled versions of a specific file for all templates:\n".
			"  generateScaled --filename=[filename]\n".
			"\n".
			"Use the --overwrite flag to overwrite existing scaled images.\n"
		);
	}
	
	protected function _scaleDatabaseImage(Garp_Db_Table_Row $record, Garp_Image_File $file, Garp_Image_Scaler $scaler, $template, $overwrite) {
		$id 		= $record->id;
		$filename 	= $record->filename;

		if (!$file->exists($filename)) {
			Garp_Cli::errorOut('Warning: '.$filename.' is in the database, but not on disk!');
			return;
		}
		
		if ($file->exists($scaler->getScaledPath($id, $template, true)) && !$overwrite) {
			Garp_Cli::lineOut($template . '/' . $id.' already exists, skipping');
			return;
		}

		try {
			$scaler->scaleAndStore($filename, $id, $template, $overwrite);
			Garp_Cli::lineOut('Scaled image #'.$id.': '.$filename);
			return true;
		} catch(Exception $e) {
			Garp_Cli::errorOut("Error scaling ".$filename." (#".$id."): ".$e->getMessage());
		}
	}
}
