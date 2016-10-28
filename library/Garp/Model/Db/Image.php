<?php
/**
 * Garp_Model_Db_Image
 * Generic image model.
 *
 * @package Garp_Model_Db
 * @author  David Spreekmeester <david@grrr.nl>
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Db_Image extends Model_Base_Image {
    protected $_name = 'image';

    public function init() {
        $scalableBehavior = new Garp_Model_Behavior_ImageScalable(
            array(
                'synchronouslyScaledTemplates' => array(
                    // @todo Make configurable? Or sensible defaults?
                    // Local model can always override.
                    'cms_list', 'cms_preview'
                )
            )
        );
        $this->registerObserver(new Garp_Model_Behavior_Timestampable())
            ->registerObserver($scalableBehavior)
            ->registerObserver(new Garp_Model_Validator_NotEmpty(array('filename')));
        parent::init();
    }

    /**
     * Include preview URLs in the resultset
     *
     * @param array $args
     * @return void
     */
    public function afterFetch(&$args) {
        $results = &$args[1];
        $scaler = new Garp_Image_Scaler();
        $templateUrl = (string)$scaler->getScaledUrl('%d', '%s');
        $templates = array_keys(Zend_Registry::get('config')->image->template->toArray());
        $iterator = new Garp_Db_Table_Rowset_Iterator(
            $results,
            function ($result) use ($templates, $templateUrl) {
                if (!isset($result->id)) {
                    return;
                }
                $result->setVirtual(
                    'urls',
                    array_reduce(
                        $templates,
                        function ($acc, $cur) use ($templateUrl, $result) {
                            $acc[$cur] = sprintf($templateUrl, $cur, $result->id);
                            return $acc;
                        },
                        array()
                    )
                );
            }
        );
        $iterator->walk();
    }

    public function fetchFilenameById($id) {
        $row = $this->fetchRow($this->select()->where('id = ?', $id));
        if (!isset($row->filename)) {
            throw new Exception("Could not retrieve image record {$id}.");
        }
        return $row->filename;
    }

    public function insertFromUrl($imageUrl, $filename = null) {
        // @todo file_get_contents too optimistic?
        $bytes = file_get_contents($imageUrl);
        if (is_null($filename)) {
            $filename = $this->_createFilenameFromUrl($imageUrl, $bytes);
        }
        $response = Zend_Controller_Action_HelperBroker::getStaticHelper('upload')
            ->uploadRaw(Garp_File::TYPE_IMAGES, $filename, $bytes);
        if (!array_key_exists($filename, $response) || !$response[$filename]) {
            return null;
        }

        return $this->insert(array('filename' => $response[$filename]));
    }

    protected function _getImageMime($bytes) {
        $finfo = new finfo(FILEINFO_MIME);
        $mime = $finfo->buffer($bytes);
        $mime = explode(';', $mime);
        $mime = $mime[0];
        return $mime;
    }

    protected function _createFilenameFromUrl($imageUrl, $bytes) {
        $filename = basename($imageUrl);
        // Strip possible query parameters
        if (strpos($filename, '?') !== false
            && strpos($filename, '.') !== false
            && strpos($filename, '?') > strrpos($filename, '.')
        ) {
            // Extract everything up until the "?"
            $filename = substr($filename, 0, strrpos($filename, '?'));
        }
        // Append extension based on mime-type
        if (strpos($filename, '.') === false) {
            $mime = $this->_getImageMime($bytes);
            if ($mime === 'application/x-gzip') {
                $bytes = gzdecode($bytes);
                $mime = $this->_getImageMime($bytes);
            }

            // Figure out mimetype, or default to jpg
            $filename .= '.' . (new Garp_File_Extension($mime) ?: 'jpg');
        }
        return $filename;
    }
}

