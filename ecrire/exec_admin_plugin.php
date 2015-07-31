<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_ecrire ("inc_config");
include_ecrire ("inc_plugin");
include_ecrire ("inc_presentation");
include_ecrire ("inc_layer");

function ligne_plug($plug_file,&$plug_actifs,$last_actif = false,$surligne = false){
		static $id_input=0;
		global $couleur_claire;
		$vals = array();
		$info = plugin_get_infos($plug_file);
		$plugok='N';
		if (@in_array($plug_file,$plug_actifs))
			$plugok='O';

		$s = "";
		$s .= "<div id='$plug_file'";
		if ($surligne)
			$s .= " style='background:$couleur_claire'";
		$s .= ">";
		$s .= bouton_block_invisible("$plug_file");
		$s .= ($plugok=='O'?"<strong>":"").$info['nom'].($plugok=='O'?"</strong>":"");
		$s .= "</div>";
		$s .= debut_block_invisible("$plug_file");
		$s .= _T("plugin:version_plugin") . " : " . $info['version'] . "<br/>";
		$s .= _T("plugin:repertoire_plugin") . " : " . $plug_file . "<br/>";

		if (isset($info['description']))
			$s .= "<hr/>" . propre($info['description']) . "<br/>";

		if (isset($info['auteur']))
			$s .= "<hr/>" . _T("plugin:auteur_plugin") . " : " . propre($info['auteur']) . "<br/>";
		if (isset($info['lien']))
			$s .= "<hr/>" . _T("plugin:lien_plugin") . " : " . propre($info['lien']) . "<br/>";

		$s .= fin_block();
		$vals[] = $s;

		$s = "";
		if ('O' == $plugok){
			if ($id_input>0)
				$s = "<a href='".generer_url_ecrire('admin_plugin',"monter=$plug_file")."'><img src='"._DIR_IMG_PACK."monter-16.png' style='border:0'></a>";
			$vals[] = $s;
			$s = "";
			if (!$last_actif)
				$s = "<a href='".generer_url_ecrire('admin_plugin',"descendre=$plug_file")."'><img src='"._DIR_IMG_PACK."descendre-16.png' style='border:0'></a>";
		}
		else{
			$vals[] = $s;
		}
		$vals[] = $s;

		$s = "";
		$s .= "<input type='checkbox' name='statusplug_$plug_file' value='O' id='label_$id_input'";
		$s .= ('O' == $plugok)?" checked='checked'":"";
		$s .= " /> <label for='label_$id_input'><strong>"._T('plugin:activer_plugin')."</strong></label>";
		$id_input++;		
		$vals[] = $s;

		return $vals;
}

function admin_plugin(){
	global $connect_statut;
	global $connect_toutes_rubriques;
	$surligne = "";
  
	if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
		debut_page(_T('plugin:onglet_plugin'), "administration", "plugin");
		echo _T('avis_non_acces_page');
		fin_page();
		exit;
	}
	
	// mise à jour des données si envoi via formulaire
	// sinon fait une passe de verif sur les plugin
	if ($_POST['changer_plugin']=='oui'){
		enregistre_modif_plugin();
		// pour la peine, un redirige, 
		// que les plugin charges soient coherent avec la liste
		redirige_par_entete(generer_url_ecrire('admin_plugin'));
	}
	else if ($_GET['monter'] || $_GET['descendre']){
		ordonne_plugin();
		// pour la peine, un redirige, 
		// que les plugin charges soient coherent avec la liste
		if (isset($_GET['monter']))
			$surligne = "surligne=".$_GET['monter']."#".$_GET['monter'];
		if (isset($_GET['descendre']))
			$surligne = "surligne=".$_GET['descendre']."#".$_GET['descendre'];
		redirige_par_entete(generer_url_ecrire('admin_plugin',$surligne, true));
	}
	else
		verif_plugin();
	if (isset($_GET['surligne']))
		$surligne = $_GET['surligne'];

	debut_page(_T('plugin:onglet_plugin'), "administration", "plugin");
	echo "<br/><br/><br/>";
	
	gros_titre(_T('plugin:titre_admin_plugin'));
	// barre_onglets("administration", "plugin"); // a creer dynamiquement en fonction des plugin chargés qui utilisent une page admin ?
	
	debut_gauche();
	
	debut_boite_info();
	
	echo _T('info_gauche_admin_tech');
	
	fin_boite_info();
	
	debut_droite();
	
	debut_cadre_trait_couleur("plugin-24.png", false, "", _T('plugin:texte_plugin'));
	
	echo "\n<p align='justify'>"._T('plugin:texte_presente_plugin')."</p>";
	echo "\n<div>&nbsp;</div>\n";
	echo generer_url_post_ecrire("admin_plugin");
	

	$titre_table = _L("Liste des plugin disponibles");
	$icone = "plugin-24.png";
	if ($titre_table) echo "<div style='height: 12px;'></div>";
	echo "<div class='liste'>";
	bandeau_titre_boite2($titre_table, $icone, $couleur_claire, "black");
	echo "<table width='100%' cellpadding='4' cellspacing='0' border='0'>";
	$tableau = array();

	//
	// boucle sur les plugins
	$plugins_actifs=liste_plugin_actifs();
	$count = 0;
	foreach ($plugins_actifs as $plug_file){
		$tableau[] = ligne_plug($plug_file,$plugins_actifs,++$count==count($plugins_actifs),$surligne==$plug_file);
	}
	foreach (liste_plugin_inactifs() as $plug_file){
		$tableau[] = ligne_plug($plug_file,$plugins_actifs,false);
	}

	$largeurs = array('','15px','20px','120px');
	$styles = array('arial11', 'arial1','arial1', 'arial1');
	afficher_liste($largeurs, $tableau, $styles);
	echo "</table>";
	echo "</div>\n";
	
	echo "\n<input type='hidden' name='id_auteur' value='$connect_id_auteur' />";
	echo "\n<input type='hidden' name='hash' value='" . calculer_action_auteur("valide_plugin") . "'>";
	echo "\n<input type='hidden' name='changer_plugin' value='oui'>";
	//echo "\n<input type='hidden' name='redirect' value='" . _DIR_RESTREINT_ABS . "admin_plugin.php3'>";
	echo "\n<p>";
	
	echo "<div style='text-align:$spip_lang_right'><input type='submit' name='Valider' value='"._T('bouton_valider')."' class='fondo'></div>";
	fin_cadre_trait_couleur();
	
	
	echo "<br />";
	
	fin_page();

}

?>