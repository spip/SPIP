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

include_spip('inc/presentation');
include_spip('base/dump');

// http://doc.spip.org/@exec_admin_tech_dist
function exec_admin_tech_dist()
{
	if (!autoriser('sauvegarder')){
		include_spip('inc/minipres');
		echo minipres();
	} else {
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_admin_tech'), "configuration", "base");

	echo "<br /><br />";
	echo "<div style='text-align: center'>",
	  gros_titre(_T('titre_admin_tech'),'',false),
	  '</div>';

	if ($GLOBALS['connect_toutes_rubriques']) {

		echo barre_onglets("administration", "sauver") . "<br />";
		echo debut_gauche('',true);
		echo debut_boite_info(true);
		echo  _T('info_gauche_admin_tech');
		echo fin_boite_info(true);
		$repertoire = _DIR_DUMP;
		if (!@file_exists($repertoire)
			AND !$repertoire = sous_repertoire(_DIR_DUMP,'',false,true)
		) {
			$repertoire = preg_replace(','._DIR_TMP.',', '', _DIR_DUMP);
			$repertoire = sous_repertoire(_DIR_TMP, $repertoire);
		}
		$dir_dump = $repertoire;

	} else {
		echo debut_gauche('', true);
		$dir_dump = determine_upload();
	}

	echo debut_droite('',true);

	//
	// Sauvegarde de la base
	//

	echo debut_cadre_trait_couleur('',true,'',_T('texte_sauvegarde'),'sauvegarder');

	// a passer en fonction
	if (substr(_DIR_IMG, 0, strlen(_DIR_RACINE)) === _DIR_RACINE)
	 $dir_img = substr(_DIR_IMG,strlen(_DIR_RACINE));
	else
	 $dir_img = _DIR_IMG;

	$dir_dump = joli_repertoire($dir_dump);

	$res = 
	 "\n<p>" .
	 http_img_pack('warning.gif', _T('info_avertissement'), 
		 "style='width: 48px; height: 48px; float: right;margin: 10px;'") .
	 _T('texte_admin_tech_01',
	   array('dossier' => '<i>'.$dir_dump.'</i>', 'img'=>'<i>'.$dir_img.'</i>')) .
	 '&nbsp;' .
	 _T('texte_admin_tech_02',
		array('spipnet' => $GLOBALS['home_server']
		      . '/' .  $GLOBALS['spip_lang'] . '_article1489.html'
		      )) .
	"</p>";
	
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');

	$form = $chercher_rubrique(0, 'rubrique', !$GLOBALS['connect_toutes_rubriques'], 0, 'admin_tech');

	if ($form) {
		$res .= "\n<label for='id_parent'>" .
			  _T('texte_admin_tech_04') .
			  "</label><br /><br />" .
			  $form . '<br />';
	}
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

	$res .= options_avancees_dump();
	echo 
 		generer_form_ecrire('export_all', $res, '', _T('texte_sauvegarde_base')),
 		fin_cadre_trait_couleur(true);

	//
	// Restauration de la base
	//

	// restaurer est equivalent a detruire, ou pas (cas des restaurations partielles, a affiner ?)
	if (autoriser('detruire')) {
	
		echo debut_cadre_trait_couleur('',true,'', _T('texte_restaurer_base'),'restaurer');
		echo admin_sauvegardes($dir_dump, _request('tri'));
		echo fin_cadre_trait_couleur(true);

		//
		// Lien vers la reparation
		//

		if (!_request('reinstall') AND version_compare(sql_version(),'3.23.14','>=')) {
			$res = "\n<p style='text-align: justify;'>".
				_T('texte_crash_base') .
				"\n</p>";
	
			echo 
				debut_cadre_trait_couleur('',true,'',_T('texte_recuperer_base'),'reparer'),
				generer_form_ecrire('admin_repair', $res, '', _T('bouton_tenter_recuperation')),
				fin_cadre_trait_couleur(true);
		}
	}
	echo "<br />";

	echo fin_gauche(), fin_page();
	}
}

function admin_sauvegardes($dir_dump, $tri)
{
	$liste_dump = preg_files(_DIR_DUMP,'\.xml(\.gz)?$',50,false);
	$selected = end($liste_dump);
	$n = strlen(_DIR_DUMP);
	$tl = $tt = $td = array(); 
	$f = "";
	$i = 0;
	foreach($liste_dump as $fichier){
		$i++;
		$d = filemtime($fichier);
		$t = filesize($fichier);
		$s = ($fichier==$selected);
		$class = 'row_'.alterner($i, 'even', 'odd');
		$fichier = substr($fichier, $n);
		$tl[]= liste_sauvegardes($i, $fichier, $class, $s, $d, $t);
		$td[] = $d;
		$tt[] = $t;
	}
	if ($tri == 'taille')
		array_multisort($tt, SORT_ASC, $tl);
	elseif ($tri == 'date')
		array_multisort($td, SORT_ASC, $tl);
	$fichier_defaut = $f ? basename($f) : str_replace(array("@stamp@","@nom_site@"),array("",""),_SPIP_DUMP);

	$self = self();
	$class = 'row_'.alterner($i+1, 'even', 'odd');
	$head = !$tl ? '' : (
		"\n<tr>"
		. '<th></th><th><a href="'
		. parametre_url($self, 'tri', 'nom')
		. '#sauvegardes">'
		. _T('info_nom')
	  	. "</a></th>\n" . '<th><a href="'
		. parametre_url($self, 'tri', 'taille')
		. '#sauvegardes">'
		. _T('taille_octets', array('taille' => ''))
	 	. "</a></th>\n" . '<th><a href="'
		. parametre_url($self, 'tri', 'date')
		. '#sauvegardes">'
		. _T('public:date')
		. '</a></th></tr>');
	  
	$texte = _T('texte_compresse_ou_non')."&nbsp;";

	$h = _T('texte_restaurer_sauvegarde', array('dossier' => '<i>'.$dir_dump.'</i>'));

	$res = "\n<p style='text-align: justify;'> "
		. $h
		.  '</p>'
		. _T('entree_nom_fichier', array('texte_compresse' => $texte))

		. "\n<br /><br /><table class='spip' id='sauvegardes'>"
		. $head
		.  join('',$tl)
		. "\n<tr class='$class'><td><input type='radio' name='archive' id='archive' value='' /></td><td  colspan='3'>"
		. "\n<span class='spip_x-small'><input type='text' name='archive_perso' id='archive_perso' value='$fichier_defaut' size='55' /></span></td></tr>"
		. '</table>';


	$plie = _T('info_options_avancees');
	// restauration partielle / fusion
	$opt = debut_cadre_enfonce('',true) .
		"\n<div>" .
		 "<input name='insertion' id='insertion' type='checkbox' />&nbsp; <label for='insertion'>". 
		  _T('sauvegarde_fusionner') .
		  "</label><br />\n" .
		 "<input name='statut' id='statut' type='checkbox' />&nbsp; <label for='statut'>\n". 
		  _T('sauvegarde_fusionner_depublier') .
		  "</label><br />\n" .
		  "<label for='url_site'>" .
		  _T('sauvegarde_url_origine') .
		  "</label>" .
		  " &nbsp;\n<input name='url_site' id='url_site' type='text' size='25' />" .
		  '</div>' .
		  fin_cadre_enfonce(true);

	$res .= block_parfois_visible('import_tables', $plie, $opt, '', false);

	return generer_form_ecrire('import_all', $res, '', _T('bouton_restaurer_base'));
}


// http://doc.spip.org/@liste_sauvegardes
function liste_sauvegardes($key, $fichier, $class, $selected, $date, $taille)
{
	return "\n<tr class='$class'><td><input type='radio' name='archive' value='"
		. $fichier
		. "' id='dump_$key' "
		. ($selected?"checked='checked' ":"")
		. "/></td><td>\n<label for='dump_$key'>"
		. str_replace('/', ' / ', $fichier)
		. "</label></td><td style='text-align: right'>"
		. taille_en_octets($taille)
		. '</td><td>'
		. affdate_heure(date('Y-m-d H:i:s',$date))
		. '</td></tr>';
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


function options_avancees_dump(){
	list($tables,) = base_liste_table_for_dump(lister_tables_noexport());
	$plie = _T('info_options_avancees');
	$res = controle_tables_en_base('export', $tables);
	$res = "<h3>"._T('install_tables_base')."</h3>"
	 . "\n<ol style='spip'><li>\n" .
			join("</li>\n<li>", $res) .
			"</li></ol>\n";

	$res = block_parfois_visible('export_tables', $plie, $res, '', false);
 	return $res;
}


// Fabrique la liste a cocher des tables presentes
function controle_tables_en_base($name, $check)
{
	$p = '/^' . $GLOBALS['table_prefix'] . '/';
	$res = $check;
	foreach(sql_alltable() as $t) {
		$t = preg_replace($p, 'spip', $t);
		if (!in_array($t, $check)) $res[]= $t;
	}
	sort($res);

	foreach ($res as $k => $t) {

		$res[$k] = "<input type='checkbox' value='$t' name='$name"
			. "[]'"
			. (in_array($t, $check) ? " checked='checked'" : '') 
			. "/>\n"
			. $t
			. " ("
			.  sql_countsel($t)
	  		. ")";
	}
	return $res;
}

?>
