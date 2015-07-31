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

// http://doc.spip.org/@exec_admin_plugin
function exec_admin_plugin() {
	global $connect_statut;
	global $connect_toutes_rubriques;
	global $spip_lang_right;
	$surligne = "";

	if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page(_T('icone_admin_plugin'), "configuration", "plugin");
		echo _T('avis_non_acces_page');
		echo fin_gauche(), fin_page();
		exit;
	}

	verif_plugin();
	if (isset($_GET['surligne']))
		$surligne = $_GET['surligne'];

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('icone_admin_plugin'), "configuration", "plugin");
	echo "<style type='text/css'>\n";
	echo <<<EOF
div.cadre-padding ul li {
	list-style:none ;
}
div.cadre-padding ul {
	padding-left:1em;
	margin:.5em 0 .5em 0;
}
div.cadre-padding ul ul {
	border-left:5px solid #DFDFDF;
}
div.cadre-padding ul li li {
	margin:0;
	padding:0 0 0.25em 0;
}
div.cadre-padding ul li li div.nomplugin {
	border:1px solid #AFAFAF;
	padding:.3em .3em .6em .3em;
	font-weight:normal;
}
div.cadre-padding ul li li div.nomplugin a {
	outline:0;
	outline:0 !important;
	-moz-outline:0 !important;
}
div.cadre-padding ul li li div.nomplugin_on {
	background: #edf3fe /* couleur claire a remettre avec une CSS */
}
div.cadre-padding ul li li div.nomplugin_on>a {
	font-weight:bold;
}

div.cadre-padding div.droite label {
	padding:.3em;
	background:#EFEFEF;
	border:1px dotted #95989F !important;
	border:1px solid #95989F;
	cursor:pointer;
	margin:.2em;
	display:block;
	width:10.1em;
}
div.cadre-padding input {
	cursor:pointer;
}
div.detailplugin {
	border-top:1px solid #B5BECF;
	padding:.6em;
	background:#F5F5F5;
}
div.detailplugin hr {
	border-top:1px solid #67707F;
	border-bottom:0;
	border-left:0;
	border-right:0;
	}
div.nomplugin label {display:none;}
EOF;
	echo "</style>\n";
	echo "<br/><br/><br/>";
	
	echo gros_titre(_T('icone_admin_plugin'),'',false);
	// barre_onglets("configuration", "plugin"); // a creer dynamiquement en fonction des plugin charges qui utilisent une page admin ?
	
	echo debut_gauche('plugin',true);
	echo debut_boite_info(true);
	$s = "";
	$s .= _T('info_gauche_admin_tech');
	$s .= "<p><img src='"._DIR_IMG_PACK . "puce-poubelle.gif' width='9' height='9' alt='dev' /> "._T('plugin_etat_developpement')."</p>";
	$s .= "<p><img src='"._DIR_IMG_PACK . "puce-orange.gif' width='9' height='9' alt='dev' /> "._T('plugin_etat_test')."</p>";
	$s .= "<p><img src='"._DIR_IMG_PACK . "puce-verte.gif' width='9' height='9' alt='dev' /> "._T('plugin_etat_stable')."</p>";
	$s .= "<p><img src='"._DIR_IMG_PACK . "puce-rouge.gif' width='9' height='9' alt='dev' /> "._T('plugin_etat_experimental')."</p>";
	echo $s;
	echo fin_boite_info(true);

	// on fait l'installation ici, cela permet aux scripts d'install de faire des affichages ...
	installe_plugins();

	echo debut_droite('plugin',true);
	if (isset($GLOBALS['meta']['plugin_erreur_activation'])){
		echo $GLOBALS['meta']['plugin_erreur_activation'];
		effacer_meta('plugin_erreur_activation');
	}

	echo debut_cadre_relief('',true);

	echo "<table border='0' cellspacing='0' cellpadding='5' width='100%'>";
	echo "<tr><td class='toile_foncee' colspan='4'><b>";
	echo "<span style='color: #ffffff;' class='verdana1 spip_medium'>", _T('plugins_liste')."</span></b></td></tr>";

	echo "<tr><td class='serif' colspan='4'>";
	echo _T('texte_presente_plugin');

	$action = generer_action_auteur('activer_plugins','activer',generer_url_ecrire("admin_plugin"));
	echo "<form action='$action' method='post' >";
	echo form_hidden($action);
	echo "<div style='text-align:$spip_lang_right'>";
	echo "<input type='submit' name='Valider' value='"._T('bouton_valider')."' class='fondo' />";
	echo "</div>";

	affiche_arbre_plugins(liste_plugin_files(),liste_chemin_plugin_actifs());

	echo "\n<br />";

	echo "<div style='text-align:$spip_lang_right'>";
	echo "<input type='submit' name='Valider' value='"._T('bouton_valider')."' class='fondo' />";
	echo "</div>";

	echo "</form></tr></table>\n";

	echo "<br />";

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
		$output .= $visible? bouton_block_visible($chemin):bouton_block_invisible($chemin);
		$output .= "<span onclick=\"jQuery(this).prev().click();\">$chemin</span>\n";

		$output .= $visible? debut_block_visible($chemin):debut_block_invisible($chemin);

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
	echo http_script("
	jQuery(function(){
		jQuery('input.check').click(function(){
			jQuery(this).parent().toggleClass('nomplugin_on');
		});
		jQuery('div.nomplugin a[@rel=info]').click(function(){
			if (!jQuery(this).siblings('div.info').html()) {
				jQuery(this).siblings('div.info').prepend(ajax_image_searching).load(
					jQuery(this).attr('href').replace(/admin_plugin/, 'info_plugin')
				);
			} else {
				jQuery(this).siblings('div.info').toggle();
			}
			return false;
		});
		var pack=jQuery('div.spip_pack');
		jQuery(pack).find('a').hide();
		jQuery(pack).find('img').bind('click',function(){jQuery(this).siblings('a').toggle();});
	});
	");

	echo "<ul>";
	while (count($liste_plugins) && $maxiter--){
		// le rep suivant
		$dir = dirname(reset($liste_plugins));
		if ($dir != $current_dir)
			echo tree_open_close_dir($current_dir,$dir,$deplie);
			
		// d'abord tous les plugins du rep courant
		if (isset($dir_index[$current_dir]))
			foreach($dir_index[$current_dir] as $key){
				$plug = $liste_plugins[$key];
				$actif = @isset($fast_liste_plugins_actifs[$plug]);
				$id = substr(md5($plug),0,16);
				echo "<li>";
				echo ligne_plug(substr($plug,strlen($racine)+1), $actif, $id);
				echo "</li>\n";
				unset($liste_plugins[$key]);
			}
	}
	echo tree_open_close_dir($current_dir,$init_dir);
	echo "</ul>";
}

// http://doc.spip.org/@ligne_plug
function ligne_plug($plug_file, $actif, $id){
	global $spip_lang_right;
	static $id_input=0;

	$erreur = false;
	$vals = array();
	$info = plugin_get_infos($plug_file);
	$s = "<div class='nomplugin ".($actif?'nomplugin_on':'')."'>";
	if (isset($info['erreur'])){
		$s .=  "<div class='toile_claire'>";
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
		$s .= "<input type='checkbox' name='$name' value='O' id='label_$id_input'";
		$s .= $actif?" checked='checked'":"";
		$s .= " class='check' />";
		$s .= "<label for='label_$id_input'>"._T('activer_plugin')."</label>";
	}
	$id_input++;

	//$s .= bouton_block_invisible("$plug_file");
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
