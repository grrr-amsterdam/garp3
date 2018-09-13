<?php
/**
 * Garp_Cli_Command_Image
 *
 * @package Garp_Cli_Command
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Cli_Command_Image extends Garp_Cli_Command {
    /**
     * Generate scaled versions of uploaded images
     *
     * @param array $args
     * @return bool
     */
    public function generateScaled($args) {
        $overwrite = array_key_exists('overwrite', $args) || array_key_exists('force', $args);
        if (array_key_exists('template', $args)
            && !empty($args['template'])
        ) {
            return $this->_generateScaledImagesForTemplate($args['template'], $overwrite);
        } elseif (array_key_exists('filename', $args)
            && !empty($args['filename'])
        ) {
            return $this->_generateScaledImagesForFilename($args['filename'], $overwrite);
        } elseif (array_key_exists('id', $args)
            && !empty($args['id'])
        ) {
            return $this->_generateScaledImagesForId($args['id'], $overwrite);
        }
        return $this->_generateAllScaledImages($overwrite);
    }

    /**
     * Optimize images
     *
     * @param array $args
     * @return bool
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
            Garp_Cli::lineOut('Optimized ' . $png->filename);
        }
        Garp_Cli::lineOut('Done.');

        return true;
    }

    /**
     * @param array $args
     * @return bool
     */
    public function remove($args) {
        if (array_key_exists('filename', $args)
            && !empty($args['filename'])
        ) {
            $result = $this->_removeScaledImagesByFilename($args['filename']);

            if ($result) {
                $result = $this->_removeFile($args['filename']);
            }

            return $result;
        }

        return false;
    }

    /**
     * Generate scaled versions of all images in all templates.
     *
     * @param bool $overwrite
     * @return bool
     */
    protected function _generateAllScaledImages($overwrite = false) {
        $imageScaler    = new Garp_Image_Scaler();
        $templates      = $imageScaler->getTemplateNames();
        $success        = 0;

        foreach ($templates as $t) {
            $success += (int)$this->_generateScaledImagesForTemplate($t, $overwrite);
        }

        return $success == count($templates);
    }

    /**
     * Generate scaled versions of a specific source file, along all configured templates.
     *
     * @param string $filename Filename of the source file in need of scaling
     * @param bool $overwrite
     * @return bool
     */
    protected function _generateScaledImagesForFilename($filename, $overwrite = false) {
        Garp_Cli::lineOut('Generating scaled images for file "' . $filename . '".');

        if (!$filename) {
            return;
        }

        $scaler     = new Garp_Image_Scaler();
        $templates  = $scaler->getTemplateNames();
        $imageModel = new Model_Image();
        $select     = $imageModel->getAdapter()->quoteInto('filename = ?', $filename);
        $record     = $imageModel->fetchRow($select);
        $file       = new Garp_Image_File();
        $success    = 0;

        if (!$record) {
            Garp_Cli::errorOut(
                'I couldn\'t find any records in the database, containing "' .
                $filename . '" as filename'
            );
            return;
        }

        foreach ($templates as $template) {
            $success += (int)$this->_scaleDatabaseImage(
                $record, $file, $scaler, $template, $overwrite
            );
        }

        return $success == count($templates);
    }

    /**
     * Generate scaled versions of a specific source file, along all configured templates.
     *
     * @param string $id Id of the source file in need of scaling
     * @param bool $overwrite
     * @return bool
     */
    protected function _generateScaledImagesForId($id, $overwrite = false) {
        if (!$id) {
            return;
        }

        $imageModel = new Model_Image();
        $record     = $imageModel->fetchById($id);
        return $this->_generateScaledImagesForFilename($record->filename);
    }

    /**
     * Generate scaled versions of all source files, along given template.
     *
     * @param string $template Template name, as it appears in configuration.
     * @param bool $overwrite
     * @return bool
     */
    protected function _generateScaledImagesForTemplate($template, $overwrite = false) {
        Garp_Cli::lineOut('Generating scaled images for template "' . $template . '".');

        $imageModel = new Model_Image();
        $records    = $imageModel->fetchAll();
        $file       = new Garp_Image_File(Garp_File::FILE_VARIANT_UPLOAD);
        $scaler     = new Garp_Image_Scaler();
        $success    = 0;

        foreach ($records as $record) {
            $success += (int)$this->_scaleDatabaseImage(
                $record, $file, $scaler, $template, $overwrite
            );
        }

        Garp_Cli::lineOut('Done scaling images for template "' . $template . '".');
        return $success == count($records);
    }

    /**
     * Show help
     *
     * @return bool
     */
    public function help() {
        Garp_Cli::lineOut(
            "Usage:\n" .
            "php garp.php Image [command] [options]\n\n" .
            "Commands:\n" .
            "to generate scaled versions of all files for all existing templates:\n" .
            "  generateScaled\n" .
            "to generate scaled versions of all files for a specific template:\n" .
            "  generateScaled --template=[template name]\n" .
            "to generate scaled versions of a specific file for all templates:\n" .
            "  generateScaled --filename=[filename]\n" .
            "\n" .
            "Use the --overwrite flag to overwrite existing scaled images.\n" .
            "\n" .
            "to optimize images with pngquant:\n" .
            "  optimize\n" .
            "\n" .
            "to remove an image from the storage:\n" .
            "  remove --filename=[filename]"
        );
        return true;
    }

    protected function _scaleDatabaseImage(
        Garp_Db_Table_Row $record, Garp_Image_File $file, Garp_Image_Scaler $scaler,
        $template, $overwrite
    ) {
        $id       = $record->id;
        $filename = $record->filename;

        if (!$file->exists($filename)) {
            Garp_Cli::errorOut('Warning: ' . $filename . ' is in the database, but not on disk!');
            return false;
        }

        if ($file->exists($scaler->getScaledPath($id, $template)) && !$overwrite) {
            Garp_Cli::lineOut($template . '/' . $id . ' already exists, skipping');
            return false;
        }

        try {
            $scaler->scaleAndStore($filename, $id, $template, $overwrite);
            Garp_Cli::lineOut('Scaled image #' . $id . ': ' . $filename);
            return true;
        } catch(Exception $e) {
            Garp_Cli::errorOut(
                "Error scaling " . $filename . " (#" . $id . "): " . $e->getMessage()
            );
            return false;
        }
    }

    /**
     * Generate scaled versions of a specific source file, along all configured templates.
     *
     * @param string $filename Filename of the source file in need of scaling
     * @return bool
     */
    protected function _removeScaledImagesByFilename($filename) {
        Garp_Cli::lineOut('Remove scaled images for file "' . $filename . '".');

        $scaler     = new Garp_Image_Scaler();
        $templates  = $scaler->getTemplateNames();
        $imageModel = new Garp_Model_Db_Image();
        $select     = $imageModel->getAdapter()->quoteInto('filename = ?', $filename);
        $record     = $imageModel->fetchRow($select);
        $success    = 0;

        if (!$record) {
            Garp_Cli::errorOut(
                'I couldn\'t find any records in the database, containing "' .
                $filename . '" as filename'
            );
            return false;
        }

        foreach ($templates as $template) {
            $success += $this->_removeFile($scaler->getScaledPath($record->id, $template));
        }

        return $success == count($templates);
    }

    protected function _removeFile($path) {
        $file = new Garp_Image_File();
        $success = false;
        Garp_Cli::lineOut('Removing "' . $path . '"');
        if ($file->exists($path)) {
            $success = $file->remove($path);
            if ($success) {
                Garp_Cli::lineOut('success');
            } else {
                Garp_Cli::errorOut('failed');
            }
        }
        return $success;
    }
}
