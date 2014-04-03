<?php
/**
 * Garp_Cli_JsonFormatter_Expr
 * Describes a Javascript expression.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Cli_JsonFormatter_Expr {
	/**
	 * The expression
	 * @var String
	 */
	protected $_expr;
	
	
	/**
	 * Class constructor
	 * @param String $expr The expression
	 * @return Void
	 */
	public function __construct($expr) {
		$this->_expr = $expr;
	}
	
	
	/**
	 * Return string representation of this expression
	 * @return String
	 */
	public function __toString() {
		return $this->_expr;
	}
}