<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_TEXTE")) return;
define("_ECRIRE_INC_TEXTE", "1");

include_ecrire("inc_filtres.php3");

//
// Initialisation de quelques variables globales
// (on peut les modifier globalement dans mes_fonctions.php3,
//  OU individuellement pour chaque type de page dans article.php3,
//  rubrique.php3, etc. cf doc...)
// Par securite ne pas accepter les variables passees par l'utilisateur
//
function tester_variable($nom_var, $val){
	if (! $GLOBALS[$nom_var]
		OR $_GET[$nom_var] OR $GLOBALS['HTTP_GET_VARS'][$nom_var]
		OR $_PUT[$nom_var] OR $GLOBALS['HTTP_PUT_VARS'][$nom_var]
		OR $_POST[$nom_var] OR $GLOBALS['HTTP_POST_VARS'][$nom_var]
		OR $_COOKIE[$nom_var] OR $GLOBALS['HTTP_COOKIE_VARS'][$nom_var]
		OR $_REQUEST[$nom_var]
	)
		$GLOBALS[$nom_var] = $val;
}

tester_variable('debut_intertitre', "\n&nbsp;<h3 class=\"spip\">\n");   // sale mais historique
tester_variable('fin_intertitre', "\n</h3><br>\n");
tester_variable('ouvre_ref', '&nbsp;[');
tester_variable('ferme_ref', ']');
tester_variable('ouvre_note', '[');
tester_variable('ferme_note', '] ');
tester_variable('les_notes', '');
tester_variable('compt_note', 0);

if (file_exists("puce.gif")) {
	$imgsize = getimagesize('puce.gif');
	tester_variable('puce', "<img src='puce.gif' align='top' alt='- ' ".$imgsize[3]." border='0'>");
}
else {
	tester_variable('puce', "-");
}


//
// Variables globales : a virer pour une gestion intelligente de la langue
//
if (!$GLOBALS['lang']
	|| $GLOBALS['HTTP_GET_VARS']['lang']
	|| $GLOBALS['HTTP_POST_VARS']['lang']
	|| $GLOBALS['HTTP_COOKIE_VARS']['lang'])
	$GLOBALS['lang'] = 'fr';

//
// Trouver une locale qui marche
//
$lang2 = strtoupper($GLOBALS['lang']);
setlocale('LC_CTYPE', $GLOBALS['lang']) ||
setlocale('LC_CTYPE', $lang2.'_'.$GLOBALS['lang']) ||
setlocale('LC_CTYPE', $GLOBALS['lang'].'_'.$lang2);


//
// Diverses fonctions essentielles
//

// ereg_ ou preg_ ?
function ereg_remplace($cherche_tableau, $remplace_tableau, $texte) {
	global $flag_preg_replace;

	if ($flag_preg_replace) return preg_replace($cherche_tableau, $remplace_tableau, $texte);

	$n = count($cherche_tableau);

	for ($i = 0; $i < $n; $i++) {
		$texte = ereg_replace(substr($cherche_tableau[$i], 1, -1), $remplace_tableau[$i], $texte);
	}
	return $texte;
}

// Ne pas afficher le chapo si article virtuel
function nettoyer_chapo($chapo){
	if (substr($chapo,0,1) == "="){
		$chapo = "";	
	}
	return $chapo;
}


//
// vignette pour les documents lies
//
function vignette_par_defaut($type_extension) {
	if ($GLOBALS['flag_ecrire'])
		$img = "../IMG/icones";
	else
		$img = "IMG/icones";

	$filename = "$img/$type_extension";

	// Glurps !
	if (file_exists($filename.'.png')) {
		$vig = "$filename.png";
	}
	else if (file_exists($filename.'.gif')) {
		$vig = "$filename.gif";
	}
	else if (file_exists($filename.'-dist.png')) {
		$vig = "$filename-dist.png";
	}
	else if (file_exists($filename.'-dist.gif')) {
		$vig = "$filename-dist.gif";
	}
	else if (file_exists("$img/defaut.png")) {
		$vig = "$img/defaut.png";
	}
	else if (file_exists("$img/defaut.gif")) {
		$vig = "$img/defaut.gif";
	}
	else if (file_exists("$img/defaut-dist.png")) {
		$vig = "$img/defaut-dist.png";
	}
	else if (file_exists("$img/defaut-dist.gif")) {
		$vig = "$img/defaut-dist.gif";
	}

	if ($size = @getimagesize($vig)) {
		$largeur = $size[0];
		$hauteur = $size[1];
	}

	return array($vig, $largeur, $hauteur);
}

// Mise de cote des echappements
function echappe_html($letexte,$source) {
	//
	// Echapper les <code>...</ code>
	//
	$regexp_echap = "<code>(([^<]|<[^/]|</[^c]|</c[^o]|</co[^d]|</cod[^e]|<\/code[^>])*)<\/code>";
	while (eregi($regexp_echap, $letexte, $regs)) {
		$num_echap++;
		$lecode = entites_html($regs[1]);

		// ne pas mettre le <div...> s'il n'y a qu'une ligne
		if (is_int(strpos($lecode,"\n")))
			$lecode = nl2br("<div align='left' class='spip_code'>".trim($lecode)."</div>");

		$lecode = ereg_replace("\t", "&nbsp; &nbsp; &nbsp; &nbsp; ", $lecode);
		$lecode = ereg_replace("  ", " &nbsp;", $lecode);
		$les_echap[$num_echap] = "<tt>".$lecode."</tt>";
		$pos = strpos($letexte, $regs[0]);
		$letexte = substr($letexte,0,$pos)."___SPIP_$source$num_echap ___"
			.substr($letexte,$pos+strlen($regs[0]));
	}

	//
	// Echapper les <cadre>...</cadre>
	//
	$regexp_echap = "<cadre>(([^<]|<[^/]|</[^c]|</c[^a]|</ca[^d]|</cad[^r]|</cadr[^e]|<\/cadre[^>])*)<\/cadre>";
	while (eregi($regexp_echap, $letexte, $regs)) {
		$num_echap++;
		$lecode = trim(entites_html($regs[1]));
		$total_lignes = count(explode("\n", $lecode)) + 1;

		$les_echap[$num_echap] = "<form><textarea cols='40' rows='$total_lignes' wrap='no' class='spip_cadre'>".$lecode."</textarea></form>";

		$pos = strpos($letexte, $regs[0]);
		$letexte = substr($letexte,0,$pos)."___SPIP_$source$num_echap ___"
			.substr($letexte,$pos+strlen($regs[0]));
	}

	//
	// Echapper les <html>...</ html>
	//
	$regexp_echap = "<html>(([^<]|<[^/]|</[^h]|</h[^t]|</ht[^m]|</htm[^l]|<\/html[^>])*)<\/html>";
	while (eregi($regexp_echap, $letexte, $regs)) {
		$num_echap++;
		$les_echap[$num_echap] = $regs[1];
		$pos = strpos($letexte, $regs[0]);
		$letexte = substr($letexte,0,$pos)."___SPIP_$source$num_echap ___"
			.substr($letexte,$pos+strlen($regs[0]));
	}

	//
	// Echapper les <a href>
	//
	$regexp_echap = "<a [^>]+>";
	while (eregi($regexp_echap, $letexte, $regs)) {
		$num_echap++;
		$les_echap[$num_echap] = $regs[0];
		$pos = strpos($letexte, $les_echap[$num_echap]);
		$letexte = substr($letexte,0,$pos)."___SPIP_$source$num_echap ___"
			.substr($letexte,$pos+strlen($les_echap[$num_echap]));
	}

	return array($letexte, $les_echap);
}

// Traitement final des echappements
function echappe_retour($letexte, $les_echap, $source) {
	while(ereg("___SPIP_$source([0-9]+) ___", $letexte, $match)) {
		$lenum = $match[1];
		$cherche = $match[0];
		$pos = strpos($letexte, $cherche);
		$letexte = substr($letexte, 0, $pos). $les_echap[$lenum] . substr($letexte, $pos + strlen($cherche));
	}
	return $letexte;
}

function couper($texte, $long) {
	$texte2 = substr($texte, 0, $long * 2); /* heuristique pour prendre seulement le necessaire */
	if (strlen($texte2) < strlen($texte)) $plus_petit = true;
	$texte = ereg_replace("\[([^\[]*)->([^]]*)\]","\\1", $texte2);

	// supprimer les notes
	$texte = ereg_replace("\[\[([^]]|\][^]])*\]\]", "", $texte);

	// supprimer les codes typos
	$texte = strtr($texte,"{}","  ");

	$texte2 = substr($texte." ", 0, $long);
	$texte2 = ereg_replace("([^[:space:]][[:space:]]+)[^[:space:]]*$", "\\1", $texte2);
	if ((strlen($texte2) + 3) < strlen($texte)) $plus_petit = true;
	if ($plus_petit) $texte2 .= ' (...)';
	return $texte2;
}

// prendre <intro>...</intro> sinon couper a la longueur demandee
function couper_intro($texte, $long) {
	$texte = eregi_replace("(</?)intro>", "\\1intro>", $texte); // minuscules
	while ($fin = strpos($texte, "</intro>")) {
		$zone = substr($texte, 0, $fin);
		$texte = substr($texte, $fin + strlen("</intro>"));
		if ($deb = strpos($zone, "<intro>") OR substr($zone, 0, 7) == "<intro>")
			$zone = substr($zone, $deb + 7);
		$intro .= $zone;
	}

	if ($intro)
		$intro = $intro.' (...)';
	else
		$intro = couper($texte, $long);

	// supprimer un eventuel chapo redirecteur =http:/.....
	$intro = ereg_replace("^=http://[^[:space:]]+","",$intro);

	return $intro;
}


//
// Les elements de propre()
//

// Securite : empecher l'execution de code PHP
function interdire_scripts($source) {
	$source = eregi_replace("<(\%|\?|([[:space:]]*)script)", "&lt;\\1", $source);
	return $source;
}

// Integration (embed) multimedia

function embed_document($id_document, $les_parametres="", $afficher_titre=true) {
	global $id_doublons;

	$id_doublons['documents'] .= ",$id_document";


	if ($les_parametres) {
		$parametres = explode("|",$les_parametres);
		
		for ($i = 0; $i < count($parametres); $i++) {
			$parametre = $parametres[$i];
			
			if (eregi("^left|right|center$", $parametre)) {
				$align = $parametre;
			}
			else {
				$params[] = $parametre;
			}
		}
	}

	$query = "SELECT * FROM spip_documents WHERE id_document = $id_document";
	$result = spip_query($query);
	if ($row = mysql_fetch_array($result)) {
		$id_document = $row['id_document'];
		$id_type = $row['id_type'];
		$titre = propre($row ['titre']);
		$descriptif = propre($row['descriptif']);
		$fichier = $row['fichier'];
		$largeur = $row['largeur'];
		$hauteur = $row['hauteur'];
		$taille = $row['taille'];
		$mode = $row['mode'];


		$query_type = "SELECT * FROM spip_types_documents WHERE id_type=$id_type";
		$result_type = spip_query($query_type);
		if ($row_type = @mysql_fetch_array($result_type)) {
			$type = $row_type['titre'];
			$inclus = $row_type['inclus'];
			$extension = $row_type['extension'];
		}
		else $type = 'fichier';

		// ajuster chemin d'acces au fichier
		if ($GLOBALS['flag_ecrire']) {
			if ($fichier) $fichier = "../$fichier";
		}
		// Pour RealVideo
		if ((!ereg("^controls", $les_parametres)) AND (ereg("^(rm|ra|ram)$", $extension))) {
			$real = true;
		}

		if ($inclus == "embed" AND !$real) {
		
				for ($i = 0; $i < count($params); $i++) {
					if (ereg("([^\=]*)\=([^\=]*)", $params[$i], $vals)){
						$nom = $vals[1];
						$valeur = $vals[2];
						$inserer_vignette .= "<param name='$nom' value='$valeur'>";
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
				
				$vignette = "<object width='$largeur' height='$hauteur'>";
				$vignette .= "<param name='movie' value='$fichier'>";
				$vignette .= "<param name='src' value='$fichier'>";
				$vignette .= $inserer_vignette;
		
				$vignette .= "<embed src='$fichier' $param_emb width='$largeur' height='$hauteur'></embed></object>";
		
		}
		else if ($inclus == "embed" AND $real) {
			$vignette .= embed_document ($id_document, "controls=ImageWindow|type=audio/x-pn-realaudio-plugin|console=Console$id_document|nojava=true|$les_parametres", false);
			$vignette .= "<br>";
			$vignette .= embed_document ($id_document, "controls=PlayButton|type=audio/x-pn-realaudio-plugin|console=Console$id_document|nojava=true|$les_parametres", false);
			$vignette .= embed_document ($id_document, "controls=PositionSlider|type=audio/x-pn-realaudio-plugin|console=Console$id_document|nojava=true|$les_parametres", false);
		}
		else if ($inclus == "image") {
			$fichier_vignette = $fichier;
			$largeur_vignette = $largeur;
			$hauteur_vignette = $hauteur;
			if ($fichier_vignette) {
				$vignette = "<img src='$fichier_vignette' border=0";
				if ($largeur_vignette && $hauteur_vignette)
					$vignette .= " width='$largeur_vignette' height='$hauteur_vignette'";
				if ($titre) {
					$titre_ko = ($taille > 0) ? ($titre . " - ". taille_en_octets($taille)) : $titre;
					$vignette .= " alt=\"$titre_ko\" title=\"$titre_ko\"";
				}
				$vignette .= ">";
			}
		}
		
		if ($afficher_titre) {
			$retour = "<table cellpadding=5 cellspacing=0 border=0 align='$align'>\n";
			$retour .= "<tr><td align='center'>\n<div class='spip_documents'>\n";
			$retour .= $vignette;

			if ($titre) $retour .= "<br><b>$titre</b>";
			if ($descriptif) $retour .= "<br>$descriptif";

			$retour .= "</div>\n</td></tr>\n</table>\n";
		}
		else {
			$retour = $vignette;
		}

		return $retour;		

	}
}


// Integration des images et documents
function integre_image($id_document, $align, $type_aff = 'IMG') {
	global $id_doublons;
	
	$id_doublons['documents'] .= ",$id_document";
	
	$query = "SELECT * FROM spip_documents WHERE id_document = $id_document";
	$result = spip_query($query);
	if ($row = mysql_fetch_array($result)) {
		$id_document = $row['id_document'];
		$id_type = $row['id_type'];
		$titre = typo($row['titre']);
		$descriptif = propre($row['descriptif']);
		$fichier = $row['fichier'];
		$largeur = $row['largeur'];
		$hauteur = $row['hauteur'];
		$taille = $row['taille'];
		$mode = $row['mode'];
		$id_vignette = $row['id_vignette'];

		// type d'affichage : IMG, DOC
		$affichage_detaille = (strtoupper($type_aff) == 'DOC');

		// on construira le lien en fonction du type de doc
		$result_type = spip_query("SELECT * FROM spip_types_documents WHERE id_type = $id_type");
		if ($type = @mysql_fetch_object($result_type)) {
			$extension = $type->extension;
		}

		// recuperer la vignette pour affichage inline
		if ($id_vignette) {
			$query_vignette = "SELECT * FROM spip_documents WHERE id_document = $id_vignette";
			$result_vignette = spip_query($query_vignette);
			if ($row_vignette = @mysql_fetch_array($result_vignette)) {
				$fichier_vignette = $row_vignette['fichier'];
				$largeur_vignette = $row_vignette['largeur'];
				$hauteur_vignette = $row_vignette['hauteur'];
			}
		}
		else if ($mode == 'vignette') {
			$fichier_vignette = $fichier;
			$largeur_vignette = $largeur;
			$hauteur_vignette = $hauteur;
		}

		// ajuster chemin d'acces au fichier
		if ($GLOBALS['flag_ecrire']) {
			if ($fichier) $fichier = "../$fichier";
			if ($fichier_vignette) $fichier_vignette = "../$fichier_vignette";
		}

		// si pas de vignette, utiliser la vignette par defaut du type du document
		if (!$fichier_vignette) {
			list($fichier_vignette, $largeur_vignette, $hauteur_vignette) = vignette_par_defaut($extension);
		}

		if ($fichier_vignette) {
			$vignette = "<img src='$fichier_vignette' border=0";
			if ($largeur_vignette && $hauteur_vignette)
				$vignette .= " width='$largeur_vignette' height='$hauteur_vignette'";
			if ($titre) {
				$titre_ko = ($taille > 0) ? ($titre . " - ". taille_en_octets($taille)) : $titre;
				$vignette .= " alt=\"$titre_ko\" title=\"$titre_ko\"";
			}
			if ($affichage_detaille)
				$vignette .= ">";
			else {
				if ($align) $vignette .= " align='$align'";
				$vignette .= " hspace='5' vspace='3'>";
				if ($align == 'center') $vignette = "<p align='center'>$vignette</p>";
			}
		}

		if ($mode == 'document')
			$vignette = "<a href='$fichier'>$vignette</a>";

		// si affichage detaille ('DOC'), ajouter une legende
		if ($affichage_detaille) {
			$query_type = "SELECT * FROM spip_types_documents WHERE id_type=$id_type";
			$result_type = spip_query($query_type);
			if ($row_type = @mysql_fetch_array($result_type)) {
				$type = $row_type['titre'];
			}
			else $type = 'fichier';

			$retour = "<table cellpadding=5 cellspacing=0 border=0 align='$align'>\n";
			$retour .= "<tr><td align='center'>\n<div class='spip_documents'>\n";
			$retour .= $vignette;

			if ($titre) $retour .= "<br><b>$titre</b>";
			if ($descriptif) $retour .= "<br>$descriptif";
			
			if ($mode == 'document')
				$retour .= "<br>(<a href='$fichier'>$type, ".taille_en_octets($taille)."</a>)";

			$retour .= "</div>\n</td></tr>\n</table>\n";
		}
		else $retour = $vignette;
	}
	return $retour;
}


// Correction typographique francaise
function typo_fr($letexte) {
	global $flag_preg_replace;
	global $flag_str_replace;

	// les "blancs durs" et les guillemets
	if ($flag_str_replace){
		$letexte = str_replace("&nbsp;","~",strtr($letexte,chr(160),"~"));
		$letexte = str_replace("&raquo;",chr(187),$letexte);
		$letexte = str_replace("&#187;", chr(187),$letexte);
		$letexte = str_replace("&laquo;",chr(171),$letexte);
		$letexte = str_replace("&#171;", chr(171),$letexte);
	}
	else {
		$letexte = ereg_replace("&nbsp;","~",strtr($letexte,chr(160),"~"));
		$letexte = ereg_replace("&(raquo|#187);",chr(187), $letexte);
		$letexte = ereg_replace("&(laquo|#171);",chr(171), $letexte);
	}

	$cherche1 = array(
		/* 2 */ 	'/((^|[^\#0-9a-zA-Z\&])[\#0-9a-zA-Z]*)\;/',
		/* 3 */		'/([:!?'.chr(187).']| -,)/',
		/* 4 */		'/('.chr(171).'|(M(M?\.|mes?|r\.?)|[MnN]'.chr(176).') )/'
	);
	$remplace1 = array(
		/* 2 */		'\1~;',
		/* 3 */		'~\1',
		/* 4 */		'\1~'
	);

	$letexte = ereg_remplace($cherche1, $remplace1, $letexte);
	$letexte = ereg_replace(" *~+ *", "~", $letexte);

	$cherche2 = array(
		'/(http|ftp|mailto)~:/',
		'/~/'
	);
	$remplace2 = array(
		'\1:',
		'&nbsp;'
	);

	$letexte = ereg_remplace($cherche2, $remplace2, $letexte);

	return ($letexte);
}

// Typographie generale : francaise sinon rien (pour l'instant)
function typo($letexte) {
	global $lang;

	list($letexte, $les_echap) = echappe_html($letexte, "SOURCETYPO");

	if ($lang == 'fr')
		$letexte = typo_fr($letexte);

	$letexte = corriger_caracteres($letexte);
	$letexte = echappe_retour($letexte, $les_echap, "SOURCETYPO");

	return $letexte;
}



//
// Traitement des listes (merci a Michael Parienti)
//
function traiter_listes ($texte) {
	$parags = split ("\n[[:space:]]*\n", $texte);
	unset($texte);

	// chaque paragraphe est traite a part
	while (list(,$para) = each($parags)) {
		$niveau = 0;
		$lignes = explode("\n-", "\n" . $para);

		// ne pas toucher a la premiere ligne
		list(,$debut) = each($lignes);
		$texte .= $debut;

		// chaque item a sa profondeur = nb d'etoiles
		unset ($type);
		while (list(,$item) = each($lignes)) {
			ereg("^(\**|#*)[[:space:]]*(.*)", $item, $regs);
			$profond = strlen($regs[1]);

			if ($profond > 0) {
				unset ($ajout);

				// changement de type de liste : il faut descendre un
				// niveau plus bas, fermer ce niveau, et remonter
				$nouv_type = (substr($item,0,1) == '*') ? 'ul' : 'ol';
				$change_type = ($type AND ($type <> $nouv_type)) ? 1 : 0;
				$type = $nouv_type;

				// d'abord traiter les descentes
				while ($niveau > $profond - $change_type) {
					$ajout .= $pile_li[$niveau];
					$ajout .= $pile_type[$niveau];
					if (!$change_type)
						unset ($pile_li[$niveau]);
					$niveau --;
				}

				/// puis les identites (y compris en fin de descente)
				if ($niveau == $profond && !$change_type) {
					$ajout .= $pile_li[$niveau];
				}

				// puis les montees (y compris apres une descente un cran trop bas)
				while ($niveau < $profond) {
					$niveau ++;
					$ajout .= "<$type class=\"spip\">";
					$pile_type[$niveau] = "</$type>";
				}

				$ajout .= "<li class=\"spip\">";
				$pile_li[$profond] = "</li>";
			}
			else { 
				$ajout = "\n- ";	// puce normale
			}

			$texte .= $ajout . $regs[2];
		}

		// retour sur terre
		unset ($ajout);
		while ($niveau > 0) {
			$ajout .= $pile_li[$niveau];
			$ajout .= $pile_type[$niveau];
			$niveau --;
		}
		$texte .= $ajout;

		// paragraphe
		$texte .= "\n\n";
	}

	// sucrer les deux derniers \n
	return substr($texte, 0, -2);
}


// Nettoie un texte, traite les raccourcis spip, la typo, etc.
function traiter_raccourcis($letexte, $les_echap = false, $traiter_les_notes = 'oui') {
	global $puce;
	global $debut_intertitre, $fin_intertitre;
	global $compt_note;
	global $les_notes;
	global $ouvre_ref;
	global $ferme_ref;
	global $ouvre_note;
	global $ferme_note;
	global $flag_strpos_3, $flag_preg_replace, $flag_str_replace;

	// Harmoniser les retours chariot
	$letexte = ereg_replace ("\r\n?", "\n",$letexte);

	// echapper les <a href>, <html>...< /html>, <code>...< /code>
	if (!$les_echap)
		list($letexte, $les_echap) = echappe_html($letexte, "SOURCEPROPRE");

	// Corriger HTML
	$letexte = eregi_replace("</?p>","\n\n\n",$letexte);

	//
	// Notes de bas de page
	//
	$regexp = "\[\[(([^]]|[^]]\][^]])*)\]\]";
	/* signifie : deux crochets ouvrants, puis pas-crochet-fermant ou
		crochet-fermant entoure de pas-crochets-fermants (c'est-a-dire
		tout sauf deux crochets fermants), puis deux fermants */
	while (ereg($regexp, $letexte, $regs)){
		$note_texte = $regs[1];
		$num_note = false;

		// note auto ou pas ?
		if (ereg("^ *<([^>]*)>",$note_texte,$regs)){
			$num_note=$regs[1];
			$note_texte = ereg_replace ("^ *<([^>]*)>","",$note_texte);
		} else {
			$compt_note++;
			$num_note=$compt_note;
		}

		// preparer la note
		if ($num_note) {
			$insert = "$ouvre_ref<a href='#nb$num_note' name='nh$num_note' class='spip_note'>$num_note</a>$ferme_ref";
			$appel = "<html>$ouvre_note<a href='#nh$num_note' name='nb$num_note' class='spip_note'>$num_note</a>$ferme_note</html>";
		} else {
			$insert = '';
			$appel = '';
		}

		// l'ajouter "brut" dans les notes
		if ($note_texte) {
			if ($mes_notes)
				$mes_notes .= "\n\n";
			$mes_notes .= $appel . $note_texte;
		}

		// dans le texte, mettre l'appel de note a la place de la note
		$letexte = implode($insert, split($regexp, $letexte, 2));
	}

	//
	// Raccourcis liens
	//
	$regexp = "\[([^][]*)->([^]]*)\]";
	$texte_a_voir = $letexte;
	$texte_vu = '';
	while (ereg($regexp, $texte_a_voir, $regs)){
		$lien_texte = $regs[1];
		$lien_url = trim($regs[2]);
		$compt_liens++;
		$lien_interne = false;
		if (ereg('^(art(icle)?|rub(rique)?|br(.ve)?|aut(eur)?|mot)? *([[:digit:]]+)$', $lien_url, $match)) {
			// Traitement des liens internes
			$id_lien = $match[6];
			$type_lien = $match[1];
			$lien_interne=true;
			$class_lien = "in";
			switch (substr($type_lien, 0, 2)) {
				case 'ru':
					$lien_url = generer_url_rubrique($id_lien);
					if (!$lien_texte) {
						$req = "select titre from spip_rubriques where id_rubrique=$id_lien";
						$row = @mysql_fetch_array(@spip_query($req));
						$lien_texte = $row['titre'];
					}
					break;
				case 'br':
					$lien_url = generer_url_breve($id_lien);
					if (!$lien_texte) {
						$req = "select titre from spip_breves where id_breve=$id_lien";
						$row = @mysql_fetch_array(@spip_query($req));
						$lien_texte = $row['titre'];
					}
					break;
				case 'au':
					$lien_url = generer_url_auteur($id_lien);
					if (!$lien_texte) {
						$req = "select nom from spip_auteurs where id_auteur = $id_lien";
						$row = @mysql_fetch_array(@spip_query($req));
						$lien_texte = $row['nom'];
					}
					break;
				case 'mo':
					$lien_url = generer_url_mot($id_lien);
					if (!$lien_texte) {
						$req = "select titre from spip_mots where id_mot=$id_lien";
						$row = @mysql_fetch_array(@spip_query($req));
						$lien_texte = $row['titre'];
					}
					break;
				default:
					$lien_url = generer_url_article($id_lien);
					if (!$lien_texte) {
						$req = "select titre from spip_articles where id_article=$id_lien";
						$row = @mysql_fetch_array(@spip_query($req));
						$lien_texte = $row['titre'];
					}
					break;
			}
		} else {	// lien non automatique
			$class_lien = "out";
			// texte vide ?
			if ((!$lien_texte) and (!$lien_interne)) {
				$lien_texte = ereg_replace('"', '', $lien_url);
				$class_lien = "url";
			}
			// petites corrections d'URL
			if (ereg("^www\.[^@]+$",$lien_url))
				$lien_url = "http://".$lien_url;
			else if (email_valide($lien_url))
				$lien_url = "mailto:".trim($lien_url);
		}

		$insert = "<a href=\"$lien_url\" class=\"spip_$class_lien\">".typo($lien_texte)."</a>";
		$zetexte = split($regexp,$texte_a_voir,2);

		// typo en-dehors des notes
		$texte_vu .= typo($zetexte[0]).$insert;
		$texte_a_voir = $zetexte[1];
	}
	$letexte = $texte_vu.typo($texte_a_voir); // typo de la queue du texte

	//
	// Insertion d'images et de documents utilisateur
	//
	while (eregi("<(IMG|DOC|EMB)([0-9]+)(\|([^\>]*))?".">", $letexte, $match)) {
		$letout = quotemeta($match[0]);
		$letout = ereg_replace("\|", "\|", $letout);
		$id_document = $match[2];
		$align = $match[4];
		if (eregi("emb", $match[1]))
			$rempl = embed_document($id_document, $align);
		else 
			$rempl = integre_image($id_document, $align, $match[1]);
		$letexte = ereg_replace($letout, $rempl, $letexte);
	}

	//
	// Tableaux
	//
	$letexte = ereg_replace("^\n?\|", "\n\n|", $letexte);
	$letexte = ereg_replace("\|\n?$", "|\n\n", $letexte);
	
	$tableBeginPos = strpos($letexte, "\n\n|");
	$tableEndPos = strpos($letexte, "|\n\n");
	while (is_integer($tableBeginPos) && is_integer($tableEndPos) && $tableBeginPos < $tableEndPos + 3) {
		$textBegin = substr($letexte, 0, $tableBeginPos);
		$textTable = substr($letexte, $tableBeginPos + 2, $tableEndPos - $tableBeginPos);
		$textEnd = substr($letexte, $tableEndPos + 3);
		
		$newTextTable = "\n<p><table class=\"spip\">";
		$rowId = 0;
		$lineEnd = strpos($textTable, "|\n");
		while (is_integer($lineEnd)) {
			$rowId++;
			$row = substr($textTable, 0, $lineEnd);
			$textTable = substr($textTable, $lineEnd + 2);
			if ($rowId == 1 && ereg("^(\\|[[:space:]]*\\{\\{[^}]+\\}\\}[[:space:]]*)+$", $row)) {
				$newTextTable .= '<tr class="row_first">';
			} else {
				$newTextTable .= '<tr class="row_'.($rowId % 2 ? 'odd' : 'even').'">';
			}
			$newTextTable .= ereg_replace("\|([^\|]+)", "<td>\\1</td>", $row);
			$newTextTable .= '</tr>';
			$lineEnd = strpos($textTable, "|\n");
		}
		$newTextTable .= "</table>\n<p>\n";
		
		$letexte = $textBegin . $newTextTable . $textEnd;

		$tableBeginPos = strpos($letexte, "\n\n|");
		$tableEndPos = strpos($letexte, "|\n\n");
	}


	//
	// Ensemble de remplacements implementant le systeme de mise
	// en forme (paragraphes, raccourcis...)
	//
	// ATTENTION : si vous modifiez cette partie, modifiez les DEUX
	// branches de l'alternative (if (flag_str_replace).../else).
	//

	$letexte = trim($letexte);

	// les listes
	if (ereg("\n-[*#]", "\n".$letexte))
		$letexte = traiter_listes($letexte);

	// autres raccourcis
	if ($flag_str_replace && !$flag_preg_replace) {
		$letexte = ereg_replace("\n(-{4,}|_{4,})", "\n<hr class=\"spip\">\n", $letexte);
		$letexte = ereg_replace("^- *", "$puce&nbsp;", $letexte);
		$letexte = str_replace("\n- *", "\n<br>$puce&nbsp;",$letexte);
		$letexte = str_replace("\n_ +", "\n<br>",$letexte);
		$letexte = ereg_replace("(( *)\n){2,}", "\n<p>", $letexte);
		$letexte = str_replace("{{{", $debut_intertitre, $letexte);
		$letexte = str_replace("}}}", $fin_intertitre, $letexte);
		$letexte = str_replace("{{", "<b class=\"spip\">", $letexte);
		$letexte = str_replace("}}", "</b>", $letexte);
		$letexte = str_replace("{", "<i class=\"spip\">", $letexte);
		$letexte = str_replace("}", "</i>", $letexte);
		$letexte = eregi_replace("(<br>)+(<p>|<br>)", "\n<p class=\"spip\">", $letexte);
		$letexte = str_replace("<p>", "<p class=\"spip\">", $letexte);
		$letexte = str_replace("\n", " ", $letexte);
	}
	else {
		$cherche1 = array(
			/* 0 */ 	"/\n(----+|____+)/",
			/* 1 */ 	"/^- */",
			/* 2 */ 	"/\n- */",
			/* 3 */ 	"/\n_ +/",
			/* 4 */ 	"/(( *)\n){2,}/",
			/* 5 */ 	"/\{\{\{/",
			/* 6 */ 	"/\}\}\}/",
			/* 7 */ 	"/\{\{/",
			/* 8 */ 	"/\}\}/",
			/* 9 */ 	"/\{/",
			/* 10 */	"/\}/",
			/* 11 */	"/(<br>){2,}/",
			/* 12 */	"/<p>([\n]*)(<br>)+/",
			/* 13 */	"/<p>/",
			/* 14 */	"/\n/"
		);
		$remplace1 = array(
			/* 0 */ 	"\n<hr class=\"spip\">\n",
			/* 1 */ 	"$puce&nbsp;",
			/* 2 */ 	"\n<br>$puce&nbsp;",
			/* 3 */ 	"\n<br>",
			/* 4 */ 	"\n<p>",
			/* 5 */ 	"$debut_intertitre",
			/* 6 */ 	"$fin_intertitre",
			/* 7 */ 	"<b class=\"spip\">",
			/* 8 */ 	"</b>",
			/* 9 */ 	"<i class=\"spip\">",
			/* 10 */	"</i>",
			/* 11 */	"\n<p class=\"spip\">",
			/* 12 */	"\n<p class=\"spip\">",
			/* 13 */	"<p class=\"spip\">",
			/* 14 */	" "
		);
		$letexte = ereg_remplace($cherche1, $remplace1, $letexte);
	}

	if (ereg('<p class="spip">',$letexte)){
		$letexte = '<p class="spip">'.ereg_replace('<p class="spip">', "</p>\n".'<p class="spip">',$letexte).'</p>';
	}

	// Reinserer les echappements
	$letexte = echappe_retour($letexte, $les_echap, "SOURCEPROPRE");

	if ($mes_notes) {
		$fin_notes = '';

		// "paragrapher" les anciennes notes
		if ($les_notes) {
			if (!ereg('<p class="spip_note">', $les_notes)) {
				$les_notes = '<p class="spip_note">' . $les_notes . '</p>';
			}
			$les_notes .= "\n".'<p class="spip_note">';
			$fin_notes = '</p>';
		}

		// "paragrapher" les nouvelles notes
		$mes_notes = traiter_raccourcis($mes_notes, $les_echap, 'non');
		if (ereg('<p class="spip">', $mes_notes)) {
			$mes_notes = ereg_replace('^<p class="spip">', '', $mes_notes);
			$mes_notes = ereg_replace('</p>$', '', $mes_notes);
			$mes_notes = ereg_replace('<p class="spip">', '<p class="spip_note">', $mes_notes);
			$fin_notes = '</p>';
		}

		// nettoyer
		$mes_notes = echappe_retour($mes_notes, $les_echap, "SOURCEPROPRE");

		// ajouter
		$les_notes .= interdire_scripts($mes_notes) . $fin_notes;
	}

	return $letexte;
}


// Filtre a appliquer aux champs du type #TEXTE*
function propre($letexte) {
	return interdire_scripts(traiter_raccourcis(trim($letexte)));
}

?>
