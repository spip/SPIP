<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');

// http://doc.spip.org/@exec_admin_tech_dist
function exec_admin_tech_dist()
{
  global $connect_statut, $connect_login, $connect_toutes_rubriques, $couleur_foncee, $flag_gz, $options;

 $commencer_page = charger_fonction('commencer_page', 'inc');
 echo $commencer_page(_T('titre_admin_tech'), "configuration", "base");

 echo "<br />";

 if ($connect_statut != '0minirezo' ){
	echo _T('avis_non_acces_page');
	echo fin_page();
	exit;
 }
	echo "<br /><br />";
	gros_titre(_T('titre_admin_tech'));
	if ($connect_toutes_rubriques) {
		echo barre_onglets("administration", "sauver");
		debut_gauche();
		debut_boite_info();
		echo _T('info_gauche_admin_tech');
		fin_boite_info();
		$repertoire = _DIR_DUMP;
		if(!@file_exists($repertoire)) {
			$repertoire = preg_replace(','._DIR_TMP.',', '', $repertoire);
			$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
		}
		$dir_dump = $repertoire;
	} else {
		debut_gauche();
		$dir_dump = determine_upload();
	}
	include_spip('exec/export_all');
	$file = joli_repertoire($dir_dump . export_nom_fichier_dump($dir_dump,false));
	$zfile = joli_repertoire($dir_dump . export_nom_fichier_dump($dir_dump,true));
	$dir_dump = joli_repertoire($dir_dump);

 debut_droite();

//
// Sauvegarde de la base
//

 debut_cadre_relief();

 // a passer en fonction
 if (substr(_DIR_IMG, 0, strlen(_DIR_RACINE)) === _DIR_RACINE)
   $dir_img = substr(_DIR_IMG,strlen(_DIR_RACINE));
 else
   $dir_img = _DIR_IMG;

 echo "<table border='0' cellspacing='0' cellpadding='5' width=\"100%\">",
   "<tr><td bgcolor='", $couleur_foncee, "' background=''><b>",
   "<font face='Verdana,Arial,Sans,sans-serif' size='3' color='#FFFFFF'>",
   _T('texte_sauvegarde'),
   "</font></b></td></tr><tr><td class='serif'>",
   generer_url_post_ecrire("export_all", "reinstall=non"),
   "\n<p align='justify'>",
   http_img_pack('warning.gif', _T('info_avertissement'), "width='48' height='48' align='right'"),
   _T('texte_admin_tech_01',
     array('dossier' => '<i>'.$dir_dump.'</i>', 'img'=>'<i>'.$dir_img.'</i>')),
   _T('texte_admin_tech_02'),
  "</p>";

if ($flag_gz) {
	echo "\n<p align='justify'>"._T('texte_admin_tech_03')."</p>\n<p>";
	echo "\n<input type='radio' name='gz' value='1' id='gz_on' checked='checked' /><label for='gz_on'> "._T('bouton_radio_sauvegarde_compressee',
	array('fichier'=>'<b>'.$zfile.'</b>'))." </label><br />\n";
	echo "\n<input type='radio' name='gz' value='0' id='gz_off' /><label for='gz_off'> "._T('bouton_radio_sauvegarde_non_compressee',
	array('fichier'=>'<b>'.$file.'</b>'))." </label><br /></p>\n";
}
else {
	echo "\n<p align='justify'>"._T('texte_sauvegarde_compressee', array('fichier'=>'<b>'.$file.'</b>'));
	echo "\n<input type='hidden' name='gz' value='0' />";
}

echo "\n<div align='right'><input class='fondo' type='submit' value='"._T('texte_sauvegarde_base')."' /></div></form>";

echo "</td></tr>";
echo "</table>";


//
// Restauration de la base
//

 if ($connect_toutes_rubriques) {
 	$liste_dump = preg_files(_DIR_DUMP,str_replace("@stamp@","(_[0-9]{6,8}_[0-9]{1,3})?",_SPIP_DUMP)."(.gz)?",50,false);
 	$selected = end($liste_dump);
 	$liste_choix = "<p><ul>"; 
 	foreach($liste_dump as $key=>$fichier){
 		$affiche_fichier = substr($fichier,strlen(_DIR_DUMP));
 		$liste_choix.="\n<li><input type='radio' name='archive' value='$affiche_fichier' id='dump_$key' ".
 			(($fichier==$selected)?"checked='checked' ":"")."/>\n<label for='dump_$key'>$affiche_fichier</label></li>";
 	}
 	
	if ($flag_gz) {
		$fichier_defaut = str_replace("@stamp@","",_SPIP_DUMP) . '.gz';
		$texte_compresse = _T('texte_compresse_ou_non')."&nbsp;";
	} else {
		$fichier_defaut = str_replace("@stamp@","",_SPIP_DUMP);
		$texte_compresse = _T('texte_non_compresse')."&nbsp;";
	}

	echo	"\n<table border='0' cellspacing='1' cellpadding='8' width=\"100%\">",
	"<tr><td bgcolor='#eeeecc' background=''><b>",
	"<font face='Verdana,Arial,Sans,sans-serif' size='3' color='#000000'>",
	_T('texte_restaurer_base')."</font></b></td></tr>",
	"<tr><td class='serif'>\n",
	generer_url_post_ecrire("import_all"),
	"\n<p align='justify'> ",
	_T('texte_restaurer_sauvegarde', array('dossier' => '<i>'.$dir_dump.'</i>')),
	  '</p>',
	_T('entree_nom_fichier', array('texte_compresse' => $texte_compresse)),
	$liste_choix,
	"\n<li><input type='radio' name='archive' value='' />",
	"\n<font size='3'><input type='text' name='archive_perso' value='$fichier_defaut' size='30' /></font></li></ul>";
	  
	debut_cadre_relief();
	echo  "<p><input name='insertion' type='radio' />&nbsp;",
	  _L('Fusionner la base actuelle et la sauvegarde'),
	  '</p>';
	fin_cadre_relief();

	echo "\n</p><div align='right'><input class='fondo' type='submit' value='",
	  _T('bouton_restaurer_base'),
	  "' /></div></form>",
	  "\n</td></tr>",
	  "</table>";

 }

//
// Lien vers la reparation
//

if ($options == "avancees" AND 	$connect_toutes_rubriques) {
	$res = spip_mysql_version();
	if ($res >= '3.23.14') {
		echo "<table border='0' cellspacing='1' cellpadding='8' width=\"100%\">";
		echo "<tr><td bgcolor='#eeeecc' background=''><b>";
		echo "<font face='Verdana,Arial,Sans,sans-serif' size='3' COLOR='#000000'>";
		echo _T('texte_recuperer_base'),
			"</font></b></td></tr>",
			"<tr><td class='serif'>",
			generer_url_post_ecrire("admin_repair"),
			"\n<p align='justify'>"._T('texte_crash_base'),
			"\n</p><div align='right'><input class='fondo' type='submit' value='",
		 	_T('bouton_tenter_recuperation'),
			"' /></div></form>",
			"</td></tr>",
			"</table>";
	}
}

fin_cadre_relief();

echo "<br />";

echo fin_page();
}

?>
