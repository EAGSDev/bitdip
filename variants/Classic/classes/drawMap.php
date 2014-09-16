<?php


defined('IN_CODE') or die('This script can not be run by itself.');

class ClassicVariant_drawMap extends drawMap {

	/**
	 * An array of colors for different countries, indexed by countryID
	 * @var array
	 */
	protected $countryColors = array(
		0 => array(226, 198, 158),
		1 => array(239, 196, 228),
		2 => array(121, 175, 198),
		3 => array(164, 196, 153),
		4 => array(160, 138, 117),
		5 => array(196, 143, 133),
		6 => array(234, 234, 175),
		7 => array(168, 126, 159)
	);

	/**
	 * Resources, all required except names, which will be drawn on by the computer if not supplied.
	 * @return array[$resourceName]=$resourceLocation
	 */
	protected function resources() {
		if( $this->smallmap )
		{
			return array(
				'map'=>l_s('variants/Classic/resources/smallmap.png'),
				'army'=>l_s('contrib/smallarmy.png'),
				'fleet'=>l_s('contrib/smallfleet.png'),
				'names'=>l_s('variants/Classic/resources/smallmapNames.png'),
				'standoff'=>l_s('images/icons/cross.png')
			);
		}
		else
		{
			return array(
				'map'=>l_s('variants/Classic/resources/map.png'),
				'army'=>l_s('contrib/army.png'),
				'fleet'=>l_s('contrib/fleet.png'),
				'names'=>l_s('variants/Classic/resources/mapNames.png'),
				'standoff'=>l_s('images/icons/cross.png')
			);
		}
	}
}

?>