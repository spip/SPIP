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

include_spip('inc/actions'); // *action_auteur et determine_upload
include_spip('inc/date');
include_spip('base/abstract_sql');

//
// Vignette pour les documents lies
//

// http://doc.spip.org/@vignette_par_defaut
function vignette_par_defaut($ext, $size=true, $loop = true) {

	if (!$ext)
		$ext = 'txt';

	// Chercher la vignette correspondant a ce type de document
	// dans les vignettes persos, ou dans les vignettes standard
	if (!@file_exists($v = _DIR_IMG_ICONES . $ext.'.png')
	AND !@file_exists($v = _DIR_IMG_ICONES . $ext.'.gif')
	# icones standard
	AND !@file_exists($v = _DIR_IMG_ICONES_DIST . $ext.'.png')
	# cas d'une install dans un repertoire "applicatif"...
	AND !@file_exists(_ROOT_IMG_ICONES_DIST . $v)
	)
		if ($loop)
			$v = vignette_par_defaut('defaut', false, $loop=false);
		else
			$v = false; # pas trouve l'icone de base

	if (!$size) return $v;

	if ($size = @getimagesize($v)) {
		$largeur = $size[0];
		$hauteur = $size[1];
	}

	return array($v, $largeur, $hauteur);
}

//
// Affiche le document avec sa vignette par defaut
//
// Attention : en mode 'doc', si c'est un fichier graphique on prefere
// afficher une vue reduite, quand c'est possible (presque toujours, donc)
// En mode 'vignette', l'image conserve sa taille
//
// A noter : dans le portfolio prive on pousse le vice jusqu'a reduire la taille
// de la vignette -> c'est a ca que sert la variable $portfolio
// http://doc.spip.org/@image_pattern
function image_pattern($vignette) {
	return "<img src='"
			. _DIR_RACINE
			. $vignette['fichier']."'
			alt=' '
			width='".$vignette['largeur']."'
			height='".$vignette['hauteur']."' />";
}

// http://doc.spip.org/@document_et_vignette
function document_et_vignette($document, $url, $portfolio=false) {
	// a supprimer avec spip_types_documents
	$extension = spip_fetch_array(spip_query("SELECT extension, mime_type FROM	spip_types_documents WHERE id_type=".$document['id_type']));
	$mime = $extension['mime_type'];
	$extension = $extension['extension'];
	$vignette = $document['id_vignette'];

	if ($vignette) 
		$vignette = spip_fetch_array(spip_query("SELECT * FROM spip_documents WHERE id_document = ".$vignette));
	if ($vignette) {
			if (!$portfolio OR !($GLOBALS['meta']['creer_preview'] == 'oui')) {
				$image = image_pattern($vignette);
			} else {
				include_spip('inc/filtres');
				$image = filtrer('image_reduire',
					suivre_lien(_DIR_RACINE, $vignette['fichier']),
					120, 110, false, true);
			}
	}
	else if (strstr($GLOBALS['meta']['formats_graphiques'], $extension)
	AND $GLOBALS['meta']['creer_preview'] == 'oui') {
		include_spip('inc/distant');
		include_spip('inc/filtres');

		if ($document['distant'] == 'oui')
			$image = _DIR_RACINE.copie_locale($document['fichier']);
		else
			$image = _DIR_RACINE.$document['fichier'];

		if ($portfolio) {
			$image = filtrer('image_reduire',
				$image,
				110, 120, false, true);
		} else {
			$image = filtrer('image_reduire',
				$image,
				-1,-1,false, true);
		}
	} else {
		$image = '';
	}
	if (!$image) {
		list($fichier, $largeur, $hauteur) = vignette_par_defaut($extension);
		$image = "<img src='$fichier'\n\theight='$hauteur' width='$largeur' alt=' ' />";
	}

	if (!$url)
		return $image;
	else
		return "<a href='$url'\n\ttype='$mime'>$image</a>";
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
		
	include_spip('inc/minipres'); // pour l'aide quand on appelle afficher_documents_colonne depuis un squelette
	include_spip('inc/presentation'); // pour l'aide quand on appelle afficher_documents_colonne depuis un squelette
	// seuls cas connus : article, breve ou rubrique
	if ($script==NULL){
		$script = $type.'s_edit';
		if (_DIR_RESTREINT)
			$script = parametre_url(self(),"show_docs",'');
	}
	$id_document_actif = _request('show_docs');

	/// Ajouter nouvelle image
	$ret .= "<a name='images'></a>\n";
	$titre_cadre = _T('bouton_ajouter_image').aide("ins_img");

	$joindre = charger_fonction('joindre', 'inc');
	$ret .= debut_cadre_relief("image-24.gif", true, "creer.gif", $titre_cadre);
	$ret .= $joindre($script, "id_$type=$id", $id, _T('info_telecharger'),'vignette',$type,'',0,generer_url_ecrire("documents_colonne","id=$id&type=$type",true));

	$ret .= fin_cadre_relief(true);

	//// Documents associes
	$res = spip_query("SELECT docs.id_document FROM spip_documents AS docs, spip_documents_".$type."s AS l WHERE l.id_".$type."=$id AND l.id_document=docs.id_document AND docs.mode='document' ORDER BY docs.id_document");

	$documents_lies = array();
	while ($row = spip_fetch_array($res))
		$documents_lies[]= $row['id_document'];

	if (count($documents_lies)) {
		$res = spip_query("SELECT DISTINCT id_vignette FROM spip_documents WHERE id_document in (".join(',', $documents_lies).")");
		while ($v = spip_fetch_array($res))
			$vignettes[]= $v['id_vignette'];
		$docs_exclus = ereg_replace('^,','',join(',', $vignettes).','.join(',', $documents_lies));

		if ($docs_exclus) $docs_exclus = "AND l.id_document NOT IN ($docs_exclus) ";
	} else $docs_exclus = '';

	//// Images sans documents
	$images_liees = spip_query("SELECT docs.id_document FROM spip_documents AS docs, spip_documents_".$type."s AS l "."WHERE l.id_".$type."=$id AND l.id_document=docs.id_document ".$docs_exclus."AND docs.mode='vignette' ORDER BY docs.id_document");

	$ret .= "\n<p></p><div id='liste_images'>";
	while ($doc = spip_fetch_array($images_liees)) {
		$id_document = $doc['id_document'];
		$deplier = $id_document_actif==$id_document;
		$ret .= afficher_case_document($id_document, $id, $script, $type, $deplier);
	}

	/// Ajouter nouveau document
	$ret .= "</div><p>&nbsp;</p>\n<a name='documents'></a>\n<a name='portfolio'></a>\n";

	if ($GLOBALS['meta']["documents_" . $type] == 'oui') {
		$titre_cadre = _T('bouton_ajouter_document').aide("ins_doc");
		$ret .= debut_cadre_enfonce("doc-24.gif", true, "creer.gif", $titre_cadre);
		$ret .= $joindre($script, "id_$type=$id", $id, _T('info_telecharger_ordinateur'), 'document',$type,'',0,generer_url_ecrire("documents_colonne","id=$id&type=$type",true));
		$ret .= fin_cadre_enfonce(true);
	}

	// Afficher les documents lies
	$ret .= "<p></p><div id='liste_documents'>\n";

	foreach($documents_lies as $doc) {
		$id_document = $doc['id_document'];
		$deplier = $id_document_actif==$id_document;
		$ret .= afficher_case_document($doc, $id, $script, $type, $deplier);
	}
	$ret .= "</div>";
  if (!_DIR_RESTREINT){
	  $ret .= "<script src='"._DIR_JAVASCRIPT."async_upload.js' type='text/javascript'></script>\n";
	  $ret .= <<<EOF
	    <script type='text/javascript'>
	    $("form.form_upload").async_upload(async_upload_article_edit)
	    </script>
EOF;
  }
    
	return $ret;
}

//
// Affiche le raccourci <doc123|left>
// et l'insere quand on le clique
//
// http://doc.spip.org/@affiche_raccourci_doc
function affiche_raccourci_doc($doc, $id, $align) {
	if ($align) {
		$pipe = "|$align";

		if ($GLOBALS['browser_barre'])
			$onclick = "\nondblclick=\"barre_inserer('\\x3C$doc$id$pipe&gt;', document.formulaire.texte);\"\ntitle=\"". str_replace('&amp;', '&', entites_html(_T('double_clic_inserer_doc')))."\"";
	} else {
		$align='center';
	}
	return "\n<div align='$align'$onclick>&lt;$doc$id$pipe&gt;</div>\n";
}


// Est-ce que le document est inclus dans le texte ?
// http://doc.spip.org/@est_inclus
function est_inclus($id_document) {
	return isset($GLOBALS['doublons_documents_inclus']) ?
		in_array($id_document,$GLOBALS['doublons_documents_inclus']) : false;
}

//
// Afficher un document sous forme de ligne depliable (pages xxx_edit)
//
// TODO: il y a du code a factoriser avec inc/documenter

// http://doc.spip.org/@afficher_case_document
function afficher_case_document($id_document, $id, $script, $type, $deplier=false) {
	global $options, $couleur_foncee, $spip_lang_left, $spip_lang_right;

	charger_generer_url();

	$document = spip_fetch_array(spip_query("SELECT * FROM spip_documents WHERE id_document = " . intval($id_document)));

	$id_vignette = $document['id_vignette'];
	$id_type = $document['id_type'];
	$titre = $document['titre'];
	$descriptif = $document['descriptif'];
	$url = generer_url_document($id_document);
	$fichier = $document['fichier'];
	$largeur = $document['largeur'];
	$hauteur = $document['hauteur'];
	$taille = $document['taille'];
	$mode = $document['mode'];
	$distant = $document['distant'];

	// le doc est-il appele dans le texte ?
	$doublon = est_inclus($id_document);

	$cadre = strlen($titre) ? $titre : basename($document['fichier']);

	$result = spip_query("SELECT * FROM spip_types_documents WHERE id_type=$id_type");
	if ($letype = @spip_fetch_array($result)) {
		$type_extension = $letype['extension'];
		$type_inclus = $letype['inclus'];
		$type_titre = $letype['titre'];
	}

	//
	// Afficher un document
	//
	$ret = "";
	if ($mode == 'document') {
		if ($options == "avancees") {
			# 'extension', a ajouter dans la base quand on supprimera spip_types_documents
			switch ($id_type) {
				case 1:
					$document['extension'] = "jpg";
					break;
				case 2:
					$document['extension'] = "png";
					break;
				case 3:
					$document['extension'] = "gif";
					break;
			}

		$ret .= "<a id='document$id_document' name='document$id_document'></a>\n";
		$ret .= debut_cadre_enfonce("doc-24.gif", true, "", lignes_longues(typo($cadre),30));

		//
		// Affichage de la vignette
		//
		$ret .= "\n<div align='center'>";

		// Signaler les documents distants par une icone de trombone
		$ret .= ($document['distant'] == 'oui')
			? "\n<img src='"._DIR_IMG_PACK.'attachment.gif'."'\n\t style='float: $spip_lang_right;'\n\talt=\"$fichier\"\n\ttitle=\"$fichier\" />\n"
			:'';

		$ret .= document_et_vignette($document, $url, true); 
		$ret .= '</div>';
		$ret .= "\n<div class='verdana1' style='text-align: center; color: black;'>\n";
		$ret .= ($type_titre ? $type_titre : 
		      ( _T('info_document').' '.majuscules($type_extension)));
		$ret .= "</div>";

		// Affichage du raccourci <doc...> correspondant
		$raccourci = '';
		if ($options == "avancees" AND ($type_inclus == "embed" OR $type_inclus == "image") AND $largeur > 0 AND $hauteur > 0) {
			$raccourci .= "<b>"._T('info_inclusion_vignette')."</b><br />";
		}
		$raccourci .= "<div style='color: 333333'>"
		. affiche_raccourci_doc('doc', $id_document, 'left')
		. affiche_raccourci_doc('doc', $id_document, 'center')
		. affiche_raccourci_doc('doc', $id_document, 'right')
		. "</div>\n";

		if ($options == "avancees" AND ($type_inclus == "embed" OR $type_inclus == "image") AND $largeur > 0 AND $hauteur > 0) {
			$raccourci .= "<div style='padding:2px; ' class='arial1 spip_xx-small'>";
			$raccourci .= "<b>"._T('info_inclusion_directe')."</b><br />";
			$raccourci .= "<div style='color: 333333'>"
			. affiche_raccourci_doc('emb', $id_document, 'left')
			. affiche_raccourci_doc('emb', $id_document, 'center')
			. affiche_raccourci_doc('emb', $id_document, 'right')
			. "</div>\n";
			$raccourci .= "</div>";
		}

		$raccourci = $doublon
			? affiche_raccourci_doc('doc', $id_document, '')
			: $raccourci;

		$ret .= "\n<div style='padding:2px; ' class='arial1 spip_xx-small'>"
			. $raccourci."</div>\n";

		$legender = charger_fonction('legender', 'inc');
		$ret .= $legender($id_document, $document, $script, $type, $id, "document$id_document", $deplier);

		$ret .= fin_cadre_enfonce(true);
		}
	}

	//
	// Afficher une image inserable dans l'article
	//
	else if ($mode == 'vignette') {
	
		$ret .= debut_cadre_relief("image-24.gif", true, "", lignes_longues(typo($cadre),30));

		//
		// Afficher un apercu (pour les images)
		//
		if ($type_inclus == 'image') {
			$ret .= "<div style='text-align: center; padding: 2px;'>\n";
			$ret .= document_et_vignette($document, $url, true);
			$ret .= "</div>\n";
		}

		//
		// Preparer le raccourci a afficher sous la vignette ou sous l'apercu
		//
		$raccourci = "";
		if (strlen($descriptif) > 0 OR strlen($titre) > 0)
			$doc = 'doc';
		else
			$doc = 'img';

		$raccourci .=
			affiche_raccourci_doc($doc, $id_document, 'left')
			. affiche_raccourci_doc($doc, $id_document, 'center')
			. affiche_raccourci_doc($doc, $id_document, 'right');

		$raccourci = $doublon
			? affiche_raccourci_doc($doc, $id_document, '')
			: $raccourci;

		$ret .= "\n<div style='padding:2px; ' class='arial1 spip_xx-small'>"
			. $raccourci."</div>\n";


		$legender = charger_fonction('legender', 'inc');
		$ret .= $legender($id_document, $document, $script, $type, $id, "document$id_document", $deplier);
		
		$ret .= fin_cadre_relief(true);
	}
	return $ret;
}

?>
