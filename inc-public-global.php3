<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_PUBLIC_GLOBAL")) return;
define("_INC_PUBLIC_GLOBAL", "1");

//
// Aller chercher la page dans le cache ou pas
//
function obtenir_page ($contexte, $chemin_cache, $delais, &$use_cache, $fond, $inclusion=false) {
	global $lastmodified;

	if (!$use_cache) {
		include_local('inc-calcul.php3');

		// page globale ? calculer le contexte
		if (!$contexte)
			$contexte = calculer_contexte();

		spip_timer('calculer_page');
		$page = calculer_page($chemin_cache,
			array('fond' => $fond,
				'contexte' => $contexte),
			$delais,
			$inclusion);

		$lastmodified = time();

		// log
		if (!$log = $chemin_cache) $log = "($fond, delais=$delais, "
		. $GLOBALS['HTTP_SERVER_VARS']['REQUEST_METHOD'].")";
		spip_log (($inclusion ? 'calcul inclus':'calcul').' ('
		.spip_timer('calculer_page')."): $log");

		// Nouveau cache : creer un invalideur 't' fixant la date
		// d'expiration et la taille du fichier
		if (@file_exists($chemin_cache)) {
			// Ici on ajoute 3600s pour eviter toute concurrence
			// entre un invalideur et un appel public de page
			$bedtime = time() + $delais + 3600;
			$taille = @filesize($chemin_cache);
			$fichier = addslashes($chemin_cache);
			spip_query("INSERT IGNORE INTO spip_caches (fichier,id,type,taille)
			VALUES ('$fichier','$bedtime','t','$taille')");
		}

	} else {

		//
		// Lire le fichier cache
		//
		lire_fichier ($chemin_cache, $page['texte']);
		$lastmodified = max($lastmodified, @filemtime($chemin_cache));
		# spip_log ("cache $chemin_cache $lastmodified");

		//
		// Lire sa carte d'identite & fixer le contexte global
		//
		if (preg_match("/^<!-- ([^\n]*) -->\n(.*)/ms", $page['texte'], $match)
		AND is_array($meta_donnees = unserialize($match[1]))) {
			foreach ($meta_donnees as $var=>$val)
				$page[$var] = $val;

			$page['texte'] = $match[2];

			// Remplir les globals pour les boutons d'admin
			if (!$inclusion AND is_array($page['contexte']))
				foreach ($page['contexte'] as $var=>$val)
					$GLOBALS[$var] = $val;
		}

	}

	return $page;
}


//
// Appeler cette fonction pour obtenir la page principale
//
function afficher_page_globale ($fond, $delais, &$use_cache) {
	global $flag_preserver, $flag_dynamique, $lastmodified;
	global $var_preview, $var_mode;
	include_local ("inc-cache.php3");

	// demande de previsualisation ?
	// -> inc-calcul.php3 n'enregistrera pas les fichiers caches
	// -> inc-reqsql-squel.php3 acceptera les objets non 'publie'
	if ($var_preview == 'oui') {
		// Verifier qu'on a le droit de previsualisation
		$statut = $GLOBALS['auteur_session']['statut'];
		if ($statut=='0minirezo' OR
		(lire_meta('preview')=='1comite' AND $statut=='1comite')) {
			$var_mode = 'recalcul';
			$delais = 0;
			$var_preview = true;
			spip_log('preview !');
		}
	}

	// Faut-il effacer des pages invalidees ?
	if (lire_meta('invalider')) {
		include_ecrire('inc_meta.php3');
		lire_metas();
		if (lire_meta('invalider'))
			retire_caches();
	}

	// Calculer le chemin putatif du cache
	$chemin_cache = generer_nom_fichier_cache('', $fond);

	// Peut-on utiliser un fichier cache ?
	determiner_cache($delais, $use_cache, $chemin_cache);

	// Repondre gentiment aux requetes sympas
	// (ici on ne tient pas compte d'une obsolence du cache ou des
	// eventuels fichiers inclus modifies depuis la date
	// HTTP_IF_MODIFIED_SINCE du client)
	if ($GLOBALS['HTTP_IF_MODIFIED_SINCE'] AND !$var_mode
	AND $chemin_cache AND !$flag_dynamique) {
		$lastmodified = @filemtime($chemin_cache);
		$headers_only = http_last_modified($lastmodified);
	}
	$headers_only |= ($GLOBALS['HTTP_SERVER_VARS']['REQUEST_METHOD'] == 'HEAD');

	if ($headers_only) {
		if ($chemin_cache)
			$t = @filemtime($chemin_cache);
		else
			$t = time();
		@header('Last-Modified: '.http_gmoddate($t).' GMT');
		@header('Connection: close');
		// Pas de bouton admin pour un HEAD
		$flag_preserver = true;
	}
	else {
		// Obtenir la page
		$page = obtenir_page ('', $chemin_cache, $delais, $use_cache,
		$fond, false);
		if (!$flag_preserver) {
		// Entete content-type: xml ou html ; charset
			if ($xhtml) {
			// Si Mozilla et tidy actif, passer en "application/xhtml+xml"
			// extremement risque: Mozilla passe en mode debugueur strict
			// mais permet d'afficher du MathML directement dans le texte
			// (et sauf erreur, c'est la bonne facon de declarer du xhtml)
				include_ecrire("inc_tidy.php");
				if (version_tidy() > 0) {
					if (ereg("application/xhtml\+xml", $GLOBALS['HTTP_ACCEPT'])) 
						@header("Content-Type: application/xhtml+xml; charset=".lire_meta('charset'));
					else 
						@header("Content-Type: text/html; charset=".lire_meta('charset'));
					echo '<'.'?xml version="1.0" encoding="'. lire_meta('charset').'"?'.">\n";
				} else {
					@header("Content-Type: text/html; charset=".lire_meta('charset'));
				}
			} else {
				@header("Content-Type: text/html; charset=".lire_meta('charset'));
			}
		}
	}

	if ($chemin_cache) $page['cache'] = $chemin_cache;

	if ($var_preview AND !$flag_preserver) {
		$url = $GLOBALS['clean_link'];
		$url->delvar('var_preview');
		$url = $url->geturl();
		include_ecrire('inc_lang.php3');
		lang_select($GLOBALS['auteur_session']['lang']);
		$page['texte'] .= '<div style="
		display: block;
		color: #eeeeee;
		background-color: #111111;
		padding-right: 5px;
		padding-top: 2px;
		padding-bottom: 5px;
		font-size: 20px;
		top: 0px;
		left: 0px;
		position: absolute;
		"><a href="'.$url.'"><img src="' . _DIR_IMG_PACK . 'naviguer-site.png" align="left" border="0" /></a>
&nbsp; '.majuscules(_T('previsualisation')).'</div>';
	}

	return $page;
}


function terminer_public_global() {

	// Mise a jour des fichiers langues de l'espace public
	if ($GLOBALS['cache_lang_modifs']) {
		include_ecrire('inc_lang.php3');
		ecrire_caches_langues();
	}

	// Gestion des statistiques du site public

	if (lire_meta("activer_statistiques") != "non") {
		include_local ("inc-stats.php3");
		ecrire_stats();
	}

	// Effectuer une tache de fond ?
	cron();
}

// Cette fonction sert au dernier ob_start() de inc-public : elle
// va absorber les eventuels messages d'erreur de inc_cron(), permettant
// de ne pas planter le content-length qu'on a annonce ; decommenter la ligne
// pour afficher les bugs
function masquer_les_bugs ($bugs) {
	# return $bugs;
}

function inclure_page($fond, $delais_inclus, $contexte_inclus, $cache_incluant='') {

	$contexte_inclus['fond'] = $fond;

	$chemin_cache = generer_nom_fichier_cache($contexte_inclus, $fond);

	// Peut-on utiliser un fichier cache ?
	determiner_cache($delais_inclus, $use_cache, $chemin_cache);

	// Si on a inclus sans fixer le critere de lang, de deux choses l'une :
	// - on est dans la langue du site, et pas besoin d'inclure inc_lang
	// - on n'y est pas, et alors il faut revenir dans la langue par defaut
	if (($lang = $contexte_inclus['lang'])
	|| ($GLOBALS['spip_lang'] != ($lang = lire_meta('langue_site')))) {
		include_ecrire('inc_lang.php3');
		lang_select($lang);
		$lang_select = true; // pour lang_dselect en sortie
	}

	$page = obtenir_page ($contexte_inclus, $chemin_cache, $delais_inclus,
	$use_cache, $fond, true);

	$page['lang_select'] = $lang_select;

	// Retourner le contenu...
	return $page;
}

function inclure_balise_dynamique($r) {
	if (is_string($r))
		echo $r;
	else {
		list($fond, $delais, $contexte_inclus) = $r;
		if ((!$contexte_inclus['lang']) AND
		($GLOBALS['spip_lang'] != lire_meta('langue_site')))
			$contexte_inclus['lang'] = $GLOBALS['spip_lang']; 
		include('inc-public.php3');
	}
}
?>
