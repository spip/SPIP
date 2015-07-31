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

//
// Ce fichier regroupe la quasi totalite des definitions de #BALISES de spip
// Pour chaque balise, il est possible de surcharger, dans mes_fonctions,
// la fonction balise_TOTO_dist par une fonction balise_TOTO() respectant la
// meme API : 
// elle recoit en entree un objet de classe CHAMP, le modifie et le retourne.
// Cette classe est definie dans inc-compilo-index
//

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@interprete_argument_balise
function interprete_argument_balise($n,$p) {
	if (($p->param) && (!$p->param[0][0]) && (count($p->param[0])>$n))
		return calculer_liste($p->param[0][$n],
			$p->descr,
			$p->boucles,
			$p->id_boucle);	
	else 
		return NULL;
}
//
// Definition des balises
//
// http://doc.spip.org/@balise_NOM_SITE_SPIP_dist
function balise_NOM_SITE_SPIP_dist($p) {
	$p->code = "\$GLOBALS['meta']['nom_site']";
	#$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_EMAIL_WEBMASTER_dist
function balise_EMAIL_WEBMASTER_dist($p) {
	$p->code = "\$GLOBALS['meta']['email_webmaster']";
	#$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_DESCRIPTIF_SITE_SPIP_dist
function balise_DESCRIPTIF_SITE_SPIP_dist($p) {
	$p->code = "\$GLOBALS['meta']['descriptif_site']";
	#$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_CHARSET_dist
function balise_CHARSET_dist($p) {
	$p->code = "\$GLOBALS['meta']['charset']";
	#$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_LANG_LEFT_dist
function balise_LANG_LEFT_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "lang_dir($_lang, 'left','right')";
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_LANG_RIGHT_dist
function balise_LANG_RIGHT_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "lang_dir($_lang, 'right','left')";
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_LANG_DIR_dist
function balise_LANG_DIR_dist($p) {
	$_lang = champ_sql('lang', $p);
	$p->code = "lang_dir($_lang, 'ltr','rtl')";
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_PUCE_dist
function balise_PUCE_dist($p) {
	$p->code = "definir_puce()";
	$p->interdire_scripts = false;
	return $p;
}

// #DATE
// Cette fonction sait aller chercher dans le contexte general
// quand #DATE est en dehors des boucles
// http://www.spip.net/fr_article1971.html
// http://doc.spip.org/@balise_DATE_dist
function balise_DATE_dist ($p) {
	$_date = champ_sql('date', $p);
	$p->code = "$_date";
	$p->interdire_scripts = false;
	return $p;
}

// #DATE_REDAC
// http://www.spip.net/fr_article1971.html
// http://doc.spip.org/@balise_DATE_REDAC_dist
function balise_DATE_REDAC_dist ($p) {
	$_date = champ_sql('date_redac', $p);
	$p->code = "$_date";
	$p->interdire_scripts = false;
	return $p;
}

// #DATE_MODIF
// http://www.spip.net/fr_article1971.html
// http://doc.spip.org/@balise_DATE_MODIF_dist
function balise_DATE_MODIF_dist ($p) {
	$_date = champ_sql('date_modif', $p);
	$p->code = "$_date";
	$p->interdire_scripts = false;
	return $p;
}

// #DATE_NOUVEAUTES
// http://www.spip.net/fr_article1971.html
// http://doc.spip.org/@balise_DATE_NOUVEAUTES_dist
function balise_DATE_NOUVEAUTES_dist($p) {
	$p->code = "((\$GLOBALS['meta']['quoi_de_neuf'] == 'oui'
	AND @file_exists(_DIR_TMP . 'mail.lock')) ?
	normaliser_date(@filemtime(_DIR_TMP . 'mail.lock')) :
	\"'0000-00-00'\")";
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_DOSSIER_SQUELETTE_dist
function balise_DOSSIER_SQUELETTE_dist($p) {
	$code = substr(addslashes(dirname($p->descr['sourcefile'])), strlen(_DIR_RACINE));
	$p->code = "_DIR_RACINE . '$code'" . 
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_SQUELETTE_dist
function balise_SQUELETTE_dist($p) {
	$code = addslashes($p->descr['sourcefile']);
	$p->code = "'$code'" . 
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_SPIP_VERSION_dist
function balise_SPIP_VERSION_dist($p) {
	$p->code = "spip_version()";
	$p->interdire_scripts = false;
	return $p;
}


// #NOM_SITE affiche le nom du site, ou sinon l'URL ou le titre de l'objet
// http://doc.spip.org/@balise_NOM_SITE_dist
function balise_NOM_SITE_dist($p) {
	if (!$p->etoile) {
		$p->code = "supprimer_numero(calculer_url(" .
		champ_sql('url_site',$p) ."," .
		champ_sql('nom_site',$p) . 
		", 'titre', \$connect))";
	} else
		$p->code = champ_sql('nom_site',$p);

	$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_NOTES_dist
function balise_NOTES_dist($p) {
	// Recuperer les notes
	$p->code = 'calculer_notes()';
	#$p->interdire_scripts = true;
	return $p;
}

// http://doc.spip.org/@balise_RECHERCHE_dist
function balise_RECHERCHE_dist($p) {
	$p->code = 'entites_html(_request("recherche"))';
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_COMPTEUR_BOUCLE_dist
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

// http://doc.spip.org/@balise_TOTAL_BOUCLE_dist
function balise_TOTAL_BOUCLE_dist($p) {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];
	if ($b === '' || !isset($p->boucles[$b])) {
		erreur_squelette(
			_T('zbug_champ_hors_boucle',
				array('champ' => "#$b" . 'TOTAL_BOUCLE')
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
// http://doc.spip.org/@balise_POINTS_dist
function balise_POINTS_dist($p) {
	return rindex_pile($p, 'points', 'recherche');
}

// http://doc.spip.org/@balise_POPULARITE_ABSOLUE_dist
function balise_POPULARITE_ABSOLUE_dist($p) {
	$p->code = 'ceil(' .
	champ_sql('popularite', $p) .
	')';
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_POPULARITE_SITE_dist
function balise_POPULARITE_SITE_dist($p) {
	$p->code = 'ceil($GLOBALS["meta"][\'popularite_total\'])';
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_POPULARITE_MAX_dist
function balise_POPULARITE_MAX_dist($p) {
	$p->code = 'ceil($GLOBALS["meta"][\'popularite_max\'])';
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_EXPOSE_dist
function balise_EXPOSE_dist($p) {
	$on = "'on'";
	$off= "''";

	if (($v = interprete_argument_balise(1,$p))!==NULL){
		$on = $v;
		if (($v = interprete_argument_balise(2,$p))!==NULL)
			$off = $v;
	
		// autres filtres
		array_shift($p->param);
	}
	return calculer_balise_expose($p, $on, $off);
}

// #EXPOSER est obsolete. utiliser #EXPOSE ci-dessus
// http://doc.spip.org/@balise_EXPOSER_dist
function balise_EXPOSER_dist($p)
{
	$on = "'on'";
	$off= "''";
	if ($a = ($p->fonctions)) {
		// Gerer la notation [(#EXPOSER|on,off)]
		$onoff = array_shift($a);
		preg_match("#([^,]*)(,(.*))?#", $onoff[0], $regs);
		$on = "" . _q($regs[1]);
		$off = "" . _q($regs[3]) ;
		// autres filtres
		array_shift($p->param);
	}
	return calculer_balise_expose($p, $on, $off);
}

// http://doc.spip.org/@calculer_balise_expose
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
// devient un alias de #MODELE{emb}
//
// On insere simplement un argument {emb} en debut de liste
//
// Attention la syntaxe est derogatoire : il faut donc attraper
// tous les faux-filtres "|autostart=true" et les transformer
// en arguments "{autostart=true}"
//
// On s'arrete au premier filtre ne contenant pas de =, afin de
// pouvoir filtrer le resultat
//
// http://doc.spip.org/@balise_EMBED_DOCUMENT_dist
function balise_EMBED_DOCUMENT_dist($p) {

	if (!is_array($p->param))
		$p->param=array();

	// Produire le premier argument {emb}
	$texte = new Texte;
	$texte->type='texte';
	$texte->texte='emb';
	$param = array(0=>NULL, 1=>array(0=>$texte));
	array_unshift($p->param, $param);

	// Transformer les filtres en arguments
	for ($i=1; $i<count($p->param); $i++) {
		if ($p->param[$i][0]) {
			if (!strstr($p->param[$i][0], '='))
				break;# on a rencontre un vrai filtre, c'est fini
			$texte = new Texte;
			$texte->type='texte';
			$texte->texte=$p->param[$i][0];
			$param = array(0=>$texte);
			$p->param[$i][1] = $param;
			$p->param[$i][0] = NULL;
		}
	}

	// Appeler la balise #MODELE{emb}{arguments}
	if (!function_exists($f = 'balise_modele'))
		$f = 'balise_modele_dist';
	return $f($p);
}

// Debut et fin de surlignage auto des mots de la recherche
// on insere une balise Span avec une classe sans spec:
// c'est transparent s'il n'y a pas de recherche,
// sinon elles seront remplacees par les fontions de inc_surligne

// http://doc.spip.org/@balise_DEBUT_SURLIGNE_dist
function balise_DEBUT_SURLIGNE_dist($p) {
	include_spip('inc/surligne');
	$p->code = "'<!-- " . MARQUEUR_SURLIGNE . " -->'";
	return $p;
}
// http://doc.spip.org/@balise_FIN_SURLIGNE_dist
function balise_FIN_SURLIGNE_dist($p) {
	include_spip('inc/surligne');
	$p->code = "'<!-- " . MARQUEUR_FSURLIGNE . "-->'";
	return $p;
}


// #SPIP_CRON
// a documenter
// insere un <div> avec un lien background-image vers les taches de fond.
// Si cette balise est presente sur la page de sommaire, le site ne devrait
// quasiment jamais se trouver ralenti par des taches de fond un peu lentes
// http://doc.spip.org/@balise_SPIP_CRON_dist
function balise_SPIP_CRON_dist ($p) {
	$p->code = '"<!-- SPIP-CRON --><div style=\"background-image: url(\'' . 
		generer_url_action('cron') .
		'\');\"></div>"';
	$p->interdire_scripts = false;
	return $p;
}


// #INTRODUCTION
// http://www.spip.net/@introduction
// http://doc.spip.org/@balise_INTRODUCTION_dist
function balise_INTRODUCTION_dist ($p) {
	$type = $p->type_requete;
	$_texte = champ_sql('texte', $p);
	if ($type != 'articles') {
		$code = $_texte;
	} else {
		$_chapo = champ_sql('chapo', $p);
		$_descriptif =  champ_sql('descriptif', $p);
		$code = "($_descriptif ? $_descriptif\n\t" .
		": (chapo_redirigetil($_chapo) ? ''\n\t\t" .
		": ($_chapo . \"\n\n\n\" . $_texte)))";
	}
	$f = chercher_filtre('introduction');
	$p->code = "$f($code, '$type', \$connect)";

	#$p->interdire_scripts = true;
	return $p;
}


// #LANG
// affiche la langue de l'objet (ou superieure), et a defaut la langue courante
// (celle du site ou celle qui a ete passee dans l'URL par le visiteur)
// #LANG* n'affiche rien si aucune langue n'est trouvee dans le sql/le contexte
// http://doc.spip.org/@balise_LANG_dist
function balise_LANG_dist ($p) {
	$_lang = champ_sql('lang', $p);
	if (!$p->etoile)
		$p->code = "htmlentities($_lang ? $_lang : \$GLOBALS['spip_lang'])";
	else
		$p->code = "htmlentities($_lang)";
	$p->interdire_scripts = false;
	return $p;
}

// http://doc.spip.org/@balise_CHAPO_dist
function balise_CHAPO_dist ($p) {
	$_chapo = champ_sql('chapo', $p);
	if ((!$p->etoile) OR (strpos($_chapo, '$Pile[$SP') === false))
		$p->code = $_chapo;
	else
		$p->code = "nettoyer_chapo($_chapo)";
	$p->interdire_scripts = false;
	return $p;
}
// #LESAUTEURS
// les auteurs d'un article (ou d'un article syndique)
// http://www.spip.net/fr_article902.html
// http://www.spip.net/fr_article911.html
// http://doc.spip.org/@balise_LESAUTEURS_dist
function balise_LESAUTEURS_dist ($p) {
	// Cherche le champ 'lesauteurs' dans la pile
	$_lesauteurs = champ_sql('lesauteurs', $p); 

	// Si le champ n'existe pas (cas de spip_articles), on applique
	// le modele lesauteurs.html en passant id_article dans le contexte;
	// dans le cas contraire on prend le champ 'lesauteurs' (cas de
	// spip_syndic_articles)
	if ($_lesauteurs
	AND $_lesauteurs != '@$Pile[0][\'lesauteurs\']') {
		$p->code = "safehtml($_lesauteurs)";
		// $p->interdire_scripts = true;
	} else {
		$connect = $p->boucles[$p->id_boucle]->sql_serveur;
		$p->code = "recuperer_fond(
			'modeles/lesauteurs',
			array('id_article' => ".champ_sql('id_article', $p)
			."), false, true, "
			. _q($connect)
			.")";
		$p->interdire_scripts = false; // securite apposee par recuperer_fond()
	}

	return $p;
}


// #RANG
// affiche le "numero de l'article" quand on l'a titre '1. Premier article';
// ceci est transitoire afin de preparer une migration vers un vrai systeme de
// tri des articles dans une rubrique (et plus si affinites)
// http://doc.spip.org/@balise_RANG_dist
function balise_RANG_dist ($p) {
	$_titre = champ_sql('titre', $p);
	$_rang = champ_sql('rang', $p);
	$p->code = "(($_rang)?($_rang):recuperer_numero($_titre))";
	$p->interdire_scripts = false;
	return $p;
}


// #PETITION 
// retourne '' si l'article courant n'a pas de petition 
// le texte de celle-ci sinon (et ' ' si il est vide)
// cf FORMULAIRE_PETITION

// http://doc.spip.org/@balise_PETITION_dist
function balise_PETITION_dist ($p) {
	$nom = $p->id_boucle;
	$p->code = "quete_petitions(" .
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
// http://doc.spip.org/@balise_POPULARITE_dist
function balise_POPULARITE_dist ($p) {
	$_popularite = champ_sql('popularite', $p);
	$p->code = "(ceil(min(100, 100 * $_popularite
	/ max(1 , 0 + \$GLOBALS['meta']['popularite_max']))))";
	$p->interdire_scripts = false;
	return $p;
}

// #PAGINATION
// http://www.spip.net/fr_articleXXXX.html
// http://doc.spip.org/@balise_PAGINATION_dist
function balise_PAGINATION_dist($p, $liste='true') {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];

	// s'il n'y a pas de nom de boucle, on ne peut pas paginer
	if ($b === '') {
		erreur_squelette(
			_T('zbug_champ_hors_boucle',
				array('champ' => '#PAGINATION')
			), $p->id_boucle);
		$p->code = "''";
		return $p;
	}

	// s'il n'y a pas de total_parties, c'est qu'on se trouve
	// dans un boucle recursive ou qu'on a oublie le critere {pagination}
	if (!$p->boucles[$b]->total_parties) {
		erreur_squelette(
			_T('zbug_pagination_sans_critere',
				array('champ' => '#PAGINATION')
			), $p->id_boucle);
		$p->code = "''";
		return $p;
	}
	$__modele = interprete_argument_balise(1,$p);
	$__modele = $__modele?", $__modele":"";

	$p->boucles[$b]->numrows = true;
	$connect = $p->boucles[$b]->sql_serveur;
	$f_pagination = chercher_filtre('pagination');
	$p->code = $f_pagination."(
	(isset(\$Numrows['$b']['grand_total']) ?
		\$Numrows['$b']['grand_total'] : \$Numrows['$b']['total']
	), ".$p->boucles[$b]->modificateur['debut_nom'].",
		\$Pile[0]['debut'.".$p->boucles[$b]->modificateur['debut_nom']."],"
	. $p->boucles[$b]->total_parties
	  . ", $liste$__modele,''," . _q($connect) . ")";

	$p->interdire_scripts = false;
	return $p;
}

// N'afficher que l'ancre de la pagination (au-dessus, par exemple, alors
// qu'on mettra les liens en-dessous de la liste paginee)
// http://doc.spip.org/@balise_ANCRE_PAGINATION_dist
function balise_ANCRE_PAGINATION_dist($p) {
	$p = balise_PAGINATION_dist($p, $liste='false');
	return $p;
}

// equivalent a #TOTAL_BOUCLE sauf pour les boucles paginees, ou elle
// indique le nombre total d'articles repondant aux criteres hors pagination
// http://doc.spip.org/@balise_GRAND_TOTAL_dist
function balise_GRAND_TOTAL_dist($p) {
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];
	if ($b === '' || !isset($p->boucles[$b])) {
		erreur_squelette(
			_T('zbug_champ_hors_boucle',
				array('champ' => "#$b" . 'TOTAL_BOUCLE')
			), $p->id_boucle);
		$p->code = "''";
	} else {
		$p->code = "(isset(\$Numrows['$b']['grand_total'])
			? \$Numrows['$b']['grand_total'] : \$Numrows['$b']['total'])";
		$p->boucles[$b]->numrows = true;
		$p->interdire_scripts = false;
	}
	return $p;
}


// #EXTRA
// [(#EXTRA|extra{isbn})]
// ou [(#EXTRA|isbn)] (ce dernier applique les filtres definis dans mes_options)
// Champs extra
// Non documentes, en voie d'obsolescence, cf. ecrire/inc/extra
// http://doc.spip.org/@balise_EXTRA_dist
function balise_EXTRA_dist ($p) {
	$_extra = champ_sql('extra', $p);
	$p->code = $_extra;

	// Gerer la notation [(#EXTRA|isbn)]
	if ($p->fonctions) {
		list($champ,) = $p->fonctions[0];
		include_spip('inc/extra');
		$type_extra = $p->type_requete;

		// ci-dessus est sans doute un peu buggue : si on invoque #EXTRA
		// depuis un sous-objet sans champ extra d'un objet a champ extra,
		// on aura le type_extra du sous-objet (!)
		if (extra_champ_valide($type_extra, $champ)) {
			array_shift($p->fonctions);
			array_shift($p->param);
			// Appliquer les filtres definis par le webmestre
			$p->code = 'extra('.$p->code.', "'.$champ.'")';

			$filtres = extra_filtres($type_extra, $champ);
			if ($filtres) foreach ($filtres as $f)
				$p->code = "$f($p->code)";
		} else {
			if (!function_exists($champ)) {
				spip_log("erreur champ extra |$champ");
				array_shift($p->fonctions);
				array_shift($p->param);
			}
		}
	}

	#$p->interdire_scripts = true;
	return $p;
}

//
// Parametres de reponse a un forum
//

// http://doc.spip.org/@balise_PARAMETRES_FORUM_dist
function balise_PARAMETRES_FORUM_dist($p) {
	$_id_article = champ_sql('id_article', $p);
	$p->code = '
		// refus des forums ?
		(quete_accepter_forum('.$_id_article.')=="non" OR
		($GLOBALS["meta"]["forums_publics"] == "non"
		AND quete_accepter_forum('.$_id_article.') == ""))
		? "" : // sinon:
		';
	// pas de calculs superflus si le site est monolingue
	$lang = strpos($GLOBALS['meta']['langues_utilisees'], ',');

	switch ($p->type_requete) {
		case 'articles':
			$c = '"id_article=".' . champ_sql('id_article', $p);
			if ($lang) $lang = champ_sql('lang', $p);
			break;
		case 'breves':
			$c = '"id_breve=".' . champ_sql('id_breve', $p);
			if ($lang) $lang = champ_sql('lang', $p);
			break;
		case 'rubriques':
			$c = '"id_rubrique=".' . champ_sql('id_rubrique', $p);
			if ($lang) $lang = champ_sql('lang', $p);
			break;
		case 'syndication':
		case 'syndic':
			// passer par la rubrique pour avoir un champ Lang
			// la table syndic n'en ayant pas
			$c =  '"id_syndic=".' . champ_sql('id_syndic', $p);
			if ($lang) $lang = 'quete_lang(' . champ_sql('id_rubrique', $p) . ',"rubrique")';
			break;
		case 'forums':
		default:
		// ATTENTION mettre 'id_rubrique' avant 'id_syndic':
		// a l'execution  lang_parametres_forum
		// y cherchera l'identifiant  donnant la langue
		// et pour id_syndic c'est id_rubrique car sa table n'en a pas
		  
			$liste_champs = array ("id_article","id_breve","id_rubrique","id_syndic","id_forum");
			$c = '';
			foreach ($liste_champs as $champ) {
				$x = champ_sql( $champ, $p);
				$c .= (($c) ? ".\n" : "") . "((!$x) ? '' : ('&$champ='.$x))";
			}
			$c = "substr($c,1)";

			if ($lang) $lang = -1;
			break;
	}

	if ($lang) $c = "lang_parametres_forum($c,$lang)";

	// Syntaxe [(#PARAMETRES_FORUM{#SELF})] pour fixer le retour du forum
	# note : ce bloc qui sert a recuperer des arguments calcules pourrait
	# porter un nom et faire partie de l'API.
	$retour = interprete_argument_balise(1,$p);
	if ($retour===NULL)
		$retour = "''";

	// Attention un eventuel &retour=xxx dans l'URL est prioritaire
	$c .= '.
	(($lien = (_request("retour") ? _request("retour") : str_replace("&amp;", "&", '.$retour.'))) ? "&retour=".rawurlencode($lien) : "")';

	// Ajouter le code d'invalideur specifique a cette balise
	include_spip('inc/invalideur');
	if (function_exists($i = 'code_invalideur_forums'))
		$p->code .= $i($p, '('.$c.')');

	$p->interdire_scripts = false;
	return $p;
}


// Reference a l'URL de la page courante
// Attention dans un INCLURE() ou une balise dynamique on n'a pas le droit de
// mettre en cache #SELF car il peut correspondre a une autre page (attaque XSS)
// (Dans ce cas faire <INCLURE{self=#SELF}> pour differencier les caches.)
// http://www.spip.net/@self
// http://doc.spip.org/@balise_SELF_dist
function balise_SELF_dist($p) {
	$p->code = 'self()';
	$p->interdire_scripts = false;
	return $p;
}


//
// #CHEMIN{fichier} -> find_in_path(fichier)
//
// http://doc.spip.org/@balise_CHEMIN_dist
function balise_CHEMIN_dist($p) {
	$p->code = interprete_argument_balise(1,$p);
	$p->code = 'find_in_path(' . $p->code .')';

	#$p->interdire_scripts = true;
	return $p;
}

//
// #ENV
// l'"environnement", id est le $contexte (ou $contexte_inclus)
//
// en standard on applique |entites_html, mais si vous utilisez
// [(#ENV*{toto})] il *faut* vous assurer vous-memes de la securite
// anti-javascript (par exemple en filtrant avec |safehtml)
//
// La syntaxe #ENV{toto, rempl} renverra 'rempl' si $toto est vide
//
// Si le tableau est vide on renvoie '' (utile pour #SESSION)
//
// http://doc.spip.org/@balise_ENV_dist
function balise_ENV_dist($p, $src = NULL) {
	// le tableau de base de la balise (cf #META ci-dessous)
	if (!$src) $src = '@$Pile[0]';

	$_nom = interprete_argument_balise(1,$p);
	$_sinon = interprete_argument_balise(2,$p);

	if (!$_nom) {
		// cas de #ENV sans argument : on retourne le serialize() du tableau
		// une belle fonction [(#ENV|affiche_env)] serait pratique
		$p->code = '(is_array($a = ('.$src.')) ? serialize($a) : "")';
	} else {
		// admet deux arguments : nom de variable, valeur par defaut si vide
		$p->code = 'is_array($a = ('.$src.')) ? $a['.$_nom.'] : ""';
		if ($_sinon)
			$p->code = 'sinon('. 
				$p->code.",$_sinon)";
		else
			$p->code = '('.$p->code.')';
	}
	#$p->interdire_scripts = true;

	return $p;
}

//
// #CONFIG
// les reglages du site
//
// Par exemple #CONFIG{gerer_trad} donne 'oui' ou 'non' selon le reglage
// Attention c'est brut de decoffrage de la table spip_meta
//
// La balise fonctionne exactement comme #ENV (ci-dessus)
//
// http://doc.spip.org/@balise_CONFIG_dist
function balise_CONFIG_dist($p) {
	if(function_exists('balise_ENV'))
		return balise_ENV($p, '$GLOBALS["meta"]');
	else
		return balise_ENV_dist($p, '$GLOBALS["meta"]');
}

//
// #SESSION
// Cette balise est un tableau des donnees du visiteur (nom, email etc)
// Si elle est invoquee, elle leve un drapeau dans le fichier cache, qui
// permet a public/cacher d'invalider le cache si le visiteur suivant n'a
// pas la meme session
// http://doc.spip.org/@balise_SESSION_dist
function balise_SESSION_dist($p) {
	$p->descr['session'] = true;

	$f = function_exists('balise_ENV')
		? 'balise_ENV'
		: 'balise_ENV_dist';

	$p = $f($p, '$GLOBALS["auteur_session"]');
	return $p;
}


//
// #EVAL{...}
// evalue un code php ; a utiliser avec precaution :-)
//
// rq: #EVAL{code} produit eval('return code;')
// mais si le code est une expression sans balise, on se dispense
// de passer par une construction si compliquee, et le code est
// passe tel quel (entre parentheses, et protege par interdire_scripts)
// Exemples : #EVAL**{6+9} #EVAL**{_DIR_IMG_PACK} #EVAL{'date("Y-m-d")'}
// #EVAL{'str_replace("r","z", "roger")'}  (attention les "'" sont interdits)
// http://doc.spip.org/@balise_EVAL_dist
function balise_EVAL_dist($p) {
	$php = interprete_argument_balise(1,$p);
	if ($php) {
		# optimisation sur les #EVAL{une expression sans #BALISE}
		# attention au commentaire "// x signes" qui precede
		if (preg_match(",^([[:space:]]*//[^\n]*\n)'([^']+)'$,ms",
		$php,$r))
			$p->code = /* $r[1]. */'('.$r[2].')';
		else
			$p->code = "eval('return '.$php.';')";
	} else
		$p->code = '';

	#$p->interdire_scripts = true;

	return $p;
}

// #CHAMP_SQL{x} renvoie la valeur du champ sql 'x'
// permet de recuperer par exemple un champ notes dans une table sql externe
// (impossible via #NOTES qui est une balise calculee)
// ne permet pas de passer une expression pour x qui ne peut etre qu'un texte statique !
// http://doc.spip.org/@balise_CHAMP_SQL_dist
function balise_CHAMP_SQL_dist($p){
	$p->code = '';
	if (isset($p->param[0][1][0]) AND $champ = ($p->param[0][1][0]->texte))
		$p->code = champ_sql($champ, $p);

	#$p->interdire_scripts = true;
	return $p;
}

// #VAL{x} renvoie 'x' (permet d'appliquer un filtre a une chaine)
// Attention #VAL{1,2} renvoie '1', indiquer #VAL{'1,2'}
// http://doc.spip.org/@balise_VAL_dist
function balise_VAL_dist($p){
	$p->code = interprete_argument_balise(1,$p);
	if (!strlen($p->code))
		$p->code = "''";
	return $p;
}
// #NOOP est un alias pour regler #948, ne pas documenter
// http://doc.spip.org/@balise_NOOP_dist
function balise_NOOP_dist($p) { return balise_VAL_dist($p); }

//
// #REM
// pour les remarques : renvoie toujours ''
//
// http://doc.spip.org/@balise_REM_dist
function balise_REM_dist($p) {
	$p->code="''";
	$p->interdire_scripts = false;
	return $p;
}


//
// #HTTP_HEADER
// pour les entetes de retour http
// Ne fonctionne pas sur les INCLURE !
// #HTTP_HEADER{Content-Type: text/css}
//
// http://doc.spip.org/@balise_HTTP_HEADER_dist
function balise_HTTP_HEADER_dist($p) {

	$header = interprete_argument_balise(1,$p);
	$p->code = "'<'.'?php header(\"' . "
		. $header
		. " . '\"); ?'.'>'";
	$p->interdire_scripts = false;
	return $p;
}

//
// #CACHE
// definit la duree de vie ($delais) du squelette
// #CACHE{24*3600}
// parametre(s) supplementaire(s) :
// #CACHE{24*3600, cache-client} autorise gestion du IF_MODIFIED_SINCE
// http://doc.spip.org/@balise_CACHE_dist
function balise_CACHE_dist($p) {
	$duree = valeur_numerique($p->param[0][1][0]->texte);

	// noter la duree du cache dans un entete proprietaire
	$p->code .= '\'<'.'?php header("X-Spip-Cache: '
		. $duree
		. '"); ?'.'>\'';

	// Remplir le header Cache-Control
	// cas #CACHE{0}
	if ($duree == 0)
		$p->code .= '.\'<'
		.'?php header("Cache-Control: no-store, no-cache, must-revalidate"); ?'
		.'><'
		.'?php header("Pragma: no-cache"); ?'
		.'>\'';

	// cas #CACHE{360, cache-client}
	if (isset($p->param[0][2])) {
		$second = ($p->param[0][2][0]->texte);
		if ($second == 'cache-client'
		AND $duree > 0)
			$p->code .= '.\'<'.'?php header("Cache-Control: max-age='
				. $duree
				. '"); ?'.'>\'';
	}

	$p->interdire_scripts = false;
	return $p;
}

//
// #INSERT_HEAD
// pour permettre aux plugins d'inserer des styles, js ou autre
// dans l'entete sans modification du squelette
//
// http://doc.spip.org/@balise_INSERT_HEAD_dist
function balise_INSERT_HEAD_dist($p) {
	$p->code = "pipeline('insert_head','<!-- insert_head -->')";
	$p->interdire_scripts = false;
	return $p;
}

//
// #INCLURE statique
// l'inclusion est realisee au calcul du squelette, pas au service
// ainsi le produit du squelette peut etre utilise en entree de filtres a suivre
// on peut faire un #INCLURE{fichier} sans squelette
// (Incompatible avec les balises dynamiques)
// http://doc.spip.org/@balise_INCLUDE_dist
function balise_INCLUDE_dist($p) {
	if(function_exists('balise_INCLURE'))
		return balise_INCLURE($p);
	else
		return balise_INCLURE_dist($p);
}
// http://doc.spip.org/@balise_INCLURE_dist
function balise_INCLURE_dist($p) {
	$champ = phraser_arguments_inclure($p, true);
	$_contexte = argumenter_inclure($champ, $p->descr, $p->boucles, $p->id_boucle, false);

	if (isset($_contexte['fond'])) {
		// Critere d'inclusion {env} (et {self} pour compatibilite ascendante)
		if (isset($_contexte['env'])
		|| isset($_contexte['self'])
		) {
			$flag_env = true;
			unset($_contexte['env']);
		} else $flag_env = false;
		$l = 'array(' . join(",\n\t", $_contexte) .')';
		if ($flag_env) {
			$l = "array_merge(\$Pile[0],$l)";
		}
		$connect = $p->boucles[$p->id_boucle]->sql_serveur;
		$p->code = "recuperer_fond('',".$l.",true, false, " . _q($connect) .")";
	} else {
		$n = interprete_argument_balise(1,$p);
		$p->code = '(($c = find_in_path(' . $n . ')) ? spip_file_get_contents($c) : "")';
	}

	$p->interdire_scripts = false; // la securite est assuree par recuperer_fond
	return $p;
}

// Inclure un modele : #MODELE{modele, params}
// http://doc.spip.org/@balise_MODELE_dist
function balise_MODELE_dist($p) {
	$contexte = array();

	// recupere le premier argument, qui est obligatoirement le nom du modele
	if (!is_array($p->param))
		die("erreur de compilation #MODELE{nom du modele}");

	// Transforme l'ecriture du deuxieme param {truc=chose,machin=chouette} en
	// {truc=chose}{machin=chouette}... histoire de simplifier l'ecriture pour
	// le webmestre : #MODELE{emb}{autostart=true,truc=1,chose=chouette}
	if ($p->param[0]) {
		while (count($p->param[0])>2){
			$p->param[]=array(0=>NULL,1=>array_pop($p->param[0]));
		}
	}
	$modele = array_shift($p->param);
	$nom = strtolower($modele[1][0]->texte);
	if (!$nom)
		die("erreur de compilation #MODELE{nom du modele}");

	$champ = phraser_arguments_inclure($p, true); 

	// a priori true
	// si false, le compilo va bloquer sur des syntaxes avec un filtre sans argument qui suit la balise
	// si true, les arguments simples (sans truc=chose) vont degager
	$code_contexte = argumenter_inclure($champ, $p->descr, $p->boucles, $p->id_boucle, false);

	// Si le champ existe dans la pile, on le met dans le contexte
	// (a priori c'est du code mort ; il servait pour #LESAUTEURS dans
	// le cas spip_syndic_articles)
	#$code_contexte[] = "'$nom='.".champ_sql($nom, $p);

	// Reserver la cle primaire de la boucle courante si elle existe
	if ($idb = $p->id_boucle) {
		if ($primary = $p->boucles[$idb]->primary) {
			$id = champ_sql($primary, $p);
			$code_contexte[] = "'$primary='.".$id;
		}
	}

	$connect = $p->boucles[$p->id_boucle]->sql_serveur;
	$p->code = "( ((\$recurs=(isset(\$Pile[0]['recurs'])?\$Pile[0]['recurs']:0))<5)?
	recuperer_fond('modeles/".$nom."',
		creer_contexte_de_modele(array(".join(',', $code_contexte).",'recurs='.(++\$recurs), \$GLOBALS['spip_lang'])),false,true," . _q($connect) . "):'')";
	$p->interdire_scripts = false; // securite assuree par le squelette

	return $p;
}

//
// #SET
// Affecte une variable locale au squelette
// #SET{nom,valeur}
// la balise renvoie la valeur
// http://doc.spip.org/@balise_SET_dist
function balise_SET_dist($p){
	$_nom = interprete_argument_balise(1,$p);
	$_valeur = interprete_argument_balise(2,$p);

	if ($_nom AND $_valeur)
		$p->code = "vide(\$Pile['vars'][$_nom] = $_valeur)";
	else
		$p->code = "''";

	$p->interdire_scripts = false; // la balise ne renvoie rien
	return $p;
}

//
// #GET
// Recupere une variable locale au squelette
// #GET{nom,defaut} renvoie defaut si la variable nom n'a pas ete affectee
//
// http://doc.spip.org/@balise_GET_dist
function balise_GET_dist($p) {
	$p->interdire_scripts = false; // le contenu vient de #SET, donc il est de confiance
	if (function_exists('balise_ENV'))
		return balise_ENV($p, '$Pile["vars"]');
	else
		return balise_ENV_dist($p, '$Pile["vars"]');
}

//
// #PIPELINE
// pour permettre aux plugins d'inserer des sorties de pipeline dans un squelette
// #PIPELINE{insert_body}
// #PIPELINE{insert_body,flux}
//
// http://doc.spip.org/@balise_PIPELINE_dist
function balise_PIPELINE_dist($p) {
	$_pipe = interprete_argument_balise(1,$p);
	$_flux = interprete_argument_balise(2,$p);
	$_flux = $_flux?$_flux:"''";
	$p->code = "pipeline( $_pipe , $_flux )";
	$p->interdire_scripts = false;
	return $p;
}

//
// #EDIT
// une balise qui ne fait rien, pour surcharge par le plugin widgets
//
// http://doc.spip.org/@balise_EDIT_dist
function balise_EDIT_dist($p) {
	$p->code = "''";
	$p->interdire_scripts = false;
	return $p;
}


//
// #TOTAL_UNIQUE
// pour recuperer le nombre d'elements affiches par l'intermediaire du filtre
// |unique
// usage:
// #TOTAL_UNIQUE afiche le nombre de #BALISE|unique
// #TOTAL_UNIQUE{famille} afiche le nombre de #BALISE|unique{famille}
//
// http://doc.spip.org/@balise_TOTAL_UNIQUE_dist
function balise_TOTAL_UNIQUE_dist($p) {
	$_famille = interprete_argument_balise(1,$p);
	$_famille = $_famille ? $_famille : "''";
	$p->code = "unique('', $_famille, true)";
	return $p;
}

//
// #ARRAY
// pour creer un array php a partir d'arguments calcules
// #ARRAY{key1,val1,key2,val2 ...} returne array(key1=>val1,...)
//
// http://doc.spip.org/@balise_ARRAY_dist
function balise_ARRAY_dist($p) {
	$_code = array();
	$n=1;
	do {
		$_key = interprete_argument_balise($n++,$p);
		$_val = interprete_argument_balise($n++,$p);
		if ($_key AND $_val) $_code[] = "$_key => $_val";
	} while ($_key && $_val);
	$p->code = 'array(' . join(', ',$_code).')';
	$p->interdire_scripts = false;
	return $p;
}

//#FOREACH
//
// http://doc.spip.org/@balise_FOREACH_dist
function balise_FOREACH_dist($p) {
	$_tableau = interprete_argument_balise(1,$p);
	$_tableau = str_replace("'", "", strtoupper($_tableau));
	$_tableau = sinon($_tableau, 'ENV');
	$f = 'balise_'.$_tableau;
	$balise = function_exists($f) ? $f : (function_exists($g = $f.'_dist') ? $g : '');

	if($balise) {
		$_modele = interprete_argument_balise(2,$p);
		$_modele = str_replace("'", "", strtolower($_modele));
		$__modele = 'foreach_'.strtolower($_tableau);
		$_modele = (!$_modele AND find_in_path('modeles/'.$__modele.'.html')) ?
			$__modele : 
			($_modele ? $_modele : 'foreach');

		$p->param = @array_shift(@array_shift($p->param));
		$p = $balise($p);
		$filtre = chercher_filtre('foreach');
		$p->code = $filtre . "(unserialize(" . $p->code . "), '" . $_modele . "')";
	}
	//On a pas trouve la balise correspondant au tableau a traiter
	else {
		erreur_squelette(
			_L(/*zbug*/'erreur #FOREACH: la balise #'.$_tableau.' n\'existe pas'),
			$p->id_boucle
		);
		$p->code = "''";
	}
	return $p;
}

// Appelle la fonction autoriser et renvoie ' ' si OK, '' si niet
// A noter : la priorite des operateurs exige && plutot que AND
// Par nature cette balise doit etre utilisee dans #CACHE{0} ou dans
// un contexte lie au profil du visiteur
// http://doc.spip.org/@balise_AUTORISER_dist
function balise_AUTORISER_dist($p) {
	$_code = array();
	$n=1;
	while ($_v = interprete_argument_balise($n++,$p))
		$_code[] = $_v;
	$p->code = '(include_spip("inc/autoriser")&&autoriser(' . join(', ',$_code).')?" ":"")';
	$p->interdire_scripts = false;
	return $p;
}

// Appelle la fonction info_plugin
// Afficher des informations sur les plugins dans le site public
// http://doc.spip.org/@balise_PLUGIN_dist
function balise_PLUGIN_dist($p) {
	$plugin = interprete_argument_balise(1,$p);
	$plugin = isset($plugin) ? str_replace('\'', '"', $plugin) : '""';
	$type_info = interprete_argument_balise(2,$p);
	$type_info = isset($type_info) ? str_replace('\'', '"', $type_info) : '"est_actif"';

	$f = chercher_filtre('info_plugin');
	$p->code = $f.'('.$plugin.', '.$type_info.')';
	return $p;
}

?>
