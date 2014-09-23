<?php


defined('IN_CODE') or die('This script can not be run by itself.');

class Modern2Variant_adjudicatorPreGame extends adjudicatorPreGame {

	protected $countryUnits = array(
			'Spain'   => array(),
			'Egypt'   => array(),
			'Poland'  => array(),
  			'Britain' => array(),
  			'France'  => array(),
  			'Italy'   => array(),
  			'Germany' => array(),
  			'Turkey'  => array(),
			'Ukraine' => array(),
  			'Russia'  => array()
		);

	// Disabled; no initial units or occupations
	protected function assignUnits() { }
	protected function assignUnitOccupations() { }

}