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
	if (!isset($GLOBALS[$nom_var])
		OR $_GET[$nom_var] OR $GLOBALS['HTTP_GET_VARS'][$nom_var]
		OR $_PUT[$nom_var] OR $GLOBALS['HTTP_PUT_VARS'][$nom_var]
		OR $_POST[$nom_var] OR $GLOBALS['HTTP_POST_VARS'][$nom_var]
		OR $_COOKIE[$nom_var] OR $GLOBALS['HTTP_COOKIE_VARS'][$nom_var]
		OR $_REQUEST[$nom_var]) {
		$GLOBALS[$nom_var] = $val;
		return false;
	}
	return true;
}

tester_variable('debut_intertitre', "\n<h3 class=\"spip\">");
tester_variable('fin_intertitre', "</h3>\n");
tester_variable('ligne_horizontale', "\n<hr class=\"spip\" />\n");
tester_variable('ouvre_ref', '&nbsp;[');
tester_variable('ferme_ref', ']');
tester_variable('ouvre_note', '[');
tester_variable('ferme_note', '] ');
tester_variable('les_notes', '');
tester_variable('compt_note', 0);
tester_variable('nombre_surligne', 4);
tester_variable('url_glossaire_externe', "http://@lang@.wikipedia.org/wiki/");


// On ne prend la $puce_rtl par defaut que si $puce n'a pas ete redefinie

//if (!tester_variable('puce', "<li class='spip_puce' style='list-style-image: url(puce.gif)'>")) {
if (!tester_variable('puce', "<img class='spip_puce' src='puce.gif' alt='-'>&nbsp;")) {
	tester_variable('puce_rtl', "<img class='spip_puce' src='puce_rtl.gif' alt='-'>&nbsp;");
}


//
// Trouver une locale qui marche
//
$lang2 = strtoupper($GLOBALS['spip_lang']);
setlocale(LC_CTYPE, $GLOBALS['spip_lang']) ||
setlocale(LC_CTYPE, $lang2.'_'.$GLOBALS['spip_lang']) ||
setlocale(LC_CTYPE, $GLOBALS['spip_lang'].'_'.$lang2);


//
// Vignette pour les documents lies
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


//
// Diverses fonctions essentielles
//

// ereg_ ou preg_ ?
function ereg_remplace($cherche_tableau, $remplace_tableau, $texte) {
	global $flag_pcre;

	if ($flag_pcre) return preg_replace($cherche_tableau, $remplace_tableau, $texte);

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


// Mise de cote des echappements
function echappe_html($letexte,$source) {
	global $flag_pcre;

	if ($flag_pcre) {	// beaucoup plus rapide si on a pcre
		$regexp_echap_html = "<html>((.*?))<\/html>";
		$regexp_echap_code = "<code>((.*?))<\/code>";
		$regexp_echap_cadre = "<cadre>((.*?))<\/cadre>";
		$regexp_echap = "/($regexp_echap_html)|($regexp_echap_code)|($regexp_echap_cadre)/si";
	} else {
		$regexp_echap_html = "<html>(([^<]|<[^/]|</[^h]|</h[^t]|</ht[^m]|</htm[^l]|<\/html[^>])*)<\/html>";
		$regexp_echap_code = "<code>(([^<]|<[^/]|</[^c]|</c[^o]|</co[^d]|</cod[^e]|<\/code[^>])*)<\/code>";
		$regexp_echap_cadre = "<cadre>(([^<]|<[^/]|</[^c]|</c[^a]|</ca[^d]|</cad[^r]|</cadr[^e]|<\/cadre[^>])*)<\/cadre>";
		$regexp_echap = "($regexp_echap_html)|($regexp_echap_code)|($regexp_echap_cadre)";
	}

	while (($flag_pcre && preg_match($regexp_echap, $letexte, $regs))
		|| (!$flag_pcre && eregi($regexp_echap, $letexte, $regs))) {
		$num_echap++;

		if ($regs[1]) {
			// Echapper les <html>...</ html>
			$les_echap[$num_echap] = $regs[2];
		}
		else
		if ($regs[4]) {
			// Echapper les <code>...</ code>
			$lecode = entites_html($regs[5]);

			// supprimer les sauts de ligne debut/fin (mais pas les espaces => ascii art).
			$lecode = ereg_replace("^\n+|\n+$", "", $lecode);

			// ne pas mettre le <div...> s'il n'y a qu'une ligne
			if (is_int(strpos($lecode,"\n")))
				$lecode = nl2br("<div align='left' class='spip_code' dir='ltr'>".$lecode."</div>");
			else
				$lecode = "<span class='spip_code' dir='ltr'>".$lecode."</span>";

			$lecode = ereg_replace("\t", "&nbsp; &nbsp; &nbsp; &nbsp; ", $lecode);
			$lecode = ereg_replace("  ", " &nbsp;", $lecode);
			$les_echap[$num_echap] = "<tt>".$lecode."</tt>";
		}
		else
		if ($regs[7]) {
			// Echapper les <cadre>...</cadre>
			$lecode = trim(entites_html($regs[8]));
			$total_lignes = count(explode("\n", $lecode));

			$les_echap[$num_echap] = "<form><textarea readonly='readonly' style='width: 100%;' rows='$total_lignes' wrap='off' class='spip_cadre' dir='ltr'>".$lecode."</textarea></form>";
		}

		$pos = strpos($letexte, $regs[0]);
		$letexte = substr($letexte,0,$pos)."@@SPIP_$source$num_echap@@"
			.substr($letexte,$pos+strlen($regs[0]));
	}


	//
	// Insertion d'images et de documents utilisateur
	//
	while (eregi("<(IMG|DOC|EMB)([0-9]+)(\|([^\>]*))?".">", $letexte, $match)) {
		include_ecrire("inc_documents.php3");
		$num_echap++;

		$letout = quotemeta($match[0]);
		$letout = ereg_replace("\|", "\|", $letout);
		$id_document = $match[2];
		$align = $match[4];
		if (eregi("emb", $match[1]))
			$rempl = embed_document($id_document, $align);
		else
			$rempl = integre_image($id_document, $align, $match[1]);
		$letexte = ereg_replace($letout, "@@SPIP_$source$num_echap@@", $letexte);
		$les_echap[$num_echap] = $rempl;
	}

	//
	// Echapper les tags html contenant des caracteres sensibles a la typo
	//
	$regexp_echap = "<[^>!':;\?]*[!':;\?][^>]*>";
	if ($flag_pcre)
		if (preg_match_all("/$regexp_echap/", $letexte, $regs)) while (list(,$reg) = each($regs)) {
			$num_echap++;
			$les_echap[$num_echap] = $reg[0];
			$pos = strpos($letexte, $les_echap[$num_echap]);
			$letexte = substr($letexte,0,$pos)."@@SPIP_$source$num_echap@@"
				.substr($letexte,$pos+strlen($les_echap[$num_echap]));
		}
	else
		while (ereg($regexp_echap, $letexte, $reg)) {
			$num_echap++;
			$les_echap[$num_echap] = $reg[0];
			$pos = strpos($letexte, $les_echap[$num_echap]);
			$letexte = substr($letexte,0,$pos)."@@SPIP_$source$num_echap@@"
				.substr($letexte,$pos+strlen($les_echap[$num_echap]));
		}

	return array($letexte, $les_echap);
}

// Traitement final des echappements
function echappe_retour($letexte, $les_echap, $source) {
	while (ereg("@@SPIP_$source([0-9]+)@@", $letexte, $match)) {
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
	$texte = ereg_replace("[{}]", "", $texte);

	$texte2 = substr($texte, 0, $long);
	$texte2 = ereg_replace("([^[:space:]][[:space:]]+)[^[:space:]]*$", "\\1", $texte2);
	if ((strlen($texte2) + 3) < strlen($texte)) $plus_petit = true;
	if ($plus_petit) $texte2 .= '&nbsp;(...)';
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
	$intro = ereg_replace("^=[^[:space:]]+","",$intro);

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


// Correction typographique francaise
function typo_fr($letexte) {
	global $flag_strtr2;
	static $trans;

	// Nettoyer 160 = nbsp ; 187 = raquo ; 171 = laquo ; 176 = deg ; 147 = ldquo; 148 = rdquo
	if (!$trans) {
		$trans = array(
			"&nbsp;" => "~",
			"&raquo;" => "&#187;",
			"&laquo;" => "&#171;",
			"&rdquo;" => "&#148;",
			"&ldquo;" => "&#147;",
			"&deg;" => "&#176;"
		);
		$chars = array(160 => '~', 187 => '&#187;', 171 => '&#171;', 148 => '&#148;', 147 => '&#147;', 176 => '&#176;');
		$charset = lire_meta('charset');
		include_ecrire('inc_charsets.php3');

		while (list($c, $r) = each($chars)) {
			$c = unicode2charset(charset2unicode(chr($c), 'iso-8859-1', 'forcer'));
			$trans[$c] = $r;
		}
	}

	if ($flag_strtr2)
		$letexte = strtr($letexte, $trans);
	else {
		reset($trans);
		while (list($c, $r) = each($trans))
			$letexte = str_replace($c, $r, $letexte);
	}

	$cherche1 = array(
		/* 1		'/{([^}]+)}/',  */
		/* 2 */ 	'/((^|[^\#0-9a-zA-Z\&])[\#0-9a-zA-Z]*)\;/',
		/* 3 */		'/&#187;|[!?]| -,|:([^0-9]|$)/',
		/* 4 */		'/&#171;|(M(M?\.|mes?|r\.?)|[MnN]&#176;) /'
	);
	$remplace1 = array(
		/* 1		'<i class="spip">\1</i>', */
		/* 2 */		'\1~;',
		/* 3 */		'~\0',
		/* 4 */		'\0~'
	);
	$letexte = ereg_remplace($cherche1, $remplace1, $letexte);
	$letexte = ereg_replace(" *~+ *", "~", $letexte);

	$cherche2 = array(
		'/(http|https|ftp|mailto)~:/',
		'/~/'
	);
	$remplace2 = array(
		'\1:',
		'&nbsp;'
	);
	$letexte = ereg_remplace($cherche2, $remplace2, $letexte);

	return $letexte;
}

// rien sauf les ~ {}
function typo_en($letexte) {

	$cherche1 = array(
		/* 1 */		'/{([^}]+)}/'
	);
	$remplace1 = array(
		/* 1 */		'<i class="spip">\1</i>'
	);
	// $letexte = ereg_remplace($cherche1, $remplace1, $letexte);

	$letexte = str_replace("&nbsp;", "~", $letexte);

	return ereg_replace(" *~+ *", "&nbsp;", $letexte);
}

// Typographie generale : francaise si la langue est 'cpf', 'fr' ou 'eo',
// sinon anglaise (minimaliste)
function typo($letexte) {
	global $spip_lang, $lang_typo;

	list($letexte, $les_echap) = echappe_html($letexte, "SOURCETYPO");

	if (!$typo = $lang_typo) {
		include_ecrire('inc_lang.php3');
		$typo = lang_typo($spip_lang);
	}

	if ($typo == 'fr')
		$letexte = typo_fr($letexte);
	else
		$letexte = typo_en($letexte);


	$letexte = str_replace("'", "&#146;", $letexte);

	$letexte = corriger_caracteres($letexte);
	$letexte = echappe_retour($letexte, $les_echap, "SOURCETYPO");

	return $letexte;
}


// cette fonction est tordue : on lui passe un tableau correspondant au match
// de la regexp ci-dessous, et elle retourne le texte a inserer a la place
// et le lien "brut" a usage eventuel de redirection...
function extraire_lien ($regs) {
	$lien_texte = $regs[1];

	$lien_url = trim($regs[3]);
	$compt_liens++;
	$lien_interne = false;
	if (ereg('^(art(icle)?|rub(rique)?|br(.ve)?|aut(eur)?|mot|site|doc(ument)?|im(age|g))? *([[:digit:]]+)$', $lien_url, $match)) {
		// Traitement des liens internes
		if (file_exists('inc-urls.php3')) {
			include_local('inc-urls.php3');
		} elseif (file_exists('inc-urls-dist.php3')) {
			include_local('inc-urls-dist.php3');
		} else {
			include_ecrire('inc_urls.php3');
		}

		$id_lien = $match[8];
		$type_lien = $match[1];
		$lien_interne=true;
		$class_lien = "in";
		switch (substr($type_lien, 0, 2)) {
			case 'ru':
				$lien_url = generer_url_rubrique($id_lien);
				if (!$lien_texte) {
					$req = "select titre from spip_rubriques where id_rubrique=$id_lien";
					$row = @spip_fetch_array(@spip_query($req));
					$lien_texte = $row['titre'];
				}
				break;
			case 'br':
				$lien_url = generer_url_breve($id_lien);
				if (!$lien_texte) {
					$req = "select titre from spip_breves where id_breve=$id_lien";
					$row = @spip_fetch_array(@spip_query($req));
					$lien_texte = $row['titre'];
				}
				break;
			case 'au':
				$lien_url = generer_url_auteur($id_lien);
				if (!$lien_texte) {
					$req = "select nom from spip_auteurs where id_auteur = $id_lien";
					$row = @spip_fetch_array(@spip_query($req));
					$lien_texte = $row['nom'];
				}
				break;
			case 'mo':
				$lien_url = generer_url_mot($id_lien);
				if (!$lien_texte) {
					$req = "select titre from spip_mots where id_mot=$id_lien";
					$row = @spip_fetch_array(@spip_query($req));
					$lien_texte = $row['titre'];
				}
				break;
			case 'im':
			case 'do':
				$lien_url = generer_url_document($id_lien);
				if (!$lien_texte) {
					$req = "select titre,fichier from spip_documents where id_document=$id_lien";
					$row = @spip_fetch_array(@spip_query($req));
					$lien_texte = $row['titre'];
					if (!$lien_texte)
						$lien_texte = ereg_replace("^.*/","",$row['fichier']);
				}
				break;
			case 'si':
				$row = @spip_fetch_array(@spip_query("SELECT nom_site,url_site FROM spip_syndic WHERE id_syndic=$id_lien"));
				if ($row) {
					$lien_url = $row['url_site'];
					if (!$lien_texte)
						$lien_texte = typo($row['nom_site']);
				}
				break;
			default:
				$lien_url = generer_url_article($id_lien);
				if (!$lien_texte) {
					$req = "select titre from spip_articles where id_article=$id_lien";
					$row = @spip_fetch_array(@spip_query($req));
					$lien_texte = $row['titre'];

				}
				break;
		}

		// supprimer les numeros des titres
		include_ecrire("inc_filtres.php3");
		$lien_texte = supprimer_numero($lien_texte);
	}
	else if (ereg('^\?(.*)$', $lien_url, $regs)) {
		// Liens glossaire
		$lien_url = substr($lien_url, 1);
		$class_lien = "glossaire";
	}
	else {
		// Liens non automatiques
		$class_lien = "out";
		// texte vide ?
		if ((!$lien_texte) and (!$lien_interne)) {
			$lien_texte = ereg_replace('"', '', $lien_url);
			if (strlen($lien_texte)>40)
				$lien_texte = substr($lien_texte,0,35).'...';
			$class_lien = "url";
			$lien_texte = "<html>$lien_texte</html>";
		}
		// petites corrections d'URL
		if (ereg("^www\.[^@]+$",$lien_url))
			$lien_url = "http://".$lien_url;
		else if (strpos($lien_url, "@") && email_valide($lien_url))
			$lien_url = "mailto:".$lien_url;
	}

	$insert = "<a href=\"$lien_url\" class=\"spip_$class_lien\""
		.">".typo($lien_texte)."</a>";

	return array($insert, $lien_url);
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
			ereg("^([*]*|[#]*)([^*#].*)", $item, $regs);
			$profond = strlen($regs[1]);

			if ($profond > 0) {
				unset ($ajout);

				// changement de type de liste au meme niveau : il faut
				// descendre un niveau plus bas, fermer ce niveau, et
				// remonter
				$nouv_type = (substr($item,0,1) == '*') ? 'ul' : 'ol';
				$change_type = ($type AND ($type <> $nouv_type) AND ($profond == $niveau)) ? 1 : 0;
				$type = $nouv_type;

				// d'abord traiter les descentes
				while ($niveau > $profond - $change_type) {
					$ajout .= $pile_li[$niveau];
					$ajout .= $pile_type[$niveau];
					if (!$change_type)
						unset ($pile_li[$niveau]);
					$niveau --;
				}

				// puis les identites (y compris en fin de descente)
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
				$ajout = "\n-";	// puce normale ou <hr>
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
	global $debut_intertitre, $fin_intertitre, $ligne_horizontale, $url_glossaire_externe;
	global $compt_note;
	global $les_notes;
	global $marqueur_notes;
	global $ouvre_ref;
	global $ferme_ref;
	global $ouvre_note;
	global $ferme_note;
	global $flag_pcre;
	global $lang_dir;

	// Puce
	if (!$lang_dir)
		$lang_dir = lang_dir($GLOBALS['spip_lang']);
	if ($lang_dir == 'rtl' AND $GLOBALS['puce_rtl'])
		$puce = $GLOBALS['puce_rtl'];
	else
		$puce = $GLOBALS['puce'];

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
	$texte_a_voir = $letexte;
	$texte_vu = '';
	$regexp = "\[\[(([^]]|[^]]\][^]])*)\]\]";
	/* signifie : deux crochets ouvrants, puis pas-crochet-fermant ou
		crochet-fermant entoure de pas-crochets-fermants (c'est-a-dire
		tout sauf deux crochets fermants), puis deux fermants */
	while (ereg($regexp, $texte_a_voir, $regs)) {
		$note_source = $regs[0];
		$note_texte = $regs[1];
		$num_note = false;

		// note auto ou pas ?
		if (ereg("^ *<([^>]*)>", $note_texte, $regs)){
			$num_note = $regs[1];
			$note_texte = ereg_replace ("^ *<([^>]*)>", "", $note_texte);
		} else {
			$compt_note++;
			$num_note = $compt_note;
		}

		// preparer la note
		if ($num_note) {
			if ($marqueur_notes) // ??????
				$mn = $marqueur_notes.'-';
			$ancre = $mn.urlencode($num_note);
			$insert = "$ouvre_ref<a href='#nb$ancre' name='nh$ancre' class='spip_note'>$num_note</a>$ferme_ref";
			$appel = "<html>$ouvre_note<a href='#nh$ancre' name='nb$ancre' class='spip_note'>$num_note</a>$ferme_note</html>";
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
		$pos = strpos($texte_a_voir, $note_source);
		$texte_vu .= substr($texte_a_voir, 0, $pos) . $insert;
		$texte_a_voir = substr($texte_a_voir, $pos + strlen($note_source));
	}
	$letexte = $texte_vu . $texte_a_voir;

	//
	// Raccourcis automatiques vers un glossaire
	// (on traite ce raccourci en deux temps afin de ne pas appliquer
	//  la typo sur les URLs, voir raccourcis liens ci-dessous)
	//
	if ($url_glossaire_externe) {
		$regexp = "\[\?+([^][<>]+)\]";
		while (ereg($regexp, $letexte, $regs)) {
			$terme = trim($regs[1]);
			$terme_underscore = urlencode(ereg_replace('[[:space:]]+', '_', $terme));
			if (strstr($url_glossaire_externe,"%s"))
				$url = str_replace("%s", $terme_underscore, $url_glossaire_externe);
			else
				$url = $url_glossaire_externe.$terme_underscore;
			$url = str_replace("@lang@", $GLOBALS['spip_lang'], $url);
			$code = "[$terme->?$url]";
			$letexte = str_replace($regs[0], $code, $letexte);
		}
	}


	//
	// Raccourcis liens (cf. fonction extraire_lien ci-dessus)
	//
	$regexp = "\[([^][]*)->(>?)([^]]*)\]";
	$texte_a_voir = $letexte;
	$texte_vu = '';
	while (ereg($regexp, $texte_a_voir, $regs)) {
		list($insert, $lien) = extraire_lien($regs);
		$pos = strpos($texte_a_voir, $regs[0]);
		$texte_vu .= typo(substr($texte_a_voir, 0, $pos)) . $insert;
		$texte_a_voir = substr($texte_a_voir, $pos + strlen($regs[0]));
	}
	$letexte = $texte_vu.typo($texte_a_voir); // typo de la queue du texte

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
	// branches de l'alternative (if (!flag_pcre).../else).
	//

	$letexte = trim($letexte);


	// les listes
	if (ereg("\n-[*#]", "\n".$letexte))
		$letexte = traiter_listes($letexte);

	// autres raccourcis
	if (!$flag_pcre) {
		/* note : on pourrait se passer de cette branche, car ereg_remplace() fonctionne
		   sans pcre ; toutefois les elements ci-dessous sont un peu optimises (str_replace
		   est plus rapide que ereg_replace), donc laissons les deux branches cohabiter, ca
		   permet de gagner un peu de temps chez les hergeurs nazes */
		$letexte = ereg_replace("(^|\n)(-{4,}|_{4,})", "@@SPIP_ligne_horizontale@@", $letexte);
		$letexte = ereg_replace("^- *", "$puce&nbsp;", $letexte);
		$letexte = ereg_replace("\n-- *", "\n<br />&mdash&nbsp;",$letexte);
		$letexte = ereg_replace("\n- *", "\n<br />$puce&nbsp;",$letexte);
		$letexte = ereg_replace("\n_ +", "\n<br />",$letexte);
		$letexte = ereg_replace("(( *)\n){2,}", "\n<p>", $letexte);
		$letexte = str_replace("{{{", "@@SPIP_debut_intertitre@@", $letexte);
		$letexte = str_replace("}}}", "@@SPIP_fin_intertitre@@", $letexte);
		$letexte = str_replace("{{", "<b class=\"spip\">", $letexte);
		$letexte = str_replace("}}", "</b>", $letexte);
		$letexte = str_replace("{", "<i class=\"spip\">", $letexte);
		$letexte = str_replace("}", "</i>", $letexte);
		$letexte = eregi_replace("(<br[[:space:]]*/?".">)+(<p>|<br[[:space:]]*/?".">)", "\n<p class=\"spip\">", $letexte);
		$letexte = str_replace("<p>", "<p class=\"spip\">", $letexte);
		$letexte = str_replace("\n", " ", $letexte);
		$letexte = str_replace("<quote>", "<div class=\"spip_quote\">", $letexte);
		$letexte = str_replace("<\/quote>", "</div>", $letexte);
	}
	else {
		$cherche1 = array(
			/* 0 */ 	"/(^|\n)(----+|____+)/",
			/* 1 */ 	"/^- */",
			/* 1bis */ 	"/\n-- */",
			/* 2 */ 	"/\n- */",
			/* 3 */ 	"/\n_ +/",
			/* 4 */ 	"/(( *)\n){2,}/",
			/* 5 */ 	"/\{\{\{/",
			/* 6 */ 	"/\}\}\}/",
			/* 7 */ 	"/\{\{/",
			/* 8 */ 	"/\}\}/",
			/* 9 */ 	"/\{/",
			/* 10 */	"/\}/",
			/* 11 */	"/(<br[[:space:]]*\/?".">){2,}/",
			/* 12 */	"/<p>([\n]*)(<br[[:space:]]*\/?".">)+/",
			/* 13 */	"/<p>/",
			/* 14 */	"/\n/",
			/* 15 */	"/<quote>/",
			/* 16 */	"/<\/quote>/",
		);
		$remplace1 = array(
			/* 0 */ 	"@@SPIP_ligne_horizontale@@",
			/* 1 */ 	"$puce&nbsp;",
			/* 1bis */ 	"\n<br />&mdash;&nbsp;",
			/* 2 */ 	"\n<br />$puce&nbsp;",
			/* 3 */ 	"\n<br />",
			/* 4 */ 	"\n<p>",
			/* 5 */ 	"@@SPIP_debut_intertitre@@",
			/* 6 */ 	"@@SPIP_fin_intertitre@@",
			/* 7 */ 	"<b class=\"spip\">",
			/* 8 */ 	"</b>",
			/* 9 */ 	"<i class=\"spip\">",
			/* 10 */	"</i>",
			/* 11 */	"\n<p class=\"spip\">",
			/* 12 */	"\n<p class=\"spip\">",
			/* 13 */	"<p class=\"spip\">",
			/* 14 */	" ",
			/* 15 */	"<div class=\"spip_quote\">",
			/* 16 */	"</div>",
		);
		$letexte = ereg_remplace($cherche1, $remplace1, $letexte);
	}

	// paragrapher
	if (strpos(' '.$letexte, '<p class="spip">'))
		$letexte = '<p class="spip">'.str_replace('<p class="spip">', "</p>\n".'<p class="spip">', $letexte).'</p>';

	// intertitres & hr compliants
	$letexte = ereg_replace('(<p class="spip">)?[[:space:]]*@@SPIP_debut_intertitre@@', $debut_intertitre, $letexte);
	$letexte = ereg_replace('@@SPIP_fin_intertitre@@[[:space:]]*(</p>)?', $fin_intertitre, $letexte);
	$letexte = ereg_replace('(<p class="spip">)?[[:space:]]*@@SPIP_ligne_horizontale@@[[:space:]]*(</p>)?', $ligne_horizontale, $letexte);

	// Reinserer les echappements
	$letexte = echappe_retour($letexte, $les_echap, "SOURCEPROPRE");

	if ($mes_notes) {
		$mes_notes = traiter_raccourcis($mes_notes, $les_echap, 'non');
		if (ereg('<p class="spip">',$mes_notes))
			$mes_notes = ereg_replace('<p class="spip">', '<p class="spip_note">', $mes_notes);
		else
			$mes_notes = '<p class="spip_note">'.$mes_notes."</p>\n";
		$mes_notes = echappe_retour($mes_notes, $les_echap, "SOURCEPROPRE");
		$les_notes .= interdire_scripts($mes_notes);
	}

	return $letexte;
}


// Filtre a appliquer aux champs du type #TEXTE*
function propre($letexte) {
	return interdire_scripts(traiter_raccourcis(trim($letexte)));
//	$a=time(); $b=microtime();
//	interdire_scripts(traiter_raccourcis(trim($letexte)));
//	return time()-$a + microtime()-$b;
}

?>
