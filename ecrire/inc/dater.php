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
include_spip('inc/texte');
include_spip('inc/actions');
include_spip('inc/date');

// http://doc.spip.org/@inc_dater_dist
function inc_dater_dist($id, $flag, $statut, $type, $script, $date, $date_redac='')
{
	global $spip_lang_left, $spip_lang_right, $options;

	if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})( ([0-9]{2}):([0-9]{2}))?", $date_redac, $regs)) {
		$annee_redac = $regs[1];
		$mois_redac = $regs[2];
		$jour_redac = $regs[3];
		$heure_redac = $regs[5];
		$minute_redac = $regs[6];
		if ($annee_redac > 4000) $annee_redac -= 9000;
	} else $annee_redac = $mois_redac = $jour_redac = 0;

	$possedeDateRedac= ($annee_redac + $mois_redac + $jour_redac);

	if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})( ([0-9]{2}):([0-9]{2}))?", $date, $regs)) {
		$annee = $regs[1];
		$mois = $regs[2];
		$jour = $regs[3];
		$heure = $regs[5];
		$minute = $regs[6];
	}

  if ($flag AND $options == 'avancees') {

	if ($statut == 'publie') {

		$js = "size='1' class='fondl'
onchange=\"findObj_forcer('valider_date').style.visibility='visible';\"";

		$invite =  "<b><span class='verdana1'>"
		. _T('texte_date_publication_article')
		. '</span> '
		.  majuscules(affdate($date))
		.  "</b>"
		. aide('artdate');

		$masque = "<div style='margin: 5px; margin-$spip_lang_left: 20px;'>"
		. afficher_jour($jour, "name='jour' $js", true)
		. afficher_mois($mois, "name='mois' $js", true)
		. afficher_annee($annee, "name='annee' $js")
		. (($type != 'article')
		   ? ''
		   : (' - '
			. afficher_heure($heure, "name='heure' $js")
			. afficher_minute($minute, "name='minute' $js")))
		. "<span class='visible_au_chargement' id='valider_date'>"
		. " &nbsp;\n<input type='submit' class='fondo' value='"
		. _T('bouton_changer')."' />"
		.  "</span>"
		.  "</div>";

		$bloc = block_parfois_visible('datepub', $invite, $masque, 'text-align: left');
		$res = ajax_action_auteur("dater", 
			"$id/$type",
			$script,
			"id_$type=$id",
			$bloc,
			"&id=$id&type=$type");

	} else {
		$res = "\n<div><b> <span class='verdana1'>"
		. _T('texte_date_creation_article')
		. "</span>\n"
		. majuscules(affdate($date))."</b>".aide('artdate')."</div>";
	}

	if (($type == 'article')
	AND (($options == 'avancees' AND $GLOBALS['meta']["articles_redac"] != 'non')
		OR $possedeDateRedac)) {
		if ($possedeDateRedac)
			$date_affichee = majuscules(affdate($date_redac))
#			." " ._T('date_fmt_heures_minutes', array('h' =>$heure_redac, 'm'=>$minute_redac))
			;
		else
			$date_affichee = majuscules(_T('jour_non_connu_nc'));

		$js = "\"findObj_forcer('valider_date_redac').style.visibility='visible';\"";

		$invite = "<b>"
		. "<span class='verdana1'>"
		. majuscules(_T('texte_date_publication_anterieure'))
		. '</span> '
		. $date_affichee
		. " "
		. aide('artdate_redac')
		.  "</b>";

		$masque = 
 "<div style='margin: 5px; margin-$spip_lang_left: 20px;'>" .
 '<table cellpadding="0" cellspacing="0" border="0" width="100%">' .
 "<tr><td align='$spip_lang_left'>" .
 '<input type="radio" name="avec_redac" value="non" id="avec_redac_on"' .
 ($possedeDateRedac ? '' : ' checked="checked"') .
 " onclick=$js" .
 ' /> <label for="avec_redac_on">'.
 _T('texte_date_publication_anterieure_nonaffichee').
 '</label>' .
 '<br /><input type="radio" name="avec_redac" value="oui" id="avec_redac_off"' .
 (!$possedeDateRedac ? '' : ' checked="checked"') .
 " onclick=$js /> <label for='avec_redac_off'>" .
 _T('bouton_radio_afficher').
 ' :</label> ' .
 afficher_jour($jour_redac, "name='jour_redac' class='fondl' onchange=$js", true) .
 afficher_mois($mois_redac, "name='mois_redac' class='fondl' onchange=$js", true) .
 "<input type='text' name='annee_redac' class='fondl' value='".$annee_redac."' size='5' maxlength='4' onclick=$js />" .
 '<div align="center">' .
 afficher_heure($heure_redac, "name='heure_redac' class='fondl' onchange=$js", true) .
 afficher_minute($minute_redac, "name='minute_redac' class='fondl' onchange=$js", true) .
 "</div>\n" .
 "</td><td align='$spip_lang_right'>" .
 "<span class='visible_au_chargement' id='valider_date_redac'>" .
 '<input type="submit" class="fondo" value="'.
 _T('bouton_changer').'" />' .
 "</span>" .
 '</td></tr>' .
 '</table>' .
 '</div>';

		$bloc = block_parfois_visible('dateredac', $invite, $masque, 'text-align: left');
		$res .= ajax_action_auteur("dater", 
			"$id/$type",
			$script,
			"id_$type=$id",
			$bloc,
			"&id=$id&type=$type");
	}
  } else {

	$res = "<div style='text-align:center;'><b> <span class='verdana1'>"
	  . (($statut == 'publie' OR $type != 'article')
		? _T('texte_date_publication_article')
		: _T('texte_date_creation_article'))
	. "</span> "
	.  majuscules(affdate($date))."</b>".aide('artdate')."</div>";

	if ($possedeDateRedac) {
		$res .= "<div style='text-align:center;'><b><span class='verdana1'>"
		. _T('texte_date_publication_anterieure')
		. "</span> "
		. ' : '
		. majuscules(affdate($date_redac))
		. "</b>"
		. aide('artdate_redac')
		. "</div>";
	}
  }

  $res =  debut_cadre_couleur('',true) . $res .  fin_cadre_couleur(true);

  return ajax_action_greffe("dater-$id", $res);
}

?>
