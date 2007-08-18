<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/config');
include_spip('inc/plugin');
include_spip('inc/presentation');
include_spip('inc/layer');
include_spip('inc/actions');
include_spip('inc/securiser_action');

// http://doc.spip.org/@exec_admin_plugin_dist
function exec_admin_plugin_dist($retour='') {

	if (!autoriser('configurer', 'plugins')) {
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}
	
	$format = '';
	if (_request('format')!==NULL)
		$format = _request('format');

	verif_plugin();

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('icone_admin_plugin'), "configuration", "plugin");
	

	echo "<br />\n";
	echo "<br />\n";


	echo gros_titre(_T('icone_admin_plugin'),'',false);

	
	echo debut_gauche('plugin',true);
	echo debut_boite_info(true);
	$s = "";
	$s .= _T('info_gauche_admin_tech');
	$s .= "<p><img src='"._DIR_IMG_PACK . "puce-verte.gif' width='9' height='9' alt='' /> "._T('plugin_etat_stable')."</p>";
	$s .= "<p><img src='"._DIR_IMG_PACK . "puce-orange.gif' width='9' height='9' alt='' /> "._T('plugin_etat_test')."</p>";
	$s .= "<p><img src='"._DIR_IMG_PACK . "puce-poubelle.gif' width='9' height='9' alt='' /> "._T('plugin_etat_developpement')."</p>";
	$s .= "<p><img src='"._DIR_IMG_PACK . "puce-rouge.gif' width='9' height='9' alt='' /> "._T('plugin_etat_experimental')."</p>";
	echo $s;
	echo fin_boite_info(true);

	// on fait l'installation ici, cela permet aux scripts d'install de faire des affichages ...
	installe_plugins();


	// Si on a CFG, ajoute un lien (oui c'est mal)
	if (defined('_DIR_PLUGIN_CFG')) {
		echo debut_cadre_enfonce('',true);
		echo icone_horizontale('CFG &ndash; '._T('configuration'), generer_url_ecrire('cfg'), _DIR_PLUGIN_CFG.'cfg-22.png', '', false);
		echo fin_cadre_enfonce(true);
	}

	// Lister les librairies disponibles
	if ($libs = liste_librairies()) {
		debut_cadre_enfonce('', '', '', _L('Librairies install&#233;es'));
		ksort($libs);
		foreach ($libs as $lib => $rep)
		echo "<dt>$lib</dt><dd>".joli_repertoire($rep)."</dd>";
		echo fin_cadre_enfonce(true);
	}

	echo debut_droite('plugin', true);

	$lpf = liste_plugin_files();
	$lcpa = liste_chemin_plugin_actifs();

	if ($lpf) {
		echo debut_cadre_trait_couleur('plugin-24.gif',true,'',_T('plugins_liste'),
		'liste_plugins');
		echo _T('texte_presente_plugin');


		$sub = "\n<div style='text-align:".$GLOBALS['spip_lang_right']."'>"
		.  "<input type='submit' value='"._T('bouton_valider')
		."' class='fondo' />" . "</div>";


		// S'il y a plus de 10 plugins pas installes, les signaler a part ;
		// mais on affiche tous les plugins mis a la racine ou dans auto/
		if (count($lpf) - count($lcpa) > 9
		AND _request('afficher_tous_plugins') != 'oui') {

			$dir_auto = substr(_DIR_PLUGINS_AUTO, strlen(_DIR_PLUGINS));
			$lcpaffiche = array();
			foreach ($lpf as $f)
				if (!strpos($f, '/')
				OR ($dir_auto AND substr($f, 0, strlen($dir_auto)) == $dir_auto)
				OR in_array($f, $lcpa))
					$lcpaffiche[] = $f;
			if (count($lcpaffiche)<10 && !$format) $format = 'liste';
			$lien_format = $format!='liste' ?
			  "<a href='".parametre_url(self(),'format','liste')."'>"._L('Liste')."</a>"
			  :"<a href='".parametre_url(self(),'format','arbre')."'>"._L('Hierarchie')."</a>";
			$corps = "<p>$lien_format | "._L(count($lcpa).' plugins activ&#233;s.')."\n"
				. " | <a href='". parametre_url(self(),'afficher_tous_plugins', 'oui') ."'>"._L(count($lpf).' plugins disponibles.')."</a></p>\n"
				. affiche_les_plugins($lcpaffiche, $lcpa, $format);

		} else {
			$lien_format = $format!='liste' ?
			  "<a href='".parametre_url(self(),'format','liste')."'>"._L('Liste')."</a>"
			  :"<a href='".parametre_url(self(),'format','arbre')."'>"._L('Hierarchie')."</a>";
			$corps = 
				"<p>$lien_format | "
				."<a href='". parametre_url(self(),'afficher_tous_plugins', '') ."'>" . _L(count($lcpa).' plugins activ&#233;s')."</a> | \n"
				. ""._L(count($lpf).' plugins disponibles.')
				. "</p>\n"
				. (count($lpf)>20 ? $sub : '')
				. affiche_les_plugins($lpf, $lcpa, $format);
		}

		$corps .= "\n<br />" . $sub;

		echo redirige_action_auteur('activer_plugins','activer','admin_plugin','', $corps, " method='post'");

		echo fin_cadre_trait_couleur(true);

	}

	if (include_spip('inc/charger_plugin')) {
		echo formulaire_charger_plugin($retour);
	}

	echo fin_gauche(), fin_page();


}

// http://doc.spip.org/@affiche_les_plugins
function affiche_les_plugins($liste_plugins, $liste_plugins_actifs, $format='arbre'){
	if ($format=='liste'){
		$liste_plugins = array_flip($liste_plugins);
		foreach(array_keys($liste_plugins) as $chemin) {
			$info = plugin_get_infos($chemin);
			$liste_plugins[$chemin] = strtoupper(trim(typo(translitteration(unicode2charset(html2unicode($info['nom']))))));
		}
		asort($liste_plugins);
		$res = affiche_liste_plugins($liste_plugins,$liste_plugins_actifs);
	}
	else
		$res = affiche_arbre_plugins($liste_plugins,$liste_plugins_actifs);
	return http_script("
	jQuery(function(){
		jQuery('input.check').click(function(){
			jQuery(this).parent().toggleClass('nomplugin_on');
		});
		jQuery('div.nomplugin a[@rel=info]').click(function(){
			var prefix = jQuery(this).parent().prev().attr('name');
			if (!jQuery(this).siblings('div.info').html()) {
				jQuery(this).siblings('div.info').prepend(ajax_image_searching).load(
					jQuery(this).attr('href').replace(/admin_plugin|plugins/, 'info_plugin'), {},
					function() {
						document.location = '#' + prefix;
					}
				);
			} else {
				if (jQuery(this).siblings('div.info').toggle().attr('display') != 'none') {
					document.location = '#' + prefix;
				}
			}
			return false;
		});
	});
	") . $res;
}

// http://doc.spip.org/@affiche_block_initiale
function affiche_block_initiale($initiale,$block,$block_actif){
	if (strlen($block)){
		return "<li>"
		  . bouton_block_depliable($initiale,$block_actif?true:false)
		  . debut_block_depliable($block_actif)
		  . "<ul>$block</ul>"
		  . fin_block()
		  . "</li>";
	}
	return "";
}

// http://doc.spip.org/@affiche_liste_plugins
function affiche_liste_plugins($liste_plugins, $liste_plugins_actifs){
	$block_par_lettre = count($liste_plugins)>10;
	$fast_liste_plugins_actifs = array_flip($liste_plugins_actifs);
	$maxiter=1000;
	$res = '';
	$block = '';
	$initiale = '';
	$block_actif = false;
	foreach($liste_plugins as $plug => $nom){
		if (($i=substr($nom,0,1))!==$initiale){
			$res .= $block_par_lettre ? affiche_block_initiale($initiale,$block,$block_actif): $block;
			$initiale = $i;
			$block = '';
			$block_actif = false;
		}
		// le rep suivant
		$actif = @isset($fast_liste_plugins_actifs[$plug]);
		$block_actif = $block_actif | $actif;
		$id = substr(md5($plug),0,16);
		$block .= "<li>"
			. ligne_plug($plug, $actif, $id)
			. "</li>\n";
	}
	$res .= $block_par_lettre ? affiche_block_initiale($initiale,$block,$block_actif): $block;
	return "<ul>"
	. $res
	. "</ul>";
}

// http://doc.spip.org/@tree_open_close_dir
function tree_open_close_dir(&$current,$target,$deplie=array()){
	if ($current == $target) return "";
	$tcur = explode("/",$current);
	$ttarg = explode("/",$target);
	$tcom = array();
	$output = "";
	// la partie commune
	while (reset($tcur)==reset($ttarg)){
		$tcom[] = array_shift($tcur);
		array_shift($ttarg);
	}
	// fermer les repertoires courant jusqu'au point de fork
	while($close = array_pop($tcur)){
		$output .= "</ul>\n";
		$output .= fin_block();
		$output .= "</li>\n";
	}
	$chemin = "";
	if (count($tcom))
		$chemin .= implode("/",$tcom)."/";
	// ouvrir les repertoires jusqu'a la cible
	while($open = array_shift($ttarg)){
		$visible = @isset($deplie[$chemin.$open]);
		$chemin .= $open . "/";
		$output .= "<li>";
		$output .= bouton_block_depliable($chemin,$visible);
		$output .= debut_block_depliable($visible);

		$output .= "<ul>\n";
	}
	$current = $target;
	return $output;
}

// http://doc.spip.org/@affiche_arbre_plugins
function affiche_arbre_plugins($liste_plugins, $liste_plugins_actifs){
	$racine = basename(_DIR_PLUGINS);
	$init_dir = $current_dir = "";
	// liste des repertoires deplies : construit en remontant l'arbo de chaque plugin actif
	// des qu'un path est deja note deplie on s'arrete
	$deplie = array($racine=>true);
	$fast_liste_plugins_actifs=array();
	foreach($liste_plugins_actifs as $key=>$plug){
		$fast_liste_plugins_actifs["$racine/$plug"]=true;
		$dir = dirname("$racine/$plug");$maxiter=100;
		while(strlen($dir) && !isset($deplie[$dir]) && $dir!=$racine && $maxiter-->0){
			$deplie[$dir] = true;
			$dir = dirname($dir);
		}
	}
	
	// index repertoires --> plugin
	$dir_index=array();
	foreach($liste_plugins as $key=>$plug){
		$liste_plugins[$key] = "$racine/$plug";
		$dir_index[dirname("$racine/$plug")][] = $key;
	}
	
	$visible = @isset($deplie[$current_dir]);
	$maxiter=1000;

	$res = '';
	while (count($liste_plugins) && $maxiter--){
		// le rep suivant
		$dir = dirname(reset($liste_plugins));
		if ($dir != $current_dir)
			$res .= tree_open_close_dir($current_dir,$dir,$deplie);
			
		// d'abord tous les plugins du rep courant
		if (isset($dir_index[$current_dir]))
			foreach($dir_index[$current_dir] as $key){
				$plug = $liste_plugins[$key];
				$actif = @isset($fast_liste_plugins_actifs[$plug]);
				$id = substr(md5($plug),0,16);
				$res .= "<li>"
				. ligne_plug(substr($plug,strlen($racine)+1), $actif, $id)
				. "</li>\n";
				unset($liste_plugins[$key]);
			}
	}
	$res .= tree_open_close_dir($current_dir,$init_dir, true);

	return "<ul>"
	. $res
	. "</ul>";
}

// http://doc.spip.org/@ligne_plug
function ligne_plug($plug_file, $actif, $id){
	global $spip_lang_right;
	static $id_input=0;
	static $versions = array();

	$erreur = false;
	$vals = array();

	$info = plugin_get_infos($plug_file);

	// plug pour CFG
	if ($actif
	AND defined('_DIR_PLUGIN_CFG')) {
		if (include_spip('inc/cfg') // test CFG version >= 1.0.5
		AND $i = icone_lien_cfg(_DIR_PLUGINS.$plug_file))
			$s .= '<div style="float:right;">'.$i.'</div>';
	}


	$versions[$info['prefix']] = isset($versions[$info['prefix']]) ?
			$versions[$info['prefix']] + 1 : '';
	$s .= "<div id='" . $info['prefix'] . $versions[$info['prefix']] . "' class='nomplugin ".($actif?'nomplugin_on':'')."'>";
	if (isset($info['erreur'])){
		$s .=  "<div class='plugin_erreur'>";
		$erreur = true;
		foreach($info['erreur'] as $err)
			$s .= "/!\ $err <br/>";
		$s .=  "</div>";
	}

	$etat = 'dev';
	if (isset($info['etat']))
		$etat = $info['etat'];
	$nom = typo($info['nom']);

	$id = substr(md5("aide_$plug_file"),0,8);
	$puce_etat = array(
	"dev"=>"<img src='"._DIR_IMG_PACK . "puce-poubelle.gif' width='9' height='9' alt='dev' />",
	"test"=>"<img src='"._DIR_IMG_PACK . "puce-orange.gif' width='9' height='9' alt='dev' />",
	"stable"=>"<img src='"._DIR_IMG_PACK . "puce-verte.gif' width='9' height='9' alt='dev' />",
	"experimental"=>"<img src='"._DIR_IMG_PACK . "puce-rouge.gif' width='9' height='9' alt='dev' />",
	);
	
	if (isset($puce_etat[$etat]))
	$s .= $puce_etat[$etat];

	if (!$erreur){
		$name = 's' . substr(md5("statusplug_$plug_file"),0,16);
		$s .= "\n<input type='checkbox' name='$name' id='label_$id_input' value='O'";
		$s .= $actif?" checked='checked'":"";
		$s .= " class='check' />";
		$s .= "\n<label for='label_$id_input'>"._T('activer_plugin')."</label>";
	}
	$id_input++;

	$url_stat = generer_url_ecrire(_request('exec'),"plug=".urlencode($plug_file));
	$s .= "<a href='$url_stat' rel='info'>$nom</a>";

	// afficher les details d'un plug en secours ; la div sert pour l'ajax
	$s .= "<div class='info'>";
	if (urldecode(_request('plug'))==$plug_file)
		$s .= affiche_bloc_plugin($plug_file, $info);
	$s .= "</div>";

	$s .= "</div>";
	return $s;
}
?>
