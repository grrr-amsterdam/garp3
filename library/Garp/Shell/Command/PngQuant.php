<?php
/**
 * Garp_Shell_Command_PngQuant
 * @author David Spreekmeester | Grrr.nl
 */
 class Garp_Shell_Command_PngQuant extends Garp_Shell_Command_Abstract {
 	const COMMAND_CREATE_DIR = "pngquant %s --force --ext %s";
	
	/**
	 * @var String $_sourcePath
	 */
	protected $_sourcePath;

	/**
	 * @var String $_destinationPostFix 	A string that will be concatenated at the end
	 * 										of the source path, to form the destination path.
	 */
	protected $_destinationPostFix;	


	public function __construct($sourcePath, $destinationPostFix) {
		 $this->setSourcePath($sourcePath);
		 $this->setDestinationPostFix($destinationPostFix);
	}

	/**
	 * @return String
	 */
	public function getSourcePath() {
		return $this->_sourcePath;
	}
	
	/**
	 * @param String $sourcePath
	 */
	public function setSourcePath($sourcePath) {
		$this->_sourcePath = $sourcePath;
	}
	
	/**
	 * @return String
	 */
	public function getDestinationPostFix() {
		return $this->_destinationPostFix;
	}
	
	/**
	 * @param String $destinationPostFix
	 */
	public function setDestinationPostFix($destinationPostFix) {
		$this->_destinationPostFix = $destinationPostFix;
	}
	 
	public function render() {
 		$sourcePath 	= $this->getSourcePath();
		$destinationPostFix 	= $this->getDestinationPostFix();
		$command		= sprintf(self::COMMAND_CREATE_DIR, $sourcePath, $destinationPostFix);
	 	return $command;
	}
 }