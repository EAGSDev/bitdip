<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * A utility class to update various cached values stored in the misc table.
 *
 * @package GameMaster
 */
class miscUpdate
{
	/**
	 * Update forum related cached values.
	 *
	 * ForumThreads=>The number of threads,
	 * ThreadAliveThreshold=>The message id of the reply posted to the 100th thread in the forum.
	 * 		If the latestReply is larger than this the thread is considered active,
	 * ThreadActiveThreshold=>The number of replies that the 10th largest active thread has.
	 */
	public static function forum()
	{
		global $DB,$Misc;

		list($Misc->ForumThreads) = $DB->sql_row("SELECT COUNT(type) FROM wD_ForumMessages WHERE type='ThreadStart'");

		$Misc->ThreadAliveThreshold=0;
		list($Misc->ThreadAliveThreshold) = $DB->sql_row(
				"SELECT MIN(f.latestReplySent)
				FROM (SELECT latestReplySent FROM wD_ForumMessages WHERE type='ThreadStart'
				ORDER BY latestReplySent DESC LIMIT 150) as f"
			);
		if(!$Misc->ThreadAliveThreshold)
			$Misc->ThreadAliveThreshold=0;

		// There are 10 threads in the latest 3 pages which have over this many replies
		// Solution: Get reply-count from all threads in the latest 3 pages
		// 		Order by reply-count and limit to the desired active-thread-count
		//		Get the smallest number of replies which makes it in
		$activeThreads=10;
		$Misc->ThreadActiveThreshold=null;
		list($Misc->ThreadActiveThreshold) = $DB->sql_row(
			"SELECT MIN(activest.replies) FROM (
				SELECT replies FROM wD_ForumMessages
				WHERE latestReplySent >= ".$Misc->ThreadAliveThreshold."
				ORDER BY replies
				DESC LIMIT ".$activeThreads."
			) as activest");
		if(!$Misc->ThreadActiveThreshold || $Misc->ThreadActiveThreshold<5)
			$Misc->ThreadActiveThreshold=0; // No actives/not active enough
	}

	/**
	 * Update game related cached values.
	 *
	 * GamesNew, GamesActive, GamesFinished, GamesCrashed, GamesPaused, GamesOpen
	 * GameFeaturedThreshold=>The minimum number of points needed for a game to be featured.
	 */
	public static function game()
	{
		global $DB, $Misc;

		list($Misc->GamesNew) = $DB->sql_row("SELECT COUNT(phase) FROM wD_Games WHERE phase = 'Pre-game'");
		list($Misc->GamesActive) = $DB->sql_row("SELECT COUNT(phase) FROM wD_Games WHERE phase = 'Diplomacy' OR phase = 'Retreats' OR phase = 'Builds'");
		list($Misc->GamesFinished) = $DB->sql_row("SELECT COUNT(phase) FROM wD_Games WHERE phase = 'Finished'");
		list($Misc->GamesCrashed) = $DB->sql_row("SELECT COUNT(processStatus) FROM wD_Games WHERE processStatus = 'Crashed'");
		list($Misc->GamesPaused) = $DB->sql_row("SELECT COUNT(processStatus) FROM wD_Games WHERE processStatus = 'Paused'");
		list($Misc->GamesOpen) = $DB->sql_row("SELECT COUNT(DISTINCT gameID) FROM wD_Members WHERE status='Left'");

		if( $Misc->GamesActive >= 16 )
		{
			$featuredCount=floor(sqrt($Misc->GamesActive));
			$Misc->GameFeaturedThreshold=null; // Null may be returned by the query below
			list($Misc->GameFeaturedThreshold) = $DB->sql_row("SELECT MIN(pot)
				FROM wD_Games WHERE NOT phase = 'Finished' AND pot >= 707
				ORDER BY pot DESC LIMIT ".$featuredCount);
			if(!$Misc->GameFeaturedThreshold)
				$Misc->GameFeaturedThreshold=0;
		}
		else
		{
			$Misc->GameFeaturedThreshold=0;
		}
	}

	/**
	 * Update ErrorLogCount=>The number of error-log files
	 */
	static public function errorLog()
	{
		global $Misc;

		list($Misc->ErrorLogCount) = libError::stats();
	}

	/**
	 * Update user related cached values.
	 *
	 * RankingPlayers=>Players with >100 points,
	 * TotalPlayers=>All players,
	 * OnlinePlayers=>Players in session table,
	 * ActivePlayers=>Players which are 'Playing' in a game
	 */
	static public function user()
	{
		global $DB,$Misc;

		list($Misc->RankingPlayers) = $DB->sql_row(
			"SELECT COUNT(id)+1 FROM wD_Users WHERE points > 100");

		list($Misc->TotalPlayers) = $DB->sql_row(
			"SELECT MAX(id) FROM wD_Users");

		list($Misc->OnlinePlayers) = $DB->sql_row("SELECT COUNT(userID) FROM wD_Sessions");

		list($Misc->ActivePlayers) = $DB->sql_row("SELECT COUNT(DISTINCT userID) FROM wD_Members WHERE status='Playing'");
	}
}
?>