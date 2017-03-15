<?php
/**
 * Garp_Model_Behavior_HtmlFilterable
 * Filter unwanted HTML out of rich-text fields.
 *
 * @package Garp_Model_Behavior
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Behavior_HtmlFilterable extends Garp_Model_Behavior_Abstract {
    /**
     * Fields to work on
     *
     * @var Array
     */
    protected $_fields;

    protected $_defaultAllowedClasses = array('figure', 'left', 'right', 'video-embed');

    /**
     * Make sure the config array is at least filled with some default values to work with.
     *
     * @param Array $config Configuration values
     * @return Array The modified array
     */
    protected function _setup($config) {
        $this->_fields = $config;
    }

    /**
     * Filter unwanted HTML out of a string
     *
     * @param String $string The string
     * @param HTMLPurifier_Config $config
     * @return String The filtered string
     */
    public function filter($string, HTMLPurifier_Config $config = null) {
        if (!$string) {
            return $string;
        }

        if (!$config) {
            $config = $this->getConfig();
        }

        $purifier = new HTMLPurifier($config);
        $string = $purifier->purify($string);
        return $string;
    }

    /**
     * Create config.
     *
     * @return HTMLPurifier_Config
     */
    public function getConfig() {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.DefinitionID', 'Garp3');
        $config->set('HTML.DefinitionRev', 5);
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Trusted', true);
        $config->set('HTML.TargetBlank', true);
        $config->set(
            'HTML.AllowedElements', array(
            'a', 'abbr', 'acronym', 'b', 'blockquote', 'br', 'caption', 'cite', 'code', 'dd', 'del',
            'dfn', 'div', 'dl', 'dt', 'em', 'embed', 'figure', 'figcaption', 'h1', 'h2', 'h3', 'h4',
            'h5', 'h6', 'hr', 'i', 'iframe', 'img', 'ins', 'kbd', 'li', 'object', 'ol', 'p',
            'param', 'pre', 's', 'small', 'span', 'strong', 'sub', 'sup', 'u', 'ul', 'var'
            )
        );

        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.RemoveSpansWithoutAttributes', true);
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
        $config->set('Output.TidyFormat', true);
        $config->set('Attr.AllowedClasses', $this->_getAllowedClasses());
        $config->set(
            'CSS.AllowedProperties', array(
            'font-weight', 'font-style', 'float', 'vertical-align', 'width', 'height'
            )
        );
        $config->set('CSS.MaxImgLength', null);
        $cachePath = $this->_getCachePath();
        $config->set('Cache.SerializerPath', $cachePath);
        if (!$cachePath) {
            $config->set('Cache.DefinitionImpl', null);
        }
        $config->set('URI.MakeAbsolute', true);
        $config->set('URI.Base', (string)new Garp_Util_FullUrl('/'));
        $config->set(
            'Filter.Custom', array(
                new Garp_Service_HTMLPurifier_Filter_MyIframe(),
                new Garp_Service_HTMLPurifier_Filter_MyEmbed(),
            )
        );

        // add proprietary elements
        if ($def = $config->maybeGetRawHTMLDefinition()) {
            $this->_addHtml5Elements($def);
            $iframe = $def->addElement(
                'iframe',   // name
                'Inline',   // content set
                'Custom: #PCDATA',  // allowed children
                'Common',   // attribute collection
                array(      // attributes
                    'src*' => 'URI',
                    'width*' => 'Number',
                    'height*' => 'Number',
                    'frameborder' => 'Text',
                    'scrolling' => 'Text',
                    'allowtransparency' => 'Text',
                )
            );
            $embed = $def->addElement(
                'embed',
                'Inline',
                'Custom: #PCDATA',
                'Common',
                array(
                    'src*' => 'URI',
                    'type*' => 'Text',
                    'width*' => 'Number',
                    'height*' => 'Number',
                    'allowscriptaccess' => 'Text'
                )
            );
        }
        return $config;
    }

    /**
     * Before insert callback. Manipulate the new data here. Set $data to FALSE to stop the insert.
     *
     * @param Array $args The new data is in $args[1]
     * @return Array Or throw Exception if you wish to stop the insert
     */
    public function beforeInsert(array &$args) {
        $data = &$args[1];
        foreach ($this->_fields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $this->filter($data[$field]);
            }
        }
    }

    /**
     * Before update callback. Manipulate the new data here.
     *
     * @param Array $args The new data is in $args[1]
     * @return Void
     */
    public function beforeUpdate(array &$args) {
        $data = &$args[1];
        foreach ($this->_fields as $field) {
            if (!empty($data[$field])) {
                $data[$field] = $this->filter($data[$field]);
            }
        }
    }

    protected function _getAllowedClasses() {
        $config = Zend_Registry::get('config');
        if (isset($config->htmlFilterable->allowedClasses)) {
            return $config->htmlFilterable->allowedClasses->toArray();
        }
        return $this->_defaultAllowedClasses;
    }

    protected function _getCachePath() {
        $config = Zend_Registry::get('config');
        if (isset($config->htmlFilterable->cachePath)) {
            return $config->htmlFilterable->cachePath;
        }
        return null;
    }

    protected function _addHtml5Elements($def) {
        $def->addElement('section', 'Block', 'Flow', 'Common');
        $def->addElement('nav',     'Block', 'Flow', 'Common');
        $def->addElement('article', 'Block', 'Flow', 'Common');
        $def->addElement('aside',   'Block', 'Flow', 'Common');
        $def->addElement('header',  'Block', 'Flow', 'Common');
        $def->addElement('footer',  'Block', 'Flow', 'Common');

        // Content model actually excludes several tags, not modelled here
        $def->addElement('address', 'Block', 'Flow', 'Common');
        $def->addElement('hgroup', 'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common');

        // http://developers.whatwg.org/grouping-content.html
        $def->addElement(
            'figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common'
        );
        $def->addElement('figcaption', 'Inline', 'Flow', 'Common');

        // http://developers.whatwg.org/the-video-element.html#the-video-element
        $def->addElement(
            'video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common',
            array(
                'src' => 'URI',
                'type' => 'Text',
                'width' => 'Length',
                'height' => 'Length',
                'poster' => 'URI',
                'preload' => 'Enum#auto,metadata,none',
                'controls' => 'Bool',
            )
        );
        $def->addElement(
            'source', 'Block', 'Flow', 'Common', array(
                'src' => 'URI',
                'type' => 'Text',
            )
        );

        // http://developers.whatwg.org/text-level-semantics.html
        $def->addElement('s',    'Inline', 'Inline', 'Common');
        $def->addElement('var',  'Inline', 'Inline', 'Common');
        $def->addElement('sub',  'Inline', 'Inline', 'Common');
        $def->addElement('sup',  'Inline', 'Inline', 'Common');
        $def->addElement('mark', 'Inline', 'Inline', 'Common');
        $def->addElement('wbr',  'Inline', 'Empty', 'Core');

        // http://developers.whatwg.org/edits.html
        $def->addElement(
            'ins', 'Block', 'Flow', 'Common', array('cite' => 'URI', 'datetime' => 'CDATA')
        );
        $def->addElement(
            'del', 'Block', 'Flow', 'Common', array('cite' => 'URI', 'datetime' => 'CDATA')
        );

        // TinyMCE
        $def->addAttribute('img', 'data-mce-src', 'Text');
        $def->addAttribute('img', 'data-mce-json', 'Text');

        // Others
        $def->addAttribute('iframe', 'allowfullscreen', 'Bool');
        $def->addAttribute('table', 'height', 'Text');
        $def->addAttribute('td', 'border', 'Text');
        $def->addAttribute('th', 'border', 'Text');
        $def->addAttribute('tr', 'width', 'Text');
        $def->addAttribute('tr', 'height', 'Text');
        $def->addAttribute('tr', 'border', 'Text');
    }
}
