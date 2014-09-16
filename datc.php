<?php


/**
 * @package DATC
 */

require_once('header.php');

libHTML::starthtml(l_t('DATC Tests'));

print libHTML::pageTitle(l_t('Diplomacy Adjudicator Test Cases'),l_t('The results of a set of automated tests which show the webDiplomacy\'s compliance with the official Diplomacy rules.'));

if ( $Misc->Maintenance )
{
	require_once(l_r('datc/interactive.php'));

}

require_once(l_r('locales/English/datc.php'));

?>
