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


//
// Ce fichier regroupe la quasi totalite des definitions de #BALISES de spip
// Pour chaque balise, il est possible de surcharger, dans mes_fonctions,
// la fonction balise_TOTO_dist par une fonction balise_TOTO() respectant la
// meme API : 
// elle recoit en entree un objet de classe CHAMP, le modifie et le retourne.
// Cette classe est definie dans inc-compilo-index
//

if (!defined("_ECRIRE_INC_VERSION")) return;


//
// Definition des balises
//
function balise_NOM_SITE_SPIP_dist($p) {
	$p->code = "\$GLOBALS['meta']['nom_site']";
	#$p->interdire_scripts = true;
	return $p;
}

function balise_EMAIL_WEBMASTER_dist($p) {
	$p->code = "\$GLOBALS['meta']['email_webmaster']";
	#$p->interdire_scripts = true;
	return $p;
}

function balise_CHARSET_dist($p) {
	$p->code = "\$GLOBALS['meta']['charset']";
	#$p->interdire_scripts = true;
	return $p;
}

function balise_LANG_LEFT_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "lang_dir(($_lang ? $_lang : \$GLOBALS['spip_lang']),'left','right')";
	$p->interdire_scripts = false;
	return $p;
}

function balise_LANG_RIGHT_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "lang_dir(($_lang ? $_lang : \$GLOBALS['spip_lang']),'right','left')";
	$p->interdire_scripts = false;
	return $p;
}

function balise_LANG_DIR_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "lang_dir(($_lang ? $_lang : \$GLOBALS['spip_lang']),'ltr','rtl')";
	$p->interdire_scripts = false;
	return $p;
}

function balise_PUCE_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "((lang_dir(($_lang ? $_lang : \$GLOBALS['spip_lang']),false,true) && \$GLOBALS['puce_rtl']) ? \$GLOBALS['puce_rtl'] : \$GLOBALS['puce'])";
	$p->interdire_scripts = false;
	return $p;
}

// #DATE
// Cette fonction sait aller chercher dans le contexte general
// quand #DATE est en dehors des boucles
// http://www.spip.net/fr_article1971.html
function balise_DATE_dist ($p) {
	$_date = champ_sql('date', $p);
	$p->code = "$_date";
	$p->interdire_scripts = false;
	return $p;
}

// #DATE_REDAC
// http://www.spip.net/fr_article1971.html
function balise_DATE_REDAC_dist ($p) {
	$_date = champ_sql('date_redac', $p);
	$p->code = "$_date";
	$p->interdire_scripts = false;
	return $p;
}

// #DATE_MODIF
// http://www.spip.net/fr_article1971.html
function balise_DATE_MODIF_dist ($p) {
	$_date = champ_sql('date_modif', $p);
	$p->code = "$_date";
	$p->interdire_scripts = false;
	return $p;
}

// #DATE_NOUVEAUTES
// http://www.spip.net/fr_article1971.html
function balise_DATE_NOUVEAUTES_dist($p) {
	$p->code = "((\$GLOBALS['meta']['quoi_de_neuf'] == 'oui'
	AND @file_exists(_DIR_SESSIONS . 'mail.lock')) ?
	normaliser_date(@filemtime(_DIR_SESSIONS . 'mail.lock')) :
	\"'0000-00-00'\")";
	$p->interdire_scripts = false;
	return $p;
}

function balise_DOSSIER_SQUELETTE_dist($p) {
	$p->code = "'" . addslashes(dirname($p->descr['sourcefile'])) . "'" ;
	$p->interdire_scripts = false;
	return $p;
}

function balise_URL_SITE_SPIP_dist($p) {
	$p->code = "\$GLOBALS['meta']['adresse_site']";
	#$p->interdire_scripts = true;
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

		if ($p->boucles[$p->nom_boucle ? $p->nom_boucle : $p->id_boucle]->hash)
			$p->code = "url_var_recherche(" . $p->code . ")";
	}

	$p->interdire_scripts = false;
	return $p;
}

function balise_URL_RUBRIQUE_dist($p) {
	$p->code = "generer_url_rubrique(" . 
	champ_sql('id_rubrique',$p) . 
	")" ;
	if ($p->boucles[$p->nom_boucle ? $p->nom_boucle : $p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->interdire_scripts = false;
	return $p;
}

function balise_URL_BREVE_dist($p) {
	$p->code = "generer_url_breve(" .
	champ_sql('id_breve',$p) . 
	")";
	if ($p->boucles[$p->nom_boucle ? $p->nom_boucle : $p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->interdire_scripts = false;
	return $p;
}

function balise_URL_MOT_dist($p) {
	$p->code = "generer_url_mot(" .
	champ_sql('id_mot',$p) .
	")";

	if ($p->boucles[$p->nom_boucle ? $p->nom_boucle : $p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->interdire_scripts = false;
	return $p;
}

# remarque : URL_SITE ne figure pas ici car c'est une donnee 'brute'
# correspondant a l'URL du site reference ; URL_SYNDIC correspond
# pour sa part a l'adresse de son backend.
# Il n'existe pas de balise pour afficher generer_url_site($id_syndic),
# a part [(#ID_SYNDIC|generer_url_site)]

function balise_URL_FORUM_dist($p) {
	$p->code = "generer_url_forum(" .
	champ_sql('id_forum',$p) .")";

	if ($p->boucles[$p->nom_boucle ? $p->nom_boucle : $p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->interdire_scripts = false;
	return $p;
}

function balise_URL_DOCUMENT_dist($p) {
	$p->code = "generer_url_document(" .
	champ_sql('id_document',$p) . ")";

	$p->interdire_scripts = false;
	return $p;
}

function balise_URL_AUTEUR_dist($p) {
	$p->code = "generer_url_auteur(" .
	champ_sql('id_auteur',$p) .")";
	if ($p->boucles[$p->nom_boucle ? $p->nom_boucle : $p->id_boucle]->hash)
	$p->code = "url_var_recherche(" . $p->code . ")";

	$p->interdire_scripts = false;
	return $p;
}

function balise_NOTES_dist($p) {
	// Recuperer les notes
	$p->code = 'calculer_notes()';
	#$p->interdire_scripts = true;
	return $p;
}

function balise_RECHERCHE_dist($p) {
	$p->code = 'entites_html($GLOBALS["recherche"])';
	$p->interdire_scripts = false;
	return $p;
}

function balise_COMPTEUR_BOUCLE_dist($p) {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];
	if ($b === '') {
		erreur_squelette(
			_T('zbug_champ_hors_boucle',
				array('champ' => '#COMPTEUR_BOUCLE')
			), $p->id_boucle);
		$p->code = "''";
	} else {
		$p->code = "\$Numrows['$b']['compteur_boucle']";
		$p->boucles[$b]->cptrows = true;
		$p->interdire_scripts = false;
		return $p;
	}
}

function balise_TOTAL_BOUCLE_dist($p) {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];
	if ($b === '') {
		erreur_squelette(
			_T('zbug_champ_hors_boucle',
				array('champ' => '#TOTAL_BOUCLE')
			), $p->id_boucle);
		$p->code = "''";
	} else {
		$p->code = "\$Numrows['$b']['total']";
		$p->boucles[$b]->numrows = true;
		$p->interdire_scripts = false;
	}
	return $p;
}

// Si on est hors d'une boucle {recherche}, ne pas "prendre" cette balise
function balise_POINTS_dist($p) {
	if ($p->boucles[$p->nom_boucle ? $p->nom_boucle : $p->id_boucle]->hash)
		return rindex_pile($p, 'points', 'recherche');
	else
		return NULL;
}

function balise_POPULARITE_ABSOLUE_dist($p) {
	$p->code = 'ceil(' .
	champ_sql('popularite', $p) .
	')';
	$p->interdire_scripts = false;
	return $p;
}

function balise_POPULARITE_SITE_dist($p) {
	$p->code = 'ceil($GLOBALS["meta"][\'popularite_total\'])';
	$p->interdire_scripts = false;
	return $p;
}

function balise_POPULARITE_MAX_dist($p) {
	$p->code = 'ceil($GLOBALS["meta"][\'popularite_max\'])';
	$p->interdire_scripts = false;
	return $p;
}

function balise_EXPOSE_dist($p) {
	$on = "'on'";
	$off= "''";

	if ($p->param && !$p->param[0][0]) {
		$on =  calculer_liste($p->param[0][1],
					$p->descr,
					$p->boucles,
					$p->id_boucle);

		$off =  calculer_liste($p->param[0][2],
					$p->descr,
					$p->boucles,
					$p->id_boucle);

		// autres filtres
		array_shift($p->param);
	}
	return calculer_balise_expose($p, $on, $off);
}

// obsolete. utiliser la precedente

function balise_EXPOSER_dist($p)
{
	$on = "'on'";
	$off= "''";
	if ($a = ($p->fonctions)) {
		// Gerer la notation [(#EXPOSER|on,off)]
		$onoff = array_shift($a);
		ereg("([^,]*)(,(.*))?", $onoff[0], $regs);
		$on = "'" . addslashes($regs[1]) . "'";
		$off = "'" . addslashes($regs[3]) . "'" ;
		// autres filtres
		array_shift($p->param);
	}
	return calculer_balise_expose($p, $on, $off);
}

function calculer_balise_expose($p, $on, $off)
{
	$primary_key = $p->boucles[$p->id_boucle]->primary;
	if (!$primary_key) {
		erreur_squelette(_T('zbug_champ_hors_boucle',
				array('champ' => '#EXPOSER')
			), $p->id_boucle);

	}

	$p->code = '(calcul_exposer('
	.champ_sql($primary_key, $p)
	.", '$primary_key', \$Pile[0]) ? $on : $off)";
	$p->interdire_scripts = false;
	return $p;
}

//
// Inserer directement un document dans le squelette
//
function balise_EMBED_DOCUMENT_dist($p) {
	balise_distante_interdite($p);
	$_id_document = champ_sql('id_document',$p);
	$p->code = "calcule_embed_document(intval($_id_document), " .
	  argumenter_balise($p->fonctions, "|") .
	  ", \$doublons, '" . $p->descr['documents'] . "')";
	$p->param = array();
	#$p->interdire_scripts = true;
	return $p;
}

// Debut et fin de surlignage auto des mots de la recherche
// on insere une balise Span avec une classe sans spec:
// c'est transparent s'il n'y a pas de recherche,
// sinon elles seront remplacees par les fontions de inc_surligne

function balise_DEBUT_SURLIGNE_dist($p) {
	include_ecrire('inc_surligne');
	$p->code = "'<" . MARQUEUR_SURLIGNE . "'";
	return $p;
}
function balise_FIN_SURLIGNE_dist($p) {
	include_ecrire('inc_surligne');
	$p->code = "'<" . MARQUEUR_FSURLIGNE . "'";
	return $p;
}


// #SPIP_CRON
// a documenter
// insere un <div> avec un lien background-image vers les taches de fond.
// Si cette balise est presente sur la page de sommaire, le site ne devrait
// quasiment jamais se trouver ralenti par des taches de fond un peu lentes
// ATTENTION: cette balise efface parfois les boutons admin implicites
function balise_SPIP_CRON_dist ($p) {
  $p->code = '"' . str_replace('"', '\"', (generer_spip_cron())) . '"';
	$p->interdire_scripts = false;
	return $p;
}


// #INTRODUCTION
// http://www.spip.net/@introduction
function balise_INTRODUCTION_dist ($p) {
	$_type = $p->type_requete;
	$_texte = champ_sql('texte', $p);
	$_chapo = champ_sql('chapo', $p);
	$_descriptif = champ_sql('descriptif', $p);
	$p->code = "calcul_introduction('$_type', $_texte, $_chapo, $_descriptif)";

	#$p->interdire_scripts = true;
	return $p;
}


// #LANG
// non documente ?
function balise_LANG_dist ($p) {
	$_lang = champ_sql('lang', $p);
	if (!$p->etoile)
		$p->code = "($_lang ? $_lang : \$GLOBALS['spip_lang'])";
	else
		$p->code = $_lang;
	$p->interdire_scripts = false;
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

	#$p->interdire_scripts = true;
	return $p;
}


// #PETITION 
// retourne '' si l'article courant n'a pas de petition 
// le texte de celle-ci sinon (et ' ' si il est vide)
// cf FORMULAIRE_PETITION

function balise_PETITION_dist ($p) {
	$nom = $p->id_boucle;
	$p->code = "sql_petitions(" .
			champ_sql('id_article', $p) .
			",'" .
			$p->boucles[$nom]->type_requete .
			"','" .
			$nom .
			"','" .
			$p->boucles[$nom]->sql_serveur .
			"', \$Cache)";
	$p->interdire_scripts = false;
	return $p;
}


// #POPULARITE
// http://www.spip.net/fr_article1846.html
function balise_POPULARITE_dist ($p) {
	$_popularite = champ_sql('popularite', $p);
	$p->code = "(ceil(min(100, 100 * $_popularite
	/ max(1 , 0 + \$GLOBALS['meta']['popularite_max']))))";
	$p->interdire_scripts = false;
	return $p;
}


//
// Fonction commune aux balises #LOGO_XXXX
// (les balises portant ce type de nom sont traitees en bloc ici)
//
function calculer_balise_logo ($p) {

	eregi("^LOGO_([A-Z]+)(_.*)?$", $p->nom_champ, $regs);
	$type_objet = $regs[1];
	$suite_logo = $regs[2];	
	if (ereg("^_SPIP(.*)$", $suite_logo, $regs)) {
		$type_objet = 'RUBRIQUE';
		$suite_logo = $regs[1];
		$_id_objet = "\"'0'\"";
	} else {

		if ($type_objet == 'SITE')
			$_id_objet = champ_sql("id_syndic", $p);
		else
			$_id_objet = champ_sql("id_".strtolower($type_objet), $p);
	}
	// analyser les faux filtres, 
	// supprimer ceux qui ont le tort d'etre vrais
	$flag_fichier = 0;
	$filtres = '';
	if (is_array($p->fonctions)) {
		foreach($p->fonctions as $couple) {
			// eliminer les faux filtres
			if (!$flag_stop) {
				array_shift($p->param);
				$nom = $couple[0];
				if (ereg('^(left|right|center|top|bottom)$', $nom))
					$align = $nom;
				else if ($nom == 'lien') {
					$flag_lien_auto = 'oui';
					$flag_stop = true;
				}
				else if ($nom == 'fichier') {
					$flag_fichier = 1;
					$flag_stop = true;
				}
				// double || signifie "on passe aux filtres"
				else if ($nom == '') {
					if (!$params = $couple[1])
						$flag_stop = true;
				}
				else if ($nom) {
					$lien = $nom;
					$flag_stop = true;
				} else {
					
				}
			}
			// apres un URL ou || ou |fichier ce sont
			// des filtres (sauf left...lien...fichier)
		}
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
			$c->descr = $p->descr;
			$c = calculer_champ($c);
			$code_lien = str_replace('#'.$match[2], "'.".$c.".'", $code_lien);
		}
		// supprimer les '' disgracieux
		$code_lien = ereg_replace("^''\.|\.''$", "", $code_lien);
	}

	if ($flag_fichier)
		$code_lien = "'',''" ; 
	else {
		if (!$code_lien)
			$code_lien = "''";
		$code_lien .= ", '". addslashes($align) . "'";
	}

	// cas des documents
	if ($type_objet == 'DOCUMENT') {
		$p->code = "calcule_logo_document($_id_objet, '" .
			$p->descr['documents'] .
			'\', $doublons, '. intval($flag_fichier).", $code_lien, '".
			// #LOGO_DOCUMENT{x,y} donne la taille maxi
			texte_script($params)
			."')";
	}
	else {
		$p->code = "affiche_logos(calcule_logo('$type_objet', '" .
			(($suite_logo == '_SURVOL') ? 'off' : 
			(($suite_logo == '_NORMAL') ? 'on' : 'ON')) .
			"', $_id_objet," .
			(($suite_logo == '_RUBRIQUE') ? 
			champ_sql("id_rubrique", $p) :
			(($type_objet == 'RUBRIQUE') ? "sql_parent($_id_objet)" : "''")) .
			",  '$flag_fichier'), $code_lien)";
	}
	$p->interdire_scripts = false;
	return $p;
}

// #EXTRA [(#EXTRA|isbn)]
// Champs extra
// Non documentes, en voie d'obsolescence, cf. ecrire/inc_extra
function balise_EXTRA_dist ($p) {
	$_extra = champ_sql('extra', $p);
	$p->code = $_extra;

	// Gerer la notation [(#EXTRA|isbn)]
	if ($p->param) {
		include_ecrire("inc_extra");
		list ($key, $champ_extra) = each($p->param);	// le premier filtre
		$type_extra = $p->type_requete;
		$champ = $champ_extra[1];

	// ci-dessus est sans doute un peu buggue : si on invoque #EXTRA
	// depuis un sous-objet sans champ extra d'un objet a champ extra,
	// on aura le type_extra du sous-objet (!)
		if (extra_champ_valide($type_extra, $champ))
		{
			array_shift($p->param);
# A quoi ca sert ?
#		$p->code = "extra($p->code, '".addslashes($champ)."')";


			// Appliquer les filtres definis par le webmestre
			$filtres = extra_filtres($type_extra, $champ);
			if ($filtres) foreach ($filtres as $f)
				$p->code = "$f($p->code)";
		}
	}

	#$p->interdire_scripts = true;
	return $p;
}

//
// Parametres de reponse a un forum
//

function balise_PARAMETRES_FORUM_dist($p) {
	include_local(find_in_path('inc-formulaire_forum' . _EXTENSION_PHP));
	$_id_article = champ_sql('id_article', $p);
	$p->code = '
		// refus des forums ?
		(sql_accepter_forum('.$_id_article.')=="non" OR
		($GLOBALS["meta"]["forums_publics"] == "non"
		AND sql_accepter_forum('.$_id_article.') == ""))
		? "" : // sinon:
		';

	switch ($p->type_requete) {
		case 'articles':
			$c = '"id_article=".' . champ_sql('id_article', $p);
			break;
		case 'breves':
			$c = '"id_breve=".' . champ_sql('id_breve', $p);
			break;
		case 'rubriques':
			$c = '"id_rubrique=".' . champ_sql('id_rubrique', $p);
			break;
		case 'syndication':
			$c = '"id_syndic=".' . champ_sql('id_syndic', $p);
			break;
		case 'forums':
		default:
			$liste_champs = array ("id_article","id_breve","id_rubrique","id_syndic","id_forum");
			foreach ($liste_champs as $champ) {
				$x = champ_sql( $champ, $p);
				$c .= (($c) ? ".\n" : "") . "((!$x) ? '' : ('&$champ='.$x))";
			}
			$c = "substr($c,1)";
			break;
	}

	// Syntaxe [(#PARAMETRES_FORUM{#SELF})] pour fixer le retour du forum
	# note : ce bloc qui sert a recuperer des arguments calcules pourrait
	# porter un nom et faire partie de l'API.
	if ($p->param && !$p->param[0][0]) {
		  $retour = array_shift( $p->param );
		  array_shift($retour);
		  $retour = calculer_liste($retour[0],
					   $p->descr,
					   $p->boucles,
					   $p->id_boucle);
	}
	else
		$retour = "''";

	// Attention un eventuel &retour=xxx dans l'URL est prioritaire
	$c .= '.
	(($lien = (_request("retour") ? _request("retour") : '.$retour.')) ? "&retour=".rawurlencode($lien) : "")';

	$p->code .= code_invalideur_forums($p, "(".$c.")");

	#$p->interdire_scripts = true;
	return $p;
}


// Noter l'invalideur de la page contenant ces parametres,
// en cas de premier post sur le forum
function code_invalideur_forums($p, $code) {
	include_ecrire('inc_invalideur');
	$type = 'id_forum';
	$valeur = "\n\t\tcalcul_index_forum("
		// Retournera 4 [$SP] mais force la demande du champ a MySQL
		. champ_sql('id_article', $p) . ','
		. champ_sql('id_breve', $p) .  ','
		. champ_sql('id_rubrique', $p) .','
		. champ_sql('id_syndic', $p) .  ")\n\t";

	return ajouter_invalideur($type, $valeur, $code);
}

// Reference a l'URL de la page courante
// Attention dans un INCLURE() ou une balise dynamique on n'a pas le droit de
// mettre en cache #SELF car il peut correspondre a une autre page (attaque XSS)
// http://www.spip.net/@self
function balise_SELF_dist($p) {
	$p->code = 'quote_amp($GLOBALS["clean_link"]->getUrl())';
	$p->interdire_scripts = false;
	return $p;
}


//
// #ENV
// l'"environnement", id est le $contexte (ou $contexte_inclus)
//
// en standard on applique |entites_html, mais attention si
// vous utilisez [(#ENV*{toto})] il *faut* vous assurer vous-memes
// de la securite anti-php et anti-javascript
//
// La syntaxe #ENV{toto, rempl} renverra 'rempl' si $toto est vide
//
function balise_ENV_dist($p) {

	if ($a = $p->param) {
		$sinon = array_shift($a);
		if  (!array_shift($sinon)) {
			$p->fonctions = $a;
			array_shift( $p->param );
			$nom = array_shift($sinon);
			$nom = ($nom[0]->type=='texte') ? $nom[0]->texte : "";
		}
	}

	if (!$nom) {
		// cas de #ENV sans argument : on retourne le serialize() du tableau
		// une belle fonction [(#ENV|affiche_env)] serait pratique
		$p->code = 'serialize($Pile[0])';
		#$p->interdire_scripts = true;
	} else {
		// admet deux arguments : nom de variable, valeur par defaut si vide
		$p->code = '$Pile[0]["' . addslashes($nom) . '"]';
		if ($sinon)
			$p->code = 'sinon('. 
				$p->code
				. compose_filtres_args($p, $sinon, ',')
				. ')';
		$p->interdire_scripts = false;
	}

	return $p;
}

//
// #REM
// pour les remarques : renvoie toujours ''
//
function balise_REM_dist($p) {
	$p->code="''";
	$p->interdire_scripts = false;
	return $p;
}

//
// #HTTP
// pour les entetes. A n'utiliser qu'en debut de squelette
//
function balise_HTTP_dist($p) {
  $a = $p->param[0];
  array_shift($a);
  $code = "";
  foreach($a as $v) $code .= 'header("' . $v[0]->texte . '");';
  $p->code="('<'.'?php $code  ?' . '>')";
  $p->interdire_scripts = false;
  return $p;
}

?>
