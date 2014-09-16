<?php


defined('IN_CODE') or die('This script can not be run by itself.');


class VariantData
{
	/**
	 * The variant ID; the only mandatory field
	 * @var int
	 */
	public $variantID;
	/**
	 * Game ID, or 0 if game ID not relevant (e.g. user specific or global variant data)
	 * @var int
	 */
	public $gameID = 0;
	/**
	 * An extra token to prevent conflict with any other unknown code using the same system for data storage. Should be a random number from 1 to 2^31-1
	 * @var int
	 */
	public $systemToken = 0;
	/**
	 * User ID, or 0 if not user specific
	 * @var int
	 */
	public $userID = 0;
	/**
	 * Data type ID, basically another way to distinguish between the sorts of data stored. Default is 0, which is fine for most use.
	 * @var int
	 */
	public $typeID = 0;

	public function VariantData($variantID)
	{
		$this->variantID = $variantID;
	}

	/**
	 * Create where clause to select this variant data
	 * @param int $offset
	 * @return string
	 */
	public function where($offset=0)
	{
		$params = array(
				'variantID' => $this->variantID,
				'gameID' => $this->gameID,
				'systemToken' => $this->systemToken,
				'typeID' => $this->typeID,
				'userID' => $this->userID,
				'offset' => $offset);

		$arr = array();
		foreach($params as $k=>$v)
			$arr[] = $k.'='.$v;

		return implode(' AND ', $arr);
	}
	/**
	 * Generic get data column
	 * @param string $col Name of the column to extract
	 * @param int $offset The variable offset to extract
	 * @return int/float The data in that record
	 */
	private function getCol($col, $offset=0)
	{
		global $DB;

		list($val) = $DB->sql_row("SELECT val_".$col." FROM wD_VariantData WHERE ".$this->where($offset));

		return $val;
	}
	public function getInt($offset=0, $default=0)
	{
		$val = $this->getCol('int',$offset);
		if( is_null($val) || empty($val) ) return $default;
		else return $val;
	}
	public function getFloat($offset=0, $default=0)
	{
		$val = $this->getCol('float',$offset);
		if( is_null($val) || empty($val) ) return $default;
		else return $val;
	}
	private function setCol($col, $val, $offset=0)
	{
		global $DB;

		$DB->sql_put("UPDATE wD_VariantData SET val_".$col." = ".number_format(round($val,3),3)." WHERE ".$this->where($offset));
	}

	public function setFloat($val, $offset=0)
	{
		$this->setCol('float',$val,$offset);
	}

	public function setInt($val, $offset=0)
	{
		$this->setCol('int',$val,$offset);
	}
}