<?php
/**
 * Garp_Adobe_InDesign_SpreadNode
 * A node in an InDesign configuration of a Spread. For instance: a Page or a TextFrame.
 *
 * @package Garp_Adobe_InDesign
 * @author  David Spreekmeester <david@grrr.nl>
 */
abstract class Garp_Adobe_InDesign_SpreadNode {

    public $id;


    /**
     * @var float X-coordinate
     */
    public $x;


    /**
     * @var float Y-coordinate
     */
    public $y;


    /**
     * @var SimpleXMLElement  The configuration of the Spread in which this node resides.
     */
    protected $_spreadConfig;

    /**
     * @var SimpleXMLElement The configuration of the <Page> or <TextFrame> node within the Spread.
     */
    protected $_nodeConfig;


    /**
     * @param SimpleXMLElement $spreadConfig The <Spread> node of an InDesign Spread configuration.
     * @param string $nodeConfig The <Page> or <TextFrame> node within the Spread configuration.
     */
    public function __construct(SimpleXMLElement $spreadConfig, $nodeConfig) {
        $this->_spreadConfig = $spreadConfig;
        $this->_nodeConfig   = $nodeConfig;

        $this->id = $this->_getId();

        $coordinates = $this->_getCoordinates();
        $this->x     = $coordinates['x'];
        $this->y     = $coordinates['y'];
    }


    protected function _getId() {
        return (string)$this->_nodeConfig->attributes()->Self;
    }


    /**
     * Get x- and y-coordinate of a node in a InDesign Spread that has an ItemTransform property.
     *
     * @return array An associative array with keys 'x' and 'y'.
     */
    protected function _getCoordinates() {
        $itemTransformString = (string)$this->_nodeConfig->attributes()->ItemTransform;
        $itemTransformArray = explode(' ', $itemTransformString);
        $coordinates = array(
            'x' => $itemTransformArray[count($itemTransformArray) - 2],
            'y' => $itemTransformArray[count($itemTransformArray) - 1]
        );

        return $coordinates;
    }
}
