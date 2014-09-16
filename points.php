<?php


/**
 * @package Base
 * @subpackage Static
 */

require_once('header.php');

libHTML::starthtml();

print libHTML::pageTitle(l_t('Intro to webDiplomacy Points'),l_t('A quick &amp; easy guide explaining what points are and what they\'re for in webDiplomacy.'));

require_once(l_r('locales/English/points.php'));

print '</div>';
libHTML::footer();

?>
