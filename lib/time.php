<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * These functions display times; either times remaining or absolute times in a friendly, concise way.
 *
 * Before 0.95 a user had a timezone offset associated with their account, which was used to adjust times,
 * now the friendly time in UTC is displayed, with the time in seconds also given as an attribute, and the
 * markup is tagged using a class to mark that it is a time.
 * Then javascript/timehandler.js is run from libHTML::footer(), and it iterates through all the timestamps
 * and updates them for the users timezone, and iterates through all timeremaining spans and updates them
 * every 0.5 seconds (if there is anything to update) giving a dynamic counter.
 *
 * @package Base
 */
class libTime
{
		static public function timeLengthText($timeLength)
		{
			return self::remainingTextString($timeLength+time(), time());
		}

        /**
         * Print the time remaining
         *
         * @param int $givenTime GMT UNIX timestamp
         * @return string Time remaining
         */
        static public function remainingText($givenTime, $timeFrom=false)
        {
			if ( $timeFrom===false ) $timeFrom = time();

			$secondsRemaining = $givenTime - $timeFrom;

			return '<span class="timeremaining" unixtime="'.$givenTime.'" unixtimefrom="'.$timeFrom.'">'.
				self::remainingTextString($givenTime, $timeFrom).'</span>';
        }

        /**
         * Print the time in the viewing $User's timezone
         *
         * @param int[optional] $givenTime GMT+0 UNIX timestamp, set to the current time if none given
         *
         * @return string Text-formatted time
         */
        static public function text($givenTime=0)
        {
			if( $givenTime == 0 )
				$givenTime = time(); // GMT+0 time

			return '<span class="timestamp" unixtime="'.$givenTime.'">'.self::textString($givenTime).' UTC</span>';
        }

        static public function stamp()
        {
        	return gmstrftime("%c");
        }

        static private function remainingTextString($givenTime, $timeFrom)
        {
                $secondsRemaining = $givenTime - $timeFrom;

                if ( $secondsRemaining <= 0 )
                        return l_t('Now');

                $seconds = floor( $secondsRemaining % 60);
                $minutes = floor(( $secondsRemaining % (60*60) )/60);
                $hours = floor( $secondsRemaining % (24*60*60)/(60*60) );
                $days = floor( $secondsRemaining /(24*60*60) );

                if ( $days > 0 )
                {
                        // D, H
                        $minutes += round($seconds/60); // Add a minute if the seconds almost give a minute
                        $seconds = 0;

                        $hours += round($minutes/60); // Add an hour if the minutes almost gives an hour
                        $minutes = 0;

                        if ( $hours > 0 )
                                return l_t('%s days, %s hours',$days,$hours);
                        else
                                return l_t('%s days', $days);
                }
                elseif ( $hours > 0 )
                {
                        // H, M
                        $minutes += round($seconds/60); // Add a minute if the seconds almost give a minute
                        $seconds = 0;

                        if ( $minutes > 0 )
                        	return l_t('%s hours, %s minutes',$hours,$minutes);
						else
							return l_t('%s hours',$hours);
                }
                else
                {
                        // M, S
                        if ( $seconds > 0 )
							return l_t('%s minutes, %s seconds',$minutes,$seconds);
						else
							return l_t('%s minutes',$minutes);
                }
        }

        static private function textString($givenTime)
        {
                $timeDifference = abs(time() - $givenTime);

                if ( $timeDifference < 22*60*60 )
					return gmstrftime("%I:%M %p", $givenTime); // HH:MM AM/PM
                elseif ( $timeDifference < 4*24*60*60 )
                	return gmstrftime("%a %I %p", $givenTime); // Day HH AM/PM
                elseif ( $timeDifference < 3*7*22*60*60 )
                	return gmstrftime("%a %d %b", $givenTime); // Day Day# Month
                else
                	return gmstrftime("%d %b %y", $givenTime); // Day# Month Year
        }
}
?>