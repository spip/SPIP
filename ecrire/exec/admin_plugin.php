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

	// mise a jour des donnees si envoi via formulaire
	// sinon fait une passe de verif sur les plugin
	if (_request('changer_plugin')=='oui'){
		enregistre_modif_plugin();
		// pour la peine, un redirige, 
		// que les plugin charges soient coherent avec la liste
		include_spip('inc/headers');
		redirige_par_entete(generer_url_ecrire(_request('exec')));
	}
	else
		verif_plugin();
	if (isset($_GET['surligne']))
		$surligne = $_GET['surligne'];
	global $couleur_claire;
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('icone_admin_plugin'), "configuration", "plugin");
	$dir_img_pack = _DIR_IMG_PACK;
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
	background:$couleur_claire;
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
span.dev{	background:url({$dir_img_pack}puce-poubelle.gif) top left no-repeat;}
span.test{	background:url({$dir_img_pack}puce-orange.gif) top left  no-repeat;}
span.stable{	background:url({$dir_img_pack}puce-verte.gif) top left  no-repeat;}
span.experimental{	background:url({$dir_img_pack}puce-rouge.gif) top left  no-repeat;}
span.dev,span.test,span.stable,span.experimental{
	display:block;float:left;
	width:9px; height:9px;margin-right:5px;margin-top:5px;
}
div.nomplugin label {display:none;}
EOF;
	echo "</style>\n";
	echo "<br/><br/><br/>";
	
	gros_titre(_T('icone_admin_plugin'));
	// barre_onglets("configuration", "plugin"); // a creer dynamiquement en fonction des plugin charges qui utilisent une page admin ?
	
	debut_gauche();
	debut_boite_info();
	$s = "";
	$s .= _T('info_gauche_admin_tech');
	$s .= "<p><span class='dev'>&nbsp;</span>"._T('plugin_etat_developpement')."</p>";
	$s .= "<p><span class='test'>&nbsp;</span>"._T('plugin_etat_test')."</p>";
	$s .= "<p><span class='stable'>&nbsp;</span>"._T('plugin_etat_stable')."</p>";
	$s .= "<p><span class='experimental'>&nbsp;</span>"._T('plugin_etat_experimental')."</p>";
	echo $s;
	fin_boite_info();

	debut_droite();

	debut_cadre_relief();

	global $couleur_foncee;
	echo "<table border='0' cellspacing='0' cellpadding='5' width='100%'>";
	echo "<tr><td style='background-color: $couleur_foncee' colspan='4'><b>";
	echo "<span style='font-size: 16px; color: #ffffff;' class='verdana1'>", _T('plugins_liste')."</span></b></td></tr>";

	echo "<tr><td class='serif' colspan='4'>";
	echo _T('texte_presente_plugin');

	echo generer_url_post_ecrire(_request('exec'));

	affiche_arbre_plugins(liste_plugin_files(),liste_chemin_plugin_actifs());

	echo "\n<input type='hidden' name='id_auteur' value='$connect_id_auteur' />";
	echo "\n<input type='hidden' name='hash' value='" . calculer_action_auteur("valide_plugin") . "' />";
	echo "\n<input type='hidden' name='changer_plugin' value='oui' />";

	echo "\n<p />";

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
	$(document).ready(
		function(){
			$('input.check').click(function(){\$(this).parent().toggleClass('nomplugin_on');});
		});
	$(document).ready(function(){
		$('div.nomplugin a[@rel=info]').click(function() {
			if (!$(this).siblings('div.info').html()) {
				$(this).siblings('div.info').prepend(ajax_image_searching).load(
					$(this).href().replace(/admin_plugin/, 'info_plugin')
				);
			} else {
				$(this).siblings('div.info').toggle();
			}
			return false;
		});
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
	static $id_input=0;

	$erreur = false;
	$vals = array();
	$info = plugin_get_infos($plug_file);
	$s = "<div class='nomplugin ".($actif?'nomplugin_on':'')."'>";
	if (isset($info['erreur'])){
		$s .=  "<div style='background:".$GLOBALS['couleur_claire']."'>";
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
	$s .= "<span class='$etat' title='$etat'>&nbsp;</span>";

	if (!$erreur){
		$s .= "<input type='checkbox' name='statusplug_$plug_file' value='O' id='label_$id_input'";
		$s .= $actif?" checked='checked'":"";
		$s .= " class='check' /> <label for='label_$id_input'>"._T('activer_plugin')."</label>";
	}
	$id_input++;

	//$s .= bouton_block_invisible("$plug_file");
	$url_stat = generer_url_ecrire(_request('exec'),"plug=$plug_file");
	$s .= "<a href='$url_stat' rel='info'>$nom</a>";

	$s .= "<div class='info'>";
	// afficher les details d'un plug en secours
	if (_request('plug')==$plug_file)
		$s .= affiche_bloc_plugin($plug_file, $info);
	$s .= "</div>";

	$s .= "</div>";
	return $s;
}
?>
