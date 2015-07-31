<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/config');
include_spip('inc/plugin');
include_spip('inc/presentation');
include_spip('inc/layer');


// http://doc.spip.org/@exec_admin_plugin
function exec_admin_plugin() {
	global $connect_statut;
	global $connect_toutes_rubriques;
	global $spip_lang_right;
	$surligne = "";

	if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
		debut_page(_T('icone_admin_plugin'), "configuration", "plugin");
		echo _T('avis_non_acces_page');
		fin_page();
		exit;
	}

	// mise a jour des donnees si envoi via formulaire
	// sinon fait une passe de verif sur les plugin
	if (_request('changer_plugin')=='oui'){
		enregistre_modif_plugin();
		// pour la peine, un redirige, 
		// que les plugin charges soient coherent avec la liste
		redirige_par_entete(generer_url_ecrire('admin_plugin'));
	}
	else
		verif_plugin();
	if (isset($_GET['surligne']))
		$surligne = $_GET['surligne'];
	global $couleur_claire;
	debut_page(_T('icone_admin_plugin'), "configuration", "plugin");
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
div.cadre-padding ul li li div.nomplugin, div.cadre-padding ul li li div.nomplugin_on {
	border:1px solid #AFAFAF;
	padding:.3em .3em .6em .3em;
	font-weight:normal;
}
div.cadre-padding ul li li div.nomplugin a, div.cadre-padding ul li li div.nomplugin_on a {
	outline:0;
	outline:0 !important;
	-moz-outline:0 !important;
}
div.cadre-padding ul li li div.nomplugin_on {
	background:$couleur_claire;
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
EOF;
	echo "</style>";

	echo "<br/><br/><br/>";
	
	gros_titre(_T('icone_admin_plugin'));
	// barre_onglets("configuration", "plugin"); // a creer dynamiquement en fonction des plugin charges qui utilisent une page admin ?
	
	debut_gauche();
	debut_boite_info();
	echo _T('info_gauche_admin_tech');
	fin_boite_info();

	debut_droite();

	debut_cadre_relief();

	global $couleur_foncee;
	echo "<table border='0' cellspacing='0' cellpadding='5' width='100%'>";
	echo "<tr><td bgcolor='$couleur_foncee' background='' colspan='4'><b>";
	echo "<font face='Verdana,Arial,Sans,sans-serif' size='3' color='#ffffff'>";
	echo _T('plugins_liste')."</font></b></td></tr>";

	echo "<tr><td class='serif' colspan=4>";
	echo _T('texte_presente_plugin');

	echo generer_url_post_ecrire("admin_plugin");

	echo "<ul>";
	affiche_arbre_plugins(liste_plugin_files(),liste_plugin_actifs());
	echo "</ul>";

	echo "</table></div>\n";

	echo "\n<input type='hidden' name='id_auteur' value='$connect_id_auteur' />";
	echo "\n<input type='hidden' name='hash' value='" . calculer_action_auteur("valide_plugin") . "'>";
	echo "\n<input type='hidden' name='changer_plugin' value='oui'>";

	echo "\n<p>";

	echo "<div style='text-align:$spip_lang_right'>";
	echo "<input type='submit' name='Valider' value='"._T('bouton_valider')."' class='fondo'>";
	echo "</div>";

# ce bouton est trop laid :-)
# a refaire en javascript, qui ne fasse que "decocher" les cases
#	echo "<div style='text-align:$spip_lang_left'>";
#	echo "<input type='submit' name='desactive_tous' value='"._T('bouton_desactive_tout')."' class='fondl'>";
#	echo "</div>";

	echo "</form></tr></table>\n";

	echo "<br />";

	fin_page();

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
		$output .= fin_block();
		$output .= "</ul></li>\n";
	}
	$chemin = "";
	if (count($tcom))
		$chemin .= implode("/",$tcom)."/";
	// ouvrir les repertoires jusqu'a la cible
	while($open = array_shift($ttarg)){
		$visible = @in_array($chemin.$open,$deplie);
		$chemin .= $open . "/";
		$output .= "<li>";
		$output .= $visible? bouton_block_visible($chemin):bouton_block_invisible($chemin);
		$output .= "$chemin\n<ul>";
			
		$output .= $visible? debut_block_visible($chemin):debut_block_invisible($chemin);
	}
	$current = $target;
	return $output;
}

// http://doc.spip.org/@affiche_arbre_plugins
function affiche_arbre_plugins($liste_plugins,$liste_plugins_actifs){
	$racine = basename(_DIR_PLUGINS);
	$init_dir = $current_dir = "";
	// liste des repertoires deplies : construit en remontant l'arbo de chaque plugin actif
	$deplie = array($racine);
	foreach($liste_plugins_actifs as $key=>$plug){
		$liste_plugins_actifs[$key] = "$racine/$plug";
		$dir = dirname("$racine/$plug");$maxiter=100;
		while(strlen($dir) && $dir!=$racine && $maxiter-->0){
			$deplie[] = $dir;
			$dir = dirname($dir);
		}
	}
	
	// index repertoires --> plugin
	$dir_index=array();
	foreach($liste_plugins as $key=>$plug){
		$liste_plugins[$key] = "$racine/$plug";
		$dir_index[dirname("$racine/$plug")][] = $key;
	}
	
	$visible = @in_array($current_dir,$deplie);
	$maxiter=1000;
	while (count($liste_plugins) && $maxiter--){
		// le rep suivant
		$dir = dirname(reset($liste_plugins));
		#$visible = @in_array($dir,$deplie);
		if ($dir != $current_dir)
			echo tree_open_close_dir($current_dir,$dir,$deplie);
			
		// d'abord tous les plugins du rep courant
		if (isset($dir_index[$current_dir]))
			foreach($dir_index[$current_dir] as $key){
				$plug = $liste_plugins[$key];
				$actif = @in_array($plug,$liste_plugins_actifs);
				$id = substr(md5($plug),0,16);
				echo "<li>";
				echo ligne_plug(substr($plug,strlen($racine)+1), $actif, $id);
				echo "</li>\n";
				unset($liste_plugins[$key]);
			}
	}
	echo tree_open_close_dir($current_dir,$init_dir);
}

// http://doc.spip.org/@ligne_plug
function ligne_plug($plug_file, $actif, $id){
	static $id_input=0;

	$erreur = false;
	$vals = array();
	$info = plugin_get_infos($plug_file);
	$s = "<script type='text/javascript'>";
$s .= <<<EOF
// http://doc.spip.org/@verifchange
function verifchange$id(inputp) {
	if(inputp.checked == true)
	{
		document.getElementById('$plug_file').className = 'nomplugin_on';
	}
	else {
		document.getElementById('$plug_file').className = 'nomplugin';
	}
	}
EOF;
	$s .= "</script>";
	$s .= "<div id='$plug_file' class='nomplugin".($actif?'_on':'')."'>";
	if (isset($info['erreur'])){
		$s .=  "<div style='background:".$GLOBALS['couleur_claire']."'>";
		$erreur = true;
		foreach($info['erreur'] as $err)
			$s .= "/!\ $err <br/>";
		$s .=  "</div>";
	}

	// puce d'etat du plugin
	// <etat>dev|experimental|test|stable</etat>
	$etat = 'dev';
	if (isset($info['etat']))
		$etat = $info['etat'];
	switch ($etat) {
		case 'experimental':
			$puce = 'puce-rouge.gif';
			$titre_etat = _T('plugin_etat_experimental');
			break;
		case 'test':
			$puce = 'puce-orange.gif';
			$titre_etat = _T('plugin_etat_test');
			break;
		case 'stable':
			$puce = 'puce-verte.gif';
			$titre_etat = _T('plugin_etat_stable');
			break;
		default:
			$puce = 'puce-poubelle.gif';
			$titre_etat = _T('plugin_etat_developpement');
			break;
	}
	$s .= "<img src='"._DIR_IMG_PACK."$puce' width='9' height='9' style='border:0;' alt=\"$titre_etat\" title=\"$titre_etat\" />&nbsp;";
	if (!$erreur){
		$s .= "<input type='checkbox' name='statusplug_$plug_file' value='O' id='label_$id_input'";
		$s .= $actif?" checked='checked'":"";
		$s .= " onclick='verifchange$id(this)' /> <label for='label_$id_input' style='display:none'>"._T('activer_plugin')."</label>";
	}
	$id_input++;
	
	$s .= bouton_block_invisible("$plug_file");
	$s .= ($actif?"":"").typo($info['nom']).($actif?"":"");


	$s .= "</div>";
	$s .= debut_block_invisible("$plug_file");
	$s .= "<div class='detailplugin'>";
	$s .= _T('version') .' '.  $info['version'] . " | <strong>$titre_etat</strong><br/>";
	$s .= _T('repertoire_plugins') .' '. $plug_file . "<br/>";

	if (isset($info['description']))
		$s .= "<hr/>" . propre($info['description']) . "<br/>";

	if (isset($info['auteur']))
		$s .= "<hr/>" . _T('auteur') .' '. propre($info['auteur']) . "<br/>";
	if (isset($info['lien']))
		$s .= "<hr/>" . _T('info_url') .' '. propre($info['lien']) . "<br/>";
	$s .= "</div>";
	$s .= fin_block();


	return $s;
}

?>
