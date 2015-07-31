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

include_ecrire ("inc_session"); // action_auteur
include_ecrire ("inc_date");

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

	if (_DIR_RESTREINT)
		return;

	if ($id_document)
		$vu[$id_document]++;
	else
		return join(',', array_keys($vu));
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
			height='".$vignette['hauteur']."'
			style='border-width: 0px;' />";
}

function document_et_vignette($document, $url, $portfolio=false) {
	// a supprimer avec spip_types_documents
	list($extension) = spip_fetch_array(spip_query("SELECT extension FROM
		spip_types_documents WHERE id_type=".$document['id_type']));

	if ($document['id_vignette'] > 0
	AND $vignette = spip_fetch_array(spip_query("SELECT * FROM spip_documents
	WHERE id_document = ".$document['id_vignette']))) {
		if (!$portfolio OR !($GLOBALS['meta']['creer_preview'] == 'oui')) {
			$image = image_pattern($vignette);
		} else {
			include_ecrire("inc_logos");
			$image = reduire_image_logo((_DIR_RACINE . $vignette['fichier']), 120, 110);
		}
	} else if (strstr($GLOBALS['meta']['formats_graphiques'], $extension)
	AND $GLOBALS['meta']['creer_preview'] == 'oui') {
		include_ecrire('inc_distant');
		include_ecrire('inc_logos');
		#var_dump($document);
		$local = copie_locale($document['fichier']);
		if ($portfolio)
			$image = reduire_image_logo($local, 110, 120);
		else
			$image = reduire_image_logo($local);
	}

	if (!$image) {
		list($fichier, $largeur, $hauteur) = vignette_par_defaut($extension);
		$image = "<img src='$fichier' style='border-width: 0px;'  height='$hauteur' width='$largeur' />";
	}

	if (!$url)
		return $image;
	else
		return "<a href='$url'>$image</a>";
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

	$s = spip_query("SELECT * FROM spip_documents
		WHERE id_document = " . intval($id_document));
	if (!($row = spip_fetch_array($s))) 
		return '';
	$id_document = $row['id_document'];
	$id_type = $row['id_type'];
	$titre = propre($row ['titre']);
	$descriptif = propre($row['descriptif']);
	$fichier = generer_url_document($id_document);
	$largeur = $row['largeur'];
	$hauteur = $row['hauteur'];
	$taille = $row['taille'];
	$mode = $row['mode'];

	$result_type = spip_query("SELECT * FROM spip_types_documents WHERE id_type=" . intval($id_type));
	if ($row_type = @spip_fetch_array($result_type)) {
		$type = $row_type['titre'];
		$inclus = $row_type['inclus'];
		$extension = $row_type['extension'];
	}
	else $type = 'fichier';

	// Pour RealVideo
	$real = ((!ereg("^controls", $les_parametres)) AND (ereg("^(rm|ra|ram)$", $extension)));
	// Pour Flash
	$flash = ((!ereg("^controls", $les_parametres)) AND (ereg("^(swf)$", $extension)));

	if ($inclus == "embed" AND !$real) {
		
				for ($i = 0; $i < count($params); $i++) {
					if (ereg("([^\=]*)\=([^\=]*)", $params[$i], $vals)){
						$nom = $vals[1];
						$valeur = $vals[2];
						$inserer_vignette .= "<param name='$nom' value='$valeur' />";
						$param_emb .= " $nom='$valeur'";
						if ($nom == "controls" AND $valeur == "PlayButton") { 
							$largeur = 40;
							$hauteur = 25;
						}
						else if ($nom == "controls" AND $valeur == "PositionSlider") { 
							$largeur = $largeur - 40;
							$hauteur = 25;
						}
					}
				}
				
				$vignette = "<object ";
				if ($flash)
					$vignette .=
					"type='application/x-shockwave-flash' data='$fichier' ";

				$vignette .= "width='$largeur' height='$hauteur'>\n";
				$vignette .= "<param name='movie' value='$fichier' />\n";
				$vignette .= "<param name='src' value='$fichier' />\n";
				$vignette .= $inserer_vignette;

				if (!$flash)
					$vignette .= "<embed src='$fichier' $param_emb width='$largeur' height='$hauteur'></embed>\n";
				$vignette .= "</object>\n";
	}
	else if ($inclus == "embed" AND $real) {
			$vignette .= "<div>".embed_document ($id_document, "controls=ImageWindow|type=audio/x-pn-realaudio-plugin|console=Console$id_document|nojava=true|$les_parametres", false)."</div>";
			$vignette .= embed_document ($id_document, "controls=PlayButton|type=audio/x-pn-realaudio-plugin|console=Console$id_document|nojava=true|$les_parametres", false);
			$vignette .= embed_document ($id_document, "controls=PositionSlider|type=audio/x-pn-realaudio-plugin|console=Console$id_document|nojava=true|$les_parametres", false);
		}
	else if ($inclus == "image") {
		$fichier_vignette = $fichier;
		$largeur_vignette = $largeur;
		$hauteur_vignette = $hauteur;
		if ($fichier_vignette) {
			$vignette = "<img src='$fichier_vignette' style='border-width: 0px;'";
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
	$forcer_largeur = " width = '$largeur_vignette'";

	if ($align) {
		$class_align = " spip_documents_".$align;
		if ($align <> 'center')
			$float = " style='float: $align;'";
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


//
// Integration des images et documents
//

function integre_image($id_document, $align, $type_aff) {
	document_vu($id_document);
	charger_generer_url();

	$s = spip_query("SELECT * FROM spip_documents
		WHERE id_document = " . intval($id_document));
	if (!($row = spip_fetch_array($s)))
		return '';
	$id_document = $row['id_document'];
	$id_type = $row['id_type'];
	$titre = typo($row['titre']);
	$descriptif = propre($row['descriptif']);
	$fichier = $row['fichier'];
	$url_fichier = generer_url_document($id_document);
	$largeur = $row['largeur'];
	$hauteur = $row['hauteur'];
	$taille = $row['taille'];
	$mode = $row['mode'];
	$id_vignette = $row['id_vignette'];

	// on construira le lien en fonction du type de doc
	if ($t = @spip_fetch_array(spip_query(
	"SELECT titre,extension FROM spip_types_documents
	WHERE id_type = $id_type"))) {
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

	// documents presentes en mode <DOC> : alt et title "JPEG, 54 ko"
	// mais pas de titre puisqu'il est en dessous
	if ($mode == 'document' AND $type_aff == 'DOC') {
		$alt = " alt=\"$alt_infos_doc\" title=\"$alt_infos_doc\"";
	}
	// document en mode <IMG> : alt + title detailles
	else if ($mode == 'document' AND $type_aff == 'IMG') {
		$alt = " alt=\"$alt_titre_doc$alt_sep$alt_infos_doc\"
			title=\"$alt_titre_doc$alt_sep$alt_infos_doc\"";
	}
	// vignette en mode <DOC> : alt disant "JPEG", pas de title
	else if ($mode == 'vignette' AND $type_aff == 'DOC') {
		$alt = " alt=\"($type)\"";
	}
	// vignette en mode <IMG> : alt + title s'il y a un titre
	else if ($mode == 'vignette' AND $type_aff == 'IMG') {
		if (strlen($titre))
			$alt = " alt=\"$alt_titre_doc ($type)\" title=\"$alt_titre_doc\"";
		else
			$alt = " alt=\"($type)\"";
	}

	$vignette = str_replace(' />', "$alt />", $vignette); # inserer l'attribut

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
		$vignette = inserer_attribut($vignette, 'style', $float.'border-width: 0px; width:'.$width.'px;');
		$vignette = inserer_attribut($vignette, 'class', "spip_document_$id_document spip_documents$class_align");
		return $vignette;
	}
	# mode <div ...>
	else {
		if ($align != 'center') {
			// Largeur de la div = celle de l'image ; mais s'il y a une legende
			// mettre au moins 120px
			if (strlen($txt) AND $width < 120) $width = 120;
			$width = 'width: '.$width.'px;';
			$style = " style='$float$width'";
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

		// XHTML : remplacer par une <div onclick> le lien
		// dans le cas [<docXX>->lien] (en fait, on recherche
		// <a href="lien"><docXX></a> ; sachant qu'il n'existe
		// pas de bonne solution en XHTML pour produire un lien
		// sur une div (!!)...)
		if (preg_match(',<div ,', $rempl)
		AND preg_match_all(
		',<a\s[^>]*>([^>]*'.preg_quote($match[0]).'[^>]*)</a>,ims',
		$letexte, $mm, PREG_SET_ORDER)) {
			foreach ($mm as $m) {
				$url = extraire_attribut($m[0],'href');
				$re = '<div onclick="document.location=\''.$url
					.'\'"'
##					.' href="'.$url.'"' # note: href deviendrait legal en XHTML2
					.'>'
					.$rempl # on pourrait eliminer le <a> (tidy le fait)
					.'</div>';
				$letexte = str_replace($m[0], $re, $letexte);
			}
		}

		// Installer le document
		$letexte = str_replace($match[0], $rempl."\n\n", $letexte);
	}

	$pile--;
	return $letexte;
}


//
// Retourner le code HTML d'utilisation de fichiers uploades a la main
//

function texte_upload_manuel($dir, $inclus = '') {
	$fichiers = preg_files($dir);
	$exts = array();
	$dirs = array();
	foreach ($fichiers as $f) {
		$f = preg_replace(",^$dir,",'',$f);
		if (ereg("\.([^.]+)$", $f, $match)) {
			$ext = strtolower($match[1]);
			if (!$exts[$ext]) {
				if ($ext == 'jpeg') $ext = 'jpg';
				$req = "SELECT extension FROM spip_types_documents WHERE extension='$ext'";
				if ($inclus) $req .= " AND inclus='$inclus'";
				if (@spip_fetch_array(spip_query($req))) $exts[$ext] = 'oui';
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
				$texte_upload .= "\n<option value=\"$ledossier\">"
				. str_repeat("&nbsp;",$k) 
				._T('tout_dossier_upload', array('upload' => $ledossier))
				."</option>";
				$dirs[]= $ledossier;
			  }
			}

			if ($exts[$ext] == 'oui')
			  $texte_upload .= "\n<option value=\"$f\">" .
			    str_repeat("&nbsp;",$k+2) .
			    $lefichier .
			    "</option>";
		}
	}

	if ($texte_upload) {
		$texte_upload = "\n<option value=\"/\" style='font-weight: bold;'>"
				._T('info_installer_tous_documents')
				."</option>" . $texte_upload;
	}

	return $texte_upload;
}


// Bloc d'edition de la taille du doc (pour embed)
function afficher_formulaire_taille($document, $type_inclus='AUTO') {

	// (on ne le propose pas pour les images qu'on sait
	// lire, id_type<=3), sauf bug, ou document distant
	if ($document['id_type'] <= 3
	AND $document['hauteur']
	AND $document['largeur']
	AND $document['distant']!='oui')
		return '';

	// Si on n'a pas le type_inclus, on va le chercher dans spip_types_documents
	if ($type_inclus == 'AUTO'
	AND $r = spip_query("SELECT * FROM spip_types_documents
	WHERE id_type=".$document['id_type'])
	AND $type = @spip_fetch_array($r))
			$type_inclus = $type['inclus'];

	if (($type_inclus == "embed"  #meme pour le MP3 : "l x h pixels"? 
	OR $type_inclus == "image")
	    AND $document['largeur']
	    AND $document['hauteur']) {
		echo "<br /><b>"._T('entree_dimensions')."</b><br />\n";
		echo "<input type='text' name='largeur_document' class='fondl' style='font-size:9px;' value=\"".$document['largeur']."\" size='5'>";
		echo " &#215; <input type='text' name='hauteur_document' class='fondl' style='font-size:9px;' value=\"".$document['hauteur']."\" size='5'> "._T('info_pixels');
	}
}

//
// Afficher un formulaire d'upload
//

function afficher_upload(
$id, 
$intitule, 
$inclus = '', 
$mode, 
$type="", 
$ancre='', 
$document=0) {
  global $connect_statut, $connect_toutes_rubriques, $options, $spip_lang_right,$connect_id_auteur;
	static $num_form = 0; $num_form ++;

	$res = "\n<div>" . bouton_block_invisible("ftp$num_form") .
		$intitule . "</div>\n<div>" .
		"\n<input name='fichier' type='file' style='font-size: 10px;' class='forml' size='15' />" .
		"\n<div align='" .
		$GLOBALS['spip_lang_right'] . 
		"'><input name='sousaction1' type='Submit' VALUE='" .
		_T('bouton_telecharger') .
		"' CLASS='fondo'></div>\n";

	// Un menu depliant si on a une possibilite supplementaire
	$test_ftp = ($connect_statut == '0minirezo' AND $GLOBALS['flag_upload']);
	$test_distant = ($mode == 'document' AND $type);

	if ($test_ftp OR $test_distant)
		$res .= "<div>" . debut_block_invisible("ftp$num_form");

	if ($test_ftp)
		$res .= afficher_transferer_upload($type,
				texte_upload_manuel(_DIR_TRANSFERT,
							$inclus));


	// Lien document distant, jamais en mode image
	if ($test_distant) {
		$res .=
			"<p /><div style='border: 1px #303030 solid; padding: 4px; color: #505050;'>" .
			"<img src='"._DIR_IMG_PACK.'attachment.gif' .
			"' style='float: $spip_lang_right;' alt=\"\" />\n" .
			"\n"._T('info_referencer_doc_distant')."<br />" .
			"\n<input name='url' class='fondo' value='http://' />" .
			"\n<div align='".$GLOBALS['spip_lang_right'].
			"'><input name='sousaction2' type='Submit' value='"._T('bouton_choisir')."' class='fondo'></div>" .
			"</div>\n";
	}

	if ($test_ftp OR $test_distant)
		$res .= "</div>\n";
	// Fin menu depliant

	$res .= "</div>\n" . fin_block();

	$redirect = generer_url_ecrire($GLOBALS['exec'],
				       ("id_$type=$id" .
					(($type == "rubrique") ?
					 '&action=calculer_rubriques' : '')));

	return construire_upload($res,
				array(
				'redirect' => $redirect,
				'hash' => calculer_action_auteur("joindre $mode"),
				'id' => $id, 
				'id_auteur' => $connect_id_auteur,
				'arg' => $mode,
				'type' => $type,
				'id_document' => $document,
				'ancre' => $ancre),
				'multipart/form-data');
}

function construire_upload($corps, $args, $enctype='')
{
	$res = "";
	foreach($args as $k => $v)
	  if ($v)
	    $res .= "\n<input type='hidden' name='$k' value='$v' />";

# ici enlever $action pour uploader directemet dans l'espace prive (UPLOAD_DIRECT)
	return "\n<form method='post' action='" . generer_url_public('spip_action.php') .
	  "'" .
	  (!$enctype ? '' : " enctype='$enctype'") .
	  " style='border: 0px; margin: 0px;'>\n" .
	  "<div>" .
  	  "\n<input type='hidden' name='action' value='joindre' />" .
	  $res . $corps . "</div></form>";
}

function afficher_transferer_upload($type, $texte_upload)
{
	if (!$texte_upload) {
		return "<div style='border: 1px #303030 solid; padding: 4px; color: #505050;'>" .
		  _T('info_installer_ftp',
			  array('upload' => '<b>' . _DIR_TRANSFERT . '</b>')).
			aide("ins_upload") .
			"</div>";
		}
	else {  return
		"<p><div style='color: #505050;'>\n"
		._T('info_selectionner_fichier',
			array('upload' => '<b>' . _DIR_TRANSFERT . '</b>'))
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
	$id_article, 		# numero de l'article ou de la rubrique
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

	foreach ($documents as $document) {
		$id_document = $document['id_document'];
		$id_vignette = $document['id_vignette'];
		$titre = $document['titre'];
		$descriptif = $document['descriptif'];
		$url = generer_url_document($id_document);
		$fichier = $document['fichier'];
		$largeur = $document['largeur'];
		$hauteur = $document['hauteur'];
		$taille = $document['taille'];
		$date = $document['date'];
		$mode = $document['mode'];

		$flag_deplie = teste_doc_deplie($id_document);

		if ($case == 0) {
			echo "<tr style='border-top: 1px solid black;'>";
		}
		
		$style = "border-$spip_lang_left: 1px solid $couleur; border-bottom: 1px solid $couleur;";
		if ($case == $bord_droit) $style .= " border-$spip_lang_right: 1px solid $couleur;";
		echo "<td width='33%' style='text-align: $spip_lang_left; $style' valign='top'>";

			// Signaler les documents distants par une icone de trombone
			if ($document['distant'] == 'oui') {
				echo "<img src='"._DIR_IMG_PACK.'attachment.gif'."' style='float: $spip_lang_right;' alt=\"".entites_html($document['fichier'])."\" title=\"" .
entites_html($document['fichier'])."\" />\n";
			}

			// bloc vignette + rotation
			echo "<div style='text-align:center;'>";
			
			if ($flag_modif)
				afficher_rotateurs($album, $document, $type, $id_article, $id_document, $id_vignette);

			//
			// Recuperer la vignette et afficher le doc
			//

			// Indiquer les documents manquants
			if ($document['distant'] != 'oui'
			AND !@file_exists(_DIR_RACINE.$document['fichier']))
				echo "<img src='" . _DIR_IMG_PACK
				. "warning-24.gif' style='float: right;' alt=\""
				. _L('Attention : document manquant'). "\" title=\""
				. _L('Attention : document manquant'). "\" />";

			echo document_et_vignette($document, $url, true);

			echo "</div>"; // fin du bloc vignette + rotation


			// bloc titre et descriptif
			if ($flag_modif) {
				if ($flag_deplie)
					$triangle = bouton_block_visible("port$id_document");
				else
					$triangle = bouton_block_invisible("port$id_document");
			}
			if (strlen($titre) > 0) {
				echo "<div class='verdana2'>$triangle <b>".typo($titre)."</b></div>";
			} else {
				$nom_fichier = basename($fichier);
				
				if (strlen($nom_fichier) > 20) {
					$nom_fichier = substr($nom_fichier, 0, 10)."...".substr($nom_fichier, strlen($nom_fichier)-10, strlen($nom_fichier));
				}
				echo "<div class='verdana1'>$triangle$nom_fichier</div>";
			}


			if (strlen($descriptif) > 0) {
				echo "<div class='verdana1'>".propre($descriptif)."</div>";
			}

			// Taille de l'image ou poids du document
			echo "<div class='verdana1' style='text-align: center;'>";
			if ($largeur * $hauteur)
				echo _T('info_largeur_vignette',
					array('largeur_vignette' => $largeur,
					'hauteur_vignette' => $hauteur));
			else
				echo taille_en_octets($taille) . ' - ';

			echo " <font size='1' face='arial,helvetica,sans-serif'><font color='333333'>&lt;doc$id_document&gt;</font></font>";

			echo "</div>";


			if ($flag_modif) {
				if ($flag_deplie)
					echo debut_block_visible("port$id_document");
				else
					echo debut_block_invisible("port$id_document");

				block_document($id_article, $id_document, $type, $titre, $descriptif,$date, $document, $album);
			// fin bloc titre + descriptif
				echo fin_block();

				echo "</td>\n";
				$case ++;
				
				if ($case > $bord_droit) {
				  $case = 0;
				  echo "</tr>\n";
				}
			
			document_vu($id_document);
			}
	}
	// fermer la derniere ligne
	if ($case > 0) {
			echo "<td style='border-$spip_lang_left: 1px solid $couleur;'>&nbsp;</td>";
			echo "</tr>";
	}
}

function block_document($id, $id_document, $type, $titre, $descriptif, $date, $document, $album)
{
	global $connect_statut, $couleur_foncee, $options;

	if ($type == "rubrique") {
	  $hidden = "<input type='hidden' name='action' value='calculer_rubriques' />";
	  $script = 'naviguer';
	} else {
	  $hidden = "";
	  $script = 'articles';
	}
	echo "<div class='verdana1' style='color: $couleur_foncee; border: 1px solid $couleur_foncee; padding: 5px; margin-top: 3px;'>";
	
	echo generer_url_post_ecrire($script, "id_$type=$id", '', "#$album");
	echo "<b>"._T('titre_titre_document')."</b><br />\n";
	echo "<input type='text' onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\" name='titre_document' class='formo' style='font-size:11px;' value=\"".entites_html($titre)."\" size='40'><br />\n";
	echo "<input type='hidden' name='modif_document' value='oui' />";
	echo "<input type='hidden' name='id_document' value='$id_document' />";
	echo "<input type='hidden' name='show_docs' value='$id_document' />";
	echo $hidden;

	// modifier la date
	if ( #$type == 'rubrique' AND  // (seulement dans les rubriques?)
	    $options == "avancees" AND
	    $connect_statut == '0minirezo') {
		if (ereg("([0-9]{4})-([0-9]{2})-([0-9]{2})", $date, $regs)){
						$mois = $regs[2];
						$jour = $regs[3];
						$annee = $regs[1];
		}
		echo "<b>"._T('info_mise_en_ligne')."</b><br />\n",
			afficher_jour($jour, "NAME='jour_doc' SIZE='1' CLASS='fondl' style='font-size:9px;'\n\tonChange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\""),
			afficher_mois($mois, "NAME='mois_doc' SIZE='1' CLASS='fondl' style='font-size:9px;'\n\tonChange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\""),
			afficher_annee($annee, "NAME='annee_doc' SIZE='1' CLASS='fondl' style='font-size:9px;'\n\tonChange=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block')\""),
		       "<br />\n";
	}

	// bloc descriptif (affiche ou hidden)
	if ($options == "avancees") {
		echo "<b>"._T('info_description')."</b><br />\n";
		echo "<textarea name='descriptif_document' rows='4' class='forml' style='font-size:10px;' cols='*' wrap='soft' onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\">";
		echo entites_html($descriptif);
		echo "</textarea>\n";
		
		if ($options == "avancees")
		  afficher_formulaire_taille($document);

	} else {
		echo "<input type='hidden' name='descriptif_document' value=\"".entites_html($descriptif)."\" />\n";
				}

	echo "<div class='display_au_chargement' id='valider_doc$id_document' align='".$GLOBALS['spip_lang_right']."'>";
	echo "<input TYPE='submit' class='fondo' NAME='Valider' VALUE='"._T('bouton_enregistrer')."'>";
	echo "</div>";
	echo "</form>";


	// bloc mettre a jour la vignette
	echo "<hr />";
	bloc_gerer_vignette($document, $id, $type, $album);

	echo "</div>";
				
	// bouton "supprimer le doc"
	icone_horizontale(_T('icone_supprimer_document'), bouton_supprime_document_et_vignette($id, $type, $id_document, $album), "image-24.gif",  "supprimer.gif");
} 

function  afficher_rotateurs($album, $document, $type, $id_article, $id_document, $id_vignette) {
	global $spip_lang_right;
	static $ftype = array(1 => 'jpg', 2 => 'png', 3 => 'gif');

	$process = $GLOBALS['meta']['image_process'];

	// bloc rotation de l'image
	// si c'est une image, qu'on sait la faire tourner, qu'elle
	// n'est pas distante, qu'elle est bien presente dans IMG/
	// qu'elle n'a pas de vignette perso ; et qu'on a la bibli !
	if ($document['distant']!='oui' AND !$id_vignette
	AND strstr($GLOBALS['meta']['formats_graphiques'],
		   $ftype[$document['id_type']])
	AND ($process == 'imagick' OR $process == 'gd2'
	OR $process == 'convert' OR $process == 'netpbm')
	AND @file_exists(_DIR_RACINE.$document['fichier'])
	) {

		echo "\n<div class='verdana1' style='float: $spip_lang_right; text-align: $spip_lang_right;'>";

		  // tourner a gauche
		echo http_href_img(bouton_tourner_document($id_article, $id_document, $album, -90, $type), 'tourner-gauche.gif', "style='border-width: 0px;'", _T('image_tourner_gauche'), '', 'bouton_rotation');
		echo "<br />";

		// tourner a droite
		echo http_href_img(bouton_tourner_document($id_article, $id_document, $album, 90, $type),
					   'tourner-droite.gif', "style='border-width: 0px;'",
					   _T('image_tourner_droite'), '', 'bouton_rotation');
		echo "<br />";

		// tourner 180
		echo http_href_img(bouton_tourner_document($id_article, $id_document, $album, 180, $type),
				   'tourner-180.gif', "style='border-width: 0px;'",
				   _T('image_tourner_180'), '', 'bouton_rotation');
		
		echo "</div>\n";
	}
}


function bouton_tourner_document($id_article, $id, $album, $rot, $type)
{
	$redirect = generer_url_ecrire($GLOBALS['exec'], ("id_$type=$id_article&show_docs=$id"), true) . "#$album";

	return generer_action_auteur('tourner', $id, $redirect) .
		("&amp;var_rot=$rot");
}

function bouton_supprime_document_et_vignette($id_article, $type, $id_v, $album, $id_document=0)
{

	$redirect = generer_url_ecrire($GLOBALS['exec'], ("id_$type=$id_article"), true) . "#$album";

	return generer_action_auteur('supprimer', $id_v, $redirect);
}

function bloc_gerer_vignette($document, $id_article, $type, $album) {
	global $connect_id_auteur;

	$id_document = $document['id_document'];
	$id_vignette = $document['id_vignette'];

	echo bouton_block_invisible("gerer_vignette$id_document");
	echo "<b>"._T('info_vignette_personnalisee')."</b>\n";
	echo debut_block_invisible("gerer_vignette$id_document");
	if ($id_vignette) {
	  icone_horizontale (_T('info_supprimer_vignette'), bouton_supprime_document_et_vignette($id_article,	$type, $id_vignette, $album, $id_document), "vignette-24.png", "supprimer.gif");
	} else {

	  echo afficher_upload($id_article,'portfolio', false, 'vignette', $type, $album, $id_document);
	}
	echo fin_block();
}

function afficher_documents_non_inclus($id_article, $type = "article", $flag_modif) {
	global $couleur_claire, $connect_id_auteur, $connect_statut;
	global $options, $spip_lang_left, $spip_lang_right;

	// Afficher portfolio
	/////////

	$query = "SELECT docs.* FROM spip_documents AS docs, spip_documents_".$type."s AS l, spip_types_documents AS lestypes ".
		"WHERE l.id_$type=$id_article AND l.id_document=docs.id_document ".
		"AND docs.mode='document'".
		" AND docs.id_type=lestypes.id_type AND lestypes.extension IN ('gif', 'jpg', 'png')";

	if ($doublons = document_vu())
		$query .= " AND docs.id_document NOT IN ($doublons) ";
	$query .= " ORDER BY 0+docs.titre, docs.titre, docs.id_document";

	//
	// recuperer tout le tableau des images du portfolio
	//
	$images_liees = spip_query($query);
	$documents = array();
	while ($document = spip_fetch_array($images_liees))
		$documents[] = $document;

	if (count($documents)) {
		echo "<a name='portfolio'></a>";
		echo "<div>&nbsp;</div>";
		echo "<div style='background-color: $couleur_claire; padding: 4px; color: black; -moz-border-radius-topleft: 5px; -moz-border-radius-topright: 5px;' class='verdana2'><b>".majuscules(_T('info_portfolio'))."</b></div>";
		echo "<table width='100%' cellspacing='0' cellpadding='3'>";

		afficher_portfolio ($documents, $id_article, $type, 'portfolio', $flag_modif, $couleur_claire);

		echo "</table>\n";
	}

	//// Documents associes
	$query = "SELECT * FROM spip_documents AS docs, spip_documents_".$type."s AS l ".
		"WHERE l.id_$type=$id_article AND l.id_document=docs.id_document ".
		"AND docs.mode='document'";

	if ($doublons = document_vu())
		$query .= " AND docs.id_document NOT IN ($doublons) ";

	$query .= " ORDER BY 0+docs.titre, docs.titre, docs.id_document";

	$documents_lies = spip_query($query);

	$documents = array();
	while ($document = spip_fetch_array($documents_lies))
		$documents[] = $document;

	if (count($documents)) {
		echo "<a id='documents'></a>";
		echo "<div>&nbsp;</div>";
		echo "<div style='background-color: #aaaaaa; padding: 4px; color: black; -moz-border-radius-topleft: 5px; -moz-border-radius-topright: 5px;' class='verdana2'><b>". majuscules(_T('info_documents')) ."</b></div>";
		echo "<table width='100%' cellspacing='0' cellpadding='5'>";

		afficher_portfolio ($documents, $id_article, $type, 'documents', $flag_modif, '#aaaaaa');
		echo "</table>";
	}

	if ($GLOBALS['meta']["documents_$type"] != 'non' AND $flag_modif) {
		/// Ajouter nouveau document/image

		echo "<p>&nbsp;</p>";
		echo "<div align='right'>";
		echo "<table width='50%' cellpadding=0 cellspacing=0 border=0><tr><td style='text-align: $spip_lang_left;'>";
		echo debut_cadre_relief("image-24.gif", false, "", _T('titre_joindre_document'));
		echo afficher_upload($id_article, _T('info_telecharger_ordinateur'), '', 'document', $type);
		echo fin_cadre_relief();
		echo "</td></tr></table>";
		echo "</div>";
	}
}


//
// Afficher un document dans la colonne de gauche
//

function afficher_documents_colonne($id, $type="article", $flag_modif = true) {
	global $connect_id_auteur, $connect_statut, $options;
	global $id_doc_actif;

	# HACK!!! simule une mise en page pour affecter les document_vu()
	# utilises dans afficher_case_document appelee plus loin :
	# utile pour un affichage differencie des image "libres" et des images
	# integrees via <imgXX|left> dans le texte
	propre($GLOBALS['descriptif']." ".$GLOBALS['texte']." ".$GLOBALS['chapo']);
	/// Ajouter nouvelle image
	echo "<a name='images'></a>\n";
	$titre_cadre = _T('bouton_ajouter_image').aide("ins_img");
	debut_cadre_relief("image-24.gif", false, "creer.gif", $titre_cadre);

	echo afficher_upload($id, _T('info_telecharger'),'','vignette',$type);

	fin_cadre_relief();

	//// Documents associes
	$query = "SELECT docs.id_document FROM spip_documents AS docs, spip_documents_".$type."s AS l ".
		"WHERE l.id_".$type."=$id AND l.id_document=docs.id_document ".
		"AND docs.mode='document' ORDER BY docs.id_document";

	$res = spip_query($query);
	$documents_lies = array();
	while ($row = spip_fetch_array($res))
		$documents_lies[]= $row['id_document'];

	if (count($documents_lies)) {
		$res = spip_query("SELECT DISTINCT id_vignette FROM spip_documents ".
			"WHERE id_document in (".join(',', $documents_lies).")");
		while ($v = spip_fetch_array($res))
			$vignettes[]= $v['id_vignette'];
		$docs_exclus = ereg_replace('^,','',join(',', $vignettes).','.join(',', $documents_lies));

		if ($docs_exclus) $docs_exclus = "AND l.id_document NOT IN ($docs_exclus) ";
	}

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
		echo afficher_upload($id,_T('info_telecharger_ordinateur'), '','document',$type);
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
		$titre_fichier .= " <small>(".ereg_replace("^[^\/]*\/[^\/]*\/","",$fichier).")</small>";
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
		echo "<a id='document$id_document' name='document$id_document'></a>\n";
		$titre_cadre = lignes_longues(typo($titre).typo($titre_fichier), 30);
		debut_cadre_enfonce("doc-24.gif", false, "", $titre_cadre);

		echo "<div style='float: $spip_lang_left;'>";
		$block = "document $id_document";
		if ($flag_deplie) echo bouton_block_visible($block);
		else echo bouton_block_invisible($block);
		echo "</div>";


		//
		// Affichage de la vignette
		//
		echo "<div align='center'>\n";
		echo document_et_vignette($document, $url, true); 
		echo "</div>\n";


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
		}

		//
		// Edition des champs
		//

		if ($flag_deplie)
			echo debut_block_visible($block);
		else
			echo debut_block_invisible($block);

		if (ereg(",$id_document,", $doublons)) {
			echo "<div style='padding:2px;'><font size='1' face='arial,helvetica,sans-serif'>";
			echo affiche_raccourci_doc('doc', $id_document, '');
			echo "</font></div>";
		}

		echo "<div class='verdana1' style='color: $couleur_foncee; border: 1px solid $couleur_foncee; padding: 5px; margin-top: 3px; text-align: left; background-color: white;'>";
		if (strlen($descriptif) > 0) echo propre($descriptif)."<br />";


		if ($options == "avancees") {
			echo "<div style='color: black;'>";
			if ($type_titre){
			  echo $type_titre;
			} else {
				echo _T('info_document').' '.majuscules($type_extension);
			}

			if ($largeur * $hauteur)
				echo ", "._T('info_largeur_vignette',
					array('largeur_vignette' => $largeur,
					'hauteur_vignette' => $hauteur));

			echo ', '.taille_en_octets($taille);
			echo "</div>";
		}

		echo generer_url_post_ecrire($GLOBALS['exec'],
					     "id_$type=$id&modif_document=oui&id_document=$id_document&show_docs=$id_document",
					     "",
					     "#document$id_document");

		echo "<b>"._T('entree_titre_document')."</b><br />\n";
		echo "<input type='text' name='titre_document' class='formo' value=\"".entites_html($titre)."\" size='40'
	onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\"><br />\n";

		if ($descriptif OR $options == "avancees") {
			echo "<b>"._T('info_description_2')."</b><br />\n";
			echo "<textarea name='descriptif_document' rows='4' class='formo' cols='*' wrap='soft'
	onFocus=\"changeVisible(true, 'valider_doc$id_document', 'block', 'block');\">";
			echo entites_html($descriptif);
			echo "</textarea>\n";
		}

		if ($options == "avancees")
			afficher_formulaire_taille($document, $type_inclus);

		echo "\n<div class='display_au_chargement' id='valider_doc$id_document' align='".$GLOBALS['spip_lang_right']."'>";
		echo "<input type='submit' class='fondo' style='font-size:9px;' ' VALUE='"._T('bouton_enregistrer')."'>";
		echo "</div>\n";
		echo "</form>";

		echo "</div>";
		echo fin_block();
		// Fin edition des champs

		echo "<p /><div align='center'>";
		icone_horizontale(_T('icone_supprimer_document'), bouton_supprime_document_et_vignette($id, $type, $id_document, 'documents'), "doc-24.gif", "supprimer.gif");
		echo "</div>";


		// Bloc edition de la vignette
		if ($options == 'avancees') {
			echo "<div class='verdana1' style='color: $couleur_foncee; border: 1px solid $couleur_foncee; padding: 5px; margin-top: 3px;'>";
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
			bloc_gerer_vignette($document, $id, $type, 'documents');
			echo "</div>\n";
		}

		fin_cadre_enfonce();
	}

	//
	// Afficher une image inserable dans l'article
	//
	else if ($mode == 'vignette') {
		$block = "image $id_document";
		$titre_cadre = lignes_longues(typo($titre).typo($titre_fichier), 30);
	
		debut_cadre_relief("image-24.gif", false, "", $titre_cadre);

		echo "<div style='float: $spip_lang_left;'>";
		if ($flag_deplie) echo bouton_block_visible($block);
		else echo bouton_block_invisible($block);
		echo "</div>";


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
			if (strlen($descriptif)>0)
				echo "<font face='Verdana,Arial,Sans,sans-serif' size='2'>"
				. propre($descriptif)
				. "</font>";

			if (!ereg(",$id_document,", $doublons))
				echo $raccourci_doc;
		}

		if ($flag_deplie) echo debut_block_visible($block);
		else  echo debut_block_invisible($block);

		if (ereg(",$id_document,", $doublons))
			echo $raccourci_doc;

		echo "\n<div class='verdana1' align='center'>",
		  _T('info_largeur_vignette', array('largeur_vignette' => $largeur, 'hauteur_vignette' => $hauteur)),
		  "</div>\n";

		echo generer_url_post_ecrire($GLOBALS['exec'],
					     "id_$type=$id&modif_document=oui&id_document=$id_document&show_docs=$id_document",
					     "",
					     "#document$id_document");

		echo "<div class='verdana1' style='color: #999999; border: 1px solid #999999; padding: 5px; margin-top: 3px; text-align: left; background-color: #eeeeee;'>";
		echo "<b>"._T('entree_titre_image')."</b><br />\n";
		echo "<input type='text' name='titre_document' class='formo' value=\"".entites_html($titre)."\" size='40'><br />";

		if ($descriptif OR $options == "avancees") {
			echo "<b>"._T('info_description_2')."</b><br />\n";
			echo "<textarea name='descriptif_document' rows='4' class='formo' cols='*' style='font-size:9px;' wrap='soft'>";
			echo entites_html($descriptif);
			echo "</textarea>\n";
		}

		echo "<div align='".$GLOBALS['spip_lang_right']."'>";
		echo "<input class='fondo' style='font-size: 9px;' type='submit' value='"._T('bouton_enregistrer')."'>";
		echo "</div>";
		echo "</div>";
		echo "</form>";

		echo "<center>";

		icone_horizontale (_T('icone_supprimer_image'), bouton_supprime_document_et_vignette($id, $type, $id_document, 'images'), "image-24.gif", "supprimer.gif");
		echo "</center>\n";


		echo fin_block();

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


// Mettre a jour la description du document postee par le redacteur
// TODO: pour le moment cette fonction ne sait traiter qu'un document...
function maj_documents ($id_objet, $type) {
	global $_POST;

	if ($id_objet
	AND $id_document = intval($_POST['id_document'])
	AND $_POST['modif_document'] == 'oui') {

		// "securite" : verifier que le document est bien lie a l'objet
		$result_doc = spip_query("SELECT * FROM spip_documents_".$type."s
		WHERE id_document=".$id_document."
		AND id_".$type." = $id_objet");
		if (spip_num_rows($result_doc) > 0) {
			$titre_document = addslashes(corriger_caracteres(
				$_POST['titre_document']));
			$descriptif_document = addslashes(corriger_caracteres(
				$_POST['descriptif_document']));
			$query = "UPDATE spip_documents
			SET titre='$titre_document', descriptif='$descriptif_document'";

			// taille du document (cas des embed)
			if ($largeur_document = intval($_POST['largeur_document'])
			AND $hauteur_document = intval($_POST['hauteur_document']))
				$query .= ", largeur='$largeur_document',
					hauteur='$hauteur_document'";

			$query .= " WHERE id_document=".$id_document;
			spip_query($query);


			// Date du document (uniquement dans les rubriques)
			if ($_POST['jour_doc']) {
				if ($_POST['annee_doc'] == "0000")
					$_POST['mois_doc'] = "00";
				if ($_POST['mois_doc'] == "00")
					$_POST['jour_doc'] = "00";
				$date = $_POST['annee_doc'].'-'
				.$_POST['mois_doc'].'-'.$_POST['jour_doc'];

				if (preg_match('/^[0-9-]+$/', $date)) {
					spip_query("UPDATE spip_documents
						SET date='$date'
						WHERE id_document=$id_document");

					// Changement de date, ce qui nous oblige a :
					calculer_rubriques();
				}
			}

		}

		// Demander l'indexation du document
		include_ecrire('inc_index');
		marquer_indexer('document', $id_document);

	}
}

?>
