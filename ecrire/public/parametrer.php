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

if (!defined("_ECRIRE_INC_VERSION")) return;

//
 // Ce fichier calcule une page en executant un squelette.
//

include_spip('base/abstract_sql');
include_spip('inc/lang');

// NB: Ce fichier peut initialiser $dossier_squelettes (old-style)
// donc il faut l'inclure "en globals"
if ($f = find_in_path('mes_fonctions.php')
OR $f = find_in_path('mes_fonctions.php3')) {
	global $dossier_squelettes;
	@include ($f); 
}
if (@is_readable(_DIR_TMP."charger_plugins_fonctions.php")){
	// chargement optimise precompile
	include_once(_DIR_TMP."charger_plugins_fonctions.php");
}

//
// Contexte : lors du calcul d'une page spip etablit le contexte a partir
// des variables $_GET et $_POST, et leur ajoute la date
// Note : pour hacker le contexte depuis le fichier d'appel (page.php),
// il est recommande de modifier $_GET['toto'] (meme si la page est
// appelee avec la methode POST).
//
// http://doc.spip.org/@calculer_contexte
function calculer_contexte() {
	$contexte = array();
	foreach($_GET as $var => $val) {
		if (strpos($var, 'var_') !== 0)
			$contexte[$var] = $val;
	}
	foreach($_POST as $var => $val) {
		if (strpos($var, 'var_') !== 0)
			$contexte[$var] = $val;
	}

	if (($a = _request('date')) !== null)
		$contexte['date'] = $contexte['date_redac'] = normaliser_date($a);
	else
		$contexte['date'] = $contexte['date_redac'] = date("Y-m-d H:i:s");

	return $contexte;
}

// http://doc.spip.org/@analyse_resultat_skel
function analyse_resultat_skel($nom, $cache, $corps) {
	$headers = array();

	// Recupere les < ?php header('Xx: y'); ? > pour $page['headers']
	// note: on essaie d'attrapper aussi certains de ces entetes codes
	// "a la main" dans les squelettes, mais evidemment sans exhaustivite
	if (preg_match_all(
	'/(<[?]php\s+)@?header\s*\(\s*.([^:]*):\s*([^)]*)[^)]\s*\)\s*[;]?\s*[?]>/ims',
	$corps, $regs, PREG_SET_ORDER))
	foreach ($regs as $r) {
		$corps = str_replace($r[0], '', $corps);
		# $j = Content-Type, et pas content-TYPE.
		$j = join('-', array_map('ucwords', explode('-', strtolower($r[2]))));
		$headers[$j] = $r[3];
	}

	return array('texte' => $corps,
		'squelette' => $nom,
		'process_ins' => ((strpos($corps,'<'.'?')=== false)?'html':'php'),
		'invalideurs' => $cache,
		'entetes' => $headers,
		'duree' => isset($headers['X-Spip-Cache']) ? intval($headers['X-Spip-Cache']) : 0 
	);
}

// Calcul de la rubrique associee a la requete
// (selection de squelette specifique par id_rubrique & lang)

// http://doc.spip.org/@quete_rubrique_fond
function quete_rubrique_fond($contexte) {

	if (isset($contexte['id_rubrique'])) {
		$id = intval($contexte['id_rubrique']);
		$row = sql_fetsel(array('lang'),
					    array('spip_rubriques'),
					    array("id_rubrique=$id"));
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array ($id, $lang);
	}

	if (isset($contexte['id_breve'])) {
		$id = intval($contexte['id_breve']);
		$row = sql_fetsel(array('id_rubrique', 'lang'),
			array('spip_breves'), 
			array("id_breve=$id"));
		$id_rubrique_fond = $row['id_rubrique'];
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array($id_rubrique_fond, $lang);
	}

	if (isset($contexte['id_syndic'])) {
		$id = intval($contexte['id_syndic']);
		$row = sql_fetsel(array('id_rubrique'),
			array('spip_syndic'),
			array("id_syndic=$id"));
		$id_rubrique_fond = $row['id_rubrique'];
		$row = sql_fetsel(array('lang'),
			array('spip_rubriques'),
			array("id_rubrique='$id_rubrique_fond'"));
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array($id_rubrique_fond, $lang);
	}

	if (isset($contexte['id_article'])) {
		$id = intval($contexte['id_article']);
		$row = sql_fetsel(array('id_rubrique', 'lang'),
			array('spip_articles'),
			array("id_article=$id"));
		$id_rubrique_fond = $row['id_rubrique'];
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array($id_rubrique_fond, $lang);
	}
}

# retourne le chapeau d'un article, et seulement s'il est publie

// http://doc.spip.org/@quete_chapo
function quete_chapo($id_article) {
	$chapo= sql_fetsel(array('chapo'),
		array('spip_articles'),
		array("id_article=".intval($id_article),
		"statut='publie'"));
	return $chapo['chapo'];
}

# retourne le parent d'une rubrique

// http://doc.spip.org/@quete_parent
function quete_parent($id_rubrique) {
	if (!$id_rubrique = intval($id_rubrique))
		return 0;

	$id_parent = sql_fetsel(array('id_parent'),
		array('spip_rubriques'), 
		array("id_rubrique=" . $id_rubrique));

	if ($id_parent['id_parent']!=$id_rubrique)
		return intval($id_parent['id_parent']);
	else
		spip_log("erreur: la rubrique $id_rubrique est son propre parent");
}

# retourne la profondeur d'une rubrique

// http://doc.spip.org/@quete_profondeur
function quete_profondeur($id) {
	$n = 0;
	while ($id) {
		$n++;
		$id = quete_parent($id);
	}
	return $n;
}

# retourne la rubrique d'un article

// http://doc.spip.org/@quete_rubrique
function quete_rubrique($id_article) {
	$id_rubrique = sql_fetsel(array('id_rubrique'),
			array('spip_articles'),
			array("id_article=" . intval($id_article)));
	return $id_rubrique['id_rubrique'];
}

# retourne le fichier d'un document

// http://doc.spip.org/@quete_fichier
function quete_fichier($id_document, $serveur) {
	$r = sql_fetsel(array('fichier'),
			array('spip_documents'),
			array("id_document=" . intval($id_document)),
			'',array(),'','','', '', '', $serveur);
	return $r['fichier'];
}

// http://doc.spip.org/@quete_petitions
function quete_petitions($id_article, $table, $id_boucle, $serveur, &$cache) {
	$retour = sql_fetsel(
		array('texte'),
		array('spip_petitions'),
		array("id_article=".intval($id_article)),
		'',array(),'','','', 
		$table, $id_boucle, $serveur);

	if (!$retour) return '';
	# cette page est invalidee par toute petition
	$cache['varia']['pet'.$id_article] = 1;
	# ne pas retourner '' car le texte sert aussi de presence
	return ($retour['texte'] ? $retour['texte'] : ' ');
}

# retourne le champ 'accepter_forum' d'un article
// http://doc.spip.org/@quete_accepter_forum
function quete_accepter_forum($id_article) {
	static $cache = array();

	if (!$id_article) return;

	if (!isset($cache[$id_article])) {
		$row = sql_fetsel(array('accepter_forum'),
			array('spip_articles'),
			array("id_article=".intval($id_article)));
		$cache[$id_article] = $row['accepter_forum'];
	}

	return $cache[$id_article];
}

// recuperer une meta sur un site distant (en local il y a plus simple)
// http://doc.spip.org/@quete_meta
function quete_meta($nom, $serveur) {
	$r = sql_fetsel("valeur", "spip_meta", "nom=" . _q($nom), '','','','','','','',$serveur);
	return $r['valeur'];
}

// Produit les appels aux fonctions generer_url parametrees par $type_urls
// demandees par les balise #URL_xxx
// Si ces balises sont rencontrees dans une boucle de base distante
// on produit le generer_url std faute de connaitre le $type_urls distant
// et sous reserve que cette base distante est geree par SPIP.
// Autrement cette balise est vue comme un champ normal dans cette base.

// http://doc.spip.org/@generer_generer_url
function generer_generer_url($type, $p)
{
	$_id = interprete_argument_balise(1,$p);

	if (!$_id) $_id = champ_sql('id_' . $type, $p);

	if ($s = $p->id_boucle) $s = $p->boucles[$s]->sql_serveur;

	if (!$s)
		return "generer_url_$type($_id)";
	elseif (!$GLOBALS['connexions'][$s]['spip_connect_version']) {
		erreur_squelette("#URL_" . strtoupper($type). ' ' . _T('zbug_distant_interdit'));
		return "";
	} else {
		$u = "quete_meta('adresse_site', '$s')";
		if ($type != 'document')
			return "$u . '?page=$type&amp;id_$type=' . " . $_id;
		else {
			$f = "$_id . '&amp;file=' . quete_fichier($_id,'$s')";
			return "$u . '?action=acceder_document&amp;arg=' .$f";
		}
	}
}


# Determine les parametres d'URL (hors reecriture) et consorts
# En deduit un contexte disant si la page est une redirection ou 
# exige un squelette deductible de $fond et du contexte de langue.
# Applique alors le squelette sur le contexte et le nom du cache.
# Retourne un tableau ainsi construit
# 'texte' => la page calculee
# 'process_ins' => 'html' ou 'php' si presence d'un '< ?php'
# 'invalideurs' => les invalideurs de ce cache
# 'entetes' => headers http
# 'duree' => duree de vie du cache
# 'signal' => contexte (les id_* globales)

# En cas d'erreur process_ins est absent et texte est un tableau de 2 chaines

// http://doc.spip.org/@public_parametrer_dist
function public_parametrer_dist($fond, $local='', $cache='', $connect='')  {
	// verifier que la fonction assembler est bien chargee (cf. #608)
	$assembler = charger_fonction('assembler', 'public');
	// et toujours charger les fonctions de generation d'URL.
	$renommer_urls= charger_fonction($GLOBALS['type_urls'], 'urls', true);
	// distinguer le premier appel des appels par inclusion
	if (!is_array($local)) {
		include_spip('inc/filtres'); // pour normaliser_date

		// ATTENTION, gestion des URLs transformee par le htaccess
		// en appelant la fonction $renommee_urls
		// 1. $contexte est global car cette fonction le modifie.
		// 2. $fond est passe par reference, pour la meme raison
		// Bref,  les URL dites propres ont une implementation sale.
		// Interdit de nettoyer, faut assumer l'histoire.
		global $contexte;
		$contexte = calculer_contexte();
		if (!$renommer_urls) {
			// compatibilite < 1.9.3
			charger_generer_url();
			if (function_exists('recuperer_parametres_url'))
				$renommer_urls = 'recuperer_parametres_url';
		}
		if ($renommer_urls) {
			$renommer_urls($fond, nettoyer_uri());
			// remettre les globales (bouton "Modifier cet article" etc)
			foreach ($contexte as $var=>$val) {
				if (substr($var,0,3) == 'id_') $GLOBALS[$var] = $val;
			}
		}
		$local = $contexte;

		// si le champ chapo commence par '=' c'est une redirection.
		// avec un eventuel raccourci Spip
		// si le raccourci a un titre il sera pris comme corps du 302
		if ($fond == 'article'
		AND $id_article = intval($local['id_article'])) {
			$m = quete_chapo($id_article);
			if ($m[0]=='=') {
				include_spip('inc/texte');
				// les navigateurs pataugent si l'URL est vide
				if ($m = chapo_redirige(substr($m,1)))
					if ($url = calculer_url($m[3]))
					return array('texte' => "<"
				. "?php header('Location: "
				. texte_script(str_replace('&amp;', '&', $url))
				. "'); echo '"
				.  addslashes($m[1])
				. "'?" . ">",
					'process_ins' => 'php');
			}
		}
	}

	// Choisir entre $fond-dist.html, $fond=7.html, etc?
	$id_rubrique_fond = 0;
	// Chercher le fond qui va servir de squelette
	if ($r = quete_rubrique_fond($local))
		list($id_rubrique_fond, $lang) = $r;

	// Si inc-urls ou un appel dynamique veut fixer la langue, la recuperer
	if (isset($local['lang']))
		$lang = $local['lang'];

	if (!isset($lang))
		$lang = $GLOBALS['meta']['langue_site'];

	$select = (!$GLOBALS['forcer_lang'] AND $lang <> $GLOBALS['spip_lang']);
	if ($select) $select = lang_select($lang);

	$styliser = charger_fonction('styliser', 'public');
	list($skel,$mime_type, $gram, $sourcefile) =
		$styliser($fond, $id_rubrique_fond, $GLOBALS['spip_lang']);

	// Charger le squelette en specifiant les langages cibles et source
	// au cas il faudrait le compiler (source posterieure au resultat)
	// et appliquer sa fonction principale sur le contexte.
	// Passer le nom du cache pour produire sa destruction automatique

	$composer = charger_fonction('composer', 'public');

	// Le debugueur veut afficher le contexte
	if ($GLOBALS['var_mode'] == 'debug')
		$GLOBALS['debug_objets']['contexte'][$sourcefile] = $local;

	if ($fonc = $composer($skel, $mime_type, $gram, $sourcefile, $connect)){
		spip_timer($a = 'calcul page '.rand(0,1000));
		$notes = calculer_notes(); // conserver les notes...

		$page = $fonc(array('cache' => $cache), array($local));

		// ... et les retablir
		if ($n = calculer_notes()) spip_log("notes ignorees par $fonc: $n");
		$GLOBALS['les_notes'] = $notes;

		// spip_log: un joli contexte
		$info = array();
		foreach($local as $var => $val)
			if($val)
				$info[] = "$var='$val'";
		spip_log("calcul ("
			.($profile = spip_timer($a))
			.") [$skel] "
			. join(', ',$info)
			.' ('.strlen($page['texte']).' octets)'
		);
		if ($GLOBALS['var_mode'] == 'debug')
			$GLOBALS['debug_objets']['profile'][$sourcefile] = $profile;

		// Si #CACHE{} n'etait pas la, le mettre a $delais
		if (!isset($page['entetes']['X-Spip-Cache']))
			$page['entetes']['X-Spip-Cache'] = $GLOBALS['delais'];

	} else
		$page = array();

	if ($GLOBALS['var_mode'] == 'debug') {
		include_spip('public/debug');
		debug_dumpfile (strlen($page['texte'])?$page['texte']:" ", $fonc, 'resultat');
	}
	$page['contexte'] = $local;

	if ($select) lang_select();

	return $page;
}

?>
