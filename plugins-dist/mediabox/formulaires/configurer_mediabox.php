<?php
/*
 * Plugin xxx
 * (c) 2009 xxx
 * Distribue sous licence GPL
 *
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('mediabox_pipelines');

function box_lister_skins(){
	$skins = array('none'=>array('nom'=>_T('mediabox:label_aucun_style')));

	$maxfiles = 1000;
	$liste_fichiers = array();
	$recurs = array();
	foreach (creer_chemin() as $d) {
		$f = $d."colorbox/";
		if (@is_dir($f)){
			$liste = preg_files($f,"colorbox[.]css$",$maxfiles-count($liste_fichiers),$recurs);
			foreach($liste as $chemin){
				$nom = substr(dirname($chemin),strlen($f));
				// ne prendre que les fichiers pas deja trouves
				// car find_in_path prend le premier qu'il trouve,
				// les autres sont donc masques
				if (!isset($liste_fichiers[$nom]))
					$liste_fichiers[$nom] = $chemin;
			}
		}
	}
	foreach($liste_fichiers as $short=>$fullpath){
		$skins[$short] = array('nom'=>basename($short));
		if (file_exists($f = dirname($fullpath)."/vignette.jpg"))
			$skins[$short]['img'] = $f;
	}
	return $skins;
}

function box_choisir_skin($skins,$selected,$name='skin'){
	$out = "";
	if (!is_array($skins) OR !count($skins))
		return $out;
	foreach($skins as $k=>$skin){
		$id = "${name}_".preg_replace(",[^a-z0-9_],i", "_", $k);
		$sel = ($selected=="$k" ?" checked='checked'":'');
		$balise_img = chercher_filtre('balise_img');
		$label = isset($skin['img'])?
		  '<a href="'.$skin['img'].'" class="mediabox" rel="habillage">'.$balise_img($skin['img'],$skin['nom'])."</a>"
			:$skin['nom'];

		$out .= "<div class='choix'>";
		$out .= "<input type='radio' name='$name' id='$id' value='$k'$sel />";
		$out .= "<label for='$id'>$label</label>";
		$out .= "</div>\n";
	}
	return $out;
}


function formulaires_configurer_mediabox_charger_dist(){
	$valeurs = mediabox_config(true);
	$valeurs['_skins'] = box_lister_skins();
	
	return $valeurs;
}

function formulaires_configurer_mediabox_traiter_dist(){
	$config = mediabox_config(true);

	include_spip('inc/meta');
	if (_request('reinit')){
		foreach ($config as $k=>$v){
			set_request($k);
		}
		effacer_meta('mediabox');
	}
	else {
		// cas particulier de la checkbox :
		if (!_request('active'))
			set_request('active','non');
		foreach ($config as $k=>$v){
			if (!is_null(_request($k)))
				$config[$k] = _request($k);
		}
		ecrire_meta('mediabox',serialize($config));
	}
	
	return array('message_ok'=>_T('config_info_enregistree'),'editable'=>true);
}

?>
