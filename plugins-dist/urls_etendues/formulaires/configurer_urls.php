<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

function formulaires_configurer_urls_charger_dist(){
	if ($GLOBALS['type_urls'] != 'page') // fixe par mes_options
		return "<p>"._T('urls:erreur_config_url_forcee')."</p>";

	$valeurs = array(
		'type_urls' => $GLOBALS['meta']['type_urls'],
		'urls_activer_controle' => $GLOBALS['meta']['urls_activer_controle'],
		'_urls_dispos'=>type_urls_lister(),
	);

	return $valeurs;

}

function formulaires_configurer_urls_traiter_dist(){
	ecrire_meta('type_urls',_request('type_urls'));
	ecrire_meta('urls_activer_controle',_request('urls_activer_controle')?'oui':'non');

	return array('message_ok'=>_T('config_info_enregistree'),'editable'=>true);
}

function type_url_choisir($liste,$name,$selected){
	$res = '<dl class="choix">';
	foreach($liste as $url){
		$k = $url[0];
		$res .= '<dt>'
			.'<input type="radio" name="'.$name.'" id="'.$name.'_'.$k.'" value="'.$k.'"'
			.($selected==$k ? ' checked="checked"':'')
			.'/>'
			.'<label for="'.$name.'_'.$k.'">'.$url[1].'</label></dt>'
		  .'<dd><tt>'.$url[2].'</tt></dd>'
		  ."\n";
	}
	$res .= "</dl>";
	return $res;
}

function type_urls_lister(){

	$dispo = array();
	foreach (find_all_in_path('urls/', '\w+\.php$', array()) as $f) {
		$r = basename($f, '.php');
		if ($r == 'index' OR strncmp('generer_',$r,8)==0) continue;
		include_once $f;
		$exemple = 'URLS_' . strtoupper($r) . '_EXEMPLE';
		$exemple = defined($exemple) ? constant($exemple) : '?';
		$dispo[_T("urls:titre_type_$r")] = array($r, _T("urls:titre_type_$r"),$exemple);
	}

	ksort($dispo);

	return $dispo;
}
?>