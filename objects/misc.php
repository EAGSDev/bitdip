<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * An object which reads/writes global named integers in the misc table. Used to
 * cache often used stats, to track the database version compared to the code
 * version, and set dynamic configuration flags (such as whether the server is in
 * panic mode)
 *
 * @package Base
 */
class Misc
{
	private $updated = array();
	private $data = array();

	public function __construct()
	{
		$this->read();
	}

	public function __get($name)
	{
		// Open was renamed to Joinable due to the verb/noun confusion in translations
		if( $name == 'GamesJoinable' )
			$name = 'GamesOpen';

		return $this->data[$name];
	}

	public function __set($name, $value)
	{
		$this->data[$name] = $value;
		$this->updated[$name] = $name;
	}

	public function write()
	{
		global $DB;

		foreach($this->updated as $name)
		{
			$DB->sql_put("UPDATE wD_Misc SET value = ".$this->data[$name]." WHERE name = '".$name."'");
			unset($this->updated[$name]);
		}
	}

	public function read()
	{
		global $DB;

		$tabl = $DB->sql_tabl("SELECT name, value FROM wD_Misc");
		while ( list($name, $value) = $DB->tabl_row($tabl) )
		{
			$this->data[$name] = $value;
		}
		$this->updated=array();
	}
}

?>