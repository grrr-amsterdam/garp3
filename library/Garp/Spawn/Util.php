<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Util {
	static public function camelcased2underscored($str) {
		$str[0] = strtolower($str[0]);
		$func = create_function('$c', 'return "_" . strtolower($c[1]);');
	    return preg_replace_callback('/([A-Z])/', $func, $str);
	}


	static public function camelcased2dashed($str) {
		$str[0] = strtolower($str[0]);
		$func = create_function('$c', 'return "-" . strtolower($c[1]);');
	    return preg_replace_callback('/([A-Z])/', $func, $str);
	}


	static public function stringEndsIn($needle, $haystack) {
		return substr($haystack, -(strlen($needle))) === $needle;
	}
	
	
	/**
	 * List the values in the numeric / associative array as an array statement, like 'array("Slow", "Deep", "Hard")'.
	 * @param Array $list The Array to form into a PHP statement.
	 * @param Boolean $castObjectsToArrays Whether objects should be casted to arrays
	 * @return String Array statement containing the elements in the provided list.
	 */
	static public function array2phpStatement(Array $list, $castObjectsToArrays = true) {
		$nodes = array();

		foreach ($list as $key => $value) {
			if (is_object($value) && $castObjectsToArrays) {
				$value = (array)$value;
			}
			$node = '';
			if (!is_numeric($key)) {
				//	associative array
				if (is_string($key)) $node.= "'";
				$node.= $key;
				if (is_string($key)) $node.= "'";
				$node.= ' => ';
			}
			if (is_array($value)) {
				$node.= self::array2phpStatement($value);
			} else {
				if (is_string($value)) {
					$node.= "'";
				}
				$node.= is_bool($value) ?
					($value ? 'true' : 'false') :
					(is_null($value) ?
						'null' :
						str_replace("'", '\\\'', $value)
					)
				;
				if (is_string($value)) {
					$node.= "'";
				}
			}
			$nodes[] = $node;
		}

		$out = "array(";
		$out.= implode($nodes, ', ');
		$out.= ')';
		
		return $out;
	}
	

	/**
	 * Converts 'doggy_dog_world_id' to 'Doggy dog world id'
	 */
	static public function underscored2readable($str) {
		$str = ucfirst($str);
		$str = str_replace("_", " ", $str);
		return $str;
	}


	/**
	 * Converts 'doggy_dog_world_id' to 'doggyDogWorldId'
	 */
	static public function underscored2camelcased($str) {
		$func = create_function('$c', 'return strtoupper($c[1]);');
		return preg_replace_callback('/_([a-z])/', $func, $str);
	}


	/**
	 * @param Array $list Numeric Array of String elements,
	 * @param String $decorator Element decorator, f.i. a quote.
	 * @param String $lastItemSeperator Seperates the last item from the rest instead of a comma, for instance: 'and' or 'or'.
	 * @return String Listed elements, like "Snoop, Dre and Devin".
	 */
	static public function humanList(Array $list, $decorator = null, $lastItemSeperator = 'and') {
		$listCount = count($list);
		if ($listCount === 1) {
			return $decorator.current($list).$decorator;
		} elseif ($listCount === 2) {
			return $decorator.implode($decorator." {$lastItemSeperator} ".$decorator, $list).$decorator;
		} elseif ($listCount > 2) {
			$last = array_pop($list);
			return $decorator.implode($decorator.", ".$decorator, $list).$decorator." {$lastItemSeperator} ".$decorator.$last.$decorator;
		}
	}


	static public function confirm($msg = null) {
		if ($msg) {
			echo self::addStringColoring($msg);
		}
		system('stty -icanon');
		$handle = fopen ("php://stdin","r");
		$char = fgetc($handle);
		system('stty icanon');
		return $char === 'y' || $char === 'Y';
	}
	
	
	static public function addStringColoring($msg) {
		$prevEnc = mb_internal_encoding();
		mb_internal_encoding("UTF-8");
		$firstChar = mb_substr($msg, 0, 1);
		if ($firstChar === '√')
			$msg = "\033[2;32m{$firstChar}\033[0m".mb_substr($msg, 1);
		elseif ($firstChar === '!')
			$msg = "\033[2;31m●\033[0m".mb_substr($msg, 1);
		mb_internal_encoding($prevEnc);
		return $msg;
	}
}