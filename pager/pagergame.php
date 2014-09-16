<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * @package Base
 * @subpackage Pager
 */
require_once(l_r('pager/pager.php'));
class PagerGames extends Pager
{
	private $approxPageCount;
	public $type='games';
	function __construct($URL, $approxItemCount=null)
	{
		if(isset($approxItemCount))
			$this->approxPageCount = ceil($approxItemCount / 10);

		parent::__construct($URL,null,10);
	}
	function currentPageNumberOfTotal()
	{
		if( $this->currentPage != 1 )
			return parent::currentPageNumber();
		else
			return '';
	}
	function currentPageNumber()
	{
		if(!isset($this->approxPageCount))
			return '';

		$this->pageCount = '~'.$this->approxPageCount;
		$buf = parent::currentPageNumber();
		unset($this->pageCount);

		return $buf;
	}
}