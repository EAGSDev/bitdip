<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * The default "classic" Diplomacy; Europe etc.
 */
class ClassicVariant extends WDVariant {
	public $id=1;
	public $mapID=1;
	public $name='Classic';
	public $fullName='Classic';
	public $description='The standard Diplomacy map of Europe.';
	public $author='Avalon Hill';

	public $countries=array('England', 'France', 'Italy', 'Germany', 'Austria', 'Turkey', 'Russia');

	public function __construct() {
		parent::__construct();

		// drawMap extended for country-colors and loading the classic map images
		$this->variantClasses['drawMap'] = 'Classic';

		/*
		 * adjudicatorPreGame extended for country starting unit data
		 */
		$this->variantClasses['adjudicatorPreGame'] = 'Classic';
	}

	public function turnAsDate($turn) {
		if ( $turn==-1 ) return l_t("Pre-game");
		else return ( $turn % 2 ? l_t("Autumn").", " : l_t("Spring").", " ).(floor($turn/2) + 1901);
	}

	public function turnAsDateJS() {
		return 'function(turn) {
			if( turn==-1 ) return l_t("Pre-game");
			else return ( turn%2 ? l_t("Autumn")+", " : l_t("Spring")+", " )+(Math.floor(turn/2) + 1901);
		};';
	}
}

?>