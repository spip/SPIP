<?php

//
// Fonctions new style
//

function balise_NOM_SITE_SPIP_dist($params) {
  $params->code = "lire_meta('nom_site')";
  return $params;
}

function balise_EMAIL_WEBMASTER_dist($params) {
  $params->code = "lire_meta('email_webmaster')";
  return $params;
}

function balise_CHARSET_dist($params) {
  $params->code = "lire_meta('charset')";
  return $params;
}


function balise_LANG_LEFT_dist($params) {
  $params->code = "lang_dir(\$GLOBALS['spip_lang'],'left','right')";
  return $params;
}

function balise_LANG_RIGHT_dist($params) {
  $params->code = "lang_dir(\$GLOBALS['spip_lang'],'right','left')";
  return $params;
}

function balise_LANG_DIR_dist($params) {
  $params->code = "lang_dir(\$GLOBALS['spip_lang'],'ltr','rtl')";
  return $params;
}

function balise_PUCE_dist($params) {
  $params->code = "propre('- ')";
  return $params;
}


function balise_DATE_NOUVEAUTES_dist($params) {
  $params->code = "((lire_meta('quoi_de_neuf') == 'oui' AND lire_meta('majnouv')) ? normaliser_date(lire_meta('majnouv')) : \"'0000-00-00'\")";
  return $params;
}

function balise_URL_SITE_SPIP_dist($params) {
  $params->code = "lire_meta('adresse_site')";
  return $params;
}


function balise_URL_ARTICLE_dist($params) {
	$_type = $params->boucles[$params->id_boucle]->type_requete;

	// Cas particulier des boucles (SYNDIC_ARTICLES)
	if ($_type == 'syndic_articles') {
		$params->code = champ_sql('url', $params);
	}

	// Cas general : chercher un id_article dans la pile
	else {
		$_id_article = champ_sql('id_article', $params);
		$params->code = "generer_url_article($_id_article)";

		if ($params->boucles[$params->id_boucle]->hash)
			$params->code = "url_var_recherche(" . $params->code . ")";
	}

	return $params;
}

function balise_URL_RUBRIQUE_dist($params) {
  $params->code = "generer_url_rubrique(" . 
	champ_sql('id_rubrique',$params) . 
	")" ;
  if ($params->boucles[$params->id_boucle]->hash)
	$params->code = "url_var_recherche(" . $params->code . ")";
  return $params;
}

function balise_URL_BREVE_dist($params) {
  $params->code = "generer_url_breve(" .
	champ_sql('id_breve',$params) . 
	")";
  if ($params->boucles[$params->id_boucle]->hash)
	$params->code = "url_var_recherche(" . $params->code . ")";
  return $params;
}

function balise_URL_MOT_dist($params) {
  $params->code = "generer_url_mot(" .
	champ_sql('id_mot',$params) .
	")";
  $params->code = "url_var_recherche(" . $params->code . ")";
  return $params;
}

function balise_URL_FORUM_dist($params) {
  $params->code = "generer_url_forum(" .
	champ_sql('id_forum',$params) .")";
  return $params;
}

function balise_URL_DOCUMENT_dist($params) {
  $params->code = "generer_url_document(" .
	champ_sql('id_document',$params) . ")";
  return $params;
}

function balise_URL_AUTEUR_dist($params) {
  $params->code = "generer_url_auteur(" .
	champ_sql('id_auteur',$params) .")";
  if ($params->boucles[$params->id_boucle]->hash)
	$params->code = "url_var_recherche(" . $params->code . ")";
  return $params;
}

function balise_NOTES_dist($params) {
  $params->entete = '$lacible = $GLOBALS["les_notes"];
$GLOBALS["les_notes"] = "";
$GLOBALS["compt_note"] = 0;
$GLOBALS["marqueur_notes"] ++;
';
  $params->code = '$lacible';
  return $params;
}

function balise_RECHERCHE_dist($params) {
  $params->code = 'htmlspecialchars($GLOBALS["recherche"])';
  return $params;
}

function balise_COMPTEUR_BOUCLE_dist($params) {
  $params->code = '$compteur_boucle';
  return $params;
}

function balise_TOTAL_BOUCLE_dist($params) {
  if ($params->id_mere === '') {
	include_local("inc-debug-squel.php3");
	erreur_squelette(_L("Champ #TOTAL_BOUCLE hors boucle"), '', $params->id_boucle);
  }
  $params->code = "\$Numrows['$params->id_mere']";
  $params->boucles[$params->id_mere]->numrows = true;
  return $params;
}

function balise_POINTS_dist($params) {
  $n = 0;
  $b = $params->id_boucle;
  $params->code = '';
  while ($b != '') {
	if ($s = $params->boucles[$b]->param) {
	  foreach($s as $v) {
		if (strpos($v,'recherche') !== false) {
		  $params->code = '$Pile[$SP' . (($n==0) ? "" : "-$n") .
			'][points]';
		  $b = '';
		  break;
		}
	  }
	}
	$n++;
	$b = $params->boucles[$b]->id_parent;
  }
  if (!$params->code) {
	include_local("inc-debug-squel.php3");
	erreur_squelette(_L("Champ #POINTS hors d'une recherche"), '', $params->id_boucle);
  }
  return $params;
}

function balise_POPULARITE_ABSOLUE_dist($params) {
  $params->code = 'ceil(' .
	champ_sql('popularite', $params) .
	')';
  return $params;
}

function balise_POPULARITE_SITE_dist($params) {
  $params->code = 'ceil(lire_meta(\'popularite_total\'))';
  return $params;
}

function balise_POPULARITE_MAX_dist($params) {
  $params->code = 'ceil(lire_meta(\'popularite_max\'))';
  return $params;
}

function balise_EXPOSER_dist($params) {
  global  $table_primary;
  $on = 'on';
  $off= '';
  if ($params->fonctions) {
	// Gerer la notation [(#EXPOSER|on,off)]
	reset($params->fonctions);
	list(, $onoff) = each($params->fonctions);
	ereg("([^,]*)(,(.*))?", $onoff, $regs);
	$on = addslashes($regs[1]);
	$off = addslashes($regs[3]);
	
	// autres filtres
	$filtres=Array();
	while (list(, $nom) = each($params->fonctions))
	  $filtres[] = $nom;
	$params->fonctions = $filtres;
  }

  $type_boucle = $params->boucles[$params->id_boucle]->type_requete;
  $primary_key = $table_primary[$type_boucle];

  $params->code = '(calcul_exposer('
	.champ_sql($primary_key, $params)
	.', "'.$primary_key.'", $Pile[0]) ?'." '$on': '$off')";
  return $params;
}


//
// Inserer directement un document dans le squelette
//
function balise_EMBED_DOCUMENT_dist($params) {
  $params->entete = '
$lacible = '
	. champ_sql('id_document',$params)
	. ';
$lacible = embed_document($lacible, \'' .
	($fonctions ? join($fonctions, "|") : "") .
	'\', false);';
  $fonctions = "";
  $params->code = '$lacible';
  return $params;
}

// Debut et fin de surlignage auto des mots de la recherche
// on insere une balise Span avec une classe sans spec:
// c'est transparent s'il n'y a pas de recherche,
// sinon elles seront remplacees par les fontions de inc_surligne
// flag_pcre est juste une flag signalant que preg_match est dispo.

function balise_DEBUT_SURLIGNE_dist($params) {
  global $flag_pcre;
  $params->code = ($flag_pcre ? ('\'<span class="spip_surligneconditionnel">\'') : "''");
  return $params;
}
function balise_FIN_SURLIGNE_dist($params) {
  global $flag_pcre;
  $params->code = ($flag_pcre ? ('\'</span class="spip_surligneconditionnel">\'') : "''");
  return $params;
}

// Formulaire de changement de langue
function balise_MENU_LANG_dist($params) {
  $params->code = '"<"."?php
include_ecrire(\"inc_lang.php3\");
echo menu_langues(\"var_lang\", \$menu_lang);
?".">"';
  return $params;
}

// Formulaire de changement de langue / page de login
function balise_MENU_LANG_ECRIRE_dist($params) {
  $params->code = '"<"."?php
include_ecrire(\"inc_lang.php3\");
echo menu_langues(\"var_lang_ecrire\", \$menu_lang);
?".">"';
  return $params;
}

//
// Formulaires de login
//
function balise_LOGIN_PRIVE_dist($params) {
  $params->code = '"<"."?php include(\'inc-login.php3\'); login(\'\', \'prive\'); ?".">"'; 
  return $params;
}

function balise_LOGIN_PUBLIC_dist($params) {
  if ($nom = $params->fonctions[0])
	$lacible = "new Link('".$nom."')";
  else
	$lacible = '\$GLOBALS[\'clean_link\']';
  $params->code = '"<"."?php include(\'inc-login.php3\'); login(' . $lacible . ', false); ?".">"';
  $params->fonctions = array();
  return $params;
}

function balise_URL_LOGOUT_dist($params) {
  if ($params->fonctions) {
	$url = "&url=".$params->fonctions[0];
	$params->fonctions = array();
  } else {
	$url = '&url=\'.urlencode(\$clean_link->getUrl()).\'';
  }
  $params->code = '"<"."?php if (\$GLOBALS[\'auteur_session\'][\'login\'])
{ echo \'spip_cookie.php3?logout_public=\'.\$GLOBALS[\'auteur_session\'][\'login\'].\'' . $url . '\'; } ?".">"';
  return $params;
}

function balise_LOGO_ARTICLE_dist($params) {
  // retour immediat: filtres derogatoires traites dans la fonction
  return calculer_champ_LOGO($params);
}

function balise_LOGO_ARTICLE_NORMAL_dist($params) {
  // retour immediat: filtres derogatoires traites dans la fonction
  return calculer_champ_LOGO($params);
}

function balise_LOGO_ARTICLE_RUBRIQUE_dist($params) {
  // retour immediat: filtres derogatoires traites dans la fonction
  return calculer_champ_LOGO($params);
}

function balise_LOGO_ARTICLE_SURVOL_dist($params) {
  // retour immediat: filtres derogatoires traites dans la fonction
  return calculer_champ_LOGO($params);
}

function balise_LOGO_AUTEUR_dist($params) {
  // retour immediat: filtres derogatoires traites dans la fonction
  return calculer_champ_LOGO($params);
}

function balise_LOGO_AUTEUR_NORMAL_dist($params) {
  // retour immediat: filtres derogatoires traites dans la fonction
  return calculer_champ_LOGO($params);
}

function balise_LOGO_AUTEUR_SURVOL_dist($params) {
  // retour immediat: filtres derogatoires traites dans la fonction
  return calculer_champ_LOGO($params);
}

function balise_LOGO_SITE_dist($params) {
  // retour immediat: filtres derogatoires traites dans la fonction
  return calculer_champ_LOGO($params);
}

function balise_LOGO_BREVE_dist($params) {
  // retour immediat: filtres derogatoires traites dans la fonction
  return calculer_champ_LOGO($params);
}

function balise_LOGO_BREVE_RUBRIQUE_dist($params) {
  // retour immediat: filtres derogatoires traites dans la fonction
  return calculer_champ_LOGO($params);
}

function balise_LOGO_MOT_dist($params) {
  // retour immediat: filtres derogatoires traites dans la fonction
  return calculer_champ_LOGO($params);
}

function balise_LOGO_RUBRIQUE_dist($params) {
  // retour immediat: filtres derogatoires traites dans la fonction
  return calculer_champ_LOGO($params);
}

function balise_LOGO_RUBRIQUE_NORMAL_dist($params) {
  // retour immediat: filtres derogatoires traites dans la fonction
  return calculer_champ_LOGO($params);
}

function balise_LOGO_RUBRIQUE_SURVOL_dist($params) {
  // retour immediat: filtres derogatoires traites dans la fonction
  return calculer_champ_LOGO($params);
}

function balise_LOGO_DOCUMENT_dist($params) {
  // retour immediat: filtres derogatoires traites dans la fonction
  return calculer_champ_LOGO($params);
}


function balise_INTRODUCTION_dist ($p) {
	$_type = $p->boucles[$p->id_boucle]->type_requete;
	$_texte = champ_sql('texte', $p);
	$_chapo = champ_sql('chapo', $p);
	$_descriptif = champ_sql('descriptif', $p);
	$p->code = "calcul_introduction('$_type', $_texte, $_chapo, $_descriptif)";

	return $p;
}

?>
