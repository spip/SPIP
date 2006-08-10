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
include_spip('inc/date');
include_spip('base/abstract_sql');

//
// Vignette pour les documents lies
//

function vignette_par_defaut($ext, $size=true, $loop = true) {

	if (!$ext)
		$ext = 'txt';

	// Chercher la vignette correspondant a ce type de document
	// dans les vignettes persos, ou dans les vignettes standard
	if (!@file_exists($v = _DIR_IMG_ICONES . $ext.'.png')
	AND !@file_exists($v = _DIR_IMG_ICONES . $ext.'.gif')
	# icones standard
	AND !@file_exists($v = _DIR_IMG_ICONES_DIST . $ext.'-dist.png')
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


// Quels documents a-t-on deja vu ? (gestion des doublons dans l'espace prive)
function document_vu($id_document=0) {
	static $vu = array();

	if (!_DIR_RESTREINT) {
		if (!$id_document) return join(',', array_keys($vu));
		if (!isset($vu[$id_document])) $vu[$id_document] = true;
	}
}

# Affecter les document_vu() utilises dans afficher_case_document 
# utile pour un affichage differencie des image "libres" et des images
# integrees via <imgXX|left> dans le texte

function document_a_voir($texte) {
	preg_match_all(__preg_img, $texte, $matches, PREG_SET_ORDER);
	foreach ($matches as $match) document_vu($match[2]);
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
function image_pattern($vignette) {
	return "<img src='"
			. _DIR_RACINE
			. $vignette['fichier']."'
			width='".$vignette['largeur']."'
			height='".$vignette['hauteur']."' />";
}

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
				include_spip('inc/logos');
				$image = reduire_image_logo((_DIR_RACINE . $vignette['fichier']), 120, 110);
			}
	} else if (strstr($GLOBALS['meta']['formats_graphiques'], $extension)
	AND $GLOBALS['meta']['creer_preview'] == 'oui') {
		include_spip('inc/distant');
		include_spip('inc/logos');
		$local = copie_locale($document['fichier']);
		if ($portfolio)
			$image = reduire_image_logo($local, 110, 120);
		else
			$image = reduire_image_logo($local);
	} else $image = '';

	if (!$image) {
		list($fichier, $largeur, $hauteur) = vignette_par_defaut($extension);
		$image = "<img src='$fichier'\n\theight='$hauteur' width='$largeur' />";
	}

	if (!$url)
		return $image;
	else
		return "<a href='$url'\n\ttype='$mime'>$image</a>";
}

//
// Integration (embed) multimedia
//

function embed_document($id_document, $les_parametres="", $afficher_titre=true) {
	document_vu($id_document);
	charger_generer_url();

	if ($les_parametres) {
		$parametres = explode("|",$les_parametres);

		for ($i = 0; $i < count($parametres); $i++) {
			$parametre = $parametres[$i];
			
			if (eregi("^(left|right|center)$", $parametre)) {
				$align = strtolower($parametre);
			}
			else {
				$params[] = $parametre;
			}
		}
	}

	$id_document = intval($id_document);

	if (!$row = spip_abstract_fetsel('*', 'spip_documents',
					 "id_document=$id_document"))
		return '';

	$id_type = $row['id_type'];
	$titre = propre($row ['titre']);
	$descriptif = propre($row['descriptif']);
	$fichier = generer_url_document($id_document);
	$largeur = $row['largeur'];
	$hauteur = $row['hauteur'];
	$taille = $row['taille'];
	$mode = $row['mode'];
	$largeur_vignette = $largeur;

	if ($row_type = @spip_abstract_fetsel('*', 'spip_types_documents',
					      "id_type=" . intval($id_type)))
	  {
		$type = $row_type['titre'];
		$inclus = $row_type['inclus'];
		$extension = $row_type['extension'];
	}
	else $type = 'fichier';

	if ($inclus == "embed") 
		$vignette = parametrer_embed_document($fichier, $id_document, $hauteur, $largeur, $extension, $les_parametres, $params);
	else if ($inclus == "image") {
		$fichier_vignette = $fichier;
		$largeur_vignette = $largeur;
		$hauteur_vignette = $hauteur;
		if ($fichier_vignette) {
			$vignette = "<img src='$fichier_vignette'";
			if ($largeur_vignette && $hauteur_vignette)
				$vignette .= " width='$largeur_vignette' height='$hauteur_vignette'";
			if ($titre) {
				$titre_ko = ($taille > 0) ? ($titre . " - ". taille_en_octets($taille)) : $titre;
				$titre_ko = supprimer_tags(propre($titre_ko));
				$vignette .= " alt=\"$titre_ko\" title=\"$titre_ko\"";
			}else{  $vignette .= " alt=\"\" title=\"\""; }
			$vignette .= " />";
		}
	}
		
	if (!$afficher_titre) return $vignette;

	if ($largeur_vignette < 120) $largeur_vignette = 120;
	$forcer_largeur = " width: ".$largeur_vignette."px;";

	if ($align) {
		$class_align = " spip_documents_".$align;
		if ($align <> 'center')
			$float = " style='float: $align;$forcer_largeur'";
	}

	$retour .= "<div class='spip_document_$id_document spip_documents$class_align'$float>\n";
	$retour .= $vignette;
	
	if ($titre) $retour .= "<div class='spip_doc_titre'><strong>$titre</strong></div>";
	
	if ($descriptif) {
	  $retour .= "<div class='spip_doc_descriptif'>$descriptif</div>"; 
	}

	$retour .= "</div>\n";
	
	return $retour;
}

function parametrer_embed_document($fichier, $id_document, $hauteur, $largeur, $extension, $les_parametres, $params)
{
	if ((!ereg("^controls", $les_parametres)) AND (ereg("^(rm|ra|ram)$", $extension)))
	// Pour RealVideo (??? -- c'est toujours irreel la video. [esj])
	 {
		$param = "|type=audio/x-pn-realaudio-plugin|console=Console$id_document|nojava=true|$les_parametres";

		return "\n<div>" .
		  embed_document($id_document, "controls=ImageWindow$param", false) . 
		  "</div>" .
		  embed_document($id_document, "controls=PlayButton$param", false) .
		  embed_document($id_document, "controls=PositionSlider$param", false);
	 } else {
		$inserer_vignette = '';

		for ($i = 0; $i < count($params); $i++) {
			if (ereg("([^\=]*)\=([^\=]*)", $params[$i], $vals)){
				$nom = $vals[1];
				$valeur = $vals[2];
				$inserer_vignette .= "<param name='$nom' value='$valeur' />";
				$param_emb .= " $nom='$valeur'";
				if ($nom == "controls" AND $valeur == "PlayButton") { 
					$largeur = 40;
					$hauteur = 25;
				} else if ($nom == "controls" AND $valeur == "PositionSlider") { 
					$largeur = $largeur - 40;
					$hauteur = 25;
				}
			}
		}

		$params = "<param name='movie' value='$fichier' />\n"
		  . "<param name='src' value='$fichier' />\n"
		  . $inserer_vignette;

		// Pour Flash
		if ((!ereg("^controls", $les_parametres)) AND ($extension=='swf'))

			return "<object "
			  . "type='application/x-shockwave-flash' data='$fichier' "
			  . "width='$largeur' height='$hauteur'>\n"
			  . $params
			  . "</object>\n";
		else {
			$emb = "<embed src='$fichier' $param_emb width='$largeur' height='$hauteur'>$alt</embed>\n";

			// Cas particulier du SVG : pas d'object
			if ($extension == 'svg')
				return $emb;

			/* 
			// essai pour compatibilite descendante (helas ca ne marche pas)
			// cf. http://www.yoyodesign.org/doc/w3c/svg1/backward.html
			if ($extension == 'svg') return 
			"<object type='image/svg+xml' data='$fichier'
			$param_emb width='$largeur' height='$hauteur'>$alt</object>\n";
			*/

			return "<object width='$largeur' height='$hauteur'>\n"
			  . $params
			  . $emb
			  . "</object>\n";
		}
	}
}

//
// Integration des images et documents
//

function integre_image($id_document, $align, $type_aff) {
	document_vu($id_document);
	charger_generer_url();
	$id_document = intval($id_document);

	$row = spip_abstract_fetsel('*', 'spip_documents', "id_document=$id_document");
	if (!$row) return '';

	$id_type = $row['id_type'];
	$titre = !strlen($row['titre']) ? '' : typo($row['titre']);
	$descriptif = !strlen($row['descriptif']) ?'' : propre($row['descriptif']);
	$fichier = $row['fichier'];
	$url_fichier = generer_url_document($id_document);
	$largeur = $row['largeur'];
	$hauteur = $row['hauteur'];
	$taille = $row['taille'];
	$mode = $row['mode'];
	$id_vignette = $row['id_vignette'];

	// on construira le lien en fonction du type de doc
	if ($t = @spip_abstract_fetsel("titre,extension", 'spip_types_documents', "id_type = $id_type")) {
			$extension = $t['extension']; # jpg, tex
			$type = $t['titre']; # JPEG, LaTeX
	}

	// Attention ne pas confondre :
	// pour un document affiche avec le raccourci <IMG> on a
	// $mode == 'document' et $type_aff == 'IMG'
	// inversement, pour une image presentee en mode 'DOC',
	// $mode == 'vignette' et $type_aff == 'DOC'

	// Type : vignette ou document ?
	if ($mode == 'document') {
		$vignette = document_et_vignette($row, $url_fichier);
	} else {
		$vignette = image_pattern($row);
	}

	//
	// Regler le alt et title
	//
	$alt_titre_doc = entites_html(texte_backend(supprimer_tags($titre)));
	$alt_infos_doc = entites_html($type
		. (($taille>0) ? ' - '.texte_backend(taille_en_octets($taille)) : ''));
	if ($row['distant'] == 'oui')
		$alt_infos_doc .= ", ".$url_fichier;
	if ($alt_titre_doc) $alt_sep = ', ';

	$alt = "";
	$title = "";
	// documents presentes en mode <DOC> : alt et title "JPEG, 54 ko"
	// mais pas de titre puisqu'il est en dessous
	if ($mode == 'document' AND $type_aff == 'DOC') {
		$alt = $alt_infos_doc;
		$title = $alt_infos_doc;
	}
	// document en mode <IMG> : alt + title detailles
	else if ($mode == 'document' AND $type_aff == 'IMG') {
		$alt = "$alt_titre_doc$alt_sep$alt_infos_doc";
		$title = "$alt_titre_doc$alt_sep$alt_infos_doc";
	}
	// vignette en mode <DOC> : alt disant "JPEG", pas de title
	else if ($mode == 'vignette' AND $type_aff == 'DOC') {
		$alt = "($type)";
	}
	// vignette en mode <IMG> : alt + title s'il y a un titre
	else if ($mode == 'vignette' AND $type_aff == 'IMG') {
		if (strlen($titre)){
			$alt = "$alt_titre_doc ($type)";
			$title = "$alt_titre_doc";
		}
		else
			$alt = "($type)";
	}

	$vignette = inserer_attribut($vignette, 'alt', $alt);
	if (strlen($title))
		$vignette = inserer_attribut($vignette, 'title', $title);

	// Preparer le texte sous l'image pour les <DOC>
	if ($type_aff == 'DOC') {
		if (strlen($titre))
			$txt = "<div class='spip_doc_titre'><strong>"
				. $titre
				. "</strong></div>\n";
		if (strlen($descriptif))
			$txt .= "<div class='spip_doc_descriptif'>$descriptif</div>\n";
	}

	// Passer un DIV pour les images centrees et, dans tous les cas, les <DOC>
	if (preg_match(',^(left|center|right)$,i', $align))
		$align = strtolower($align);
	else
		$align = '';
	if ($align == 'center' OR $type_aff =='DOC') {
		$span = "div";
	} else {
		$span = "span";
	}

	if ($align) {
		$class_align = " spip_documents_".$align;
		if ($align <> 'center')
			$float = "float: $align; ";
	}

	# extraire la largeur de la vignette
	$width = extraire_attribut($vignette, 'width');

	# mode <span ...> : ne pas mettre d'attributs de type block sinon MSIE Windows refuse de faire des liens dessus
	if ($span == 'span') {
		$width = 'width: '.$width.'px;';
		$vignette = "<span class='spip_document_$id_document spip_documents$class_align' style='${float}${width}'>$vignette</span>";
		return $vignette;
	}
	# mode <div ...>
	else {
		if ($align != 'center') {
			// Largeur de la div = celle de l'image ; mais s'il y a une legende
			// mettre au moins 120px
			if (strlen($txt) AND $width < 120) $width = 120;
			$width = 'width: '.$width.'px;';
			if (strlen($style = "$float$width"))
				$style = " style='$style'";
		}
		return
			"<div class='spip_document_$id_document spip_documents$class_align'$style>"
			. $vignette
			. $txt
			. '</div>';
	}
}


//
// Traitement des images et documents <IMGxx|right> pour inc_texte
//
function inserer_documents($letexte) {
	# HACK: empecher les boucles infernales lorsqu'un document est mentionne
	# dans son propre descriptif (on peut citer un document dans un autre,
	# mais il faut pas trop pousser...)
	static $pile = 0;
	if (++$pile > 5) return '';

	preg_match_all(__preg_img, $letexte, $matches, PREG_SET_ORDER);
	foreach ($matches as $match) {
		$type = strtoupper($match[1]);
		if ($type == 'EMB')
			$rempl = embed_document($match[2], $match[4]);
		else
			$rempl = integre_image($match[2], $match[4], $type);

		// Temporaire : un pis-aller pour eviter que des paragraphes dans
		// le descriptif d'un document ne soient doublonnes en <br /> :
		// le probleme est que propre() est passe deux fois...
		$rempl = preg_replace(",\n+,", " ", $rempl);

		// XHTML : remplacer par une <div onclick> le lien
		// dans le cas [<docXX>->lien] ; sachant qu'il n'existe
		// pas de bonne solution en XHTML pour produire un lien
		// sur une div (!!)...
		if (substr($rempl, 0, 5) == '<div '
		AND preg_match(',(<a [^>]+>)'.preg_quote($match[0]).'</a>,Uims',
		$letexte, $r)) {
			$lien = extraire_attribut($r[1], 'href');
			$re = '<div style="cursor:pointer;cursor:hand;" '
				.'onclick="document.location=\''.$lien
				.'\'"'
##				.' href="'.$lien.'"' # href deviendra legal en XHTML2
				.'>'
				.$rempl
				.'</div>';
			$letexte = str_replace($r[0], $re, $letexte);
		}

		// cas normal
		// Installer le document ; les <div> sont suivies de deux \n de maniere
		// a provoquer un paragraphe a la suite ; les span, non, sinon les liens
		// [<img|left>->URL] ne fonctionnent pas.
		else {
			$saut = preg_match(',<div ,', $rempl) ? "\n\n" : "";
			$letexte = str_replace($match[0], $rempl . $saut, $letexte);
		}
	}

	$pile--;
	return $letexte;
}


//
// Retourner le code HTML d'utilisation de fichiers envoyes
//

function texte_upload_manuel($dir, $inclus = '', $mode = 'document') {
	$fichiers = preg_files($dir);
	$exts = array();
	$dirs = array(); 
	$texte_upload = array();
	foreach ($fichiers as $f) {
		$f = preg_replace(",^$dir,",'',$f);
		if (ereg("\.([^.]+)$", $f, $match)) {
			$ext = strtolower($match[1]);
			if (!isset($exts[$ext])) {
				if ($ext == 'jpeg') $ext = 'jpg'; # cf. corriger_extension dans inc/getdocument
				if (spip_abstract_fetsel('extension', 'spip_types_documents', "extension='$ext'" . (!$inclus ? '':  " AND inclus='$inclus'")))
					$exts[$ext] = 'oui';
				else $exts[$ext] = 'non';
			}
			
			$k = 2*substr_count($f,'/');
			$n = strrpos($f, "/");
			if ($n === false)
			  $lefichier = $f;
			else {
			  $lefichier = substr($f, $n+1, strlen($f));
			  $ledossier = substr($f, 0, $n);
			  if (!in_array($ledossier, $dirs)) {
				$texte_upload[] = "\n<option value=\"$ledossier\">"
				. str_repeat("&nbsp;",$k) 
				._T('tout_dossier_upload', array('upload' => $ledossier))
				."</option>";
				$dirs[]= $ledossier;
			  }
			}

			if ($exts[$ext] == 'oui')
			  $texte_upload[] = "\n<option value=\"$f\">" .
			    str_repeat("&nbsp;",$k+2) .
			    $lefichier .
			    "</option>";
		}
	} 

	$texte = join('', $texte_upload);

	if ($mode == "document" AND count($texte_upload)>1) {
		$texte = "\n<option value=\"/\" style='font-weight: bold;'>"
				._T('info_installer_tous_documents')
				."</option>" . $texte;
	}

	return $texte;
}


// Bloc d'edition de la taille du doc (pour embed)
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
	)) {
		return "<br /><b>"._T('entree_dimensions')."</b><br />\n" .
		  "<input type='text' name='largeur_document' class='fondl' style='font-size:9px;' value=\"".$document['largeur']."\" size='5' onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" />" .
		  " &#215; <input type='text' name='hauteur_document' class='fondl' style='font-size:9px;' value=\"".$document['hauteur']."\" size='5' onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" /> "._T('info_pixels');
	}
}

//
// Construire un formulaire pour telecharger un fichier
//

function formulaire_upload($id, $intitule='', $inclus = '', $mode='', $type="", $ancre='', $id_document=0) {
	global $spip_lang_right;
	static $num_form = 0; $num_form ++;

	if (!_DIR_RESTREINT)
		$dir_ftp = determine_upload();
	else $dir_ftp = '';

	$res = "<input name='fichier' type='file' style='font-size: 10px;' class='forml' size='15' />" .
		"\n\t\t<div align='" .
		$GLOBALS['spip_lang_right'] . 
		"'><input name='sousaction1' type='submit' value='" .
		_T('bouton_telecharger') .
		"' class='fondo' />";

	// Un menu depliant si on a une possibilite supplementaire

	$test_distant = ($mode == 'document' AND $type);
	if ($dir_ftp OR $test_distant OR ($mode == 'vignette')) {
		$debut = "<div style='float:".$GLOBALS['spip_lang_left'].";'>"
			. bouton_block_invisible("ftp$num_form") ."</div>\n";
		$milieu = debut_block_invisible("ftp$num_form");
		$fin = fin_block();

		if ($mode == 'vignette')
			$res = $milieu . $res;
		else
			$res = $res . $milieu;
	}

	$res = $debut . ($intitule ? "<span>$intitule</span><br />" : '') .$res;

	if ($dir_ftp) {
		$l = texte_upload_manuel($dir_ftp,$inclus, $mode);
		// pour ne pas repeter l'aide en ligne dans le portolio
		if ($l OR ($mode != 'vignette'))
			$res .= afficher_transferer_upload($type, $l);
	}

	// Lien document distant, jamais en mode image
	if ($test_distant) {
		$res .=	"<p />\n<div style='border: 1px #303030 solid; padding: 4px; color: #505050;'>" .
			"\n\t<img src='"._DIR_IMG_PACK.'attachment.gif' .
			"' style='float: $spip_lang_right;' alt=\"\" />\n" .
			_T('info_referencer_doc_distant') .
			"<br />\n\t<input name='url' class='fondo' value='http://' />" .
			"\n\t<div align='" .
			$GLOBALS['spip_lang_right'] .
			"'><input name='sousaction2' type='Submit' value='".
			_T('bouton_choisir').
			"' class='fondo'></div>" .
			"\n</div>";
	}

	$res .= $fin;
	// Fin eventuel menu depliant

	$res .= "\n\t\t<input type='hidden' name='id' value='$id' />" .
		"\n\t\t<input type='hidden' name='id_document' value='$id_document' />" .
		"\n\t\t<input type='hidden' name='type' value='$type' />" .
		"\n\t\t<input type='hidden' name='ancre' value='$ancre' />" .
		"\n\t$fin";

	// a cause d'ajax, on ne peut pas faire confiance au script "documenter"
	// pour reafficher la page apres upload de la vignette... donc il faut
	// hacker
	$script = $GLOBALS['exec'];
	if ($script == 'documenter')
		$script = ($type == 'rubriques') ? 'naviguer' : 'articles';

	return generer_action_auteur('joindre',
		$mode,
		generer_url_ecrire($script, "id_$type=$id"),
		$res,
		" method='post' enctype='multipart/form-data' style='border: 0px; margin: 0px;'");
}

function construire_upload($corps, $args, $enctype='')
{
	$res = "";
	foreach($args as $k => $v)
	  if ($v)
	    $res .= "\n<input type='hidden' name='$k' value='$v' />";

# ici enlever $action pour uploader directemet dans l'espace prive (UPLOAD_DIRECT)
	return "\n<form method='post' action='" . generer_url_action('joindre') .
	  "'" .
	  (!$enctype ? '' : " enctype='$enctype'") .
	  " 
	  >\n" .
	  "<div>" .
  	  "\n<input type='hidden' name='action' value='joindre' />" .
	  $res . $corps . "</div></form>";
}

function afficher_transferer_upload($type, $texte_upload)
{
	$doc = array('upload' => '<b>' . joli_repertoire(determine_upload()) . '</b>');
	if (!$texte_upload) {
		return "<div style='border: 1px #303030 solid; padding: 4px; color: #505050;'>" .
			_T('info_installer_ftp', $doc) .
			aide("ins_upload") .
			"</div>";
		}
	else {  return
		"<p><div style='color: #505050;'>\n"
		._T('info_selectionner_fichier', $doc)
		."&nbsp;:<br />" .
		"\n<select name='chemin' size='1' class='fondl'>" .
		$texte_upload .
		"\n</select>" .
		"\n<div align='".
		$GLOBALS['spip_lang_right'] .
		"'><input name='sousaction3' type='Submit' value='" .
		_T('bouton_choisir').
		"' class='fondo'></div>" .
		"</div>\n";
	}
}

//
// Afficher les documents non inclus
// (page des articles)

function afficher_portfolio(
	$documents = array(),	# liste des documents, avec toutes les donnees
	$type = "article",	# article ou rubrique ?
	$album = 'portfolio',	# album d'images ou de documents ?
	$flag_modif = false,	# a-t-on le droit de modifier ?
	$couleur		# couleur des cases du tableau
) {
	charger_generer_url();
	global $connect_id_auteur, $connect_statut;
	global $options,  $couleur_foncee;
	global $spip_lang_left, $spip_lang_right;

	// la derniere case d'une rangee
	$bord_droit = ($album == 'portfolio' ? 2 : 1);
	$case = 0;

	foreach ($documents as $document) {
		$id_document = $document['id_document'];

		# script pour l'action des formulaires
		if (isset($document['script']))
			$script = $document['script']; # jamais utilise !?
		elseif ($type == "rubrique")
			$script = 'naviguer';
		else
			$script = 'articles';

		$style = "";
		if (!$case)
			echo "<tr style='border-top: 1px solid black;'>";
		else if ($case == $bord_droit)
			$style .= " border-$spip_lang_right: 1px solid $couleur;";
		echo "\n<td  style='width:33%; text-align: $spip_lang_left; border-$spip_lang_left: 1px solid $couleur; border-bottom: 1px solid $couleur; $style' valign='top'>";

		echo formulaire_tourner($id_document, $document, $script, $flag_modif, $type);

		if ($flag_modif)
			echo formulaire_documenter($id_document, $document, $type, $document["id_$type"], $album);

		if (isset($document['info']))
			echo "<div class='verdana1'>".$document['info']."</div>";
		echo "</td>\n";
		$case++;
				
		if ($case > $bord_droit) {
			  $case = 0;
			  echo "</tr>\n";
		}
			
		document_vu($id_document);
	}
	// fermer la derniere ligne
	if ($case) {
		echo "<td style='border-$spip_lang_left: 1px solid $couleur;'>&nbsp;</td>";
		echo "</tr>";
	}
}


function formulaire_tourner($id_document, $document, $script, $flag_modif, $type)
{
	global $spip_lang_right;

	if (!$document) {
	  	// retour d'Ajax
		$document = spip_fetch_array(spip_query("SELECT * FROM spip_documents WHERE id_document = " . intval($id_document)));
	}

	$id = $document["id_$type"];
	$titre = $document['titre'];
	$id_vignette = $document['id_vignette'];
	$fichier = entites_html($document['fichier']);

	if (isset($document['url']))
		$url = $document['url'];
	else {
		charger_generer_url();
		$url = generer_url_document($id_document);
	}

	if ($flag_modif)
		$res .= boutons_rotateurs($document, $type, $id, $id_document,$script,  $id_vignette);
	else $res = '';
	// Indiquer les documents manquants avec un panneau de warning

	if ($document['distant'] != 'oui'
	AND !@file_exists(_DIR_RACINE.$document['fichier'])) {
			$c = _T('fichier_introuvable',
					array('fichier'=>basename($document['fichier'])));
			$res .= "<img src='" . _DIR_IMG_PACK . "warning-24.gif'"
				."\n\tstyle='float: right;'\n\talt=\"$c\"\n\ttitle=\"$c\" />";
	}

	$res .= "<div style='text-align: center;'>";
	$res .= document_et_vignette($document, $url, true);
	$res .= "</div>\n";

	$res .= "<div class='verdana1' style='text-align: center;'>";
	$res .= " <font size='1' face='arial,helvetica,sans-serif' color='333333'>&lt;doc$id_document&gt;</font>";
	$res .= "</div>";

	if ($flag_modif === 'ajax') return $res;

	$boite = '';

	// Signaler les documents distants par une icone de trombone
	if ($document['distant'] == 'oui')
		$boite .= "\n<img src='"._DIR_IMG_PACK.'attachment.gif'."'\n\t style='float: $spip_lang_right;'\n\talt=\"$fichier\"\n\ttitle=\"$fichier\" />\n";
	$boite .= "<div id='tourner-$id_document'>" .
		$res .
		'</div></div>';

	return $boite;


}

function boutons_rotateurs($document, $type, $id, $id_document, $script, $id_vignette) {
	global $spip_lang_right;
	static $ftype = array(1 => 'jpg', 2 => 'png', 3 => 'gif');

	$process = $GLOBALS['meta']['image_process'];

	// bloc rotation de l'image
	// si c'est une image, qu'on sait la faire tourner, qu'elle
	// n'est pas distante, qu'elle est bien presente dans IMG/
	// qu'elle n'a pas de vignette perso ; et qu'on a la bibli !
	if ($document['distant']!='oui' AND !$id_vignette
	AND isset($ftype[$document['id_type']])
	AND strstr($GLOBALS['meta']['formats_graphiques'],
		   $ftype[$document['id_type']])
	AND ($process == 'imagick' OR $process == 'gd2'
	OR $process == 'convert' OR $process == 'netpbm')
	AND @file_exists(_DIR_RACINE.$document['fichier'])
	) {

	  return "\n<div class='verdana1' style='float: $spip_lang_right; text-align: $spip_lang_right;'>" .

		bouton_tourner_document($id, $id_document, $script, -90, $type, 'tourner-gauche.gif', _T('image_tourner_gauche')) .

		bouton_tourner_document($id, $id_document, $script,  90, $type, 'tourner-droite.gif', _T('image_tourner_droite')) .

		bouton_tourner_document($id, $id_document, $script, 180, $type, 'tourner-180.gif', _T('image_tourner_180')) .
		"</div>\n";
	}
}

function bouton_tourner_document($id, $id_document, $script, $rot, $type, $img, $title)
{
  return ajax_action_auteur("tourner",
			    "$id_document-$rot",
			    array(http_img_pack($img, $title, ''),
				  'bouton_rotation'),
			    $script,
"&id_document=$id_document&id=$id&type=$type",
"&show_docs=$id_document&id_$type=$id#tourner-$id_document");
}

function afficher_documents_non_inclus($id_article, $type = "article", $flag_modif) {
	global $couleur_claire, $connect_id_auteur, $connect_statut;
	global $options, $spip_lang_left, $spip_lang_right;


	// Afficher portfolio
	/////////

	$doublons = document_vu();

	$images_liees = spip_query("SELECT docs.*,l.id_$type FROM spip_documents AS docs, spip_documents_".$type."s AS l, spip_types_documents AS lestypes WHERE l.id_$type=$id_article AND l.id_document=docs.id_document AND docs.mode='document' AND docs.id_type=lestypes.id_type AND lestypes.extension IN ('gif', 'jpg', 'png')" . (!$doublons ?'':" AND docs.id_document NOT IN ($doublons) ") . " ORDER BY 0+docs.titre, docs.date");

	//
	// recuperer tout le tableau des images du portfolio
	//

	$documents = array();
	while ($document = spip_fetch_array($images_liees))
		$documents[] = $document;

	if (count($documents)) {
		echo "<a name='portfolio'></a>";
		echo "\n<div>&nbsp;</div>";
		echo "\n<div style='background-color: $couleur_claire; padding: 4px; color: black; -moz-border-radius-topleft: 5px; -moz-border-radius-topright: 5px;' class='verdana2'>\n<b>".majuscules(_T('info_portfolio'))."</b></div>";
		echo "\n<table width='100%' cellspacing='0' cellpadding='3'>";

		afficher_portfolio ($documents, $type, 'portfolio', $flag_modif, $couleur_claire);

		echo "\n</table>\n";
	}

	$doublons = document_vu();

	//// Documents associes
	$documents_lies = spip_query("SELECT docs.*,l.id_$type FROM spip_documents AS docs, spip_documents_".$type."s AS l WHERE l.id_$type=$id_article AND l.id_document=docs.id_document AND docs.mode='document'" . (!$doublons ? '' : " AND docs.id_document NOT IN ($doublons) ") . " ORDER BY 0+docs.titre, docs.date");

	$documents = array();
	while ($document = spip_fetch_array($documents_lies))
		$documents[] = $document;

	if (count($documents)) {
		echo "<a id='documents'></a>";
		echo "\n<div>&nbsp;</div>";
		echo "\n<div style='background-color: #aaaaaa; padding: 4px; color: black; -moz-border-radius-topleft: 5px; -moz-border-radius-topright: 5px;' class='verdana2'><b>". majuscules(_T('info_documents')) ."</b></div>";
		echo "\n<table width='100%' cellspacing='0' cellpadding='5'>";

		afficher_portfolio ($documents, $type, 'documents', $flag_modif, '#aaaaaa');
		echo "\n</table>";
	}

	if ($GLOBALS['meta']["documents_$type"] != 'non' AND $flag_modif) {
		/// Ajouter nouveau document/image

		global $browser_name;
		echo "<p>&nbsp;</p>";
		if ($browser_name=="MSIE") // eviter le formulaire upload qui se promene sur la page a cause des position:relative
			echo "<div>";
		else 	 {
			echo "\n<div align='right'>";
			echo "<table width='50%' cellpadding='0' cellspacing='0' border='0'><tr><td style='text-align: $spip_lang_left;'>";
		}
		echo debut_cadre_relief("image-24.gif", false, "", _T('titre_joindre_document'));
		echo formulaire_upload($id_article, _T('info_telecharger_ordinateur'), '', 'document', $type);
		echo fin_cadre_relief();
		if ($browser_name!=="MSIE") // eviter le formulaire upload qui se promene sur la page a cause des position:relative
			echo "</td></tr></table>";
		echo "</div>";
	}
}


//
// Afficher un document dans la colonne de gauche
//

function afficher_documents_colonne($id, $type="article", $flag_modif = true) {
	global $connect_id_auteur, $connect_statut, $options, $id_doc_actif;

	/// Ajouter nouvelle image
	echo "<a name='images'></a>\n";
	$titre_cadre = _T('bouton_ajouter_image').aide("ins_img");
	debut_cadre_relief("image-24.gif", false, "creer.gif", $titre_cadre);
	echo formulaire_upload($id, _T('info_telecharger'),'','vignette',$type);

	fin_cadre_relief();

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

	echo "\n<p />";
	while ($document = spip_fetch_array($images_liees)) {
		$id_document = $document['id_document'];
		afficher_case_document($id_document, $id, $type, $id_doc_actif == $id_document);
	}

	/// Ajouter nouveau document
	echo "<p>&nbsp;</p>\n<a name='documents'></a>\n<a name='portfolio'></a>\n";
	if ($type == "article" AND $GLOBALS['meta']["documents_$type"] != 'non') {
		$titre_cadre = _T('bouton_ajouter_document').aide("ins_doc");
		debut_cadre_enfonce("doc-24.gif", false, "creer.gif", $titre_cadre);
		echo formulaire_upload($id,_T('info_telecharger_ordinateur'), '','document',$type);
		fin_cadre_enfonce();
	}

	// Afficher les documents lies
	echo "<p />\n";
	if ($type == "article") {
		if ($documents_lies) {
			reset($documents_lies);
			while (list(, $id_document) = each($documents_lies)) {
			  afficher_case_document($id_document, $id, $type, $id_doc_actif == $id_document);
			}
		}
	}
}

//
// Affiche le raccourci &lt;doc123|left&gt;
// et l'insere quand on le clique
//
function affiche_raccourci_doc($doc, $id, $align) {
	if ($align) {
		$pipe = "|$align";

		if ($GLOBALS['browser_barre'])
			$onclick = " ondblclick='barre_inserer(\"&lt;$doc$id$pipe&gt;\", document.formulaire.texte);' title=\"". entites_html(_T('double_clic_inserer_doc'))."\"";
	} else {
		$align='center';
	}
	return "<div align='$align'$onclick>&lt;$doc$id$pipe&gt;</div>\n";
}

//
// Afficher un document sous forme de ligne depliable
//

function afficher_case_document($id_document, $id, $type, $deplier = false) {
	global $connect_id_auteur, $connect_statut;
	global $options, $couleur_foncee, $spip_lang_left, $spip_lang_right;

	charger_generer_url();
	$flag_deplie = teste_doc_deplie($id_document);

	$doublons = ','.document_vu().',';

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
	if (!$titre) {
		$titre_fichier = _T('info_sans_titre_2');
		//		$titre_fichier .= " <small>(".ereg_replace("^[^\/]*\/[^\/]*\/","",$fichier).")</small>";
	}

	$result = spip_query("SELECT * FROM spip_types_documents WHERE id_type=$id_type");
	if ($letype = @spip_fetch_array($result))	{
		$type_extension = $letype['extension'];
		$type_inclus = $letype['inclus'];
		$type_titre = $letype['titre'];
	}

	//
	// Afficher un document
	//

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

		echo "<a id='document$id_document' name='document$id_document'></a>\n";
		$titre_cadre = lignes_longues(typo($titre).typo($titre_fichier), 30);
		debut_cadre_enfonce("doc-24.gif", false, "", $titre_cadre);

		//
		// Affichage de la vignette
		//
		echo "\n<div align='center'>";
		echo document_et_vignette($document, $url, true); 
		echo '</div>';
		echo "\n<div class='verdana1' style='text-align: center; color: black;'>\n";
		echo ($type_titre ? $type_titre : 
		      ( _T('info_document').' '.majuscules($type_extension)));
		echo "</div>";


		// Affichage du raccourci <doc...> correspondant
		if (!ereg(",$id_document,", $doublons)) {
			echo "<div style='padding:2px;'><font size='1' face='arial,helvetica,sans-serif'>";
			if ($options == "avancees" AND ($type_inclus == "embed" OR $type_inclus == "image") AND $largeur > 0 AND $hauteur > 0) {
				echo "<b>"._T('info_inclusion_vignette')."</b><br />";
			}
			echo "<font color='333333'>"
			. affiche_raccourci_doc('doc', $id_document, 'left')
			. affiche_raccourci_doc('doc', $id_document, 'center')
			. affiche_raccourci_doc('doc', $id_document, 'right')
			. "</font>\n";
			echo "</font></div>";

			if ($options == "avancees" AND ($type_inclus == "embed" OR $type_inclus == "image") AND $largeur > 0 AND $hauteur > 0) {
				echo "<div style='padding:2px;'><font size='1' face='arial,helvetica,sans-serif'>";
				echo "<b>"._T('info_inclusion_directe')."</b></br>";
				echo "<font color='333333'>"
				. affiche_raccourci_doc('emb', $id_document, 'left')
				. affiche_raccourci_doc('emb', $id_document, 'center')
				. affiche_raccourci_doc('emb', $id_document, 'right')
				. "</font>\n";
				echo "</font></div>";
			}
		} else {
			echo "<div style='padding:2px;'><font size='1' face='arial,helvetica,sans-serif'>",
			  affiche_raccourci_doc('doc', $id_document, ''),
			  "</font></div>";
		}

		echo formulaire_documenter($id_document, $document, $type, $id, "document$id_document");

		fin_cadre_enfonce();
		}
	}

	//
	// Afficher une image inserable dans l'article
	//
	else if ($mode == 'vignette') {
		$titre_cadre = lignes_longues(typo($titre).typo($titre_fichier), 30);
	
		debut_cadre_relief("image-24.gif", false, "", $titre_cadre);

		//
		// Preparer le raccourci a afficher sous la vignette ou sous l'apercu
		//
		$raccourci_doc = "<div style='padding:2px;'>
		<font size='1' face='arial,helvetica,sans-serif'>";
		if (strlen($descriptif) > 0 OR strlen($titre) > 0)
			$doc = 'doc';
		else
			$doc = 'img';
		if (!ereg(",$id_document,", $doublons)) {
			$raccourci_doc .=
				affiche_raccourci_doc($doc, $id_document, 'left')
				. affiche_raccourci_doc($doc, $id_document, 'center')
				. affiche_raccourci_doc($doc, $id_document, 'right');
		} else {
			$raccourci_doc .= affiche_raccourci_doc($doc, $id_document, '');;
		}
		$raccourci_doc .= "</font></div>\n";

		//
		// Afficher un apercu (pour les images)
		//
		if ($type_inclus == 'image') {
			echo "<div style='text-align: center; padding: 2px;'>\n";
			echo document_et_vignette($document, $url, true);
			echo "</div>\n";
			if (!ereg(",$id_document,", $doublons))
				echo $raccourci_doc;
		}

		if (ereg(",$id_document,", $doublons))
			echo $raccourci_doc;

		echo formulaire_documenter($id_document, $document, $type, $id, "document$id_document");
		
		fin_cadre_relief();
	}
}

function teste_doc_deplie($id_document) {
	global $show_docs;
	static $deplies;

	if (!$deplies)
		$deplies = split('-',$show_docs);

	return in_array($id_document, $deplies);
}


function date_formulaire_documenter($date, $id_document) {

	if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date, $regs)){
		$mois = $regs[2];
		$jour = $regs[3];
		$annee = $regs[1];
	}
	return  "<b>"._T('info_mise_en_ligne')."</b><br />\n" .
		afficher_jour($jour, "NAME='jour_doc' SIZE='1' CLASS='fondl' style='font-size:9px;'\n\tonChange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\"") .
		afficher_mois($mois, "NAME='mois_doc' SIZE='1' CLASS='fondl' style='font-size:9px;'\n\tonChange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\"") .
		afficher_annee($annee, "NAME='annee_doc' SIZE='1' CLASS='fondl' style='font-size:9px;'\n\tonChange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block')\"") .
		"<br />\n";
}


// Formulaire de description d'un document (titre, date etc)
// En mode Ajax pour eviter de recharger toute la page ou il se trouve
// (surtout si c'est un portfolio)

function formulaire_documenter($id_document, $document, $type, $id, $ancre) {

	// + securite (avec le script exec=documenter ca vient de dehors)
	if (!preg_match('/^(article|rubrique)$/',$type, $r)) return;

	if ($document) {
		// premier appel
		$flag_deplie = teste_doc_deplie($id_document);
	} else if ($id_document) {
		// retour d'Ajax
		$document = spip_fetch_array(spip_query("SELECT * FROM spip_documents WHERE id_document = " . intval($id_document)));
		$flag_deplie = 'ajax';
	} else {
		spip_log("erreur dans formulaire_documenter()");
		return;
	}

	$descriptif = $document['descriptif'];
	$titre = $document['titre'];
	$date = $document['date'];

	// vers ou rediriger
	// a cause d'ajax, on ne peut pas faire confiance au script "documenter"
	// pour reafficher la page apres upload de la vignette... donc il faut
	// hacker
	$script = $GLOBALS['exec'];
	if ($script == 'documenter')
		$script = ($type == 'rubrique') ? 'naviguer' : 'articles';
 
	if ($document['mode'] == 'vignette') {

	  $label = _T('entree_titre_image');
	  $taille ='';
	  $vignette = '';
	  $supp = 'image-24.gif';
	} else {
	  $label = _T('entree_titre_document');
	  $taille = formulaire_taille($document);
	  $supp = 'doc-24.gif';

	  $id_vignette = $document['id_vignette'];
	  $vignette = "<hr style='margin-left: -5px; margin-right: -5px; height: 1px; border: 0px; color: #eeeeee; background-color: white;' />" .
	    ($id_vignette ?
	     icone_horizontale (_T('info_supprimer_vignette'), redirige_action_auteur('supprimer', "document-$id_vignette", $script, "id_$type=$id&show_docs=$id_document#$ancre"), "vignette-24.png", "supprimer.gif", false) :
	     formulaire_upload($id,_T('info_vignette_personnalisee'), false, 'vignette', $type, $ancre, $id_document));
	}

	$entete = basename($document['fichier']);
	if (($n=strlen($entete)) > 20) 
		$entete = substr($entete, 0, 10)."...".substr($entete, $n-10, $n);
	if (strlen($document['titre']))
		$entete = "<b>". typo($titre) . "</b>";

	$contenu = '';
	if ($descriptif)
	  $contenu .=  propre($descriptif)  . '<br />' ;
	if ($document['largeur'] OR $document['hauteur'])
	  $contenu .= _T('info_largeur_vignette',
		     array('largeur_vignette' => $document['largeur'],
			   'hauteur_vignette' => $document['hauteur']));
	else
	  $contenu .= taille_en_octets($document['taille']) . ' - ';

	if ($date) $contenu .= "<br />" . affdate($date);

	$corps =
	  (!$contenu ? '' :
	   "<div class='verdana1' style='text-align: center;'>$contenu</div>") .
	  "<b>$label</b><br />\n" .

	  "<input type='text' name='titre_document' class='formo' value=\"".entites_html($titre).
	  "\" size='40'	onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\"><br />\n" .
	  '<br />' . 
	  date_formulaire_documenter($date, $id_document) .
	  "<br /><b>".
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

	$corps = ajax_action_auteur("documenter", $id_document, $corps, $script, "&id_document=$id_document&id=$id&type=$type&ancre=$ancre","show_docs=$id_document&id_$type=$id#$ancre");

	$corps .= 
	  $vignette .
	  icone_horizontale(_T('icone_supprimer_document'), redirige_action_auteur('supprimer', "document-$id_document", $script, "id_$type=$id#$ancre"), $supp,  "supprimer.gif", false);

	$bloc = "documenter-aff-$id_document";

	$corps = "<div style='text-align:center;'>"
		. "<div style='float:".$GLOBALS['spip_lang_left'].";'>"
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
	   "<div id='documenter-$id_document' class='verdana1' style='color: " . $GLOBALS['couleur_foncee'] . "; border: 1px solid ". $GLOBALS['couleur_foncee'] ."; padding: 5px; margin-top: 3px; background-color: white'>" .
	   $corps .
	  '</div>';
}
?>
