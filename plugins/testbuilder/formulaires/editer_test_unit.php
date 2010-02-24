<?php
/*
 * Plugin xxx
 * (c) 2009 xxx
 * Distribue sous licence GPL
 *
 */

include_spip('inc/tb_lib');
function formulaires_editer_test_unit_charger_dist($filename,$funcname){
	$valeurs = array('essais'=>array(),'_args'=>array(),'args'=>array());

	// recuperer les tests precedents si possible
	if ($filetest=tb_hastest($funcname)){
		$valeurs['essais'] = tb_test_essais($funcname,$filetest);
		$valeurs['_hidden'] = "<input type='hidden' name='ctrl_essais' value='".md5(serialize($valeurs['essais']))."' />";
	}

	$funcs = tb_liste_fonctions($filename);
	$valeurs['_args'] = reset($funcs[$funcname]);
	$valeurs['args'] = array();

	return $valeurs;
}

?>