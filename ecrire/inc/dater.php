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
include_spip('inc/texte');
include_spip('inc/actions');
include_spip('inc/date');

// http://doc.spip.org/@inc_dater_dist
function inc_dater_dist($id, $flag, $statut, $type, $script, $date, $date_redac='', $fct_ajax='')
{
	$possedeDateRedac = !preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2})( ([0-9]{2}):([0-9]{2}))?/", $date_redac, $regs) ? false :  (($regs[1] + $regs[2] + $regs[3]) ? $regs : false);

	if ($flag) {
	  $res = dater_ecriture($id, $possedeDateRedac, $statut, $type, $script, $date, $date_redac, $fct_ajax);
	} else {
		$res = dater_lecture($date, $date_redac, $possedeDateRedac,
				(($statut == 'publie' OR $type != 'article')
				 ? _T('texte_date_publication_article')
				 : _T('texte_date_creation_article')));
	}

	return ajax_action_greffe("dater", $id, $res);
}

function dater_lecture($date, $date_redac, $possedeDateRedac, $label)
{
	$res = "<div style='text-align:center;'><b> <span class='verdana1'>"
	  . $label
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
	return $res;
}

function dater_ecriture($id, $possedeDateRedac, $statut, $type, $script, $date, $date_redac, $fct_ajax) {
	global $spip_lang_left, $spip_lang_right, $debut_date_publication;

	if ($possedeDateRedac) {
		$annee_redac = $possedeDateRedac[1];
		$mois_redac = $possedeDateRedac[2];
		$jour_redac = $possedeDateRedac[3];
		$heure_redac = $possedeDateRedac[5];
		$minute_redac = $possedeDateRedac[6];
		if ($annee_redac > 4000) $annee_redac -= 9000;
	} else $annee_redac = $mois_redac = $jour_redac = 0;

	include_spip('inc/autoriser');

	if (autoriser('dater',$type,$id,null,array('statut'=>$statut))) {

		$res = dater_ajax($id, $type, $script, $date, $fct_ajax, $debut_date_publication);
		if ($res) {
			$invite =  "<b><span class='verdana1'>"
			. _T('texte_date_publication_article')
			. '</span> '
			.  majuscules(affdate($date))
			.  "</b>"
			. aide('artdate');

			$res = block_parfois_visible("datepub-$id", $invite, $res, 'text-align: left');
		}

	} else {
		if ($type == 'article')
			$res = "\n<div style='padding-$spip_lang_left:7px;'><b> <span class='verdana1'>"
			. _T('texte_date_creation_article')
			. "</span>\n"
			. majuscules(affdate($date))."</b>".aide('artdate')."</div>";
		else
			$res = "\n<div style='text-align:center;'><b>"
			. majuscules(affdate($date))."</b></div>";
	}

	if (!(($type == 'article')
	      AND ($GLOBALS['meta']["articles_redac"] != 'non' OR $possedeDateRedac)))
		return $res;

	return $res . dater_redac($id, $type, $script, $possedeDateRedac, $date_redac, $fct_ajax);
}

function dater_redac($id, $type, $script, $possedeDateRedac, $date_redac, $fct_ajax)
{
	if ($possedeDateRedac)
		$date_affichee = majuscules(affdate($date_redac));
	else
		$date_affichee = majuscules(_T('jour_non_connu_nc'));

	$invite = "<b>"
			. "<span class='verdana1'>"
			. majuscules(_T('texte_date_publication_anterieure'))
			. '</span> '
			. $date_affichee
			. " "
			. aide('artdate_redac')
			.  "</b>";

	$js = "\"findObj_forcer('valider_date_redac-$id').style.visibility='visible';\"";
	$label =
		"<input type='radio' name='avec_redac' value='non' id='avec_redac_on$id'" .
		($possedeDateRedac ? '' : " checked='checked'") .
		" onclick=$js" .
		" /> <label for='avec_redac_on$id'>" .
		_T('texte_date_publication_anterieure_nonaffichee').
		'</label>' .
		"<br /><input type='radio' name='avec_redac' value='oui' id='avec_redac_off$id'" .
		(!$possedeDateRedac ? '' : " checked='checked'") .
		" onclick=$js /> <label for='avec_redac_off$id'>" .
		_T('bouton_radio_afficher').
	  ' :</label> ';

	$masque = dater_ajax($id, $type, $script, $date_redac, $fct_ajax, 0, '_redac', $label, true);

	return block_parfois_visible("dateredac-$id", $invite, $masque, 'text-align: left');
}

function dater_ajax($id, $type, $script, $date, $fct_ajax, $start=0, $suffixe='', $label='', $autre=false)
{
	global $spip_lang_left, $spip_lang_right, $debut_date_publication;

	if (!is_string($date)) return '';
	preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2})( ([0-9]{2}):([0-9]{2}))?/", $date, $regs);
	$annee = $regs[1];
	if ($annee > 4000) $annee -= 9000;
	$mois = $regs[2];
	$jour = $regs[3];
	$heure = $regs[5];
	$minute = $regs[6];

	$idom = "valider_date$suffixe-$id";
	$js = " onchange=\"findObj_forcer('$idom').style.visibility='visible';\"";

	$res = $label 
	. afficher_jour($jour, "name='jour$suffixe' $js", $autre)
	. afficher_mois($mois, "name='mois$suffixe' $js", $autre)
	. afficher_annee($annee, "name='annee$suffixe' $js", $start)
	. (($type != 'article')
		 ? ''
		 : (' - '
		    . afficher_heure($heure, "name='heure$suffixe' $js")
		    . afficher_minute($minute, "name='minute$suffixe' $js")))
	  . "<div class='nettoyeur'></div>";
	
	$res = "<div style='margin-bottom: 5px; margin-$spip_lang_left: 20px;'>$res</div>";
	return ajax_action_post("dater",
			"$id/$type",
			$script,
			"id_$type=$id",
			$res,
			_T('bouton_changer'),
			" style=' float:$spip_lang_right;position:relative;' class='visible_au_chargement' id='$idom'",
			"",
			"&id=$id&type=$type",
			$fct_ajax);
}
?>
