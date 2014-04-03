<?php
/**
 * Garp_Cli_JsonFormatter
 * Formats JSON in a way that it can be edited later.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Cli_JsonFormatter {
	/**
	 * Encode an array to a readable JSON format.
	 * @param Array $data
	 * @param Int $indentation Amount of tabs
	 * @param String $append An optional string to append to the output
	 * @return String
	 */
	static public function encode(array $data, $indentation = 1, $append = "\n") {
		$len = count($data);
		$i   = 0;
		$tab = "\t";
		$out = str_repeat($tab, $indentation)."{\n";
		foreach ($data as $key => $value) {
			$out .= str_repeat($tab, ($indentation+1));
			$out .= "'$key': ";
			if ($value instanceof Garp_Cli_JsonFormatter_Expr) {
				$out .= (string)$value;
			} elseif (is_bool($value)) {
				$out .= $value ? 'true' : 'false';
			} elseif (is_numeric($value)) {
				$out .= $value;
			} elseif (is_string($value) || (is_object($value) && is_callable(array($value, '__toString')))) {
				$out .= "'$value'";
			} elseif (is_null($value)) {
				$out .= 'null';
			} elseif (is_array($value)) {
				$out .= Garp_Cli_JsonFormatter::encode($value, $indentation+1);
			}
			
			if (++$i < $len) {
				$out .= ",";
			}
			$out .= "\n";
		}
		$out .= str_repeat($tab, $indentation)."}";
		$out .= $append;
		return $out;
	}
}