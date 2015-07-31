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

include_spip('inc/actions'); // *action_auteur et determine_upload
include_spip('inc/presentation');
include_spip('inc/documents');
include_spip('inc/date');

// Formulaire de description d'un document (titre, date etc)
// En mode Ajax pour eviter de recharger toute la page ou il se trouve
// (surtout si c'est un portfolio)

// http://doc.spip.org/@inc_legender_dist
function inc_legender_dist($id_document, $document, $script, $type, $id, $ancre, $deplier=false) {

	// premier appel
	if ($document) {
		$flag = $deplier;
	} elseif (!$id_document) {
		return '';
	} else {
	// retour d'Ajax

		$document = sql_fetsel("*", "spip_documents", "id_document = " . intval($id_document));

		$document['vu'] = sql_getfetsel("vu", 'spip_documents_liens', "id_objet=" . intval($id) ." AND objet=" . sql_quote($type) . " AND id_document=".intval($id_document));

		if (!$document['vu']) $document['vu'] = 'non';
		$flag = 'ajax';
	}

	if ($document['mode'] == 'image') {
		$supp = 'image-24.gif';
		$label = _T('entree_titre_image');
		$taille = $vignette = '';
	  
	} else {
		$supp = 'doc-24.gif';
		$label = _T('entree_titre_document');
		$taille = formulaire_taille($document);
		$vignette = "<div style='margin-bottom: 10px;'>".vignette_formulaire_legender($id_document, $document, $script, $type, $id, $ancre)."</div>";
	}

	$s = ($ancre =='documents' ? '': '-');
	$corps = legender_corps($ancre, $flag, $id, $id_document, $script, $type, $document, $label, $taille) .
		$vignette .
		"\n\n" .
		legender_suppression($id, $id_document, $ancre, $s, $script, $supp, $type);

	// ne pas afficher le bouton de changement dans le portfolio ?
	// mais tout cela n'est pas tr�s extensible et declenchera d'autres bugs plus tard...
	if (preg_match(",_edit,",$script))
		$corps .= legender_image_doc($document, $id_document, $id, $type, $s, $script, $ancre);

	$corps = block_parfois_visible("legender-aff-$id_document", legender_entete($document), $corps, "text-align:center;", $flag);

	return ajax_action_greffe("legender", $id_document, $corps);
}


function legender_entete($document)
{
	$titre = $document['titre'];
	$entete = basename($document['fichier']);
	if (($n=strlen($entete)) > 20)
		$entete = substr($entete, 0, 7)."...".substr($entete, $n-7, $n);
	if (strlen($titre))
		$entete = "<strong>". lignes_longues(typo($titre),25) . "</strong>";
	return sinon($entete,_T('info_sans_titre'));
}

function legender_suppression($id, $id_document, $ancre, $s, $script, $supp, $type)
{
	// le cas $id<0 correspond a un doc charge dans un article pas encore cree,
	// et ca buggue si on propose de supprimer => on ne propose pas
	// Le cas id = 0 correspond au cas d'une mediatheque : l'action est alors
	// sans doute a revoir car le document serait alors peut-etre orphelin

	if ($id <= 0) return '';

	// la fonction 'supprimer_document' ne fait que de-associer si un document est utilise par ailleurs
	$texte = _T('icone_supprimer_document');

	if (preg_match('/_edit$/', $script)) {
		$action = ajax_action_auteur('documenter', "$s$id/$type/$id_document", $script, "id_$type=$id&type=$type&s=$s#$ancre", array($texte), '', 'function() {jQuery(this).remove()}');
	} else {
		if (test_espace_prive())
			$action = ajax_action_auteur('documenter', "$s$id/$type/$id_document", $script, "id_$type=$id&type=$type&s=$s#$ancre", array($texte));
		else{
			$redirect = str_replace('&amp;','&',$script);
			$action = generer_action_auteur('documenter', "$s$id/$type/$id_document", $redirect);
			$action = "<a href='$action'>$texte</a>";
		}
	}

	return icone_horizontale($texte, $action, $supp, "supprimer.gif", false);
}


function legender_corps($ancre, $flag, $id, $id_document, $script, $type, $document, $label, $taille)
{
	include_spip('inc/editer');

	$contenu = '';
	$descriptif = $document['descriptif'];
	if ($descriptif)
	  $contenu .=  "<p>".PtoBR(lignes_longues(propre($descriptif),25)) . "</p>\n";
	if ($document['largeur'] OR $document['hauteur'])
	  $contenu .= _T('info_largeur_vignette',
		     array('largeur_vignette' => $document['largeur'],
			   'hauteur_vignette' => $document['hauteur']))
			 . ' &mdash; ';

	  $contenu .= taille_en_octets($document['taille']);

	$date = $document['date'];
	if ($date AND ($GLOBALS['meta']["documents_date"] == 'oui'))
		$contenu .= "<br />\n" . affdate($date);

	$corps = (!$contenu ? '' :
		  "<div class='verdana1' style='text-align: center; margin-bottom: 10px;'>$contenu</div>") .
	  "<div class='formulaire_spip formulaire_spip_compact'><ul><li class='editer_titre'><label for='titre_document$id_document'>$label</label>\n" .

	  "<input type='text' name='titre_document' id='titre_document$id_document' class='text' value=\"".entites_html($document['titre']).
	  "\" size='40'	onfocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" /></li>\n"
	  . (($GLOBALS['meta']["documents_date"] == 'oui')
	  	? "<li class='editer_date'>".date_formulaire_legender($date, $id_document)."</li>"
	  	:'' )
	  . "<li class='editer_descriptif'><label for='descriptif_document$id_document'>".
	  _T('info_description_2').
	  "</label>\n" .
	  "<textarea name='descriptif_document' id='descriptif_document$id_document' rows='4' class='textarea' cols='*' onfocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\">" .
	    entites_html($descriptif) .
	  "</textarea></li></ul></div>\n" .
	  $taille
	  
	  .controles_md5($document);

	$att_bouton = "";

	$att_span = " id='valider_doc$id_document' "
	. ($flag == 'ajax' ? '' : "class='display_au_chargement'")
	.  " style='text-align:"
	.  $GLOBALS['spip_lang_right']
	. ($flag == 'ajax' ? ';display:block' : "")
	. "'";

	if (test_espace_prive())
		return ajax_action_post("legender", $id_document, $script, "show_docs=$id_document&id_$type=$id#legender-$id_document", $corps, _T('bouton_enregistrer'), $att_bouton, $att_span, "&id_document=$id_document&id=$id&type=$type&ancre=$ancre")
		  . "<div class='nettoyeur'></div>";
	else {
		$corps = "<div class='boutons'>"
		       . $corps 
		       . "<span"
		       . $att_span
		       . "><input type='submit' value='"
		       . _T('bouton_enregistrer')
		       ."' /></span></div>";
		$redirect = parametre_url($script,'show_docs',$id_document,'&');
		$redirect = parametre_url($redirect,"id_$type",$id,'&');
		$redirect = parametre_url($redirect,"id_$type",$id,'&');
		$redirect = ancre_url($redirect,"legender-$id_document");
		return generer_action_auteur("legender", $id_document, $redirect, $corps, "\nmethod='post'");
	}
}

//
// Changement de mode image/document
//
function legender_image_doc($document, $id_document, $id, $type, $s, $script, $ancre)
{
	define('_INTERFACE_DOCUMENTS', true);
	define('_BOUTON_MODE_IMAGE', true);
	if (!_INTERFACE_DOCUMENTS) return '';
	if (!_BOUTON_MODE_IMAGE
	OR !in_array($document['extension'], array('jpg', 'gif', 'png'))) 
		return '';
	if ($document['mode'] == 'image') {
				$texte = _T('upload_info_mode_document');
				$mode = 'document';
				$logo = 'doc-24.gif';
	} else {
				$texte = _T('upload_info_mode_image');
				$mode = 'image';
				$logo = 'image-24.gif';
	}

	$action = redirige_action_auteur('changer_mode_document', "$id_document/$mode", $script,
			(($id>0)
				? "id_$type=$id"
				: "new=oui"
			) . "&type=$type&s=$s#$ancre");

	return icone_horizontale($texte, $action, $logo, '', false," onclick=\"jQuery('#document$id_document').parent().animeajax().load(this.href+'&afficher_case=$id-$type-$script&var_noajax=1');return false;\"");
}


// http://doc.spip.org/@vignette_formulaire_legender
function vignette_formulaire_legender($id_document, $document, $script, $type, $id, $ancre)
{
	$id_vignette = $document['id_vignette'];
	$texte = _T('info_supprimer_vignette');

	$s = ($ancre =='documents' ? '': '-');
	if (preg_match('/_edit$/', $script)) { 
		$iframe_redirect = generer_url_ecrire("documents_colonne","id=$id&type=$type",true);
		$action = redirige_action_auteur('documenter', "$s$id/$type/$id_vignette", $script, "id_$type=$id&show_docs=$id_document#$ancre");
	} else {
		$iframe_redirect = generer_url_ecrire("documenter","id_$type=$id&type=$type",true);
		$action = ajax_action_auteur('documenter', "$s$id/$type/$id_vignette", $script, "id_$type=$id&type=$type&s=$s&show_docs=$id_document#$ancre", array($texte),'',"function(r,noeud) {noeud.innerHTML = r; \$('form.form_upload',noeud).async_upload(async_upload_portfolio_documents);}");
	}

	$joindre = charger_fonction('joindre', 'inc');

	$supprimer = icone_horizontale($texte, $action, "vignette-24.png", "supprimer.gif", false);
	if ($id<0) $supprimer = ''; // cf. ci-dessus, article pas encore cree

	return "<hr style='margin-left: -5px; margin-right: -5px; height: 1px; border: 0px; color: #eeeeee; background-color: white;' />"
	. (!$id_vignette
		? $joindre(array(
			'script' => $script,
			'args' => "id_$type=$id",
			'id' => $id,
			'intitule' => _T('info_vignette_personnalisee'),
			'mode' => 'vignette',
			'type' => $type,
			'ancre' => $ancre,
			'id_document' => $id_document,
			'titre' => '',
			'iframe_script' => $iframe_redirect
			))
		: $supprimer
	);
}


// Bloc d'edition de la taille du doc (pour embed)
// http://doc.spip.org/@formulaire_taille
function formulaire_taille($document) {

	// (on ne le propose pas pour les images qu'on sait
	// lire : gif jpg png), sauf bug, ou document distant
	if (in_array($document['extension'], array('gif','jpg','png'))
	AND $document['hauteur']
	AND $document['largeur']
	AND $document['distant']!='oui')
		return '';
	$id_document = $document['id_document'];

	// Donnees sur le type de document
	$extension = $document['extension'];
	$t = sql_fetsel('inclus','spip_types_documents', "extension=".sql_quote($extension));
	$type_inclus = $t['inclus'];

	# TODO -- pour le MP3 "l x h pixels" ne va pas
	if (($type_inclus == "embed" OR $type_inclus == "image")
	AND (
		// documents dont la taille est definie
		($document['largeur'] * $document['hauteur'])
		// ou distants
		OR $document['distant'] == 'oui'
		// ou tous les formats qui s'affichent en embed
		OR $type_inclus == "embed"
	)) {
		return "\n<br /><label for='largeur_document$id_document'>"._T('entree_dimensions')."</label><br />\n" .
		  "<input type='text' name='largeur_document' id='largeur_document$id_document' class='spip_xx-small' value=\"".$document['largeur']."\" size='5' onfocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" />" .
		  " &times; <input type='text' name='hauteur_document' id='hauteur_document$id_document' class='spip_xx-small' value=\"".$document['hauteur']."\" size='5' onfocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" /> "._T('info_pixels');
	}
}

// http://doc.spip.org/@date_formulaire_legender
function date_formulaire_legender($date, $id_document) {

	if (preg_match(",([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}),", $date, $regs)){
		$mois = $regs[2];
		$jour = $regs[3];
		$annee = $regs[1];
		$heure = $regs[4];
		$minute = $regs[5];
	}

	return  "<div>"._T('info_mise_en_ligne')."<br />\n" .
		afficher_jour($jour, "name='jour_doc' id='jour_doc$id_document' size='1' class='spip_xx-small'\n\tonchange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\"") .
		afficher_mois($mois, "name='mois_doc' id='mois_doc$id_document' size='1' class='spip_xx-small'\n\tonchange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\"") .
		afficher_annee($annee, "name='annee_doc' id='annee_doc$id_document' size='1' class='spip_xx-small'\n\tonchange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block')\"") .
		"<br />".
		afficher_heure($heure, "name='heure_doc' size='1' class='spip_xx-small'\n\tonchange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block')\"") . 
			" : ".
		afficher_minute($minute, "name='minute_doc' size='1' class='spip_xx-small'\n\tonchange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block')\"") . 
		"<br /><br /></div>\n";

}

?>
