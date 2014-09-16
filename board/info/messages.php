<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * Output the chat logs
 *
 * @package Board
 */


require_once('pager/pagerarchive.php');

list($itemsTotal) = $DB->sql_row("SELECT COUNT(*) FROM wD_GameMessages WHERE gameID = ".$Game->id." AND ".
	"(toCountryID = 0".(isset($Member)?" OR fromCountryID = ".$Member->countryID." OR toCountryID = ".$Member->countryID:'').")");
$pager = new PagerArchive($itemsTotal, $Game->id, 'Messages');

print $pager->html();


print '<h4>'.l_t('Chat archive').'</h4>';

print '<div class="variant'.$Game->Variant->name.'">';

$CB = $Game->Variant->Chatbox();
print '<table>'.$CB->getMessages( -1, $pager->SQLLimit()).'</table>';

print '</div>';

?>