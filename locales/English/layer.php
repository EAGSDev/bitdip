<?php


defined('IN_CODE') or die('This script can not be run by itself.');


class Locale_English extends Locale_Abstract {
	function textLookup($text) {
		return $text; // No lookup needed; the text is english
	}
}

$Locale = new Locale_English();
