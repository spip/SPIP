<?php

//
// Ce fichier regroupe la quasi totalite des definitions de #BALISES de spip
// Pour chaque balise, il est possible de surcharger, dans mes_fonctions.php3,
// la fonction balise_TOTO_dist par une fonction balise_TOTO() respectant la
// meme API : 
// elle recoit en entree un objet de classe CHAMP, le modifie et le retourne.
// Cette classe est definie dans inc-compilo-index.php3
//

## NB: les fonctions de forum sont definies dans inc-forum.php3


// Ce fichier ne sera execute qu'une fois
if (defined("_INC_BALISES")) return;
define("_INC_BALISES", "1");



//
// Traitements standard de divers champs
//
function champs_traitements ($p) {
	static $traitements = array (
		'BIO' => 'traiter_raccourcis(%s)',
		'CHAPO' => 'traiter_raccourcis(nettoyer_chapo(%s))',
		'DATE' => 'vider_date(%s)',
		'DATE_MODIF' => 'vider_date(%s)',
		'DATE_NOUVEAUTES' => 'vider_date(%s)',
		'DATE_REDAC' => 'vider_date(%s)',
		'DESCRIPTIF' => 'traiter_raccourcis(%s)',
		'LIEN_TITRE' => 'typo(%s)',
		'LIEN_URL' => 'htmlspecialchars(vider_url(%s))',
		'MESSAGE' => 'traiter_raccourcis(%s)',
		'NOM_SITE_SPIP' => 'typo(%s)',
		'NOM' => 'typo(%s)',
		'PARAMETRES_FORUM' => 'htmlspecialchars(%s)',
		'PS' => 'traiter_raccourcis(%s)',
		'SOUSTITRE' => 'typo(%s)',
		'SURTITRE' => 'typo(%s)',
		'TEXTE' => 'traiter_raccourcis(%s)',
		'TITRE' => 'typo(%s)',
		'TYPE' => 'typo(%s)',
		'URL_ARTICLE' => 'htmlspecialchars(vider_url(%s))',
		'URL_BREVE' => 'htmlspecialchars(vider_url(%s))',
		'URL_DOCUMENT' => 'htmlspecialchars(vider_url(%s))',
		'URL_FORUM' => 'htmlspecialchars(vider_url(%s))',
		'URL_MOT' => 'htmlspecialchars(vider_url(%s))',
		'URL_RUBRIQUE' => 'htmlspecialchars(vider_url(%s))',
		'URL_SITE_SPIP' => 'htmlspecialchars(vider_url(%s))',
		'URL_SITE' => 'htmlspecialchars(vider_url(%s))',
		'URL_SYNDIC' => 'htmlspecialchars(vider_url(%s))'
	);
	$ps = $traitements[$p->nom_champ];
	if (!$ps) return $p->code;
	if ($p->documents)
	  {$ps = str_replace('traiter_raccourcis(', 
			     'traiter_raccourcis_doublon($doublons,',
			     str_replace('typo(', 
					 'typo_doublon($doublons,',
					 $ps));
	  }
	// on supprime les <IMGnnn> tant qu'on ne rapatrie pas
	// les documents distants joints..
	// il faudrait aussi corriger les raccourcis d'URL locales
	return str_replace('%s',
			   (!$p->boucles[$p->id_boucle]->sql_serveur ?
			    $p->code :
			    ('supprime_img(' . $p->code . ')')),
			   $ps);				
}

//
// Definition des balises
//
function balise_NOM_SITE_SPIP_dist($p) {
	$p->code = "lire_meta('nom_site')";
	$p->statut = 'php';
	return $p;
}

function balise_EMAIL_WEBMASTER_dist($p) {
	$p->code = "lire_meta('email_webmaster')";
	$p->statut = 'php';
	return $p;
}

function balise_CHARSET_dist($p) {
	$p->code = "lire_meta('charset')";
	$p->statut = 'php';
	return $p;
}


function balise_LANG_LEFT_dist($p) {
	$p->code = "lang_dir(\$GLOBALS['spip_lang'],'left','right')";
	$p->statut = 'php';
	return $p;
}

function balise_LANG_RIGHT_dist($p) {
	$p->code = "lang_dir(\$GLOBALS['spip_lang'],'right','left')";
	$p->statut = 'php';
	return $p;
}

function balise_LANG_DIR_dist($p) {
	$p->code = "lang_dir(\$GLOBALS['spip_lang'],'ltr','rtl')";
	$p->statut = 'php';
	return $p;
}

function balise_PUCE_dist($p) {
	$p->code = "propre('- ')";
	$p->statut = 'php';
	return $p;
}

// #DATE
// Cette fonction sait aller chercher dans le contexte general
// quand #DATE est en dehors des boucles
// http://www.spip.net/fr_article1971.html
function balise_DATE_dist ($p) {
	$_date = champ_sql('date', $p);
	$p->code = "$_date";
	$p->statut = 'php';
	return $p;
}

// #DATE_REDAC
// http://www.spip.net/fr_article1971.html
function balise_DATE_REDAC_dist ($p) {
	$_date = champ_sql('date_redac', $p);
	$p->code = "$_date";
	$p->statut = 'php';
	return $p;
}

// #DATE_MODIF
// http://www.spip.net/fr_article1971.html
function balise_DATE_MODIF_dist ($p) {
	$_date = champ_sql('date_modif', $p);
	$p->code = "$_date";
	$p->statut = 'php';
	return $p;
}

// #DATE_NOUVEAUTES
// http://www.spip.net/fr_article1971.html
function balise_DATE_NOUVEAUTES_dist($p) {
	$p->code = "((lire_meta('quoi_de_neuf') == 'oui' AND lire_meta('majnouv')) ? normaliser_date(lire_meta('majnouv')) : \"'0000-00-00'\")";
	$p->statut = 'php';
	return $p;
}

function balise_URL_SITE_SPIP_dist($p) {
	$p->code = "lire_meta('adresse_site')";
	$p->statut = 'php';
	return $p;
}


function balise_URL_ARTICLE_dist($p) {
	$_type = $p->type_requete;

	// Cas particulier des boucles (SYNDIC_ARTICLES)
	if ($_type == 'syndic_articles') {
		$p->code = champ_sql('url', $p);
	}

	// Cas general : chercher un id_article dans la pile
	else {
		$_id_article = champ_sql('id_article', $p);
		$p->code = "generer_url_article($_id_article)";

		if ($p->boucles[$p->id_boucle]->hash)
			$p->code = "url_var_recherche(" . $p->code . ")";
	}

	$p->statut = 'html';
	return $p;
}

function balise_URL_RUBRIQUE_dist($p) {
	$p->code = "generer_url_rubrique(" . 
	champ_sql('id_rubrique',$p) . 
	")" ;
	if ($p->boucles[$p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->statut = 'html';
	return $p;
}

function balise_URL_BREVE_dist($p) {
	$p->code = "generer_url_breve(" .
	champ_sql('id_breve',$p) . 
	")";
	if ($p->boucles[$p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->statut = 'html';
	return $p;
}

function balise_URL_MOT_dist($p) {
	$p->code = "generer_url_mot(" .
	champ_sql('id_mot',$p) .
	")";
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->statut = 'html';
	return $p;
}

function balise_URL_FORUM_dist($p) {
	$p->code = "generer_url_forum(" .
	champ_sql('id_forum',$p) .")";

	$p->statut = 'html';
	return $p;
}

function balise_URL_DOCUMENT_dist($p) {
	$p->code = "generer_url_document(" .
	champ_sql('id_document',$p) . ")";

	$p->statut = 'html';
	return $p;
}

function balise_URL_AUTEUR_dist($p) {
	$p->code = "generer_url_auteur(" .
	champ_sql('id_auteur',$p) .")";
	if ($p->boucles[$p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->statut = 'html';
	return $p;
}

function balise_NOTES_dist($p) {
	// Recuperer les notes
	$p->code = 'calculer_notes()';
	$p->statut = 'html';
	return $p;
}

function balise_RECHERCHE_dist($p) {
	$p->code = 'htmlspecialchars($GLOBALS["recherche"])';
	$p->statut = 'php';
	return $p;
}

function balise_COMPTEUR_BOUCLE_dist($p) {
	if ($p->id_mere === '') {
		erreur_squelette(_L("Champ #COMPTEUR_BOUCLE hors boucle"), $p->id_boucle);
		$p->code = "''";
	} else {
		$p->code = '$compteur_boucle';
		$p->statut = 'php';
		return $p;
	}
}

function balise_TOTAL_BOUCLE_dist($p) {
	if ($p->id_mere === '') {
		erreur_squelette(_L("Champ #TOTAL_BOUCLE hors boucle"), $p->id_boucle);
		$p->code = "''";
	} else {
		$p->code = "\$Numrows['$p->id_mere']";
		$p->boucles[$p->id_mere]->numrows = true;
		$p->statut = 'php';
	}
	return $p;
}

function balise_POINTS_dist($p) {
	$n = 0;
	$b = $p->id_boucle;
	$p->code = '';
	while ($b != '') {
	if ($s = $p->boucles[$b]->param) {
	  foreach($s as $v) {
		if (strpos($v,'recherche') !== false) {
		  $p->code = '$Pile[$SP' . (($n==0) ? "" : "-$n") .
			'][points]';
		  $b = '';
		  break;
		}
	  }
	}
	$n++;
	$b = $p->boucles[$b]->id_parent;
	}
	if (!$p->code) {
		erreur_squelette(_L("Champ #POINTS hors d'une recherche"), $p->id_boucle);
	}
	$p->statut = 'php';
	return $p;
}

function balise_POPULARITE_ABSOLUE_dist($p) {
	$p->code = 'ceil(' .
	champ_sql('popularite', $p) .
	')';
	$p->statut = 'php';
	return $p;
}

function balise_POPULARITE_SITE_dist($p) {
	$p->code = 'ceil(lire_meta(\'popularite_total\'))';
	$p->statut = 'php';
	return $p;
}

function balise_POPULARITE_MAX_dist($p) {
	$p->code = 'ceil(lire_meta(\'popularite_max\'))';
	$p->statut = 'php';
	return $p;
}

function balise_EXPOSER_dist($p) {
	global  $table_primary;
	$type_boucle = $p->type_requete;
	$primary_key = $table_primary[$type_boucle];
	if (!$primary_key) {
		erreur_squelette(_L("Champ #EXPOSER hors boucle"), $p->id_boucle);
	}
	$on = 'on';
	$off= '';
	if ($p->fonctions) {
		// Gerer la notation [(#EXPOSER|on,off)]
		reset($p->fonctions);
		list(, $onoff) = each($p->fonctions);
		ereg("([^,]*)(,(.*))?", $onoff, $regs);
		$on = addslashes($regs[1]);
		$off = addslashes($regs[3]);
		
		// autres filtres
		$filtres=Array();
		while (list(, $nom) = each($p->fonctions))
		  $filtres[] = $nom;
		$p->fonctions = $filtres;
	}


	$p->code = '(calcul_exposer('
	.champ_sql($primary_key, $p)
	.', "'.$primary_key.'", $Pile[0]) ?'." '$on': '$off')";
	$p->statut = 'php';
	return $p;
}


//
// Inserer directement un document dans le squelette
//
function balise_EMBED_DOCUMENT_dist($p) {
	balise_distante_interdite($p);
	$_id_document = champ_sql('id_document',$p);
	$p->code = "calcule_embed_document(intval($_id_document), '" .
	texte_script($p->fonctions ? join($p->fonctions, "|") : "") .
	  "', \$doublons, '" . $p->documents . "')";
	unset ($p->fonctions);
	$p->statut = 'html';
	return $p;
}

// Debut et fin de surlignage auto des mots de la recherche
// on insere une balise Span avec une classe sans spec:
// c'est transparent s'il n'y a pas de recherche,
// sinon elles seront remplacees par les fontions de inc_surligne
// flag_pcre est juste une flag signalant que preg_match est dispo.

function balise_DEBUT_SURLIGNE_dist($p) {
	global $flag_pcre;
	$p->code = ($flag_pcre ? ('\'<span class="spip_surligneconditionnel">\'') : "''");
	return $p;
}
function balise_FIN_SURLIGNE_dist($p) {
	global $flag_pcre;
	$p->code = ($flag_pcre ? ('\'</span class="spip_surligneconditionnel">\'') : "''");
	return $p;
}

// Formulaire de changement de langue
function balise_MENU_LANG_dist($p) {
	$p->code = '("<"."?php
include_ecrire(\"inc_lang.php3\");
echo menu_langues(\"var_lang\", \$menu_lang);
?".">")';
	$p->statut = 'php';
	return $p;
}

// Formulaire de changement de langue / page de login
function balise_MENU_LANG_ECRIRE_dist($p) {
	$p->code = '("<"."?php
include_ecrire(\"inc_lang.php3\");
echo menu_langues(\"var_lang_ecrire\", \$menu_lang);
?".">")';
	$p->statut = 'php';
	return $p;
}

//
// Formulaires de login
//
function balise_LOGIN_PRIVE_dist($p) {
	balise_distante_interdite($p);
	$p->code = '("<"."?php include(\'inc-login.php3\'); login(\'\', \'prive\'); ?".">")'; 
	$p->statut = 'php';
	return $p;
}

function balise_LOGIN_PUBLIC_dist($p) {
	balise_distante_interdite($p);
	if ($nom = $p->fonctions[0])
	$lacible = "new Link('".$nom."')";
	else
	$lacible = '\$GLOBALS[\'clean_link\']';
	$p->code = '("<"."?php include(\'inc-login.php3\'); login(' . $lacible . ', false); ?".">")';
	$p->fonctions = array();
	$p->statut = 'php';
	return $p;
}

function balise_URL_LOGOUT_dist($p) {
	if ($p->fonctions) {
	$url = "&url=".$p->fonctions[0];
	$p->fonctions = array();
	} else {
	$url = '&url=\'.urlencode(\$clean_link->getUrl()).\'';
	}
	$p->code = '("<"."?php if (\$GLOBALS[\'auteur_session\'][\'login\'])
{ echo \'spip_cookie.php3?logout_public=\'.\$GLOBALS[\'auteur_session\'][\'login\'].\'' . $url . '\'; } ?".">")';
	$p->statut = 'php';
	return $p;
}

function balise_INTRODUCTION_dist ($p) {
	$_type = $p->type_requete;
	$_texte = champ_sql('texte', $p);
	$_chapo = champ_sql('chapo', $p);
	$_descriptif = champ_sql('descriptif', $p);
	$p->code = "calcul_introduction('$_type', $_texte, $_chapo, $_descriptif)";

	$p->statut = 'html';
	return $p;
}


// #LANG
// non documente ?
function balise_LANG_dist ($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "($_lang ? $_lang : \$GLOBALS['spip_lang'])";
	$p->statut = 'php';
	return $p;
}


// #LESAUTEURS
// les auteurs d'un article (ou d'un article syndique)
// http://www.spip.net/fr_article902.html
// http://www.spip.net/fr_article911.html
function balise_LESAUTEURS_dist ($p) {
	// Cherche le champ 'lesauteurs' dans la pile
	$_lesauteurs = champ_sql('lesauteurs', $p); 

	// Si le champ n'existe pas (cas de spip_articles), on donne la
	// construction speciale sql_auteurs(id_article) ;
	// dans le cas contraire on prend le champ 'les_auteurs' (cas de
	// spip_syndic_articles)
	if ($_lesauteurs AND $_lesauteurs != '$Pile[0][\'lesauteurs\']') {
		$p->code = $_lesauteurs;
	} else {
		$nom = $p->id_boucle;
	# On pourrait mieux faire qu'utiliser cette fonction assistante ?
		$p->code = "sql_auteurs(" .
			champ_sql('id_article', $p) .
			",'" .
			$nom .
			"','" .
			$p->boucles[$nom]->type_requete .
			"','" .
			$p->boucles[$nom]->sql_serveur .
			"')";
	}

	$p->statut = 'html';
	return $p;
}


// #PETITION
// Champ testant la presence d'une petition
// non documente ???
function balise_PETITION_dist ($p) {
	$nom = $p->id_boucle;
	$p->code = "sql_petitions(" .
			champ_sql('id_article', $p) .
			",'" .
			$nom .
			"','" .
			$p->boucles[$nom]->type_requete .
			"','" .
			$p->boucles[$nom]->sql_serveur .
			"')";
	$p->statut = 'php';
	return $p;
}


// #POPULARITE
// http://www.spip.net/fr_article1846.html
function balise_POPULARITE_dist ($p) {
	$_popularite = champ_sql('popularite', $p);
	$p->code = "(ceil(min(100, 100 * $_popularite
	/ max(1 , 0 + lire_meta('popularite_max')))))";
	$p->statut = 'php';
	return $p;
}


//
// Fonction commune aux balises #LOGO_XXXX
// (les balises portant ce type de nom sont traitees en bloc ici)
//
function calcul_balise_logo ($p) {

	// analyser la balise LOGO_xxx
	eregi("^LOGO_(([A-Z]+)(_.*)?)", $p->nom_champ, $regs);
	$type_logo = $regs[1];	// ARTICLE_RUBRIQUE
	$type_objet = $regs[2];	// ARTICLE
	$suite_logo = $regs[3];	// _RUBRIQUE

	if ($type_objet == 'SITE')
		$_id_objet = champ_sql("id_syndic", $p);
	else
		$_id_objet = champ_sql("id_".strtolower($type_objet), $p);

	// analyser les filtres
	$flag_fichier = 'false';
	$filtres = '';
	if (is_array($p->fonctions)) {
		foreach($p->fonctions as $nom) {
			if (ereg('^(left|right|center|top|bottom)$', $nom))
				$align = $nom;
			else if ($nom == 'lien') {
				$flag_lien_auto = 'oui';
				$flag_stop = true;
			}
			else if ($nom == 'fichier') {
				$flag_fichier = 'true';
				$flag_stop = true;
			}
			// double || signifie "on passe aux filtres"
			else if ($nom == '')
				$flag_stop = true;
			else if (!$flag_stop) {
				$lien = $nom;
				$flag_stop = true;
			}
			// apres un URL ou || ou |fichier ce sont
			// des filtres (sauf left...lien...fichier)
			else
				$filtres[] = $nom;
		}
		// recuperer les autres filtres s'il y en a
		$p->fonctions = $filtres;
	}

	//
	// Preparer le code du lien
	//
	// 1. filtre |lien
	if ($flag_lien_auto AND !$lien)
		$code_lien = '($lien = generer_url_'.$type_objet.'('.$_id_objet.')) ? $lien : ""';
	// 2. lien indique en clair (avec des balises : imprimer#ID_ARTICLE.html)
	else if ($lien) {
		$code_lien = "'".texte_script(trim($lien))."'";
		while (ereg("^([^#]*)#([A-Za-z_]+)(.*)$", $code_lien, $match)) {
			$c = new Champ();
			$c->nom_champ = $match[2];
			$c->id_boucle = $p->id_boucle;
			$c->boucles = &$p->boucles;
			$c->id_mere = $p->id_mere;
			$c = calculer_champ($c);
			$code_lien = str_replace('#'.$match[2], "'.".$c.".'", $code_lien);
		}
		// supprimer les '' disgracieux
		$code_lien = ereg_replace("^''\.|\.''$", "", $code_lien);
	}
	if (!$code_lien)
		$code_lien = "''";

	switch ($suite_logo) {
		case '_NORMAL':
			$onoff = 'true, false';
			break;
		case '_SURVOL':
			$onoff = 'false, true';
			break;
		case '':
		default:
			$onoff = 'true, true';
			break;
	}

	// cas des documents
	if ($type_objet == 'DOCUMENT')
		$code_logo = "calcule_document($_id_objet, '" .
			$p->documents .
			'\', $doublons)';
	else
		$code_logo = "cherche_logo_objet('$type_objet',
			$_id_objet, $onoff)";

	// cas des logo #BREVE_RUBRIQUE et #ARTICLE_RUBRIQUE
	if ($suite_logo == '_RUBRIQUE') {
		$_id_rubrique = champ_sql("id_rubrique", $p);
		$code_logo = "(\$logo = $code_logo) ? \$logo : ".
		"cherche_logo_objet('RUBRIQUE', $_id_rubrique, $onoff)";
	}

	$p->code = "affiche_logos($code_logo, $code_lien, '$align', $flag_fichier)";

	$p->statut = 'php';
	return $p;
}

// #EXTRA [(#EXTRA|isbn)]
// Champs extra
// Non documentes, en voie d'obsolescence, cf. ecrire/inc_extra.php3
function balise_EXTRA_dist ($p) {
	$_extra = champ_sql('extra', $p);
	$p->code = $_extra;

	// Gerer la notation [(#EXTRA|isbn)]
	if ($p->fonctions) {
		include_ecrire("inc_extra.php3");
		list ($key, $champ_extra) = each($p->fonctions);	// le premier filtre
		$type_extra = $p->type_requete;
			// ci-dessus est sans doute un peu buggue : si on invoque #EXTRA
			// depuis un sous-objet sans champ extra d'un objet a champ extra,
			// on aura le type_extra du sous-objet (!)
		if (extra_champ_valide($type_extra, $champ_extra)) {
			unset($p->fonctions[$key]);
			$p->code = "extra($p->code, '".addslashes($champ_extra)."')";

			// Appliquer les filtres definis par le webmestre
			$filtres = extra_filtres($type_extra, $champ_extra);
			if ($filtres) foreach ($filtres as $f)
				$p->code = "$f($p->code)";
		}
	}

	$p->statut = 'html';
	return $p;
}



//
// Traduction des champs "formulaire"
//

//
// Note : les balises de gestion de forums (FORMULAIRE_FORUM et
// PARAMETRES_FORUM) sont definies dans le fichier inc-forum.php3
// qui centralise toute la gestion des forums
//

//
// Formulaire de recherche
//
function balise_FORMULAIRE_RECHERCHE_dist($p) {
	if ($p->fonctions) {
		list(, $lien) = each($p->fonctions);	// le premier est un url
		while (list(, $filtre) = each($p->fonctions))
			$filtres[] = $filtre;	// les suivants sont des filtres
		$p->fonctions = $filtres;
	}
	if (!$lien) $lien = 'recherche.php3';

	$formulaire_recherche = "\"<form action='$lien' method='get' class='formrecherche'><input type='text' id='formulaire_recherche' size='20' class='formrecherche' name='recherche' value='\" . _T('info_rechercher') . \"' /></form>\"";

	$p->code = "((lire_meta('activer_moteur') != 'oui') ? '' :
	$formulaire_recherche)";

	$p->statut = 'html';
	return $p;
}


//
// Formulaire d'inscription comme redacteur (dans inc-formulaires.php3)
//
function balise_FORMULAIRE_INSCRIPTION_dist($p) {
	balise_distante_interdite($p);
	$p->code = '((lire_meta("accepter_inscriptions") != "oui") ? "" :
		("<"."?php include_local(\'inc-formulaires.php3\'); lang_select(\'".$GLOBALS[\'spip_lang\']."\'); echo formulaire_inscription(\"redac\"); lang_dselect(); ?".">"))';

	$p->statut = 'php';
	return $p;
}

//
// Formulaire ecrire auteur
//
function balise_FORMULAIRE_ECRIRE_AUTEUR_dist($p) {
	balise_distante_interdite($p);
	$_id_auteur = champ_sql('id_auteur', $p);
	$_mail_auteur = champ_sql('email', $p);

	$p->code = '(!email_valide('.$_mail_auteur.') ? "" :
		("<'.'?php include_local(\'inc-formulaires.php3\'); lang_select(\'".$GLOBALS[\'spip_lang\']."\'); echo formulaire_ecrire_auteur(".'.$_id_auteur.'.", \'".texte_script('.$_mail_auteur.')."\'); lang_dselect(); ?'.'>"))';

	$p->statut = 'php';
	return $p;
}

//
// Formulaire signature de petition
//
function balise_FORMULAIRE_SIGNATURE_dist($p) {
	balise_distante_interdite($p);
	$_id_article = champ_sql('id_article', $p);
	$nom = $p->id_boucle;
	$code = "sql_petitions(" .
			$_id_article .
			",'" .
			$nom .
			"','" .
			$p->boucles[$nom]->type_requete .
			"','" .
			$p->boucles[$nom]->sql_serveur .
			"')";

	$p->code = '(!($petition = '.
		$code .
		') ? "" : ("<"."?php include_local(\'inc-formulaires.php3\'); lang_select(\'".$GLOBALS[\'spip_lang\']."\'); 
echo formulaire_signature(".' .
		$_id_article .
		'.", \'".texte_script(serialize($petition))."\'); lang_dselect(); ?".">"))';

	$p->statut = 'php';
	return $p;
}

// Formulaire d'inscription de site dans l'annuaire
function balise_FORMULAIRE_SITE_dist($p) {
	balise_distante_interdite($p);
	$_id_rubrique = champ_sql('id_rubrique', $p);

	$p->code = '((lire_meta("proposer_sites") != 2) ? "":
		("<"."?php include_local(\'inc-formulaires.php3\'); lang_select(\'".$GLOBALS[\'spip_lang\']."\'); echo formulaire_site(\'".'.$_id_rubrique.'."\'); lang_dselect(); ?".">"))';

	$p->statut = 'php';
	return $p;
}

// il faudrait traiter le formulaire en local 
// tout en appelant le serveur SQL distant.
// En attendant, refuser une authentification sur qqch qui n'a rien à voir.

function balise_distante_interdite($p) {
	$nom = $p->id_boucle;
	if ($p->boucles[$nom]->sql_serveur) {
		erreur_squelette($p->nom_champ ._L(" distant interdit"), $nom);
	}
}


//
// Boutons d'administration: 
//
function balise_FORMULAIRE_ADMIN_dist($p) {
	$p->code = "'<!-- @@formulaire_admin@@45609871@@ -->'";
	$p->statut = "php";
	return $p;
}


function balise_HTTP_dist($p) {
	if (is_array($p->fonctions)) {
		foreach($p->fonctions as $nom) {
			if (is_numeric($nom))
				$p->code = " http_status($nom);";
			else
				$p->code = " header($nom);";
		}
		$p->code = '("<" . "?php ' . $p->code . ' ?" . ">")';
		$p->fonctions = array();
	}
	$p->statut = 'php';
	return $p;
}
?>
