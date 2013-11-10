<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

// inclure les fonctions bases du core
include_once _DIR_RESTREINT . "inc/documents.php";

include_spip('inc/actions'); // *action_auteur et determine_upload

// Constante indiquant le charset probable des documents non utf-8 joints

if (!defined('CHARSET_JOINT')) define('CHARSET_JOINT', 'iso-8859-1');

// Filtre pour #FICHIER permettant d'incruster le contenu d'un document
// Si 2e arg fourni, conversion dans le charset du site si possible

// http://doc.spip.org/@contenu_document
function contenu_document($arg, $charset='')
{
	if (is_numeric($arg)) {
		$r = Sql::fetsel("fichier,distant", "spip_documents", "id_document=".intval($arg));
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

	$r = Sql::fetsel("fichier,distant", "spip_documents", "id_document=".intval($id_document));

	if (!$r) return '';

	$f = $r['fichier'];

	if ($r['distant'] == 'oui') return $f;

	// Si droit de voir tous les docs, pas seulement celui-ci
	// il est inutilement couteux de rajouter une protection
	$r = (autoriser('voir', 'document'));
	if (($r AND $r !== 'htaccess'))
		return get_spip_doc($f);

	include_spip('inc/securiser_action');

	// cette action doit etre publique !
	return generer_url_action('acceder_document',
		$args . ($args ? "&" : '')
			. 'arg='.$id_document
			. ($ancre ? "&ancre=$ancre" : '')
			. '&cle=' . calculer_cle_action($id_document.','.$f)
			. '&file=' . rawurlencode($f)
			,true,true);
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

	$type = Sql::fetsel('titre, mime_type','spip_types_documents', "extension = " . Sql::quote($e));

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


/**
 * Afficher un document dans la colonne de gauche
 *
 * @deprecated
 *     Utiliser l'inclusion prévue ou une véritable
 *     déclaration d'objet éditorial (la colonne document
 *     est alors affichée automatiquement sur la page d'édition de l'objet)
 *
 * @param int $id
 *     Identifiant de l'objet, ou id_auteur négatif pour un nouvel objet
 * @param string $type
 *     Type d'objet
 * @param null $script
 *     ??
 * @return string
 *     Code HTML permettant de gérer des documents
 */
function afficher_documents_colonne($id, $type="article", $script=NULL) {
	return recuperer_fond('prive/objets/editer/colonne_document', array('objet'=>$type,'id_objet'=>$id));
}


/**
 * Affiche le code d'un raccourcis de document, tel que <doc123|left>
 *
 * Affiche un code de raccourcis de document, et l'insère
 * dans le textarea principal de l'objet (champ 'texte') sur un double-clic 
 *
 * @param string $doc
 * 		Type de raccourcis : doc,img,emb...
 * @param int $id
 * 		Identifiant du document
 * @param string $align
 * 		Alignement du document : left,center,right
 * 
 * @return string
 * 		Texte du raccourcis
**/
function affiche_raccourci_doc($doc, $id, $align) {
	static $num = 0;
	$pipe = $onclick = "";

	if ($align) {
		$pipe = "|$align";
		$onclick = "\nondblclick=\"barre_inserer('\\x3C$doc$id$pipe&gt;', $('textarea[name=texte]')[0]);\"\ntitle=\"". str_replace('&amp;', '&', entites_html(_T('medias:double_clic_inserer_doc')))."\"";
	} else {
		$align='center';
	}

	return "\n<div style='text-align: $align'$onclick>&lt;$doc$id$pipe&gt;</div>\n";
}

?>
