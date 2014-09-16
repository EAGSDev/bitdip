<?php


/**
 * @package Base
 * @subpackage Static
 */

require_once('header.php');

libHTML::starthtml();

print libHTML::pageTitle(l_t('webDiplomacy Credits'),l_t('A list of the people who helped/help make webDiplomacy what it is. (Chronological order)'));

require_once(l_r('locales/English/credits.php'));
print '</div>';

libHTML::footer();

?>
