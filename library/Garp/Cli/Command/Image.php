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
		if (
			array_key_exists('template', $args) &&
			!empty($args['template'])
		) {
			return $this->_generateScaledImagesForTemplate($args['template']);
		} elseif(
			array_key_exists('filename', $args) &&
			!empty($args['filename'])
		) {
			return $this->_generateScaledImagesForFilename($args['filename']);
		}
		return $this->_generateAllScaledImages();
	}


	/**
 	 * Generate scaled versions of all images in all templates.
 	 * @return Boolean
 	 */
	protected function _generateAllScaledImages() {
		$imageScaler = new Garp_Image_Scaler();
		$templates = $imageScaler->getTemplateNames();
		$success = 0;

		foreach ($templates as $t) {
			$success += (int)$this->_generateScaledImagesForTemplate($t);
		}
		return $success == count($templates);
	}


	/**
	 * Generate scaled versions of a specific source file, along all configured templates.
	 * @param String $filename Filename of the source file in need of scaling
	 * @return Void
	 */
	protected function _generateScaledImagesForFilename($filename) {
		Garp_Cli::lineOut('Generating scaled images for file "'.$filename.'".');

		if ($filename) {
			$scaler = new Garp_Image_Scaler();
			$templates = $scaler->getTemplateNames();

			$imageModel = new G_Model_Image();
			$select = $imageModel->getAdapter()->quoteInto('filename = ?', $filename);
			$record = $imageModel->fetchRow($select);
			
			$file = new Garp_Image_File();

			if ($record) {
				$id = $record->id;
				$success = 0;
				foreach ($templates as $t) {
					$success++;
					if ($file->exists(
						$scaler->getScaledPath($id, $t, true)
					)) {
						Garp_Cli::lineOut($t.'/'.$id.' already exists, skipping');
					} else {
						try {
							$scaler->scaleAndStore($filename, $id, $t);
							Garp_Cli::lineOut('Scaled image #'.$id.': '.$filename.' for template "'.$t.'"');
						} catch(Exception $e) {
							Garp_Cli::errorOut("Error scaling ".$filename." (#".$id."): ".$e->getMessage());
							$success--;
						}
					}
				}
				return $success == count($templates);
			}
			Garp_Cli::errorOut('I couldn\'t find any records in the database, containing "'.$filename.'" as filename');
		}
		return false;
	}
	
	
	/**
	 * Generate scaled versions of all source files, along given template.
	 * @param String $template Template name, as it appears in configuration.
	 * @return Void
	 */
	protected function _generateScaledImagesForTemplate($template) {
		Garp_Cli::lineOut('Generating scaled images for template "'.$template.'".');
	
		$imageModel = new G_Model_Image();
		$records = $imageModel->fetchAll();
		$file = new Garp_Image_File('upload');
		$scaler = new Garp_Image_Scaler();
		$success = 0;

		foreach ($records as $record) {
			$id = $record->id;
			$filename = $record->filename;
			$success++;

			if ($file->exists($filename)) {
				if ($file->exists(
					$scaler->getScaledPath($id, $template, true)
				)) {
					Garp_Cli::lineOut($template.'/'.$id.' already exists, skipping');
				} else {
					try {
						$scaler->scaleAndStore($filename, $id, $template);
						Garp_Cli::lineOut('Scaled image #'.$id.': '.$filename);
					} catch(Exception $e) {
						Garp_Cli::errorOut("Error scaling ".$filename." (#".$id."): ".$e->getMessage());
						$success--;
					}
				}
			} else {
				Garp_Cli::errorOut('Warning: '.$filename.' is in the database, but not on disk!');
				$success--;
			}
		}
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
			"  generateScaled --filename=[filename]\n"
		);
	}
}
