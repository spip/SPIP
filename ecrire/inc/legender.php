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

include_spip('inc/actions'); // *action_auteur et determine_upload
include_spip('inc/presentation');
include_spip('inc/documents');
include_spip('inc/date');

// Formulaire de description d'un document (titre, date etc)
// En mode Ajax pour eviter de recharger toute la page ou il se trouve
// (surtout si c'est un portfolio)

function inc_legender_dist($id_document, $document, $script, $type, $id, $ancre) {

	// + securite (avec le script exec=legender ca vient de dehors)
	if (!preg_match('/^\w+$/',$type, $r)) {
	  return;
	}

	if ($document) {
		// premier appel
		$flag_deplie = teste_doc_deplie($id_document);
	} else if ($id_document) {
		// retour d'Ajax
		$document = spip_fetch_array(spip_query("SELECT * FROM spip_documents WHERE id_document = " . intval($id_document)));
		$flag_deplie = 'ajax';
	} else {
		return;
	}

	$descriptif = $document['descriptif'];
	$titre = $document['titre'];
	$date = $document['date'];

	if ($document['mode'] == 'vignette') {
		$supp = 'image-24.gif';
		$label = _T('entree_titre_image');
		$taille = $vignette = '';
	  
	} else {
		$supp = 'doc-24.gif';
		$label = _T('entree_titre_document');
		$taille = formulaire_taille($document);
		$vignette = vignette_formulaire_legender($id_document, $document, $script, $type, $id, $ancre);
	}

	$entete = basename($document['fichier']);
	if (($n=strlen($entete)) > 20) 
		$entete = substr($entete, 0, 10)."...".substr($entete, $n-10, $n);
	if (strlen($document['titre']))
		$entete = "<b>". typo($titre) . "</b>";

	$contenu = '';
	if ($descriptif)
	  $contenu .=  propre($descriptif)  . "<br />\n" ;
	if ($document['largeur'] OR $document['hauteur'])
	  $contenu .= _T('info_largeur_vignette',
		     array('largeur_vignette' => $document['largeur'],
			   'hauteur_vignette' => $document['hauteur']));
	else
	  $contenu .= taille_en_octets($document['taille']) . ' - ';

	if ($date) $contenu .= "<br />\n" . affdate($date);

	$corps =
	  (!$contenu ? '' :
	   "<div class='verdana1' style='text-align: center;'>$contenu</div>") .
	  "<b>$label</b><br />\n" .

	  "<input type='text' name='titre_document' class='formo' value=\"".entites_html($titre).
	  "\" size='40'	onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" /><br /><br />\n" .
	  date_formulaire_legender($date, $id_document) .
	  "<br />\n<b>".
	  _T('info_description_2').
	  "</b><br />\n" .
	  "<textarea name='descriptif_document' rows='4' class='formo' cols='*' wrap='soft'	onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\">" .
	    entites_html($descriptif) .
	  "</textarea>\n" .
	  $taille .
	  "\n<div " .
	  ($flag_deplie == 'ajax' ? '' : "class='display_au_chargement'") .
	  "id='valider_doc$id_document' align='".
	  $GLOBALS['spip_lang_right'].
	  "'>\n<input class='fondo' style='font-size:9px;' value='".
	  _T('bouton_enregistrer') .
	  "' type='submit' />" .
	  "</div>\n";

	$texte = _T('icone_supprimer_document');
	if (preg_match('/_edit$/', $script))
		$action = redirige_action_auteur('supprimer', "document-$id_document", $script, "id_$type=$id#$ancre");
	else {
		$s = ($ancre =='documents' ? '': '-');
		$action = ajax_action_auteur('documenter', "$s$id/$type/$id_document", $script, "id_$type=$id&type=$type&s=$s#$ancre", array($texte));
	}

	$corps = ajax_action_auteur("legender", $id_document, $script, "show_docs=$id_document&id_$type=$id#$ancre", $corps, "&id_document=$id_document&id=$id&type=$type&ancre=$ancre");

	$corps .=  $vignette . "\n\n\n\n"
	  .  icone_horizontale($texte, $action, $supp, "supprimer.gif", false);

	$bloc = "legender-aff-$id_document";

	$corps = "<div style='text-align:center;height:1%'>"
		. "<div style='float:".$GLOBALS['spip_lang_left']."'>"
		. ($flag_deplie ?
			bouton_block_visible($bloc) : bouton_block_invisible($bloc))
		. "</div>\n"
		. $entete
		. "</div>\n"
		. ($flag_deplie ?
			debut_block_visible($bloc) : debut_block_invisible($bloc))
		. $corps
		. fin_block();

	return ($flag_deplie === 'ajax') ? $corps :
	   "<div id='legender-$id_document' class='verdana1' style='color: " . $GLOBALS['couleur_foncee'] . "; border: 1px solid ". $GLOBALS['couleur_foncee'] ."; padding: 5px; margin-top: 3px; background-color: white'>" .
	   $corps .
	  '</div>';
}


function vignette_formulaire_legender($id_document, $document, $script, $type, $id, $ancre)
{
	$id_vignette = $document['id_vignette'];
	$texte = _T('info_supprimer_vignette');

	if (preg_match('/_edit$/', $script))
		$action = redirige_action_auteur('supprimer', "document-$id_vignette", $script, "id_$type=$id&show_docs=$id_document#$ancre");
	else {
		$s = ($ancre =='documents' ? '': '-');
		$action = ajax_action_auteur('documenter', "$s$id/$type/$id_vignette", $script, "id_$type=$id&type=$type&s=$s&show_docs=$id_document#$ancre", array($texte));
	}

	return "<hr style='margin-left: -5px; margin-right: -5px; height: 1px; border: 0px; color: #eeeeee; background-color: white;' />"
	. (!$id_vignette
	? formulaire_upload($script, "id_$type=$id",$id, _T('info_vignette_personnalisee'), 'vignette', $type, $ancre, $id_document)
	   : icone_horizontale($texte, $action, "vignette-24.png", "supprimer.gif", false));
}


// Bloc d'edition de la taille du doc (pour embed)
// http://doc.spip.org/@formulaire_taille
function formulaire_taille($document) {

	// (on ne le propose pas pour les images qu'on sait
	// lire, id_type<=3), sauf bug, ou document distant
	if ($document['id_type'] <= 3
	AND $document['hauteur']
	AND $document['largeur']
	AND $document['distant']!='oui')
		return '';
	$id_document = $document['id_document'];

	// Donnees sur le type de document
	$t = @spip_abstract_fetsel('inclus,extension',
		'spip_types_documents', "id_type=".$document['id_type']);
	$type_inclus = $t['inclus'];
	$extension = $t['extension'];

	# TODO -- pour le MP3 "l x h pixels" ne va pas
	if (($type_inclus == "embed" OR $type_inclus == "image")
	AND (
		// documents dont la taille est definie
		($document['largeur'] * $document['hauteur'])
		// ou distants
		OR $document['distant'] == 'oui'
		// ou formats dont la taille ne peut etre lue par getimagesize
		OR $extension=='rm' OR $extension=='mov' 
		OR $extension=='flv' OR $extension=='mpg'
	)) {
		return "\n<br /><b>"._T('entree_dimensions')."</b><br />\n" .
		  "<input type='text' name='largeur_document' class='fondl' style='font-size:9px;' value=\"".$document['largeur']."\" size='5' onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" />" .
		  " &#215; <input type='text' name='hauteur_document' class='fondl' style='font-size:9px;' value=\"".$document['hauteur']."\" size='5' onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" /> "._T('info_pixels');
	}
}

function date_formulaire_legender($date, $id_document) {

	if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date, $regs)){
		$mois = $regs[2];
		$jour = $regs[3];
		$annee = $regs[1];
	}
	return  "<b>"._T('info_mise_en_ligne')."</b><br />\n" .
		afficher_jour($jour, "name='jour_doc' size='1' CLASS='fondl' style='font-size:9px;'\n\tonChange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\"") .
		afficher_mois($mois, "name='mois_doc' size='1' CLASS='fondl' style='font-size:9px;'\n\tonChange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\"") .
		afficher_annee($annee, "name='annee_doc' size='1' CLASS='fondl' style='font-size:9px;'\n\tonChange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block')\"") .
		"<br />\n";
}

?>
