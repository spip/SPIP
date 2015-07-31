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

// Affiche le portfolio et les documents lies a l'article (ou a la rubrique)
// => Nouveau : au lieu de les ignorer, on affiche desormais avec un fond gris
// les documents et images inclus dans le texte.

// http://doc.spip.org/@afficher_portfolio
function inc_documenter_dist(
	$doc,		# tableau des documents ou numero de l'objet attachant
	$type = "article",	# article ou rubrique ?
	$ancre = 'portfolio',	# album d'images ou de documents ?
	$flag = false,	# a-t-on le droit de modifier ?
	$couleur='',		# couleur des cases du tableau
	$appelant =''		# pour le rappel (cf plugin)
) {
	global $couleur_claire, $spip_lang_left, $spip_lang_right;

	if (is_int($doc)) {
		if ($ancre == 'portfolio') {
			$lies = spip_query("SELECT docs.*,l.id_$type FROM spip_documents AS docs, spip_documents_".$type."s AS l, spip_types_documents AS lestypes WHERE l.id_$type=$doc AND l.id_document=docs.id_document AND docs.mode='document' AND docs.id_type=lestypes.id_type AND lestypes.extension IN ('gif', 'jpg', 'png') ORDER BY 0+docs.titre, docs.date");
			$couleur = $couleur_claire;
		} else {
			$lies = spip_query("SELECT docs.*,l.id_$type FROM spip_documents AS docs, spip_documents_".$type."s AS l,spip_types_documents AS lestypes WHERE l.id_$type=$doc AND l.id_document=docs.id_document AND docs.mode='document' AND docs.id_type=lestypes.id_type AND lestypes.extension NOT IN ('gif', 'jpg', 'png') ORDER BY 0+docs.titre, docs.date");
			$couleur = '#aaaaaa';
		}

		$documents = array();
		while ($document = spip_fetch_array($lies))
			$documents[] = $document;
	} else $documents = $doc;

	if (!$documents) return '';

	charger_generer_url();
	// la derniere case d'une rangee
	$bord_droit = ($ancre == 'portfolio' ? 2 : 1);
	$case = 0;
	$res = '';

	$tourner = charger_fonction('tourner', 'inc');
	$legender = charger_fonction('legender', 'inc');

	foreach ($documents as $document) {
		$id_document = $document['id_document'];

		if (isset($document['script']))
			$script = $document['script']; # pour plugin Cedric
		else
		  // ref a $exec inutilise en standard
		  $script = $appelant ? $appelant : $GLOBALS['exec'];

		$style = est_inclus($id_document) ? ' background-color: #cccccc;':'';

		if (!$case)
			$res .= "<tr style='border-top: 1px solid black;'>";
		else if ($case == $bord_droit)
			$style .= " border-$spip_lang_right: 1px solid $couleur;";
		$res .= "\n<td  style='width:33%; text-align: $spip_lang_left; border-$spip_lang_left: 1px solid $couleur; border-bottom: 1px solid $couleur; $style' valign='top'>"
		.  $tourner($id_document, $document, $script, $flag, $type)
		. (!$flag  ? '' :
		   $legender($id_document, $document, $script, $type, $document["id_$type"], $ancre))
		. (!isset($document['info']) ? '' :
		       ("<div class='verdana1'>".$document['info']."</div>"))
		. "</td>\n";

		$case++;
				
		if ($case > $bord_droit) {
			  $case = 0;
			  $res .= "</tr>\n";
		}

	}

	// fermer la derniere ligne
	if ($case) {
		$res .= "<td style='border-$spip_lang_left: 1px solid $couleur;'>&nbsp;</td>";
		$res .= "</tr>";
	}

	$s = ($ancre =='documents' ? '': '-');
	if (is_int($doc)) {
		$head = "\n<div id='$ancre'>&nbsp;</div>"
		. "\n<div style='background-color: $couleur; padding: 4px; color: black; -moz-border-radius-topleft: 5px; -moz-border-radius-topright: 5px;' class='verdana2'>\n<b>".majuscules(_T("info_$ancre"))."</b></div>";

		if (count($documents) > 3) {
			$head .= "<div style='background-color: #dddddd; padding: 4px; color: black; text-align: right' class='arial1'>"
			. ajax_action_auteur('documenter', "$s$doc/$type", $script, "id_$type=$doc&s=$s&type=$type",array(_L('Supprimer_tout')))
			. "</div>\n";
		}
	} else $head = '';

	$res = $head
	. "\n<table width='100%' cellspacing='0' cellpadding='4'>"
	. $res
	. "</table>";	  

	return ajax_action_greffe("documenter-$s$doc", $res, $flag==='ajax');
}
