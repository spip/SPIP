<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/lang');
include_spip('public/quete'); // pour quete_chapo et ses dependances

// NB: mes_fonctions peut initialiser $dossier_squelettes (old-style)
// donc il faut l'inclure "en globals"
if ($f = find_in_path('mes_fonctions.php')) {
	global $dossier_squelettes;
	include ($f);
}

if (@is_readable(_DIR_TMP."charger_plugins_fonctions.php")){
	// chargement optimise precompile
	include_once(_DIR_TMP."charger_plugins_fonctions.php");
}

# Determine le squelette associe a une requete 
# et l'applique sur le contexte, le nom du cache et le serveur
# en ayant evacue au prealable le cas de la redirection
# Retourne un tableau ainsi construit
# 'texte' => la page calculee
# 'process_ins' => 'html' ou 'php' si presence d'un '< ?php'
# 'invalideurs' => les invalideurs de ce cache
# 'entetes' => headers http
# 'duree' => duree de vie du cache
# 'signal' => contexte (les id_* globales)

# En cas d'erreur process_ins est absent et texte est un tableau de 2 chaines

// http://doc.spip.org/@public_parametrer_dist
function public_parametrer_dist($fond, $contexte='', $cache='', $connect='')  {
	$page = tester_redirection($fond, $contexte, $connect);
	if ($page) return $page;

	// Choisir entre $fond-dist.html, $fond=7.html, etc?
	$id_rubrique_fond = 0;
	// Chercher le fond qui va servir de squelette
	if ($r = quete_rubrique_fond($contexte))
		list($id_rubrique_fond, $lang) = $r;

	if (isset($contexte['lang']))
		$lang = $contexte['lang'];
	elseif (!isset($lang))
		$lang = $GLOBALS['meta']['langue_site'];

	$select = ((!isset($GLOBALS['forcer_lang']) OR !$GLOBALS['forcer_lang']) AND $lang <> $GLOBALS['spip_lang']);
	if ($select) $select = lang_select($lang);

	$styliser = charger_fonction('styliser', 'public');
	list($skel,$mime_type, $gram, $sourcefile) =
		$styliser($fond, $id_rubrique_fond, $GLOBALS['spip_lang'], $connect);

	$debug = (isset($GLOBALS['var_mode']) && ($GLOBALS['var_mode'] == 'debug'));
	// sauver le nom de l'eventuel squelette en cours d'execution
	// (recursion possible a cause des modeles)
	if ($debug) {
		$courant = @$GLOBALS['debug_objets']['courant'];
		$GLOBALS['debug_objets']['contexte'][$sourcefile] = $contexte;
	}

	// charger le squelette en specifiant les langages cibles et source
	// au cas il faudrait le compiler (source posterieure au resultat)

	$composer = charger_fonction('composer', 'public');
	$code = $composer($skel, $mime_type, $gram, $sourcefile, $connect);

	if (!$code) // squelette inconnu ou faux
		$page = array();
	else {
	// Appeler la fonction principale du squelette 
		list($fonc) = $code;
		spip_timer($a = 'calcul page '.rand(0,1000));
		$notes = calculer_notes(); // conserver les notes...
	// Passer le nom du cache pour produire sa destruction automatique
		$page = $fonc(array('cache' => $cache), array($contexte));

		// ... et les retablir
		if ($n = calculer_notes()) spip_log("notes ignorees par $fonc: $n");
		$GLOBALS['les_notes'] = $notes;

		// spip_log: un joli contexte
		$infos = array();
		foreach (array_filter($contexte) as $var => $val) {
			if (is_array($val)) $val = "[".join($val)."]";
			if (strlen("$val") > 20)
				$val = substr("$val", 0,17).'..';
			if (strstr($val,' '))
				$val = "'$val'";
			$infos[] = $var.'='.$val;
		}
		$profile = spip_timer($a);
		spip_log("calcul ($profile) [$skel] "
			. join(', ', $infos)
			.' ('.strlen($page['texte']).' octets)');

		if ($debug) {
			include_spip('public/debug');
			debug_dumpfile (strlen($page['texte'])?$page['texte']:" ", $fonc, 'resultat');
			$GLOBALS['debug_objets']['courant'] = $courant;
			$GLOBALS['debug_objets']['profile'][$sourcefile] = $profile;
		}
		// Si #CACHE{} n'etait pas la, le mettre a $delais
		if (!isset($page['entetes']['X-Spip-Cache']))
			$page['entetes']['X-Spip-Cache'] = isset($GLOBALS['delais'])?$GLOBALS['delais']:36000;
	}

	$page['contexte'] = $contexte;

	if ($select) lang_select();

	// Si un modele contenait #SESSION, on note l'info dans $page
	if (isset($GLOBALS['cache_utilise_session'])) {
		$page['invalideurs']['session'] = $GLOBALS['cache_utilise_session'];
		unset($GLOBALS['cache_utilise_session']);
	}

	return $page;
}

// Calcul de la rubrique associee a la requete
// (selection de squelette specifique par id_rubrique & lang)

// http://doc.spip.org/@quete_rubrique_fond
function quete_rubrique_fond($contexte) {

	if (isset($contexte['id_rubrique'])
	AND $id = intval($contexte['id_rubrique'])
	AND $row = sql_fetsel('id_parent, lang', 'spip_rubriques',"id_rubrique=$id")) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array ($id, $lang);
	}

	if (isset($contexte['id_breve'])
	AND $id = intval($contexte['id_breve'])
	AND $row = sql_fetsel('id_rubrique, lang', 'spip_breves', "id_breve=$id")
	AND $id_rubrique_fond = $row['id_rubrique']) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array($id_rubrique_fond, $lang);
	}

	if (isset($contexte['id_syndic'])
	AND $id = intval($contexte['id_syndic'])
	AND $row = sql_fetsel('id_rubrique', 'spip_syndic', "id_syndic=$id")
	AND $id_rubrique_fond = $row['id_rubrique']
	AND $row = sql_fetsel('id_parent, lang', 'spip_rubriques', "id_rubrique=$id_rubrique_fond")) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array($id_rubrique_fond, $lang);
	}

	if (isset($contexte['id_article'])
	AND $id = intval($contexte['id_article'])
	AND $row = sql_fetsel('id_rubrique, lang', 'spip_articles', "id_article=$id")
	AND $id_rubrique_fond = $row['id_rubrique']) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array($id_rubrique_fond, $lang);
	}
}

// si le champ chapo commence par '=' c'est une redirection.
// avec un eventuel raccourci Spip
// si le raccourci a un titre il sera pris comme corps du 302

// http://doc.spip.org/@tester_redirection
function tester_redirection($fond, $contexte, $connect)
{
	if ($fond == 'article'
	AND $id_article = intval($contexte['id_article'])) {
		$m = quete_chapo($id_article, $connect);
		if ($m[0]=='=') {
			include_spip('inc/texte');
			// les navigateurs pataugent si l'URL est vide
			if ($url = chapo_redirige(substr($m,1), true))
				return array('texte' => "<"
				. "?php header('Location: "
				. texte_script(str_replace('&amp;', '&', $url))
				. "'); echo '"
				.  addslashes($m[1])
				. "'?" . ">",
					'process_ins' => 'php');
		}
	}
	return false;
}

?>
