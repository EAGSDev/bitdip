<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * @package Base
 * @subpackage Pager
 */

require_once(l_r('pager/pager.php'));
class PagerArchive extends Pager
{
	public static $defaultPostsPerPage=30;
	public $type='archive';

	function __construct($itemsTotal, $gameID, $archiveType)
	{
		parent::__construct('board.php',$itemsTotal,self::$defaultPostsPerPage);

		$this->extraArgs='gameID='.$gameID.'&amp;viewArchive='.$archiveType;
	}
	function getCurrentPage($currentPage=1)
	{
		parent::getCurrentPage($this->pageCount);
		if ( $this->currentPage>$this->pageCount )
			$this->currentPage = $this->pageCount;
	}
	function currentPageNumber()
	{
		if( $this->currentPage != $this->pageCount )
			return parent::currentPageNumber();
		else
			return '';
	}

	function SQLLimit()
	{
		// This doesn't start with limit because board/chatbox.php's getMessages function takes a limit parameter
		// which is placed immidiately after LIMIT
		return ''.($this->pageCount-$this->currentPage)*$this->itemsPerPage.', '.$this->itemsPerPage;
	}
}