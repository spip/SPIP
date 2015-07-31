<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('inc/charsets');
include_spip('inc/texte');

// http://doc.spip.org/@ligne_plug
function plugins_afficher_plugin_distant_dist($url_page, $zip_file, $info, $expose=false, $class_li="item"){
	static $id_input=0;
	static $versions = array();
	static $charger_plugin = null;

	$erreur = false;
	$s = "";

	$titre = $info[0];
	$url_doc = $info[1];
	$info = $info[2]; // recuperer le tableau

	$titre = typo('<multi>'.$titre.'</multi>'); // recuperer les blocs multi du flux de la zone (temporaire?)
	$nick = strtolower(basename($zip_file, '.zip'));
	$info['prefix'] = $nick;
	$plug_file = $zip_file;
	
	// numerotons les occurences d'un meme prefix
	$versions[$info['prefix']] = isset($versions[$info['prefix']]) ? $versions[$info['prefix']] + 1 : '';
	$id = $info['prefix'] . $versions[$info['prefix']];
	
	$class = $class_li;
	$class .= $actif?" actif":"";
	$class .= $expose?" on":"";
	$erreur = isset($info['erreur']);
	if ($erreur)
		$class .= " erreur";
	$s .= "<li id='$id' class='$class'>";


	// checkbox pour activer ou desactiver
	// si $actif vaut -1, c'est actif, et ce n'est pas desactivable (extension)
	/*
	if (!$erreur AND $actif>=0){
		$id_input++;
		$check = "\n<input type='radio' name='url_zip_plugin' id='label_$id_input' value='O'";
		$check .= $actif?" checked='checked'":"";
		$check .= " class='checkbox' />";
		$check .= "<label for='label_$id_input'>".$titre."</label>";
		$s .= "<div class='check'>$check</div>";
	}*/

	if (!$erreur){
		// bouton de telechargement
		if (!$charger_plugin)
			$charger_plugin = generer_action_auteur('charger_plugin',"charger_zip","./");
		$balise_img = chercher_filtre('balise_img');
		$action = parametre_url($charger_plugin,'url_zip_plugin',$plug_file);
		$s .= "<div class='download'>".
		"<a href='$action' title='"._T('plugin_charger')."'>"
		. $balise_img(find_in_path("images/telecharger-16.png"),_T('plugin_charger'))
		."</a></div>"
		;
	}

	// Cartouche Resume
	$s .= "<div class='resume'>";

	$desc = $info['descriptif'];
	$url_stat = parametre_url($url_page, "plugin",$plug_file);

	$s .= "<h3 class='nom'><a href='$url_stat' rel='info'>".$titre."</a></h3>";
	$s .= "<div class='short'>".couper($desc,60)."</div>";
	if (isset($info['icon']) and $info['icon']) {
		include_spip("inc/filtres_images_mini");
		$s.= "<div class='icon'><a href='$url_stat' rel='info'><img src='".$info['icon']."' width='32' height='auto' /></a></div>";
	}
	$s .= "</div>";

	if ($erreur){
		$s .=  "<div class='erreur'>";
		foreach($info['erreur'] as $err)
			$s .= "$err <br/>";
		$s .=  "</div>";
	}

	// afficher les details d'un plug en secours ; la div sert pour l'ajax
	$s .= "<div class='details'>";
	if ($expose)
		$s .= affiche_bloc_plugin_distant($plug_file, $info);
	$s .= "</div>";

	$s .= "</li>";
	return $s;
}

// http://doc.spip.org/@affiche_bloc_plugin
function affiche_bloc_plugin_distant($plug_file, $info) {
	//recuperer_fond('prive/contenu/item_rss_plugin',$item)

	$s = "";
	if (isset($info['descriptif']))
		$s .= "<div class='desc'>".$info['descriptif']. "</div>";

	if (isset($info['lesauteurs']) AND trim($info['lesauteurs']))
		$s .= "<div class='auteurs'>" . _T('public:par_auteur') .' '. $info['lesauteurs'] . "</div>";
	if (isset($info['licence']))
		$s .= "<div class='licence'>" . _T('intitule_licence') .' '. $info['licence'] . "</div>";

	if (trim($info['url'])) {
		$lien = $info['url'];
		if (!preg_match(',^https?://,iS', $lien))
			$lien = extraire_attribut(extraire_balise($lien,'a'),'href');
		$s .= "<div class='site'><a href='$lien' class='spip_out'>" . _T('en_savoir_plus') .'</a></div>';
	}

	//
	// Ajouter les infos techniques
	//
	$infotech = array();

	// source zip le cas echeant
	$source = _T('plugin_source').' '.trim($plug_file);

	$s .= "<div class='tech'>"
		. $source
		."</div>";


	return $s;
}
?>
