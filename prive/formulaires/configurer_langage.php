<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function formulaires_configurer_langage_charger_dist(){

	include_spip('inc/lang');
	$GLOBALS['meta']['langues_proposees'] = '';
	init_langues();
	$langues = explode(',', $GLOBALS['meta']['langues_proposees']);

	
	$valeurs = array(
		'var_lang_ecrire'=>$GLOBALS['spip_lang'],
		'_langues'=>$langues
	);
		
	return $valeurs;
}


function formulaires_configurer_langage_traiter_dist(){
	include_spip('action/converser');
	action_converser_changer_langue();

	return array('message_ok'=>_T('config_info_enregistree'),'editable'=>true);
}

function afficher_langues_choix($langues,$name,$id,$selected){
	include_spip('inc/lang');
	$ret = '';
	sort($langues);
	foreach ($langues as $l) {
		$checked = ($l == $selected) ? ' checked=\'checked\'' : '';
		$ret .= "<div class='choix'>"
		 . "<input type='radio' name='$name' id='{$id}_$l' value='$l'$checked>"
		 . "<label for='{$id}_$l'>" . traduire_nom_langue($l)."</label>"
		 . "</div>";
	}
	return $ret;
}