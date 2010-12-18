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

// Affiche le portfolio et les documents lies a l'article (ou a la rubrique)
// => Nouveau : au lieu de les ignorer, on affiche desormais avec un fond gris
// les documents et images inclus dans le texte.

// http://doc.spip.org/@inc_documenter_dist
function inc_documenter_dist(
	$doc,		# tableau des documents ou numero de l'objet attachant
	$type = "article",	# article ou rubrique ?
	$ancre = 'portfolio',	# album d'images ou de documents ?
	$ignore_flag = false,	# IGNORE, remplace par autoriser(modifier,document)
	$couleur='',		# IGNORE couleur des cases du tableau
	$appelant =''		# pour le rappel (cf plugin)
) {
	if (is_numeric($doc)) {
		$prim = 'id_' . $type;
		$img = ($ancre == 'portfolio') ? '' : " NOT";
		$select = "D.id_document, D.id_vignette, D.extension, D.titre,  D.date,  D.descriptif,  D.fichier,  D.taille, D.largeur,  D.hauteur,  D.mode,  D.distant, L.vu, L.id_objet, L.id_objet as $prim";
		$from = "spip_documents AS D LEFT JOIN spip_documents_liens AS L ON  L.id_document=D.id_document"; 
		$where = "L.id_objet=$doc AND L.objet='$type' AND D.mode='document' AND D.extension $img IN ('gif', 'jpg', 'png')";
		$order = "0+D.titre, D.date";
		$docs = sql_allfetsel($select, $from, $where, '', $order);
		$opt = array('objet'=>$type, 'id_objet' => $doc);
	} else {
		$docs = $doc;
		$opt = array();
	}

	if (!$docs) return '';

	// On passe &$tous dans la boucle pour verifier si on a bien
	// le droit de supprimer tous les documents
	$tous = (count($docs) > 3);
	$s = ($ancre =='documents' ? '': '-');
	if (preg_match('/_edit$/', _request('script'))) {
	  $res = " ";
	} else {
	  $res = documenter_boucle($docs, $type, $ancre, $tous, $appelant, $opt);
	  if (is_int($doc))
		$res = documenter_bloc($doc, $res, $s, $appelant, $ancre, $tous, $type);
	}
	return ajax_action_greffe("documenter", "$s$doc", $res);
}

// http://doc.spip.org/@documenter_bloc
function documenter_bloc($id, $res, $s, $script, $ancre, $tous, $type)
{
	// seulement s'il y a au moins un document dedans
	if (!$res) return "";

	if ($tous) {
		$tous = "<div class='lien_tout_supprimer'>"
			. ajax_action_auteur('documenter', "$s$id/$type", $appelant ? $appelant : _request('exec'), "id_$type=$id&s=$s&type=$type",array(_T('lien_tout_supprimer')))
			. "</div>\n";
	} else $tous = '';

	$bouton = bouton_block_depliable(majuscules(_T("info_$ancre")),true,"portfolio_$ancre");

	return debut_cadre("$ancre","","",$bouton)
		. debut_block_depliable(true,"portfolio_$ancre")
		. $tous
		. $res
		. fin_block()
		. fin_cadre();
}

// http://doc.spip.org/@documenter_boucle
function documenter_boucle($documents, $type, $ancre, &$tous_autorises, $appelant, $opt=array())
{
	// la derniere case d'une rangee
	$bord_droit = ($ancre == 'portfolio' ? 2 : 1);
	$case = 0;
	$res = '';

	$tourner = charger_fonction('tourner', 'inc');
	$legender = charger_fonction('legender', 'inc');

	// Pour les doublons d'article et en mode ajax, il faut faire propre()
	/*if ($type=='article'
	AND !isset($GLOBALS['doublons_documents_inclus'])
	AND is_int($doc)) {
		$r = sql_fetsel("chapo,texte", "spip_articles", "id_article=".sql_quote($doc));
		propre(join(" ",$r));
	}*/

	$show_docs = explode(',', _request('show_docs'));

	foreach ($documents as $document) {
		$id_document = $document['id_document'];

		// $opt : options pour l'autorisation (type d'objet parent, et id de l'objet parent)
		if (!autoriser('voir', 'document', $id_document, null, $opt))
			continue;
			
		if (isset($document['script']))
			$script = $document['script']; # pour plugin Cedric
		else
		  // ref a $exec inutilise en standard
		  $script = $appelant ? $appelant : $GLOBALS['exec'];

		if (!$case)
			$res .= "<tr>";

		$flag = autoriser('modifier', 'document', $id_document);
		$tous_autorises &= $flag;
		$vu = ($document['vu']=='oui') ? ' vu':'';

		$vue_document = $tourner($id_document, $document, $script, $flag, $type);

		$editer_document =  (!$flag  ? '' :
		   $legender($id_document, $document, $script, $type, $document["id_$type"], $ancre, in_array($id_document, $show_docs)))
			. (!isset($document['info']) ? '' :
		       ("<div class='verdana1'>".$document['info']."</div>"));

		// Prevoir le passage de la vue et de l'edition sous forme de squelettes separes
		// Ces pipelines seront alors inutiles, car integres dans l'appel des squelettes
		$vue_document = pipeline('afficher_contenu_objet',
			array(
				'args' => array(
					'type'=> 'case_document',
					'id'=>$id_document
				),
				'data'=> $vue_document
			)
		);

		$editer_document = pipeline('editer_contenu_objet',
			array(
				'args' => array(
					'type'=> 'case_document',
					'id'=>$id_document
				),
				'data'=> $editer_document
			)
		);

		$res .= "\n<td  class='document$vu'>"
			. $vue_document
			. $editer_document
			. "</td>\n";

		$case++;
		if ($case > $bord_droit) {
			  $case = 0;
			  $res .= "</tr>\n";
		}
	}

	// fermer la derniere ligne
	if ($case) {
		$res .= "<td></td>";
		$res .= "</tr>";
	}

	// pas de contenu, pas de tableau
	if (!$res) return "";
	
	return "\n<table width='100%' cellspacing='0' cellpadding='4'>"
	. $res
	. "</table>";
}
?>
