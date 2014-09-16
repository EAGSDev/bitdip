<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * @package Base
 * @subpackage Pager
 */
require_once(l_r('pager/pager.php'));
class PagerThread extends Pager
{
	public static $defaultPostsPerPage=30;
	public $type='thread';
	function __construct($itemsTotal, $threadID)
	{
		parent::__construct('forum.php',$itemsTotal,self::$defaultPostsPerPage);
		$this->addArgs('threadID='.$threadID);
	}
	function getCurrentPage($currentPage=1)
	{
		parent::getCurrentPage($this->pageCount);
		if ( $this->currentPage>$this->pageCount )
			$this->currentPage = $this->pageCount;
	}
	function currentPageNumber()
	{
		return parent::currentPageNumber();
		if( $this->currentPage != $this->pageCount )
			return parent::currentPageNumber();
		else
			return '';
	}
}