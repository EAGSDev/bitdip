<?php


/**
 * @package Base
 * @subpackage Static
 */

require_once('header.php');

libHTML::starthtml();
print '<div class="content">';
require_once(l_r('locales/English/translating.php'));
print '</div>';
libHTML::footer();

?>
