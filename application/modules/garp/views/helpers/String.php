<?php
/**
 * G_View_Helper_String
 * Various String helper functionality.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Helper
 * @lastmodified $Date: $
 */
class G_View_Helper_String extends Zend_View_Helper_Abstract {
	/**
	 * Chain method.
	 * @return G_View_Helper_String 
	 */
	public function string() {
		return $this;
	}
	
	
	/**
	 * Generates an HTML-less excerpt from a marked up string.
	 * For instance; "<p><strong>This</strong>" is some content.</p> becomes
	 * "This is some content.".
	 * @param String $content
	 * @param Int $chars The length of the generated excerpt
	 * @return String
	 */
	public function excerpt($content, $chars = 140, $respectWords = true) {
		$content = $this->view->escape(
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
			$content = substr($content, 0, $pos).'&hellip;';
		}
		return $content;
	}
	
	
	/**
	 * Automatically wrap URLs and email addresses in HTML <a> tags.
	 * @param String $text
	 * @param Array $attribs HTML attributes 
	 * @return String
	 */
	public function linkify($text, array $attribs = array()) {
		return $this->linkUrls($this->linkEmailAddresses($text, $attribs), $attribs);
	}
	
	
	/**
	 * Link URLs.
	 * @param String $text
	 * @param Array $attribs HTML attributes
	 * @return String
	 */
	public function linkUrls($text, array $attribs = array()) {
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
	public function linkEmailAddresses($text, array $attribs = array()) {
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
	public function scrambleEmail($email, $mailTo = false) {
		for ($i = 0, $c = strlen($email), $out = ''; $i < $c; $i++) {
			$out .= '&#'.ord($email[$i]).';';
		}
		if ($mailTo) {
			$out = $this->scrambleEmail('mailto:').$out;
		}		
		return $out;
	}
}
