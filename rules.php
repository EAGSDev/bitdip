<?php


/**
 * @package Base
 * @subpackage Static
 */

require_once('header.php');

libHTML::starthtml();

print libHTML::pageTitle(l_t('webDiplomacy rulebook'),l_t('The webDiplomacy rules which let moderators and users keep this server fun to play on.'));

require_once(l_r('locales/English/rules.php'));

print '</div>';
libHTML::footer();
