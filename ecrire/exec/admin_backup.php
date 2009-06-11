<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');

function exec_admin_backup_dist()
{
	if (!autoriser('sauvegarder')){
		include_spip('inc/minipres');
		echo minipres();
	} 
	else {
		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page(_T('texte_sauvegarde'), "configuration", "base");

		echo gros_titre(_T('texte_sauvegarde'),'',false);

		echo debut_gauche('',true);
		if ($GLOBALS['connect_toutes_rubriques']) {
			$repertoire = _DIR_DUMP;
			if (!@file_exists($repertoire)
				AND !$repertoire = sous_repertoire(_DIR_DUMP,'',false,true)
			) {
				$repertoire = preg_replace(','._DIR_TMP.',', '', _DIR_DUMP);
				$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
			}
			$dir_dump = $repertoire;

		}
		else {
			$dir_dump = determine_upload();
		}

		echo debut_droite('',true);

		//
		// Sauvegarde de la base
		//

		echo debut_cadre_trait_couleur('',true,'',"",'sauvegarder');

		// a passer en fonction
		if (substr(_DIR_IMG, 0, strlen(_DIR_RACINE)) === _DIR_RACINE)
		 $dir_img = substr(_DIR_IMG,strlen(_DIR_RACINE));
		else
		 $dir_img = _DIR_IMG;

		$dir_dump = joli_repertoire($dir_dump);

		$res =
		 "\n<p>" .
		 http_img_pack(chemin_image("warning-48.png"), _T('info_avertissement'),
			 "style=' float: right;margin: 10px;'") .
		 _T('texte_admin_tech_01',
		   array('dossier' => '<i>'.$dir_dump.'</i>', 'img'=>'<i>'.$dir_img.'</i>')) .
		 '&nbsp;' .
		  _T('texte_admin_tech_02',
			  array('archive' => str_replace('/', ' / ', $archive),
				'spipnet' => $GLOBALS['home_server']
				. '/' .  $GLOBALS['spip_lang'] . '_article1489.html'
				)) .
		"</p>";

		$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');

		$form = $chercher_rubrique(0, 'rubrique', !$GLOBALS['connect_toutes_rubriques'], 0, 'admin_backup');

		$res .= "\n<label for='id_parent'>" .
			  _T('texte_admin_tech_04') .
			  "</label><br /><br />" .
			  $form . '<br />';

		$file = nom_fichier_dump();
		$nom = "\n<input name='nom_sauvegarde' id='nom_sauvegarde' size='40' value='$file' />";
		$znom = "\n<input name='znom_sauvegarde' id='znom_sauvegarde' size='40' value='$file' />";

		$res .=
		  _T('texte_admin_tech_03') .
		  "\n<ul>" .
		  "\n<li style='list-style:none;'><input type='radio' name='gz' value='1' id='gz_on' checked='checked' /><label for='gz_on'> " .
		  _T('bouton_radio_sauvegarde_compressee', array('fichier'=>'')) .
		  " </label><br />\n" .
		  '<b>' . $dir_dump . "</b>" .
		  $znom .
		  "<b>.xml.gz</b></li>" .
		  "\n<li style='list-style:none;'><input type='radio' name='gz' value='0' id='gz_off' /><label for='gz_off'>" .
		  _T('bouton_radio_sauvegarde_non_compressee',  array('fichier'=>'')) .
		  '</label><br /><b>' .
		  $dir_dump .
		  "</b>$nom<b>.xml</b></li></ul>\n"
		  . "\n<input type='hidden' name='reinstall' value='non' />";

		echo
			generer_form_ecrire('export_all', $res, '', _T('texte_sauvegarde_base')),
			fin_cadre_trait_couleur(true);

		echo fin_gauche(), fin_page();
	}
}

// http://doc.spip.org/@nom_fichier_dump
function nom_fichier_dump()
{
	global $connect_toutes_rubriques;

	if ($connect_toutes_rubriques AND file_exists(_DIR_DUMP))
		$dir = _DIR_DUMP;
	else $dir = determine_upload();
	$site = isset($GLOBALS['meta']['nom_site'])
	  ? preg_replace(array(",\W,is",",_(?=_),",",_$,"),array("_","",""), couper(translitteration(trim($GLOBALS['meta']['nom_site'])),30,""))
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
