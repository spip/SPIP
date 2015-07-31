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
	echo fin_gauche(), fin_page();
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
   "<tr><td style='background-color: ", $couleur_foncee, ";'><b>",
   "<span style='color: #FFFFFF;' class='verdana1 spip_medium'>",   _T('texte_sauvegarde'), "</span></b></td></tr><tr><td class='serif'>",
   generer_url_post_ecrire("export_all", "reinstall=non"),
   "\n<div><p style='text-align: justify;'>",
   http_img_pack('warning.gif', _T('info_avertissement'), 
		 "style='width: 48px; height: 48px; float: right;margin: 10px;'"),
   _T('texte_admin_tech_01',
     array('dossier' => '<i>'.$dir_dump.'</i>', 'img'=>'<i>'.$dir_img.'</i>')),
   '&nbsp;',
   _T('texte_admin_tech_02'),
  "</p>";

 $file = nom_fichier_dump();
 $nom = "\n<input name='nom_sauvegarde' size='40' value='$file' />";
 $znom = "\n<input name='znom_sauvegarde' size='40' value='$file' />";
 if ($flag_gz) {

	echo "\n<p style='text-align: justify;'>",
	  _T('texte_admin_tech_03'),
	  "</p>\n<p>",
	  "\n<input type='radio' name='gz' value='1' id='gz_on' checked='checked' /><label for='gz_on'> ",
	  _T('bouton_radio_sauvegarde_compressee', array('fichier'=>'')),
	  " </label><br />\n",
	  '<b>' . $dir_dump . "</b>",
	  $znom,
	  "<b>.xml.gz</b><br /><br />", 
	  "\n<input type='radio' name='gz' value='0' id='gz_off' /><label for='gz_off'>",
	  _T('bouton_radio_sauvegarde_non_compressee',  array('fichier'=>'')),
	  '</label><br /><b>',
	  $dir_dump,
	  "</b>$nom<b>.xml</b><br /></p>\n";
 }
else {
  echo "\n<p style='text-align: justify;'>",
    _T('texte_sauvegarde_compressee',
       array('fichier'=>'<br /><b>' . $dir_dump . "</b>$nom<b>.xml</b>"));
    echo "\n<input type='hidden' name='gz' value='0' /></p>";
}


echo "\n<div style='text-align: right'><input class='fondo' type='submit' value='", _T('texte_sauvegarde_base'), "' /></div></div></form>";

echo "</td></tr>";
echo "</table>";


//
// Restauration de la base
//

 if ($connect_toutes_rubriques) {

 	$liste_dump = preg_files(_DIR_DUMP,'\.xml(\.gz)?$',50,false);
 	$selected = end($liste_dump);
 	$liste_choix = "<ul>"; 
 	foreach($liste_dump as $key=>$fichier){
 		$affiche_fichier = substr($fichier,strlen(_DIR_DUMP));
 		$liste_choix.="\n<li><input type='radio' name='archive' value='"
		. $affiche_fichier
		. "' id='dump_$key' "
		.  (($fichier==$selected)?"checked='checked' ":"")
		. "/>\n<label for='dump_$key'>"
		.   $file = str_replace('/', ' / ', $affiche_fichier)
		. '&nbsp;&nbsp; ('
		. _T('taille_octets',
		     array('taille' => number_format(filesize($fichier), 0, ' ', ' ')))
		. ')</label></li>';
 	}
 	
	if ($flag_gz) {
		$fichier_defaut = str_replace(array("@stamp@","@nom_site@"),array("",""),_SPIP_DUMP) . '.gz';
		$texte_compresse = _T('texte_compresse_ou_non')."&nbsp;";
	} else {
		$fichier_defaut = str_replace(array("@stamp@","@nom_site@"),array("",""),_SPIP_DUMP);
		$texte_compresse = _T('texte_non_compresse')."&nbsp;";
	}

	echo	"\n<table border='0' cellspacing='1' cellpadding='8' width=\"100%\">",
	"<tr><td style='background-color: #eeeecc;'><b>",
	"<span style='color: #000000;' class='verdana1 spip_medium'>", _T('texte_restaurer_base')."</span></b></td></tr>",
	"<tr><td class='serif'>\n",
	generer_url_post_ecrire("import_all"),
	"\n<p style='text-align: justify;'> ",
	_T('texte_restaurer_sauvegarde', array('dossier' => '<i>'.$dir_dump.'</i>')),
	  '</p>',
	_T('entree_nom_fichier', array('texte_compresse' => $texte_compresse)),
	$liste_choix,
	"\n<li><input type='radio' name='archive' value='' />",
	"\n<span class='spip_medium'><input type='text' name='archive_perso' value='$fichier_defaut' size='30' /></span></li></ul>";
	  
	debut_cadre_relief();
	echo  "<p><input name='insertion' type='checkbox' />&nbsp;",
	  _T('sauvegarde_fusionner'),
	  '</p>';
	echo  "<p>",
	  _T('sauvegarde_url_origine'),
	  "<br /><input name='url_site' type='text' size='60'/>",
	  '</p>';
	fin_cadre_relief();

	echo "\n<div align='right'><input class='fondo' type='submit' value='",
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
		echo "<tr><td style='background-color: #eeeecc;'><b>";
		echo "<span style='color: #000000;' class='verdana1 spip_medium'>", _T('texte_recuperer_base'), "</span></b></td></tr>",
			"<tr><td class='serif'>",
			generer_url_post_ecrire("admin_repair"),
			"\n<p style='text-align: justify;'>"._T('texte_crash_base'),
			"\n</p><div align='right'><input class='fondo' type='submit' value='",
		 	_T('bouton_tenter_recuperation'),
			"' /></div></form>",
			"</td></tr>",
			"</table>";
	}
}

fin_cadre_relief();

echo "<br />";

echo fin_gauche(), fin_page();
}


// http://doc.spip.org/@nom_fichier_dump
function nom_fichier_dump()
{
	global $connect_toutes_rubriques;

	if ($connect_toutes_rubriques AND file_exists(_DIR_DUMP))
		$dir = _DIR_DUMP;
	else $dir = determine_upload();

	$site = isset($GLOBALS['meta']['nom_site'])
	  ? preg_replace(",\W,is","_", substr(trim($GLOBALS['meta']['nom_site']),0,20))
	  : 'spip';

	$site .= '_' . date('Ymd');

	$nom = $site;
	$cpt=0;
	while (file_exists($dir. $nom . ".xml") OR
	       file_exists($dir. $nom . ".xml.gz")) {
		$nom = $site . sprintf('_%03d', ++$cpt);
	}
	return $nom;
}
?>
