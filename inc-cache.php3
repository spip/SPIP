<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_CACHE")) return;
define("_INC_CACHE", "1");


//
// Calcul du nom du fichier cache
//

function nettoyer_uri() {
	$fichier_requete = $GLOBALS['REQUEST_URI'];
	$fichier_requete = eregi_replace
		('[?&](submit|valider|PHPSESSID|(var_[^=&]*)|recalcul)=[^&]*',
		'', $fichier_requete);
	return $fichier_requete;
}

function generer_nom_fichier_cache($contexte='', $fond='') {
	global $HTTP_POST_VARS;

	if (!$contexte) {
		$fichier_requete = nettoyer_uri();
	} else {
		$fichier_requete = $fond;
		foreach ($contexte as $var=>$val)
			$fichier_requete .= "&$var=$val";
	}

	$md_cache = md5($fichier_requete);

	$fichier_cache = ereg_replace('^/+', '', $fichier_requete);
	$fichier_cache = ereg_replace('\.[a-zA-Z0-9]*', '', $fichier_cache);
	$fichier_cache = ereg_replace('&[^&]+=([^&]+)', '&\1', $fichier_cache);
	$fichier_cache = rawurlencode(strtr($fichier_cache, '/&-', '--_'));
	if (strlen($fichier_cache) > 24)
		$fichier_cache = substr(ereg_replace('([a-zA-Z]{1,3})[^-]*-',
		'\1-', $fichier_cache), -22);

	// Pour la page d'accueil
	if (!$fichier_cache)
		$fichier_cache = 'INDEX-';

	// Cas des POST sur une meme adresse : ne pas melanger (desuet?)
	if (!empty($HTTP_POST_VARS)) $fichier_cache .= '.'.@getmypid();
	$fichier_cache .= '.'.substr($md_cache, 1, 8);

	$subdir_cache = substr($md_cache, 0, 1);

	if (creer_repertoire('CACHE', $subdir_cache))
		$fichier_cache = "$subdir_cache/$fichier_cache";

	return $fichier_cache;
}


//
// Doit-on recalculer le cache ?
//

function utiliser_cache($chemin_cache, $delais) {
	global $HTTP_SERVER_VARS, $HTTP_POST_VARS;
	global $lastmodified;

	// A priori cache
	$ok_cache = true;

	// Existence du fichier
	$ok_cache = @file_exists($chemin_cache);

	// Date de creation du fichier
	if ($ok_cache) {
		$t = filemtime($chemin_cache);
		$age = time() - $t;
		$age_ok = (($age < $delais) AND ($age >= 0));

		// fichier cache trop vieux ?
		if (!$age_ok)
			$ok_cache = false;

		// Inclusions multiples : derniere modification
		if ($lastmodified < $t) $lastmodified = $t;
	}

	// recalcul obligatoire
	$ok_cache &= ($GLOBALS['recalcul'] != 'oui');
	$ok_cache &= empty($HTTP_POST_VARS);

	// ne jamais recalculer pour les moteurs de recherche, proxies...
	if ($HTTP_SERVER_VARS['REQUEST_METHOD'] == 'HEAD')
		$ok_cache = true;

	# spip_log (($ok_cache ? "cache":"calcul")." ($chemin_cache)". ($age ? " age: $age s (reste ".($delais-$age)." s)":''));
	return $ok_cache;
}


function ecrire_fichier_cache ($fichier, $contenu) {
	$fichier_tmp = $fichier.'_tmp';

	// Essayer de poser un verrou pour proteger l'ecriture du fichier
	if (!spip_get_lock($fichier_tmp, 1)) {
		spip_log ("Echec du lock $fichier_tmp !");
		return;
	}

	// Entrer les donnees et verifier qu'on est alle au bout
	$f = fopen($fichier_tmp, "wb");
	if (!$f) {
		spip_log ("Echec d'ouverture de $fichier_tmp !");
		return $fichier;
	} else {
		$r = fwrite($f, $contenu);
		if ($r != strlen($contenu))
			$bug = true;
		if (!fclose($f))
			$bug = true;
	}

	// Finaliser
	if ($bug) {
		spip_log ("Probleme avec le fichier $fichier_tmp - je meurs");
		@unlink($fichier_tmp);
	} else {
		@rename($fichier_tmp, $fichier);
	}
	spip_release_lock($fichier_tmp);
}


//
// Retourne $subdir/ si le sous-repertoire peut etre cree, '' sinon
//

function creer_repertoire($base, $subdir) {
	if (@file_exists("$base/.plat")) return '';
	$path = $base.'/'.$subdir;
	if (@file_exists($path)) return "$subdir/";

	@mkdir($path, 0777);
	@chmod($path, 0777);
	$ok = false;
	if ($f = @fopen("$path/.test", "w")) {
		@fputs($f, '<'.'?php $ok = true; ?'.'>');
		@fclose($f);
		include("$path/.test");
	}
	if (!$ok) {
		$f = @fopen("$base/.plat", "w");
		if ($f)
			fclose($f);
		else {
			@header("Location: spip_test_dirs.php3");
			exit;
		}
	}
	return ($ok? "$subdir/" : '');
}


function purger_repertoire($dir, $age, $regexp = '') {
	$handle = @opendir($dir);
	if (!$handle) return;

	$t = time();
	while (($fichier = @readdir($handle)) != '') {
		// Eviter ".", "..", ".htaccess", etc.
		if ($fichier[0] == '.') continue;
		if ($regexp AND !ereg($regexp, $fichier)) continue;
		$chemin = "$dir/$fichier";
		if (is_file($chemin)) {
			$d = $t - filemtime($chemin);
			if ($d > $age OR (ereg('\.NEW$', $fichier) AND $d > 60)) {
				@unlink($chemin);
				$fichier = ereg_replace('\.NEW$', '', $fichier);
				$query = "DELETE FROM spip_forum_cache WHERE fichier='$fichier'";
				spip_query($query);
			}
		}
		else if (is_dir($chemin)) {
			if ($fichier != 'CVS') purger_repertoire($chemin, $age);
		}
	}
	closedir($handle);
}


// Recuperer les meta donnees du fichier cache
function meta_donnees_cache ($chemin_cache) {
	// Lire le debut du fichier cache ; permet de savoir s'il n'a
	// pas ete invalide par une modif sur une table
	if ($f = fopen($chemin_cache, "r")) {
		$l = fgets($f,1024);
		if (!preg_match("/^<!-- ([^\n]*) -->\n/", $l, $match))
			return false; // non conforme
		$meta_donnees = unserialize($match[1]);
	}
	return $meta_donnees;
}

// Determination du fichier cache (si besoin)
function determiner_cache($delais, &$use_cache, &$chemin_cache) {
	if ($delais == 0) {
		$use_cache = false;
		$chemin_cache = '';
	} else {
		// Le fichier cache est-il valide ?
		$use_cache = utiliser_cache($chemin_cache, $delais);

		if ($use_cache) {
			$meta_donnees = meta_donnees_cache($chemin_cache);
			if (!is_array($meta_donnees)) {
				$use_cache = false;
			} else {
				// Remplir les globals pour les boutons d'admin
				if (is_array($meta_donnees['contexte']))
				foreach ($meta_donnees['contexte'] as $var=>$val)
					$GLOBALS[$var] = $val;
			}
		}

		// S'il faut calculer, poser un lock (et tester MySQL)
		if (!$use_cache) {
			// Attendre 20 secondes maxi, que le copain ait
			// calcule le meme fichier cache ou que
			// l'invalideur ait fini de supprimer le fichier
			$ok = spip_get_lock($chemin_cache, 20);

			if (!$ok)
				$use_cache = @file_exists($chemin_cache);

			// Toujours rien ? La base est morte :-(
			if (!$use_cache AND !$GLOBALS['db_ok']) {
				if (!$GLOBALS['flag_preserver']) {
					include_ecrire('inc_presentation.php3');
					install_debut_html(_T('info_travaux_titre'));
					echo "<p>"._T('titre_probleme_technique')."</p>\n";
					install_fin_html();
					spip_log("Erreur base de donnees & ".
					"impossible de creer $chemin_cache");
				}
				exit;
			}
		}

		// On a le fichier cache et la date
		if ($use_cache)
			$lastmodified = filemtime($chemin_cache);
			###  Si on utilise la fraicheur ce sera inutile
	}

	return $lastmodified;
}

?>
