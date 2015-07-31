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
include_spip('inc/date');

// donne le chemin du fichier relatif a _DIR_IMG
// pour stockage 'tel quel' dans la base de donnees
// http://doc.spip.org/@set_spip_doc
function set_spip_doc($fichier) {
	if (strpos($fichier, _DIR_IMG) === 0)
		return substr($fichier, strlen(_DIR_IMG));
	else
		return $fichier; // ex: fichier distant
}

// donne le chemin complet du fichier
// http://doc.spip.org/@get_spip_doc
function get_spip_doc($fichier) {
	// fichier distant
	if (preg_match(',^\w+://,', $fichier))
		return $fichier;

	// gestion d'erreurs, fichier=''
	if (!strlen($fichier))
		return false;

	$fichier = (strpos($fichier, _DIR_IMG) === false)
		? _DIR_IMG . $fichier
		: $fichier ;

	// fichier normal
	return $fichier;
}

// Constante indiquant le charset probable des documents non utf-8 joints

if (!defined('CHARSET_JOINT')) define('CHARSET_JOINT', 'iso-8859-1');

// Filtre pour #FICHIER permettant d'incruster le contenu d'un document
// Si 2e arg fourni, conversion dans le charset du site si possible

// http://doc.spip.org/@contenu_document
function contenu_document($arg, $charset='')
{
	if (is_numeric($arg)) {
		$r = sql_fetsel("fichier,distant", "spip_documents", "id_document=".sql_quote($arg));
		if (!$r) return '';
		$f = $r['fichier'];
		$f = ($r['distant'] =='oui') ? _DIR_RACINE . copie_locale($f) : get_spip_doc($f);
	}
	else {
		if (!@file_exists($f=$arg)){
			if (!$f = copie_locale($f))
				return '';
			$f = _DIR_RACINE . $f;
		}
	}

	$r = spip_file_get_contents($f);

	if ($charset) {
		include_spip('inc/charset');
		if ($charset !== 'auto') {
			$r = importer_charset($r, $charset);
		} elseif ($GLOBALS['meta']['charset'] == 'utf-8' AND !is_utf8($r))
			$r = importer_charset($r, CHARSET_JOINT);
	}
	return $r;
}

// http://doc.spip.org/@generer_url_document_dist
function generer_url_document_dist($id_document, $args='', $ancre='') {

	include_spip('inc/autoriser');
	if (!autoriser('voir', 'document', $id_document)) return '';

	$r = sql_fetsel("fichier,distant", "spip_documents", "id_document=".sql_quote($id_document));

	if (!$r) return '';

	$f = $r['fichier'];

	if ($r['distant'] == 'oui') return $f;

	// Si droit de voir tous les docs, pas seulement celui-ci
	// il est inutilement couteux de rajouter une protection

	$r = autoriser('voir', 'document');

	if ($r AND $r !== 'htaccess') return get_spip_doc($f);

	include_spip('inc/securiser_action');

	// cette action doit etre publique !
	return generer_url_action('acceder_document',
		$args . ($args ? "&" : '')
			. 'arg='.$id_document
			. ($ancre ? "&ancre=$ancre" : '')
			. '&cle=' . calculer_cle_action($id_document.','.$f)
			. '&file=' . rawurlencode($f)
			,false,true);
}

// http://doc.spip.org/@document_et_vignette
function document_et_vignette($document, $url, $portfolio=false) {
	$image = $document['id_vignette'];

	if ($image)
		$image = sql_fetsel("*", "spip_documents", "id_document = ".$image);
	if ($image) {
		if (!$portfolio OR !($GLOBALS['meta']['creer_preview'] == 'oui')) {
			$x = $image['largeur'];
			$y = $image['hauteur'];
		} else {
			$x = 120;
			$y = 110;
		}
		$image = get_spip_doc($image['fichier']);
	} else {
		if ($portfolio) {
			$x = 110;
			$y = 120;
		} else 	$x = $y =-1;
	}
	if (!$url) $url = generer_url_document_dist($document['id_document'], 'document');
	return vignette_automatique($image, $document, $url, $x, $y, '', "miniature_document");
}

//
// Affiche le document avec sa vignette par defaut
//
// Attention : en mode 'doc', si c'est un fichier graphique on prefere
// afficher une vue reduite, quand c'est possible (presque toujours, donc)
// En mode 'image', l'image conserve sa taille
//
// A noter : dans le portfolio prive on pousse le vice jusqu'a reduire la taille
// de la vignette -> c'est a ca que sert la variable $portfolio
// http://doc.spip.org/@vignette_automatique
function vignette_automatique($img, $doc, $lien, $x=0, $y=0, $align='', $class='spip_logos')
{
	include_spip('inc/distant');
	include_spip('inc/texte');
	include_spip('inc/filtres_images_mini');
	$e = $doc['extension'];
	if (!$img) {
		if ($img = image_du_document($doc)) {
			if (!$x AND !$y) // eviter une double reduction
				$img = image_reduire($img);
		}
		else{
			$f = charger_fonction('vignette','inc');
			$img = $f($e, false);
			$size = @getimagesize($img);
			$img = "<img src='$img' ".$size[3]." />";
		}
	}
	else{
		$size = @getimagesize($img);
		$img = "<img src='$img' ".$size[3]." />";
	}
	// on appelle image_reduire independamment de la presence ou non
	// des librairies graphiques
	// la fonction sait se debrouiller et faire de son mieux dans tous les cas
	if ($x OR $y) {
		$img = image_reduire($img, $x, $y);
	}
	$img = inserer_attribut($img, 'alt', '');
	$img = inserer_attribut($img, 'class', $class);
	if ($align) $img = inserer_attribut($img, 'align', $align);

	if (!$lien) return $img;

	$titre = supprimer_tags(typo($doc['titre']));
	$titre = " - " .taille_en_octets($doc['taille'])
	  . ($titre ? " - $titre" : "");

	$type = sql_fetsel('titre, mime_type','spip_types_documents', "extension = " . sql_quote($e));

	$mime = $type['mime_type'];
	$titre = attribut_html(couper($type['titre'] . $titre, 80));

	return "<a href='$lien' type='$mime' title='$titre'>$img</a>";
}

// Trouve une image caracteristique d'un document.
// Si celui-ci est une image et que les outils graphiques sont dispos,
// retourner le document (en exploitant sa copie locale s'il est distant).
// Autrement retourner la vignette fournie par SPIP pour ce type MIME
// Resultat: un fichier local existant

function image_du_document($document)
{
	$e = $document['extension'];
	if ((strpos($GLOBALS['meta']['formats_graphiques'], $e) !== false)
	  AND (!test_espace_prive() OR $GLOBALS['meta']['creer_preview']=='oui')
	  AND $document['fichier']) {
		if ($document['distant'] == 'oui') {
			$image = _DIR_RACINE.copie_locale($document['fichier']);
		}
		else
			$image = get_spip_doc($document['fichier']);
		if (@file_exists($image)) return $image;
	}
	return '';
}

//
// Afficher un document dans la colonne de gauche
//

// http://doc.spip.org/@afficher_documents_colonne
function afficher_documents_colonne($id, $type="article",$script=NULL) {
	include_spip('inc/autoriser');

	// il faut avoir les droits de modif sur l'article pour pouvoir uploader !
	if (!autoriser('joindredocument',$type,$id))
		return "";

	include_spip('inc/presentation'); // pour l'aide quand on appelle afficher_documents_colonne depuis un squelette
	// seuls cas connus : article, breve ou rubrique
	if ($script==NULL){
		$script = $type.'s_edit';
		if (!test_espace_prive())
			$script = parametre_url(self(),"show_docs",'');
	}
	$id_document_actif = _request('show_docs');

	$joindre = charger_fonction('joindre', 'inc');

	define('_INTERFACE_DOCUMENTS', true);
	if (!_INTERFACE_DOCUMENTS
	OR $GLOBALS['meta']["documents_$type"]=='non') {

	// Ajouter nouvelle image
	$ret = "<div id='images'>\n"
		. $joindre(array(
			'cadre' => 'relief',
			'icone' => 'image-24.gif',
			'fonction' => 'creer.gif',
			'titre' => majuscules(_T('bouton_ajouter_image')).aide("ins_img"),
			'script' => $script,
			'args' => "id_$type=$id",
			'id' => $id,
			'intitule' => _T('info_telecharger'),
			'mode' => 'image',
			'type' => $type,
			'ancre' => '',
			'id_document' => 0,
			'iframe_script' => generer_url_ecrire("documents_colonne","id=$id&type=$type",true)
		))
		. '</div><br />';

	if (!_INTERFACE_DOCUMENTS) {
		//// Images sans documents
		$res = sql_select("D.id_document", "spip_documents AS D LEFT JOIN spip_documents_liens AS T ON T.id_document=D.id_document", "T.id_objet=" . intval($id) . " AND T.objet=" . sql_quote($type) . " AND D.mode='image'", "", "D.id_document");

		$ret .= "\n<div id='liste_images'>";

		while ($doc = sql_fetch($res)) {
			$id_document = $doc['id_document'];
			$deplier = ($id_document_actif==$id_document);
			$ret .= afficher_case_document($id_document, $id, $script, $type, $deplier);
		}

		$ret .= "</div><br /><br />\n";
	}
	}

	/// Ajouter nouveau document
	$bouton = !_INTERFACE_DOCUMENTS
		? majuscules(_T('bouton_ajouter_document')).aide("ins_doc")
		: (_T('bouton_ajouter_image_document')).aide("ins_doc");

	$ret .= "<div id='documents'></div>\n<div id='portfolio'></div>\n";
	if ($GLOBALS['meta']["documents_$type"]!='non') {
		$ret .= $joindre(array(
			'cadre' => _INTERFACE_DOCUMENTS ? 'relief' : 'enfonce',
			'icone' => 'doc-24.gif',
			'fonction' => 'creer.gif',
			'titre' => $bouton,
			'script' => $script,
			'args' => "id_$type=$id",
			'id' => $id,
			'intitule' => _T('info_telecharger'),
			'mode' => _INTERFACE_DOCUMENTS ? 'choix' : 'document',
			'type' => $type,
			'ancre' => '',
			'id_document' => 0,
			'iframe_script' => generer_url_ecrire("documents_colonne","id=$id&type=$type",true)
		));
	}

	// Afficher les documents lies
	$ret .= "<br /><div id='liste_documents'>\n";

	//// Documents associes
	$res = sql_select("D.id_document", "spip_documents AS D LEFT JOIN spip_documents_liens AS T ON T.id_document=D.id_document", "T.id_objet=" . intval($id) . " AND T.objet=" . sql_quote($type)
	. ((!_INTERFACE_DOCUMENTS)
		? " AND D.mode='document'"
    	: " AND D.mode IN ('image','document')"
	), "", "D.mode, D.id_document");

	while($row = sql_fetch($res))
		$ret .= afficher_case_document($row['id_document'], $id, $script, $type, ($id_document_actif==$row['id_document']));

	$ret .= "</div>";
	if (test_espace_prive()){
		$ret .= http_script('', "async_upload.js")
		  . http_script('$("form.form_upload").async_upload(async_upload_article_edit)');
	}

	return $ret;
}

//
// Affiche le raccourci <doc123|left>
// et l'insere quand on le clique
//
// http://doc.spip.org/@affiche_raccourci_doc
function affiche_raccourci_doc($doc, $id, $align) {
	static $num = 0;

	if ($align) {
		$pipe = "|$align";

		if ($GLOBALS['browser_barre'])
			$onclick = "\nondblclick=\"barre_inserer('\\x3C$doc$id$pipe&gt;', $('textarea[name=texte]')[0]);\"\ntitle=\"". str_replace('&amp;', '&', entites_html(_T('double_clic_inserer_doc')))."\"";
	} else {
		$align='center';
	}

	return
	  ((++$num > 1) ? "" : http_script('',  "spip_barre.js"))
		. "\n<div style='text-align: $align'$onclick>&lt;$doc$id$pipe&gt;</div>\n";
}


// Est-ce que le document est inclus dans le texte ?
// http://doc.spip.org/@est_inclus
function est_inclus($id_document) {
	return isset($GLOBALS['doublons_documents_inclus']) ?
		in_array($id_document,$GLOBALS['doublons_documents_inclus']) : false;
}

//
// Afficher un document sous forme de bloc depliable
// en donnant un apercu
// et en indiquer le raccourci permettant l'incrustation
// Pour les distant, donner un bouton pour rappatriement (trombone)
// Pour les images, donnner les boutons de rotations


// http://doc.spip.org/@afficher_case_document
function afficher_case_document($id_document, $id, $script, $type, $deplier=false) {
	global $spip_lang_right;

	$document = sql_fetsel("D.id_document, D.id_vignette,D.extension,D.titre,D.descriptif,D.fichier,D.largeur,D.hauteur,D.taille,D.mode,D.distant, D.date, L.vu", "spip_documents AS D INNER JOIN spip_documents_liens AS L ON L.id_document=D.id_document", "L.id_objet=".intval($id)." AND objet=".sql_quote($type)." AND L.id_document=".intval($id_document));

	if (!$document) return "";

	$id_vignette = $document['id_vignette'];
	$extension = $document['extension'];
	$descriptif = $document['descriptif'];
	$fichier = $document['fichier'];
	$largeur = $document['largeur'];
	$hauteur = $document['hauteur'];
	$mode = $document['mode'];
	$distant = $document['distant'];
	$titre = $document['titre'];
	$legender = charger_fonction('legender', 'inc');
	$dist = '';

	$r = sql_fetsel("titre,inclus", "spip_types_documents", "extension=".sql_quote($extension));
	if ($r) {
		$type_inclus = $r['inclus'];
		$type_titre = $r['titre'];
	}

	if ($mode == 'document') {

		if ($distant == 'oui') {
			include_spip('inc/tourner');
			$dist = "\n<div class='verdana1' style='float: $spip_lang_right; text-align: $spip_lang_right;'>"
			. "\n<img src='" . chemin_image('attachment.gif') . "'\n\talt=\"$fichier\"\n\ttitle=\"$fichier\" />\n"
			. bouton_copier_local($document, $type, $id, $id_document, $script)
			. "</div>\n";
		}

		if (est_inclus($id_document))
			$raccourci = affiche_raccourci_doc('doc', $id_document, '');
		else {
			$vign= (($type_inclus == "embed" OR $type_inclus == "image") AND $largeur > 0 AND $hauteur > 0);
			$raccourci = $vign ? ("<b>"._T('info_inclusion_vignette')."</b><br />") : '';

			$raccourci .= "<div style='color: 333333'>"
			. affiche_raccourci_doc('doc', $id_document, 'left')
			. affiche_raccourci_doc('doc', $id_document, 'center')
			. affiche_raccourci_doc('doc', $id_document, 'right')
			. "</div>\n";

			if ($vign) {
				$raccourci .= "<div style='padding:2px; ' class='arial1 spip_xx-small'>";
				$raccourci .= "<b>"._T('info_inclusion_directe')."</b><br />";
				$raccourci .= "<div style='color: 333333'>"
				. affiche_raccourci_doc('emb', $id_document, 'left')
				. affiche_raccourci_doc('emb', $id_document, 'center')
				. affiche_raccourci_doc('emb', $id_document, 'right')
				. "</div>\n";
				$raccourci .= "</div>";
			}
		}
		$ninclus = false;
		$icone = 'doc-24.gif';
		$style = 'e';

	} else if ($mode == 'image') {

		$icone = 'image-24.gif';
		$style = 'r';
		$ninclus = ($type_inclus !== 'image');
		$doc = ($descriptif OR $titre) ? 'doc' : 'img';

		if (est_inclus($id_document))
			$raccourci = affiche_raccourci_doc($doc, $id_document, '');
		else {
			$raccourci =
				affiche_raccourci_doc($doc, $id_document, 'left')
				. affiche_raccourci_doc($doc, $id_document, 'center')
				. affiche_raccourci_doc($doc, $id_document, 'right');
		}

	}
	$cadre = lignes_longues(typo($titre ? $titre : basename($fichier)), 20);
	// encapsuler chaque document dans un container pour permettre son remplacement en ajax
	return  '<div>'
		. debut_cadre($style, $icone, '', $cadre, "document$id_document")
		. ($ninclus ? '' :
		   ("\n<div style='text-align: center'>"
		    . $dist
		    . document_et_vignette($document, '', true)
		    . '</div>'
		    . "\n<div class='verdana1' style='text-align: center; color: black;'>\n"
		    . ($type_titre ? $type_titre :
		       ( _T('info_document').' '.majuscules($extension)))
		    . "</div>"))
		. $apercu
		. "\n<div style='padding:2px; ' class='arial1 spip_xx-small'>"
		. $raccourci
		. "</div>\n"
		. $legender($id_document, $document, $script, $type, $id, "document$id_document", $deplier)
		. fin_cadre($style)
		. '</div>';
}

// Etablit la liste des documents orphelins, c'est-a-dire qui ne sont lies
// a rien ; renvoie un tableau (id_document)
// ici on ne join pas avec la table objet pour voir si l'objet existe vraiment
// on considere que c'est le role d'optimiser que de nettoyer les liens morts
// sinon eventuellement appeler avant une fonction nettoyer_liens_documents
// http://doc.spip.org/@lister_les_documents_orphelins
function lister_les_documents_orphelins() {
	$s = sql_select("D.id_document, D.id_vignette", "spip_documents AS D LEFT JOIN spip_documents_liens AS L ON D.id_document=L.id_document", "(L.id_objet IS NULL)");

	$orphelins = array();
	while ($t = sql_fetch($s)) {
		$orphelins[$t['id_document']] = true;
		// la vignette d'un orphelin est orpheline
		if ($t['id_vignette'])
			$orphelins[$t['id_vignette']] = true;

	}

	// les vignettes qui n'appartiennent a aucun document sont aussi orphelines
	$s = sql_select("V.id_document", "spip_documents AS V LEFT JOIN spip_documents AS D ON V.id_document=D.id_vignette", "V.mode='vignette' AND D.id_document IS NULL");
	while ($t = sql_fetch($s))
		$orphelins[$t['id_document']] = true;

	return array_keys(array_filter($orphelins));
}

// Supprimer les documents de la table spip_documents,
// ainsi que les fichiers correspondants dans IMG/
// Fonction a n'appeler que sur des documents orphelins
// http://doc.spip.org/@supprimer_documents
function supprimer_documents($liste = array()) {
	if (!count($liste))
		return;

	$in = sql_in('id_document', $liste);

	// Supprimer les fichiers locaux et les copies locales
	// des docs distants
	$s = sql_select("fichier, distant", "spip_documents", $in);
	while ($t = sql_fetch($s)) {
		if ($t['distant'] == 'oui') {
			include_spip('inc/distant');
			if ($local = copie_locale($t['fichier'], 'test'))
				spip_log("efface $local = ".$t['fichier']);
				supprimer_fichier($local);
		}
		else {
			if (@file_exists($f = get_spip_doc($t['fichier']))) {
				spip_log("efface $f");
				supprimer_fichier($f);
			}
		}
	}

	// Supprimer les entrees dans spip_documents et associees
	sql_delete('spip_documents', $in);
	// en principe il ne devrait rien y avoir ici si les documents sont bien orphelins
	sql_delete('spip_documents_liens', $in);
}

?>
