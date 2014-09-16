<?php


require_once('header.php');

if ( ! defined('FACEBOOKSCRIPT') )
{
	libHTML::error('This page is Facebook-only.');
}

libHTML::starthtml();

print '<fb:request-form
action=""
method="POST"
invite="true"
type="Diplomacy"
content="'.l_t('webDiplomacy is based on the popular turn-based-strategy game of international relations. '.
	'Play with your friends and see if you can conquer Europe.').' '.
	'<fb:req-choice url=\''.DYNAMICSRV.'\' label=\''.l_t('Add webDiplomacy').'\' />">

<fb:multi-friend-selector
showborder="false"
actiontext="'.l_t('Invite more friends to play webDiplomacy with you:').'">

</fb:request-form>';

print '</div>';
libHTML::footer();

?>