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
	 * Central start method
	 * @param Array $args Various options. Must contain;
	 * ['t']	String	Table name.
	 * @return Void
	 */
	public function main(array $args = array()) {
		if (empty($args)) {
			Garp_Cli::lineOut($this->_getHelpText());
		} else {
			if (!array_key_exists(1, $args)) {
				Garp_Cli::lineOut("Please indicate what you want me to do.\n");
				$this->_exit($this->_getHelpText());
			} else {
				$command = $args[1];

				if (method_exists($this, '_'.$command)) {
					$this->{'_'.$command}($args);
				} else {
					$this->_exit("Sorry, I don't know the command '{$command}'.\n\n".$this->_getHelpText());
				}
			}
		}
	}
	

	private function _generateScaled($args) {
		if (
			array_key_exists('template', $args) &&
			!empty($args['template'])
		) {
			$this->_generateScaledImagesForTemplate($args['template']);
		} elseif(
			array_key_exists('filename', $args) &&
			!empty($args['filename'])
		) {
			$this->_generateScaledImagesForFilename($args['filename']);
		} else {
			$this->_generateAllScaledImages();
		}
	}


	private function _generateAllScaledImages() {
		$imageScaler = new Garp_Image_Scaler();
		$templates = $imageScaler->getTemplateNames();

		foreach ($templates as $t) {
			$this->_generateScaledImagesForTemplate($t);
		}
	}


	/**
	 * Generate scaled versions of a specific source file, along all configured templates.
	 * @param String $filename Filename of the source file in need of scaling
	 * @return Void
	 */
	private function _generateScaledImagesForFilename($filename) {
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
				foreach ($templates as $t) {
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
						}
					}
				}
			} else Garp_Cli::errorOut('I couldn\'t find any records in the database, containing "'.$filename.'" as filename');
		} else $this->_exit('Please provide a filename to scale.');
	}
	
	
	/**
	 * Generate scaled versions of all source files, along given template.
	 * @param String $template Template name, as it appears in configuration.
	 * @return Void
	 */
	private function _generateScaledImagesForTemplate($template) {
		Garp_Cli::lineOut('Generating scaled images for template "'.$template.'".');
	
		$imageModel = new G_Model_Image();
		$records = $imageModel->fetchAll();
		$file = new Garp_Image_File('upload');
		$scaler = new Garp_Image_Scaler();

		foreach ($records as $record) {
			$id = $record->id;
			$filename = $record->filename;

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
					}
				}
			} else {
				Garp_Cli::errorOut('Warning: '.$filename.' is in the database, but not on disk!');
			}
		}
	}
	
	
	private function _exit($msg = null) {
		if (!is_null($msg))
			Garp_Cli::lineOut($msg);
		exit;
	}


	private function _getHelpText() {
		return
			"Usage:\n".
			"php garp.php Image [command] [options]\n\n".
			"Commands:\n".
			"to generate scaled versions of all files for all existing templates:\n".
			"  generateScaled\n".
			"to generate scaled versions of all files for a specific template:\n".
			"  generateScaled --template=[template name]\n".
			"to generate scaled versions of a specific file for all templates:\n".
			"  generateScaled --filename=[filename]\n"
		;
	}
}
