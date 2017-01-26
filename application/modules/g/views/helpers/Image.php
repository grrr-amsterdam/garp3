<?php
/**
 * G_View_Helper_Image
 * Assists in rendering dynamic images.
 *
 * @package G_View_Helper
 * @author  David Spreekmeester <david@grrr.nl>
 */
class G_View_Helper_Image extends Zend_View_Helper_HtmlElement {
    /**
     * Store configuration, don't fetch it fresh every time.
     *
     * @var Zend_Config_Ini
     */
    protected $_config;

    /**
     * @var Garp_Image_Scaler
     */
    protected $_scaler;

    const ERROR_SCALING_TEMPLATE_MISSING = 'You will need to provide a scaling template.';
    const ERROR_ARGUMENT_IS_NOT_FILENAME = 'The provided argument is not a filename.';
    const ERROR_IMAGE_NOT_FOUND = 'Image not found';

    /**
     * Instance conveyer method to enable calling of the other methods in this class from the view.
     *
     * @return G_View_Helper_Image $this
     */
    public function image() {
        return $this;
    }

    /**
     * Render an HTML img tag.
     *
     * @param   mixed   $image       Database id for uploads, or a filename
     *                               in case of a static image asset.
     * @param   string  $template    The id of the scaling template as defined in application.ini.
     *                               For instance: 'cms_preview'
     * @param   array   $htmlAttribs HTML attributes on the image tag
     * @param   string  $partial     Custom partial for rendering an upload
     * @return  string
     */
    public function render($image, $template = null, $htmlAttribs = array(), $partial = null) {
        if ($this->_isFilename($image)) {
            // When calling for a static image, you can use the second param as $htmlAttribs.
            $htmlAttribs = $template ?: array();
            return $this->_renderStatic($image, $htmlAttribs);
        }
        if (!$template) {
            throw new Exception(self::ERROR_SCALING_TEMPLATE_MISSING);
        }
        return $this->_renderUpload($image, $template, $htmlAttribs, $partial);
    }

    /**
     * Return URL of scaled image
     *
     * @param   mixed   $image      Database id for uploads, or a filename
     *                              in case of a static image asset.
     * @param   string  $template   The id of the scaling template as defined in application.ini.
     *                              For instance: 'cms_preview'
     * @return  string
     */
    public function getUrl($image, $template = null) {
        if ($this->_isFilename($image)) {
            return $this->getStaticUrl($image);
        }
        if (!$template) {
            throw new Exception(self::ERROR_SCALING_TEMPLATE_MISSING);
        }
        return $this->getScaledUrl($image, $template);
    }

    /**
     * Return the URL of a static image
     *
     * @param string $image A filename
     * @return string
     */
    public function getStaticUrl($image) {
        $file = new Garp_Image_File(Garp_File::FILE_VARIANT_STATIC);
        return $file->getUrl($image);
    }

    /**
     * Return the URL of a scaled image
     *
     * @param string $image
     * @param string $template
     * @return string
     */
    public function getScaledUrl($image, $template) {
        return $this->_getImageScaler()->getScaledUrl($image, $template);
    }

    /**
     * Returns the url to the source file of an upload.
     *
     * @param string $filename The filename of the upload, without the path.
     * @return string
     */
    public function getSourceUrl($filename) {
        if (!$this->_isFilename($filename)) {
            throw new Exception(self::ERROR_ARGUMENT_IS_NOT_FILENAME);
        }
        $file = new Garp_Image_File();
        return $file->getUrl($filename);
    }

    /**
     * Returns the url to the source file of an upload by id
     *
     * @param int $id The id of the upload
     * @return string
     */
    public function getSourceUrlById($id) {
        $image = instance(new Model_Image)->fetchById($id);
        if (!$image) {
            throw new InvalidArgumentException(self::ERROR_IMAGE_NOT_FOUND);
        }
        return $this->getSourceUrl($image['filename']);
    }

    /**
     * Check wether the given string is a filename.
     *
     * @param string $image
     * @return bool
     */
    protected function _isFilename($image) {
        return is_string($image) && strpos($image, '.') !== false;
    }

    /**
     * Render a static image
     *
     * @param string $filename
     * @param array $htmlAttribs
     * @return string
     */
    protected function _renderStatic($filename, array $htmlAttribs = array()) {
        $file = new Garp_Image_File(Garp_File::FILE_VARIANT_STATIC);
        $src = $file->getUrl($filename);

        if (!array_key_exists('alt', $htmlAttribs)) {
            $htmlAttribs['alt'] = '';
        }

        return $this->view->htmlImage($src, $htmlAttribs);
    }

    /**
     * Returns an HTML image tag, with the correct path to the image provided.
     *
     * @param mixed $imageIdOrRecord Id of the image record, or a Garp_Db_Table_Row image record.
     *                               This can also be an instance of an Image model.
     *                               If so, the image will be rendered inside a partial that
     *                               includes its caption and other metadata.
     * @param array $template        Template name.
     * @param array $htmlAttribs     HTML attributes for this <img> tag, such as 'alt'.
     * @param string $partial        Custom partial for rendering this image
     * @return string                Full image tag string, containing attributes and full path
     */
    protected function _renderUpload(
        $imageIdOrRecord, $template = null, array $htmlAttribs = array(), $partial = ''
    ) {
        if (!empty($template)) {
            $scaler = $this->_getImageScaler();
            $src = $scaler->getScaledUrl($imageIdOrRecord, $template);
            $tplScalingParams = $scaler->getTemplateParameters($template);
            $htmlAttribs = array_merge(
                $htmlAttribs,
                $this->_getHtmlAttribsFromSizeParams($tplScalingParams)
            );
        } else {
            if ($imageIdOrRecord instanceof Garp_Db_Table_Row) {
                $filename = $imageIdOrRecord->filename;
            } else {
                $imageModel = new Model_Image();
                $filename = $imageModel->fetchFilenameById($imageIdOrRecord);
            }
            $file = new Garp_Image_File(Garp_File::FILE_VARIANT_UPLOAD);
            $src = $file->getUrl($filename);
        }

        if (!array_key_exists('alt', $htmlAttribs)) {
            $htmlAttribs['alt'] = '';
        }

        $htmlAttribs['src'] = $src;
        $imgTag = '<img' . $this->_htmlAttribs($htmlAttribs) . '>';

        if ($imageIdOrRecord instanceof Garp_Db_Table_Row) {
            if ($partial) {
                $module  = 'default';
            } else {
                $partial = 'partials/image.phtml';
                $module  = 'g';
            }
            return $this->view->partial(
                $partial,
                $module,
                array(
                    'imgTag' => $imgTag,
                    'imgObject' => $imageIdOrRecord
                )
            );
        } else {
            return $imgTag;
        }
    }


    /**
     * Returns 'width' and 'height' html attributes when available in $scalingParams
     *
     * @param array $scalingParams Scaling parameters,
     *                             either custom or distilled from template configuration
     * @return array
     */
    protected function _getHtmlAttribsFromSizeParams($scalingParams) {
        $attribs = array();
        if (array_key_exists('w', $scalingParams) && $scalingParams['w']) {
            $attribs['width'] = $scalingParams['w'];
        }

        if (!$this->_config) {
            $this->_config = Zend_Registry::get('config');
        }

        if (is_null($this->_config->image->setHtmlHeight) || $this->_config->image->setHtmlHeight) {
            if (array_key_exists('h', $scalingParams) && $scalingParams['h']) {
                $attribs['height'] = $scalingParams['h'];
            }
        }
        return $attribs;
    }

    /**
     * Create Garp_Image_Scaler object
     *
     * @return Garp_Image_Scaler
     */
    protected function _getImageScaler() {
        if (!$this->_scaler) {
            $this->_scaler = new Garp_Image_Scaler();
        }
        return $this->_scaler;
    }
}
