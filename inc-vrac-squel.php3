<?php

//
// Fonctions new style
//

function balise_NOM_SITE_SPIP_dist($p) {
	$p->code = "lire_meta('nom_site')";
	$p->type = 'php';
	return $p;
}

function balise_EMAIL_WEBMASTER_dist($p) {
	$p->code = "lire_meta('email_webmaster')";
	$p->type = 'php';
	return $p;
}

function balise_CHARSET_dist($p) {
	$p->code = "lire_meta('charset')";
	$p->type = 'php';
	return $p;
}


function balise_LANG_LEFT_dist($p) {
	$p->code = "lang_dir(\$spip_lang,'left','right')";
	$p->type = 'php';
	return $p;
}

function balise_LANG_RIGHT_dist($p) {
	$p->code = "lang_dir(\$spip_lang,'right','left')";
	$p->type = 'php';
	return $p;
}

function balise_LANG_DIR_dist($p) {
	$p->code = "lang_dir(\$spip_lang,'ltr','rtl')";
	$p->type = 'php';
	return $p;
}

function balise_PUCE_dist($p) {
	$p->code = "propre('- ')";
	$p->type = 'php';
	return $p;
}


// #DATE
// Cette fonction sait aller chercher dans le contexte general
// quand #DATE est en dehors des boucles
// http://www.spip.net/fr_article1971.html
function balise_DATE_dist ($p) {
	$_date = champ_sql('date', $p);
	$p->code = "$_date";
	$p->process = 'vider_date(%s)';
	$p->type = 'php';
	return $p;
}

// #DATE_REDAC
// http://www.spip.net/fr_article1971.html
function balise_DATE_REDAC_dist ($p) {
	$_date = champ_sql('date_redac', $p);
	$p->code = "$_date";
	$p->process = 'vider_date(%s)';
	$p->type = 'php';
	return $p;
}

// #DATE_MODIF
// http://www.spip.net/fr_article1971.html
function balise_DATE_MODIF_dist ($p) {
	$_date = champ_sql('date_modif', $p);
	$p->code = "$_date";
	$p->process = 'vider_date(%s)';
	$p->type = 'php';
	return $p;
}

// #DATE_NOUVEAUTES
// http://www.spip.net/fr_article1971.html
function balise_DATE_NOUVEAUTES_dist($p) {
	$p->code = "((lire_meta('quoi_de_neuf') == 'oui' AND lire_meta('majnouv')) ? normaliser_date(lire_meta('majnouv')) : \"'0000-00-00'\")";
	$p->process = 'vider_date(%s)';
	$p->type = 'php';
	return $p;
}

function balise_URL_SITE_SPIP_dist($p) {
	$p->code = "lire_meta('adresse_site')";
	$p->type = 'php';
	return $p;
}


function balise_URL_ARTICLE_dist($p) {
	$_type = $p->boucles[$p->id_boucle]->type_requete;

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

	$p->type = 'html';
	return $p;
}

function balise_URL_RUBRIQUE_dist($p) {
	$p->code = "generer_url_rubrique(" . 
	champ_sql('id_rubrique',$p) . 
	")" ;
	if ($p->boucles[$p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->type = 'html';
	return $p;
}

function balise_URL_BREVE_dist($p) {
	$p->code = "generer_url_breve(" .
	champ_sql('id_breve',$p) . 
	")";
	if ($p->boucles[$p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->type = 'html';
	return $p;
}

function balise_URL_MOT_dist($p) {
	$p->code = "generer_url_mot(" .
	champ_sql('id_mot',$p) .
	")";
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->type = 'html';
	return $p;
}

function balise_URL_FORUM_dist($p) {
	$p->code = "generer_url_forum(" .
	champ_sql('id_forum',$p) .")";

	$p->type = 'html';
	return $p;
}

function balise_URL_DOCUMENT_dist($p) {
	$p->code = "generer_url_document(" .
	champ_sql('id_document',$p) . ")";

	$p->type = 'html';
	return $p;
}

function balise_URL_AUTEUR_dist($p) {
	$p->code = "generer_url_auteur(" .
	champ_sql('id_auteur',$p) .")";
	if ($p->boucles[$p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->type = 'html';
	return $p;
}

function balise_NOTES_dist($p) {
	// Recuperer les notes
	$p->code = '$GLOBALS["les_notes"]';
	// Vider ensuite les globales des notes recuperees
	// avec une formule qui renvoit toujours ""
	$p->code .= '. ($GLOBALS["les_notes"] = $GLOBALS["compt_note"] = '
	. '($GLOBALS["marqueur_notes"]++)?"":"")';
	$p->type = 'html';
	return $p;
}

function balise_RECHERCHE_dist($p) {
	$p->code = 'htmlspecialchars($GLOBALS["recherche"])';
	$p->type = 'php';
	return $p;
}

function balise_COMPTEUR_BOUCLE_dist($p) {
	$p->code = '$compteur_boucle';
	$p->type = 'php';
	return $p;
}

function balise_TOTAL_BOUCLE_dist($p) {
	if ($p->id_mere === '') {
		include_local("inc-debug-squel.php3");
		erreur_squelette(_L("Champ #TOTAL_BOUCLE hors boucle"), '', $p->id_boucle);
	}
	$p->code = "\$Numrows['$p->id_mere']";
	$p->boucles[$p->id_mere]->numrows = true;
	$p->type = 'php';
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
	include_local("inc-debug-squel.php3");
	erreur_squelette(_L("Champ #POINTS hors d'une recherche"), '', $p->id_boucle);
	}
	$p->type = 'php';
	return $p;
}

function balise_POPULARITE_ABSOLUE_dist($p) {
	$p->code = 'ceil(' .
	champ_sql('popularite', $p) .
	')';
	$p->type = 'php';
	return $p;
}

function balise_POPULARITE_SITE_dist($p) {
	$p->code = 'ceil(lire_meta(\'popularite_total\'))';
	$p->type = 'php';
	return $p;
}

function balise_POPULARITE_MAX_dist($p) {
	$p->code = 'ceil(lire_meta(\'popularite_max\'))';
	$p->type = 'php';
	return $p;
}

function balise_EXPOSER_dist($p) {
	global  $table_primary;
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

	$type_boucle = $p->boucles[$p->id_boucle]->type_requete;
	$primary_key = $table_primary[$type_boucle];

	$p->code = '(calcul_exposer('
	.champ_sql($primary_key, $p)
	.', "'.$primary_key.'", $Pile[0]) ?'." '$on': '$off')";
	$p->type = 'php';
	return $p;
}


//
// Inserer directement un document dans le squelette
//
function balise_EMBED_DOCUMENT_dist($p) {
	$_id_document = champ_sql('id_document',$p);
	$p->code = "embed_document($_id_document, '" .
	texte_script($p->fonctions ? join($p->fonctions, "|") : "") .
	"', false)";
	unset ($p->fonctions);
	$p->type = 'html';
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
	$p->code = '"<"."?php
include_ecrire(\"inc_lang.php3\");
echo menu_langues(\"var_lang\", \$menu_lang);
?".">"';
	$p->type = 'php';
	return $p;
}

// Formulaire de changement de langue / page de login
function balise_MENU_LANG_ECRIRE_dist($p) {
	$p->code = '"<"."?php
include_ecrire(\"inc_lang.php3\");
echo menu_langues(\"var_lang_ecrire\", \$menu_lang);
?".">"';
	$p->type = 'php';
	return $p;
}

//
// Formulaires de login
//
function balise_LOGIN_PRIVE_dist($p) {
	$p->code = '"<"."?php include(\'inc-login.php3\'); login(\'\', \'prive\'); ?".">"'; 
	$p->type = 'php';
	return $p;
}

function balise_LOGIN_PUBLIC_dist($p) {
	if ($nom = $p->fonctions[0])
	$lacible = "new Link('".$nom."')";
	else
	$lacible = '\$GLOBALS[\'clean_link\']';
	$p->code = '"<"."?php include(\'inc-login.php3\'); login(' . $lacible . ', false); ?".">"';
	$p->fonctions = array();
	$p->type = 'php';
	return $p;
}

function balise_URL_LOGOUT_dist($p) {
	if ($p->fonctions) {
	$url = "&url=".$p->fonctions[0];
	$p->fonctions = array();
	} else {
	$url = '&url=\'.urlencode(\$clean_link->getUrl()).\'';
	}
	$p->code = '"<"."?php if (\$GLOBALS[\'auteur_session\'][\'login\'])
{ echo \'spip_cookie.php3?logout_public=\'.\$GLOBALS[\'auteur_session\'][\'login\'].\'' . $url . '\'; } ?".">"';
	$p->type = 'php';
	return $p;
}

function balise_INTRODUCTION_dist ($p) {
	$_type = $p->boucles[$p->id_boucle]->type_requete;
	$_texte = champ_sql('texte', $p);
	$_chapo = champ_sql('chapo', $p);
	$_descriptif = champ_sql('descriptif', $p);
	$p->code = "calcul_introduction('$_type', $_texte, $_chapo, $_descriptif)";

	$p->type = 'html';
	return $p;
}

?>
