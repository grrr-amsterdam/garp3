<?php
/**
 * Garp_Adobe_InDesign_TextFrame
 * Wrapper around various InDesign related functionality.
 *
 * @package Garp_Adobe_InDesign
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Adobe_InDesign_TextFrame extends Garp_Adobe_InDesign_SpreadNode {

    /**
     * @var string  $storyId    The Story that this TextFrame belongs to.
     */
    public $storyId;

    /**
     * @param SimpleXMLElement $spreadConfig The <Spread> node of an InDesign Spread configuration.
     * @param string $textFrameConfig The <TextFrame> node within the Spread configuration.
     * @return void
     */
    public function __construct(SimpleXMLElement $spreadConfig, $textFrameConfig) {
        parent::__construct($spreadConfig, $textFrameConfig);

        $this->storyId = $this->_getStoryId();
    }

    protected function _getStoryId() {
        return (string)$this->_nodeConfig->attributes()->ParentStory;
    }


}
