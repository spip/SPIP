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

	verif_plugin();

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('icone_admin_plugin'), "configuration", "plugin");
	

	// barre_onglets("configuration", "plugin"); // a creer dynamiquement en fonction des plugin charges qui utilisent une page admin ? // cfg
	
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

	echo debut_droite('plugin', true);

	echo gros_titre(_T('icone_admin_plugin'),'',false);

	echo "<br /><br />\n";

	echo debut_cadre_trait_couleur('plugin-24.gif',true,'',_T('plugins_liste'),
		'liste_plugins');
	echo _T('texte_presente_plugin');

	$lpf = liste_plugin_files();
	$lcpa = liste_chemin_plugin_actifs();


	$sub = "\n<div style='text-align:".$GLOBALS['spip_lang_right']."'>"
	.  "<input type='submit' value='"._T('bouton_valider')."' class='fondo' />"
	. "</div>";


	// S'il y a plus de 10 plugins pas installes, les signaler a part ;
	// mais on affiche tous les plugins mis a la racine
	if (count($lpf) - count($lcpa) > 9
	AND _request('afficher_tous_plugins') != 'oui') {
		$lcpaffiche = array();
		foreach ($lpf as $f)
			if (!strpos($f, '/') OR in_array($f, $lcpa))
				$lcpaffiche[] = $f;
		$corps = "<p>"._L(count($lcpa).' plugins activ&#233;s.')."</p>\n"
			. "<p><a href='". parametre_url(self(),'afficher_tous_plugins', 'oui') ."'>"._L(count($lpf).' plugins disponibles.')."</a></p>\n"
			. affiche_arbre_plugins($lcpaffiche, $lcpa);

	} else {
		$corps = 
			"<p>"._L(count($lcpa).' plugins activ&#233;s')."&nbsp;;\n"
			. ""._L(count($lpf).' plugins disponibles.')."</p>\n"
			. (count($lpf)>20 ? $sub : '')
			. affiche_arbre_plugins($lpf, $lcpa);
	}


	$corps .= "\n<br />" . $sub;

	echo redirige_action_auteur('activer_plugins','activer','admin_plugin','', $corps, " method='post'");

	echo fin_cadre_trait_couleur(true);

	if (include_spip('inc/charger_plugin')) {
		echo formulaire_charger_plugin($retour);
	}

	echo fin_gauche(), fin_page();


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
function affiche_arbre_plugins($liste_plugins,$liste_plugins_actifs){
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
	$res .= tree_open_close_dir($current_dir,$init_dir);

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
		var pack=jQuery('div.spip_pack');
		jQuery(pack).find('a').hide();
		jQuery(pack).find('img').bind('click',function(){jQuery(this).siblings('a').toggle();});
	});
	")
	.  "<ul>"
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
	$versions[$info['prefix']] = isset($versions[$info['prefix']]) ?
			$versions[$info['prefix']] + 1 : '';
	$s = "<a name='" . $info['prefix'] . $versions[$info['prefix']] . 
		"'></a><div class='nomplugin ".($actif?'nomplugin_on':'')."'>";
	if (isset($info['erreur'])){
		$s .=  "<div class='plugin_erreur'>";
		$erreur = true;
		foreach($info['erreur'] as $err)
			$s .= "/!\ $err <br/>";
		$s .=  "</div>";
	}
	
	// bouton de desinstallation
	if ($actif && plugin_est_installe($plug_file)){
		$s .= "<div style='float:$spip_lang_right' class='spip_pack'>";
		$action = generer_action_auteur('desinstaller_plugin',$plug_file,generer_url_ecrire('admin_plugin'));
		$s .= "<a href='$action'>"._T('bouton_effacer_tout')."</a>";
		$s .= http_img_pack('spip-pack-24.png','spip-pack','','spip-pack');
		$s .= "</div>";
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
		$s .= "\n<input type='checkbox' name='$name' value='O' id='label_$id_input'";
		$s .= $actif?" checked='checked'":"";
		$s .= " class='check' />";
		$s .= "\n<label for='label_$id_input'>"._T('activer_plugin')."</label>";
	}
	$id_input++;

	$url_stat = generer_url_ecrire(_request('exec'),"plug=".urlencode($plug_file));
	$s .= "<a href='$url_stat' rel='info'>$nom</a>";

	$s .= "<div class='info'>";
	// afficher les details d'un plug en secours
	if (urldecode(_request('plug'))==$plug_file)
		$s .= affiche_bloc_plugin($plug_file, $info);
	$s .= "</div>";

	$s .= "</div>";
	return $s;
}
?>
