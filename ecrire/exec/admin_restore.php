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

// http://doc.spip.org/@exec_admin_tech_dist
function exec_admin_restore_dist()
{
	if (!autoriser('detruire')){
		include_spip('inc/minipres');
		echo minipres();
	}
	else {
		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page(_T('texte_restaurer_base'), "configuration", "base");

		echo gros_titre(_T('texte_restaurer_base'),'',false);

		echo debut_gauche('',true);

		echo debut_droite('',true);

		//
		// Restauration de la base
		//

	
		echo debut_cadre_trait_couleur('',true,'', "",'restaurer');
		echo admin_sauvegardes($dir_dump, _request('tri'));
		echo fin_cadre_trait_couleur(true);

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
		"<tr>"
		. '<th></th><th><a href="'
		. parametre_url($self, 'tri', 'nom')
		. '#sauvegardes">'
		. _T('info_nom')
	  	. '</a></th><th><a href="'
		. parametre_url($self, 'tri', 'taille')
		. '#sauvegardes">'
		. _T('taille_octets', array('taille' => ''))
	 	. '</th><th><a href="'
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

		. "<br /><br /><table class='spip' id='sauvegardes'>"
		. $head
		.  join('',$tl)
		. "\n<tr class='$class'><td><input type='radio' name='archive' id='archive' value='' /></td><td  colspan='3'>"
		. "\n<span class='spip_x-small'><input type='text' name='archive_perso' id='archive_perso' value='$fichier_defaut' size='55' /></span></td></tr>"
		. '</table>';


	// restauration partielle / fusion
	$res .= debut_cadre_enfonce('',true) .
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

?>