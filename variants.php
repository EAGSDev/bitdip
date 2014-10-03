<?php


require_once('header.php');

libHTML::starthtml();

print libHTML::pageTitle('Europa','Information on the game version played at BitDip.');

$variantsOn=array();
$variantsOff=array();

$variants = glob('variants/*');
foreach($variants as $variantDir) {
   if( is_dir($variantDir) && file_exists($variantDir.'/variant.php') )
   {
      $variantDir=substr($variantDir,9);
      if( in_array($variantDir, Config::$variants) )
         $variantsOn[] = $variantDir;
      else
         $variantsOff[] = $variantDir;
   }
}



print '<ul>';
foreach( $variantsOn as $variantName )
{
   $Variant = libVariant::loadFromVariantName($variantName);
   print '<li><a href="variants.php#' . $Variant->name . '">' . l_t($Variant->fullName) . '</a> '.l_t('(%s Players)',count($Variant->countries)).'';
   $sql = 'SELECT COUNT(*) FROM wD_Games WHERE variantID=' .  $Variant->id . ' AND phase != "Pre-game"';
   list($num) = $DB->sql_row($sql);
   print ' - '.l_t('%s game(s) played on this server',$num).'</li>';
}
print '</ul>';


libHTML::pagebreak();


foreach( $variantsOn as $variantName )
{
   $Variant = libVariant::loadFromVariantName($variantName);
   print '<h2><a name="'. $Variant->name .'"></a>'.l_t( $Variant->fullName ) . ' '.l_t('(%s Players)',count($Variant->countries)).'</h2>';
   if (isset($Variant->description))
      print l_t($Variant->description)."<br /><br />";

   print '<div style="text-align:center"><img id="Image_'. $Variant->name . '" src="';
   if (file_exists(l_s(libVariant::cacheDir($Variant->name).'/sampleMap.png')))
      print l_s(libVariant::cacheDir($Variant->name).'/sampleMap.png');
   else
      print 'map.php?variantID=' . $Variant->id;
   print '" alt=" " title="'.l_t('The map for the %s Variant',$Variant->name).'" /></div><br />';
   print '<strong>'.l_t('Variant Parameters').'';
   if (isset($Variant->version))
      print ' '.l_t('(Version: %s)',$Variant->version).'';
   print ':</strong>';
   print '<ul>';

   if (isset($Variant->homepage))
      print '<li><a href="'. $Variant->homepage .'">'.l_t('Variant homepage').'</a></li>';
   if (isset($Variant->author))
      print '<li> '.l_t('Created by: %s',$Variant->author).'</li>';
   if (isset($Variant->adapter))
      print '<li> '.l_t('Adapted for webDiplomacy by: %s',$Variant->adapter).'</li>';
   print '<li> '.l_t('SCs required for solo win: %s',$Variant->supplyCenterTarget).'</li>';

   if (!file_exists(l_s('variants/'. $Variant->name .'/rules.html')))
      print '<li>'.l_t('Standard Diplomacy Rules Apply').'</li>';

   print '</ul>';

   if (file_exists(l_s('variants/'. $Variant->name .'/rules.html'))) {
      print '<p><strong>'.l_t('Special rules/information:').'</strong></p>';
      print '<div>'.file_get_contents(l_s('variants/'. $Variant->name .'/rules.html')).'</div>';
   }
   print '<div><a href="#top" class="light">'.l_t('Back to top').'</a></div>';

   print '<div class="hr"></div>';
}

print '</div>';
libHTML::footer();

?>