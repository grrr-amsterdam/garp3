<?php
/**
 * Garp_Util_String
 * class description
 * @author David Spreekmeester | grrr.nl, Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Util
 * @lastmodified $Date: $
 */
class Garp_Util_String {
	/** Converts 'SnoopDoggyDog' to 'snoop_doggy_dog' */
	static public function camelcasedToUnderscored($str) {
		$str = lcfirst($str);
	    return preg_replace_callback('/([A-Z])/', function($str) { return "_".strtolower($str[1]); }, $str);
	}


	/** Converts 'SnoopDoggyDog' to 'snoop-doggy-dog' */
	static public function camelcasedToDashed($str) {
		$str = lcfirst($str);
	    return preg_replace_callback('/([A-Z])/', function($str) { return "-".strtolower($str[1]); }, $str);
	}


	/** Converts a string like 'Snoop Döggy Døg!' or 'Snoop, doggy-dog' to URL- & cross filesystem-safe string 'snoop-doggy-dog' */
	static public function toDashed($str, $convertToAscii = true) {
		if ($convertToAscii) {
			$str = self::utf8ToAscii($str);
		}

		$str = preg_replace_callback('/([A-Z])/', function($str) {return "-".strtolower($str[1]);}, $str);
		$str = preg_replace('/[^a-z0-9]/', '-', $str);
		$str = preg_replace('/\-{2,}/', '-', $str);
		return trim($str, "\n\t -");
	}


	/** Converts 'doggy_dog_world_id' to 'Doggy dog world id' */
	static public function underscoredToReadable($str, $ucfirst = true) {
		if ($ucfirst) {
			$str = ucfirst($str);
		}
		$str = str_replace("_", " ", $str);
		return $str;
	}


	/**
	 * Converts 'doggy_dog_world_id' to 'doggyDogWorldId'
	 */
	static public function underscoredToCamelcased($str) {
		$func = create_function('$c', 'return strtoupper($c[1]);');
		return preg_replace_callback('/_([a-z])/', $func, $str);
	}


	/**
 	 * Converts 'doggy-dog-world-id' to 'doggyDogWorldId'
 	 */
	static public function dashedToCamelcased($str) {
		$func = create_function('$c', 'return strtoupper($c[1]);');
		return preg_replace_callback('/\-([a-z])/', $func, $str);
	}


	/**
	 * Converts 'Snøøp Düggy Døg' to 'Snoop Doggy Dog'
	 */
	static public function utf8ToAscii($str) {
		$a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýýþÿŔŕ?';
	    $b = 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYBSaaaaaaaceeeeiiiidnoooooouuuuyybyRr?';

		$origEncoding = mb_internal_encoding();
		mb_internal_encoding("UTF-8");
	    $str = strtr($str, utf8_decode($a), $b);
		mb_internal_encoding($origEncoding);
	    return utf8_encode($str);
	}


	/** Returns true if the $haystack string ends in $needle */
	static public function endsIn($needle, $haystack) {
		return substr($haystack, -(strlen($needle))) === $needle;
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


	/**
	 * Generates an HTML-less excerpt from a marked up string.
	 * For instance; "<p><strong>This</strong>" is some content.</p> becomes
	 * "This is some content.".
	 * @param String $content
	 * @param Int $chars The length of the generated excerpt
	 * @return String
	 */
	static public function excerpt($content, $chars = 140, $respectWords = true) {
		$content = htmlspecialchars(
			str_replace(
				array(
					'. · ',
					'.  · '
				),
				'. ',
				strip_tags(
					preg_replace('~</([a-z]+)><~i', '</$1> · <', $content)
					/*str_replace('</p><', '</p> · <', $content)*/
				)
			)
		);
		if (strlen($content) > $chars) {
			if ($respectWords) {
				$pos = strrpos(substr($content, 0, $chars), ' ');
			} else $pos = $chars;
			$content = substr($content, 0, $pos);
			$content = preg_replace('/\W$/', '', $content);
			$content .= '&hellip;';
		}
		return $content;
	}


	/**
	 * Automatically wrap URLs and email addresses in HTML <a> tags.
	 * @param String $text
	 * @param Array $attribs HTML attributes 
	 * @return String
	 */
	static public function linkify($text, array $attribs = array()) {
		return self::linkUrls(self::linkEmailAddresses($text, $attribs), $attribs);
	}


	/**
	 * Link URLs.
	 * @param String $text
	 * @param Array $attribs HTML attributes
	 * @return String
	 */
	static public function linkUrls($text, array $attribs = array()) {
		$htmlAttribs = array();
		foreach ($attribs as $name => $value) {
			$htmlAttribs[] = $name.'="'.$value.'"';
		}
		$htmlAttribs = implode(' ', $htmlAttribs);
		$htmlAttribs = $htmlAttribs ? ' '.$htmlAttribs : '';
		
		$regexpProtocol	= "/(?:^|\s)((http|https|ftp):\/\/[^\s<]+[\w\/#])([?!,.])?(?=$|\s)/i";
		$regexpWww		= "/(?:^|\s)((www\.)[^\s<]+[\w\/#])([?!,.])?(?=$|\s)/i";
		
		$text = preg_replace($regexpProtocol, " <a href=\"\\1\"$htmlAttribs>\\1</a>\\3 ", $text);
		$text = preg_replace($regexpWww, " <a href=\"http://\\1\"$htmlAttribs>\\1</a>\\3 ", $text);
		return trim($text);
	}


	/**
	 * Link email addresses
	 * @param String $text
	 * @param Array $attribs HTML attributes
	 * @return String
	 */
	static public function linkEmailAddresses($text, array $attribs = array()) {
		$htmlAttribs = array();
		foreach ($attribs as $name => $value) {
			$htmlAttribs[] = $name.'="'.$value.'"';
		}
		$htmlAttribs = implode(' ', $htmlAttribs);
		$htmlAttribs = $htmlAttribs ? ' '.$htmlAttribs : '';
		
		$regexp = '/[a-zA-Z0-9\.-_]+@[a-zA-Z0-9\.\-_]+\.([a-zA-Z]{2,})/';
		$text = preg_replace($regexp, "<a href=\"mailto:$0\"$htmlAttribs>$0</a>", $text);
		return $text;
	}


	/**
	 * Output email address as entities
	 * @param String $email
	 * @param Boolean $mailTo Wether to append "mailto:" for embedding in <a href="">
	 * @return String
	 */
	static public function scrambleEmail($email, $mailTo = false) {
		for ($i = 0, $c = strlen($email), $out = ''; $i < $c; $i++) {
			$out .= '&#'.ord($email[$i]).';';
		}
		if ($mailTo) {
			$out = self::scrambleEmail('mailto:').$out;
		}		
		return $out;
	}


	/**
 	 * str_replace, but replace only the first occurrence of the search string.
 	 * @see http://tycoontalk.freelancer.com/php-forum/21334-str_replace-only-once-occurence-only.html#post109511
 	 * @param String $needle
 	 * @param String $replace
 	 * @param String $haystack
 	 * @return String
 	 */
	static public function strReplaceOnce($needle, $replace, $haystack) {
		if (!$needle) {
			return $haystack;
		}
		// Looks for the first occurence of $needle in $haystack
    	// and replaces it with $replace.
    	$pos = strpos($haystack, $needle);
    	if ($pos === false) {
        	// Nothing found
    		return $haystack;
    	}
    	return substr_replace($haystack, $replace, $pos, strlen($needle));
	}


	/**
 	 * Interpolate strings with variables
 	 * @param String $str The string
 	 * @param Array $vars The variables
 	 * @return The interpolated string
 	 */
	static public function interpolate($str, array $vars) {
		$keys = array_keys($vars);
		$vals = array_values($vars);
		// surround keys by "%"
		array_walk($keys, function(&$s) {
			$s = '%'.$s.'%';
		});
		$str = str_replace($keys, $vals, $str);
		return $str;
	}
}
