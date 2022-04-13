<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Utilitaires indispensables autour du serveur Http.
 *
 * @package SPIP\Core\Utilitaires
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Cherche une fonction surchargeable et en retourne le nom exact,
 * après avoir chargé le fichier la contenant si nécessaire.
 *
 * Charge un fichier (suivant les chemins connus) et retourne si elle existe
 * le nom de la fonction homonyme `$dir_$nom`, ou suffixé `$dir_$nom_dist`
 *
 * Peut être appelé plusieurs fois, donc optimisé.
 *
 * @api
 * @uses include_spip() Pour charger le fichier
 * @example
 *     ```
 *     $envoyer_mail = charger_fonction('envoyer_mail', 'inc');
 *     $envoyer_mail($email, $sujet, $texte);
 *     ```
 *
 * @param string $nom
 *     Nom de la fonction (et du fichier)
 * @param string $dossier
 *     Nom du dossier conteneur
 * @param bool $continue
 *     true pour ne pas râler si la fonction n'est pas trouvée
 * @return string
 *     Nom de la fonction, ou false.
 */
function charger_fonction($nom, $dossier = 'exec', $continue = false) {
	static $echecs = [];

	if (strlen($dossier) and substr($dossier, -1) != '/') {
		$dossier .= '/';
	}
	$f = str_replace('/', '_', $dossier) . $nom;

	if (function_exists($f)) {
		return $f;
	}
	if (function_exists($g = $f . '_dist')) {
		return $g;
	}

	if (isset($echecs[$f])) {
		return $echecs[$f];
	}
	// Sinon charger le fichier de declaration si plausible

	if (!preg_match(',^\w+$,', $f)) {
		if ($continue) {
			return false;
		} //appel interne, on passe
		include_spip('inc/minipres');
		echo minipres();
		exit;
	}

	// passer en minuscules (cf les balises de formulaires)
	// et inclure le fichier
	if (
		!$inc = include_spip($dossier . ($d = strtolower($nom)))
		// si le fichier truc/machin/nom.php n'existe pas,
		// la fonction peut etre definie dans truc/machin.php qui regroupe plusieurs petites fonctions
		and strlen(dirname($dossier)) and dirname($dossier) != '.'
	) {
		include_spip(substr($dossier, 0, -1));
	}
	if (function_exists($f)) {
		return $f;
	}
	if (function_exists($g)) {
		return $g;
	}

	if ($continue) {
		return $echecs[$f] = false;
	}

	// Echec : message d'erreur
	spip_log("fonction $nom ($f ou $g) indisponible" .
		($inc ? '' : " (fichier $d absent de $dossier)"));

	include_spip('inc/minipres');
	echo minipres(
		_T('forum_titre_erreur'),
		$inc ?
			_T('fonction_introuvable', ['fonction' => '<code>' . spip_htmlentities($f) . '</code>'])
			. '<br />'
			. _T('fonction_introuvable', ['fonction' => '<code>' . spip_htmlentities($g) . '</code>'])
			:
			_T('fichier_introuvable', ['fichier' => '<code>' . spip_htmlentities($d) . '</code>']),
		['all_inline' => true,'status' => 404]
	);
	exit;
}

/**
 * Inclusion unique avec verification d'existence du fichier + log en crash sinon
 *
 * @param string $file
 * @return bool
 */
function include_once_check($file) {
	if (file_exists($file)) {
		include_once $file;

		return true;
	}
	$crash = (isset($GLOBALS['meta']['message_crash_plugins']) ? unserialize($GLOBALS['meta']['message_crash_plugins']) : '');
	$crash = ($crash ?: []);
	$crash[$file] = true;
	ecrire_meta('message_crash_plugins', serialize($crash));

	return false;
}


/**
 * Inclut un fichier PHP (en le cherchant dans les chemins)
 *
 * @api
 * @uses find_in_path()
 * @example
 *     ```
 *     include_spip('inc/texte');
 *     ```
 *
 * @param string $f
 *     Nom du fichier (sans l'extension)
 * @param bool $include
 *     - true pour inclure le fichier,
 *     - false ne fait que le chercher
 * @return string|bool
 *     - false : fichier introuvable
 *     - string : chemin du fichier trouvé
 **/
function include_spip($f, $include = true) {
	return find_in_path($f . '.php', '', $include);
}

/**
 * Requiert un fichier PHP (en le cherchant dans les chemins)
 *
 * @uses find_in_path()
 * @see  include_spip()
 * @example
 *     ```
 *     require_spip('inc/texte');
 *     ```
 *
 * @param string $f
 *     Nom du fichier (sans l'extension)
 * @return string|bool
 *     - false : fichier introuvable
 *     - string : chemin du fichier trouvé
 **/
function require_spip($f) {
	return find_in_path($f . '.php', '', 'required');
}


/**
 * Raccourci pour inclure mes_fonctions.php et tous les fichiers _fonctions.php des plugin
 * quand on a besoin dans le PHP de filtres/fonctions qui y sont definis
 */
function include_fichiers_fonctions() {
	static $done = false;
	if (!$done) {
		include_spip('inc/lang');

		// NB: mes_fonctions peut initialiser $dossier_squelettes (old-style)
		// donc il faut l'inclure "en globals"
		if ($f = find_in_path('mes_fonctions.php')) {
			global $dossier_squelettes;
			include_once(_ROOT_CWD . $f);
		}

		if (@is_readable(_CACHE_PLUGINS_FCT)) {
			// chargement optimise precompile
			include_once(_CACHE_PLUGINS_FCT);
		}
		if (test_espace_prive()) {
			include_spip('inc/filtres_ecrire');
		}
		include_spip('public/fonctions'); // charger les fichiers fonctions associes aux criteres, balises..
		$done = true;
	}
}

/**
 * Exécute une fonction (appellée par un pipeline) avec la donnée transmise.
 *
 * Un pipeline est lie a une action et une valeur
 * chaque element du pipeline est autorise a modifier la valeur
 * le pipeline execute les elements disponibles pour cette action,
 * les uns apres les autres, et retourne la valeur finale
 *
 * Cf. compose_filtres dans references.php, qui est la
 * version compilee de cette fonctionnalite
 * appel unitaire d'une fonction du pipeline
 * utilisee dans le script pipeline precompile
 *
 * on passe $val par reference pour limiter les allocations memoire
 *
 * @param string $fonc
 *     Nom de la fonction appelée par le pipeline
 * @param string|array $val
 *     Les paramètres du pipeline, son environnement
 * @return string|array $val
 *     Les paramètres du pipeline modifiés
 **/
function minipipe($fonc, &$val) {
	// fonction
	if (function_exists($fonc)) {
		$val = $fonc($val);
	} // Class::Methode
	else {
		if (
			preg_match('/^(\w*)::(\w*)$/S', $fonc, $regs)
			and $methode = [$regs[1], $regs[2]]
			and is_callable($methode)
		) {
			$val = $methode($val);
		} else {
			spip_log("Erreur - '$fonc' non definie !");
		}
	}

	return $val;
}

/**
 * Appel d’un pipeline
 *
 * Exécute le pipeline souhaité, éventuellement avec des données initiales.
 * Chaque plugin qui a demandé à voir ce pipeline vera sa fonction spécifique appelée.
 * Les fonctions (des plugins) appelées peuvent modifier à leur guise le contenu.
 *
 * Deux types de retours. Si `$val` est un tableau de 2 éléments, avec une clé `data`
 * on retourne uniquement ce contenu (`$val['data']`) sinon on retourne tout `$val`.
 *
 *
 * @example
 *     Appel du pipeline `pre_insertion`
 *     ```
 *     $champs = pipeline('pre_insertion', array(
 *         'args' => array('table' => 'spip_articles'),
 *         'data' => $champs
 *     ));
 *     ```
 *
 * @param string $action
 *     Nom du pipeline
 * @param mixed $val
 *     Données à l’entrée du pipeline
 * @return mixed|null
 *     Résultat
 */
function pipeline($action, $val = null) {
	static $charger;

	// chargement initial des fonctions mises en cache, ou generation du cache
	if (!$charger) {
		if (!($ok = @is_readable($charger = _CACHE_PIPELINES))) {
			include_spip('inc/plugin');
			// generer les fichiers php precompiles
			// de chargement des plugins et des pipelines
			actualise_plugins_actifs();
			if (!($ok = @is_readable($charger))) {
				spip_log("fichier $charger pas cree");
			}
		}

		if ($ok) {
			include_once $charger;
		}
	}

	// appliquer notre fonction si elle existe
	$fonc = 'execute_pipeline_' . strtolower($action);
	if (function_exists($fonc)) {
		$val = $fonc($val);
	} // plantage ?
	else {
		spip_log("fonction $fonc absente : pipeline desactive", _LOG_ERREUR);
	}

	// si le flux est une table avec 2 cle args&data
	// on ne ressort du pipe que les donnees dans 'data'
	// array_key_exists pour php 4.1.0
	if (
		is_array($val)
		and count($val) == 2
		and (array_key_exists('data', $val))
	) {
		$val = $val['data'];
	}

	return $val;
}

/**
 * Enregistrement des événements
 *
 * Signature : `spip_log(message[,niveau|type|type.niveau])`
 *
 * Le niveau de log par défaut est la valeur de la constante `_LOG_INFO`
 *
 * Les différents niveaux possibles sont :
 *
 * - `_LOG_HS` : écrira 'HS' au début de la ligne logguée
 * - `_LOG_ALERTE_ROUGE` : 'ALERTE'
 * - `_LOG_CRITIQUE` :  'CRITIQUE'
 * - `_LOG_ERREUR` : 'ERREUR'
 * - `_LOG_AVERTISSEMENT` : 'WARNING'
 * - `_LOG_INFO_IMPORTANTE` : '!INFO'
 * - `_LOG_INFO` : 'info'
 * - `_LOG_DEBUG` : 'debug'
 *
 * @example
 *   ```
 *   spip_log($message)
 *   spip_log($message, 'recherche')
 *   spip_log($message, _LOG_DEBUG)
 *   spip_log($message, 'recherche.'._LOG_DEBUG)
 *   ```
 *
 * @api
 * @link https://programmer.spip.net/spip_log
 * @uses inc_log_dist()
 *
 * @param string $message
 *     Message à loger
 * @param string|int $name
 *
 *     - int indique le niveau de log, tel que `_LOG_DEBUG`
 *     - string indique le type de log
 *     - `string.int` indique les 2 éléments.
 *     Cette dernière notation est controversée mais le 3ème
 *     paramètre est planté pour cause de compatibilité ascendante.
 */
function spip_log($message = null, $name = null) {
	static $pre = [];
	static $log;
	preg_match('/^([a-z_]*)\.?(\d)?$/iS', (string)$name, $regs);
	if (!isset($regs[1]) or !$logname = $regs[1]) {
		$logname = null;
	}
	if (!isset($regs[2])) {
		$niveau = _LOG_INFO;
	}
	else {
		$niveau = intval($regs[2]);
	}

	if ($niveau <= (defined('_LOG_FILTRE_GRAVITE') ? _LOG_FILTRE_GRAVITE : _LOG_INFO_IMPORTANTE)) {
		if (!$pre) {
			$pre = [
				_LOG_HS => 'HS:',
				_LOG_ALERTE_ROUGE => 'ALERTE:',
				_LOG_CRITIQUE => 'CRITIQUE:',
				_LOG_ERREUR => 'ERREUR:',
				_LOG_AVERTISSEMENT => 'WARNING:',
				_LOG_INFO_IMPORTANTE => '!INFO:',
				_LOG_INFO => 'info:',
				_LOG_DEBUG => 'debug:'
			];
			$log = charger_fonction('log', 'inc');
		}
		if (!is_string($message)) {
			$message = print_r($message, true);
		}
		$log($pre[$niveau] . ' ' . $message, $logname);
	}
}

/**
 * Enregistrement des journaux
 *
 * @uses inc_journal_dist()
 * @param string $phrase Texte du journal
 * @param array $opt Tableau d'options
 **/
function journal($phrase, $opt = []) {
	$journal = charger_fonction('journal', 'inc');
	$journal($phrase, $opt);
}


/**
 * Renvoie le `$_GET` ou le `$_POST` émis par l'utilisateur
 * ou pioché dans un tableau transmis
 *
 * @api
 * @param string $var
 *     Clé souhaitée
 * @param bool|array $c
 *     Tableau transmis (sinon cherche dans GET ou POST)
 * @return mixed|null
 *     - null si la clé n'a pas été trouvée
 *     - la valeur de la clé sinon.
 **/
function _request($var, $c = false) {

	if (is_array($c)) {
		return $c[$var] ?? null;
	}

	if (isset($_GET[$var])) {
		$a = $_GET[$var];
	} elseif (isset($_POST[$var])) {
		$a = $_POST[$var];
	} else {
		return null;
	}

	// Si on est en ajax et en POST tout a ete encode
	// via encodeURIComponent, il faut donc repasser
	// dans le charset local...
	if (
		defined('_AJAX')
		and _AJAX
		and isset($GLOBALS['meta']['charset'])
		and $GLOBALS['meta']['charset'] != 'utf-8'
		and is_string($a)
		// check rapide mais pas fiable
		and preg_match(',[\x80-\xFF],', $a)
		// check fiable
		and include_spip('inc/charsets')
		and is_utf8($a)
	) {
		return importer_charset($a, 'utf-8');
	}

	return $a;
}


/**
 * Affecte une valeur à une clé (pour usage avec `_request()`)
 *
 * @see _request() Pour obtenir la valeur
 * @note Attention au cas ou l'on fait `set_request('truc', NULL);`
 *
 * @param string $var Nom de la clé
 * @param string $val Valeur à affecter
 * @param bool|array $c Tableau de données (sinon utilise `$_GET` et `$_POST`)
 * @return array|bool
 *     - array $c complété si un $c est transmis,
 *     - false sinon
 **/
function set_request($var, $val = null, $c = false) {
	if (is_array($c)) {
		unset($c[$var]);
		if ($val !== null) {
			$c[$var] = $val;
		}

		return $c;
	}

	unset($_GET[$var]);
	unset($_POST[$var]);
	if ($val !== null) {
		$_GET[$var] = $val;
	}

	return false; # n'affecte pas $c
}

/**
 * Sanitizer une valeur *SI* elle provient du GET ou POST
 * Utile dans les squelettes pour les valeurs qu'on attrape dans le env,
 * dont on veut permettre à un squelette de confiance appelant de fournir une valeur complexe
 * mais qui doit etre nettoyee si elle provient de l'URL
 *
 * On peut sanitizer
 * - une valeur simple : `$where = spip_sanitize_from_request($value, 'where')`
 * - un tableau en partie : `$env = spip_sanitize_from_request($env, ['key1','key2'])`
 * - un tableau complet : `$env = spip_sanitize_from_request($env, '*')`
 *
 * @param string|array $value
 * @param string|array $key
 * @param string $sanitize_function
 * @return array|mixed|string
 */
function spip_sanitize_from_request($value, $key, $sanitize_function = 'entites_html') {
	if (is_array($value)) {
		if ($key == '*') {
			$key = array_keys($value);
		}
		if (!is_array($key)) {
			$key = [$key];
		}
		foreach ($key as $k) {
			if (!empty($value[$k])) {
				$value[$k] = spip_sanitize_from_request($value[$k], $k, $sanitize_function);
			}
		}
		return $value;
	}
	// si la valeur vient des GET ou POST on la sanitize
	if (!empty($value) and $value == _request($key)) {
		$value = $sanitize_function($value);
	}
	return $value;
}

/**
 * Tester si une URL est absolue
 *
 * On est sur le web, on exclut certains protocoles,
 * notamment 'file://', 'php://' et d'autres…

 * @param string $url
 * @return bool
 */
function tester_url_absolue($url) {
	$url = trim($url ?? '');
	if ($url && preg_match(';^([a-z]{3,7}:)?//;Uims', $url, $m)) {
		if (
			isset($m[1])
			and $p = strtolower(rtrim($m[1], ':'))
			and in_array($p, ['file', 'php', 'zlib', 'glob', 'phar', 'ssh2', 'rar', 'ogg', 'expect', 'zip'])
		) {
			return false;
		}
		return true;
	}
	return false;
}

/**
 * Prend une URL et lui ajoute/retire un paramètre
 *
 * @filtre
 * @link https://www.spip.net/4255
 * @example
 *     ```
 *     [(#SELF|parametre_url{suite,18})] (ajout)
 *     [(#SELF|parametre_url{suite,''})] (supprime)
 *     [(#SELF|parametre_url{suite[],1})] (tableaux valeurs multiples)
 *     ```
 *
 * @param string $url URL
 * @param string $c Nom du paramètre
 * @param string|array|null $v Valeur du paramètre
 * @param string $sep Séparateur entre les paramètres
 * @return string URL
 */
function parametre_url($url, $c, $v = null, $sep = '&amp;') {
	// requete erronnee : plusieurs variable dans $c et aucun $v
	if (strpos($c, '|') !== false and is_null($v)) {
		return null;
	}

	// lever l'#ancre
	if (preg_match(',^([^#]*)(#.*)$,', $url, $r)) {
		$url = $r[1];
		$ancre = $r[2];
	} else {
		$ancre = '';
	}

	// eclater
	$url = preg_split(',[?]|&amp;|&,', $url);

	// recuperer la base
	$a = array_shift($url);
	if (!$a) {
		$a = './';
	}

	// preparer la regexp de maniere securisee
	$regexp = explode('|', $c);
	foreach ($regexp as $r => $e) {
		$regexp[$r] = str_replace('[]', '\[\]', preg_replace(',[^\w\d\[\]-],', '', $e));
	}
	$regexp = ',^(' . implode('|', $regexp) . '[[]?[]]?)(=.*)?$,';
	$ajouts = array_flip(explode('|', $c));
	$u = is_array($v) ? $v : rawurlencode((string) $v);
	$testv = (is_array($v) ? count($v) : strlen((string) $v));
	$v_read = null;
	// lire les variables et agir
	foreach ($url as $n => $val) {
		if (preg_match($regexp, urldecode($val), $r)) {
			$r = array_pad($r, 3, null);
			if ($v === null) {
				// c'est un tableau, on memorise les valeurs
				if (substr($r[1], -2) == '[]') {
					if (!$v_read) {
						$v_read = [];
					}
					$v_read[] = $r[2] ? substr($r[2], 1) : '';
				} // c'est un scalaire, on retourne direct
				else {
					return $r[2] ? substr($r[2], 1) : '';
				}
			} // suppression
			elseif (!$testv) {
				unset($url[$n]);
			}
			// Ajout. Pour une variable, remplacer au meme endroit,
			// pour un tableau ce sera fait dans la prochaine boucle
			elseif (substr($r[1], -2) != '[]') {
				$url[$n] = $r[1] . '=' . $u;
				unset($ajouts[$r[1]]);
			}
			// Pour les tableaux on laisse tomber les valeurs de
			// départ, on remplira à l'étape suivante
			else {
				unset($url[$n]);
			}
		}
	}

	// traiter les parametres pas encore trouves
	if (
		$v === null
		and $args = func_get_args()
		and count($args) == 2
	) {
		return $v_read; // rien trouve ou un tableau
	} elseif ($testv) {
		foreach ($ajouts as $k => $n) {
			if (!is_array($v)) {
				$url[] = $k . '=' . $u;
			} else {
				$id = (substr($k, -2) == '[]') ? $k : ($k . '[]');
				foreach ($v as $w) {
					$url[] = $id . '=' . (is_array($w) ? 'Array' : rawurlencode($w));
				}
			}
		}
	}

	// eliminer les vides
	$url = array_filter($url);

	// recomposer l'adresse
	if ($url) {
		$a .= '?' . join($sep, $url);
	}

	return $a . $ancre;
}

/**
 * Ajoute (ou retire) une ancre sur une URL
 *
 * L’ancre est nettoyée : on translitère, vire les non alphanum du début,
 * et on remplace ceux à l'interieur ou au bout par `-`
 *
 * @example
 *     - `$url = ancre_url($url, 'navigation'); // => mettra l’ancre #navigation
 *     - `$url = ancre_url($url, ''); // => enlèvera une éventuelle ancre
 * @uses translitteration()
 * @param string $url
 * @param string $ancre
 * @return string
 */
function ancre_url($url, $ancre) {
	// lever l'#ancre
	if (preg_match(',^([^#]*)(#.*)$,', $url, $r)) {
		$url = $r[1];
	}
	if (preg_match('/[^-_a-zA-Z0-9]+/S', $ancre)) {
		if (!function_exists('translitteration')) {
			include_spip('inc/charsets');
		}
		$ancre = preg_replace(
			['/^[^-_a-zA-Z0-9]+/', '/[^-_a-zA-Z0-9]/'],
			['', '-'],
			translitteration($ancre)
		);
	}
	return $url . (strlen($ancre) ? '#' . $ancre : '');
}

/**
 * Pour le nom du cache, les `types_urls` et `self`
 *
 * @param string|null $reset
 * @return string
 */
function nettoyer_uri($reset = null) {
	static $done = false;
	static $propre = '';
	if (!is_null($reset)) {
		return $propre = $reset;
	}
	if ($done) {
		return $propre;
	}
	$done = true;
	return $propre = nettoyer_uri_var($GLOBALS['REQUEST_URI']);
}

/**
 * Nettoie une request_uri des paramètres var_xxx
 *
 * Attention, la regexp doit suivre _CONTEXTE_IGNORE_VARIABLES défini au début de public/assembler.php
 *
 * @param $request_uri
 * @return string
 */
function nettoyer_uri_var($request_uri) {
	$uri1 = $request_uri;
	do {
		$uri = $uri1;
		$uri1 = preg_replace(
			',([?&])(var_[^=&]*|PHPSESSID|fbclid|utm_[^=&]*)=[^&]*(&|$),i',
			'\1',
			$uri
		);
	} while ($uri <> $uri1);
	return preg_replace(',[?&]$,', '', $uri1);
}


/**
 * Donner l'URL de base d'un lien vers "soi-meme", modulo les trucs inutiles
 *
 * @param string $amp
 *    Style des esperluettes
 * @param bool $root
 * @return string
 *    URL vers soi-même
 **/
function self($amp = '&amp;', $root = false) {
	$url = nettoyer_uri();
	if (
		!$root
		and (
			// si pas de profondeur on peut tronquer
			$GLOBALS['profondeur_url'] < (_DIR_RESTREINT ? 1 : 2)
			// sinon c'est OK si _SET_HTML_BASE a ete force a false
			or (defined('_SET_HTML_BASE') and !_SET_HTML_BASE))
	) {
		$url = preg_replace(',^[^?]*/,', '', $url);
	}
	// ajouter le cas echeant les variables _POST['id_...']
	foreach ($_POST as $v => $c) {
		if (substr($v, 0, 3) == 'id_') {
			$url = parametre_url($url, $v, $c, '&');
		}
	}

	// supprimer les variables sans interet
	if (test_espace_prive()) {
		$url = preg_replace(',([?&])('
			. 'lang|show_docs|'
			. 'changer_lang|var_lang|action)=[^&]*,i', '\1', $url);
		$url = preg_replace(',([?&])[&]+,', '\1', $url);
		$url = preg_replace(',[&]$,', '\1', $url);
	}

	// eviter les hacks
	include_spip('inc/filtres_mini');
	$url = spip_htmlspecialchars($url);

	$url = str_replace(["'", '"', '<', '[', ']', ':'], ['%27', '%22', '%3C', '%5B', '%5D', '%3A'], $url);

	// &amp; ?
	if ($amp != '&amp;') {
		$url = str_replace('&amp;', $amp, $url);
	}

	// Si ca demarre par ? ou vide, donner './'
	$url = preg_replace(',^([?].*)?$,', './\1', $url);

	return $url;
}


/**
 * Indique si on est dans l'espace prive
 *
 * @return bool
 *     true si c'est le cas, false sinon.
 */
function test_espace_prive() {
	return defined('_ESPACE_PRIVE') ? _ESPACE_PRIVE : false;
}

/**
 * Vérifie la présence d'un plugin actif, identifié par son préfixe
 *
 * @param string $plugin
 * @return bool
 */
function test_plugin_actif($plugin) {
	return ($plugin and defined('_DIR_PLUGIN_' . strtoupper($plugin))) ? true : false;
}

/**
 * Traduction des textes de SPIP
 *
 * Traduit une clé de traduction en l'obtenant dans les fichiers de langues.
 *
 * @api
 * @uses inc_traduire_dist()
 * @uses _L()
 * @example
 *     ```
 *     _T('bouton_enregistrer')
 *     _T('medias:image_tourner_droite')
 *     _T('medias:erreurs', array('nb'=>3))
 *     _T("email_sujet", array('spip_lang'=>$lang_usager))
 *     ```
 *
 * @param string $texte
 *     Clé de traduction
 * @param array $args
 *     Couples (variable => valeur) pour passer des variables à la chaîne traduite. la variable spip_lang permet de forcer la langue
 * @param array $options
 *     - string class : nom d'une classe a ajouter sur un span pour encapsuler la chaine
 *     - bool force : forcer un retour meme si la chaine n'a pas de traduction
 *     - bool sanitize : nettoyer le html suspect dans les arguments
 * @return string
 *     Texte
 */
function _T($texte, $args = [], $options = []) {
	static $traduire = false;
	$o = ['class' => '', 'force' => true, 'sanitize' => true];
	if ($options) {
		// support de l'ancien argument $class
		if (is_string($options)) {
			$options = ['class' => $options];
		}
		$o = array_merge($o, $options);
	}

	if (!$traduire) {
		$traduire = charger_fonction('traduire', 'inc');
		include_spip('inc/lang');
	}

	// On peut passer explicitement la langue dans le tableau
	// On utilise le même nom de variable que la globale
	if (isset($args['spip_lang'])) {
		$lang = $args['spip_lang'];
		// On l'enleve pour ne pas le passer au remplacement
		unset($args['spip_lang']);
	} // Sinon on prend la langue du contexte
	else {
		$lang = $GLOBALS['spip_lang'];
	}
	$text = $traduire($texte, $lang);

	if ($text === null || !strlen($text)) {
		if (!$o['force']) {
			return '';
		}

		$text = $texte;

		// pour les chaines non traduites, assurer un service minimum
		if (!$GLOBALS['test_i18n'] and (_request('var_mode') != 'traduction')) {
			$n = strpos($text, ':');
			if ($n !== false) {
				$text = substr($text, $n + 1);
			}
			$text = str_replace('_', ' ', $text);
		}
		$o['class'] = null;
	}

	return _L($text, $args, $o);
}


/**
 * Remplace les variables `@...@` par leur valeur dans une chaîne de langue.
 *
 * Cette fonction est également appelée dans le code source de SPIP quand une
 * chaîne n'est pas encore dans les fichiers de langue.
 *
 * @see _T()
 * @example
 *     ```
 *     _L('Texte avec @nb@ ...', array('nb'=>3)
 *     ```
 *
 * @param string $text
 *     Texte
 * @param array $args
 *     Couples (variable => valeur) à transformer dans le texte
 * @param array $options
 *     - string class : nom d'une classe a ajouter sur un span pour encapsuler la chaine
 *     - bool sanitize : nettoyer le html suspect dans les arguments
 * @return string
 *     Texte
 */
function _L($text, $args = [], $options = []) {
	$f = $text;
	$defaut_options = [
		'class' => null,
		'sanitize' => true,
	];
	// support de l'ancien argument $class
	if ($options and is_string($options)) {
		$options = ['class' => $options];
	}
	if (is_array($options)) {
		$options += $defaut_options;
	} else {
		$options = $defaut_options;
	}

	if (is_array($args) and count($args)) {
		if (!function_exists('interdire_scripts')) {
			include_spip('inc/texte');
		}
		if (!function_exists('echapper_html_suspect')) {
			include_spip('inc/texte_mini');
		}
		foreach ($args as $name => $value) {
			if (strpos($text, (string) "@$name@") !== false) {
				if ($options['sanitize']) {
					$value = echapper_html_suspect($value);
					$value = interdire_scripts($value, -1);
				}
				if (!empty($options['class'])) {
					$value = "<span class='" . $options['class'] . "'>$value</span>";
				}
				$text = str_replace("@$name@", $value, $text);
				unset($args[$name]);
			}
		}
		// Si des variables n'ont pas ete inserees, le signaler
		// (chaines de langues pas a jour)
		if ($args) {
			spip_log("$f:  variables inutilisees " . join(', ', array_keys($args)), _LOG_DEBUG);
		}
	}

	if (($GLOBALS['test_i18n'] or (_request('var_mode') == 'traduction')) and is_null($options['class'])) {
		return "<span class='debug-traduction-erreur'>$text</span>";
	} else {
		return $text;
	}
}


/**
 * Retourne un joli chemin de répertoire
 *
 * Pour afficher `ecrire/action/` au lieu de `action/` dans les messages
 * ou `tmp/` au lieu de `../tmp/`
 *
 * @param string $rep Chemin d’un répertoire
 * @return string
 */
function joli_repertoire($rep) {
	$a = substr($rep, 0, 1);
	if ($a <> '.' and $a <> '/') {
		$rep = (_DIR_RESTREINT ? '' : _DIR_RESTREINT_ABS) . $rep;
	}
	$rep = preg_replace(',(^\.\.\/),', '', $rep);

	return $rep;
}


/**
 * Débute ou arrête un chronomètre et retourne sa valeur
 *
 * On exécute 2 fois la fonction, la première fois pour démarrer le chrono,
 * la seconde fois pour l’arrêter et récupérer la valeur
 *
 * @example
 *     ```
 *     spip_timer('papoter');
 *     // actions
 *     $duree = spip_timer('papoter');
 *     ```
 *
 * @param string $t
 *     Nom du chronomètre
 * @param bool $raw
 *     - false : retour en texte humainement lisible
 *     - true : retour en millisecondes
 * @return float|int|string|void
 */
function spip_timer($t = 'rien', $raw = false) {
	static $time;
	$a = time();
	$b = microtime();
	// microtime peut contenir les microsecondes et le temps
	$b = explode(' ', $b);
	if (count($b) == 2) {
		$a = end($b);
	} // plus precis !
	$b = reset($b);
	if (!isset($time[$t])) {
		$time[$t] = $a + $b;
	} else {
		$p = ($a + $b - $time[$t]) * 1000;
		unset($time[$t]);
#			echo "'$p'";exit;
		if ($raw) {
			return $p;
		}
		if ($p < 1000) {
			$s = '';
		} else {
			$s = sprintf('%d ', $x = floor($p / 1000));
			$p -= ($x * 1000);
		}

		return $s . sprintf($s ? '%07.3f ms' : '%.3f ms', $p);
	}
}


// Renvoie False si un fichier n'est pas plus vieux que $duree secondes,
// sinon renvoie True et le date sauf si ca n'est pas souhaite
function spip_touch($fichier, $duree = 0, $touch = true) {
	if ($duree) {
		clearstatcache();
		if ((@$f = filemtime($fichier)) and ($f >= time() - $duree)) {
			return false;
		}
	}
	if ($touch !== false) {
		if (!@touch($fichier)) {
			spip_unlink($fichier);
			@touch($fichier);
		};
		@chmod($fichier, _SPIP_CHMOD & ~0111);
	}

	return true;
}


/**
 * Action qui déclenche une tache de fond
 *
 * @see  queue_affichage_cron()
 * @uses cron()
 **/
function action_cron() {
	include_spip('inc/headers');
	http_response_code(204); // No Content
	header('Connection: close');
	define('_DIRECT_CRON_FORCE', true);
	cron();
}

/**
 * Exécution des tâches de fond
 *
 * @uses inc_genie_dist()
 *
 * @param array $taches
 *     Tâches forcées
 * @param array $taches_old
 *     Tâches forcées, pour compat avec ancienne syntaxe
 * @return bool
 *     True si la tache a pu être effectuée
 */
function cron($taches = [], $taches_old = []) {
	// si pas en mode cron force, laisser tomber.
	if (!defined('_DIRECT_CRON_FORCE')) {
		return false;
	}
	if (!is_array($taches)) {
		$taches = $taches_old;
	} // compat anciens appels
	// si taches a inserer en base et base inaccessible, laisser tomber
	// sinon on ne verifie pas la connexion tout de suite, car si ca se trouve
	// queue_sleep_time_to_next_job() dira qu'il n'y a rien a faire
	// et on evite d'ouvrir une connexion pour rien (utilisation de _DIRECT_CRON_FORCE dans mes_options.php)
	if ($taches and count($taches) and !spip_connect()) {
		return false;
	}
	spip_log('cron !', 'jq' . _LOG_DEBUG);
	if ($genie = charger_fonction('genie', 'inc', true)) {
		return $genie($taches);
	}

	return false;
}

/**
 * Ajout d'une tache dans la file d'attente
 *
 * @param string $function
 *     Le nom de la fonction PHP qui doit être appelée.
 * @param string $description
 *     Une description humainement compréhensible de ce que fait la tâche
 *     (essentiellement pour l’affichage dans la page de suivi de l’espace privé)
 * @param array $arguments
 *     Facultatif, vide par défaut : les arguments qui seront passés à la fonction, sous forme de tableau PHP
 * @param string $file
 *     Facultatif, vide par défaut : nom du fichier à inclure, via `include_spip($file)`
 *     exemple : `'inc/mail'` : il ne faut pas indiquer .php
 *     Si le nom finit par un '/' alors on considère que c’est un répertoire et SPIP fera un `charger_fonction($function, $file)`
 * @param bool $no_duplicate
 *     Facultatif, `false` par défaut
 *
 *     - si `true` la tâche ne sera pas ajoutée si elle existe déjà en file d’attente avec la même fonction et les mêmes arguments.
 *     - si `function_only` la tâche ne sera pas ajoutée si elle existe déjà en file d’attente avec la même fonction indépendamment de ses arguments
 * @param int $time
 *     Facultatif, `0` par défaut : indique la date sous forme de timestamp à laquelle la tâche doit être programmée.
 *     Si `0` ou une date passée, la tâche sera exécutée aussitôt que possible (en général en fin hit, en asynchrone).
 * @param int $priority
 *     Facultatif, `0` par défaut : indique un niveau de priorité entre -10 et +10.
 *     Les tâches sont exécutées par ordre de priorité décroissante, une fois leur date d’exécution passée. La priorité est surtout utilisée quand une tâche cron indique qu’elle n’a pas fini et doit être relancée : dans ce cas SPIP réduit sa priorité pour être sûr que celle tâche ne monopolise pas la file d’attente.
 * @return int
 *     Le numéro de travail ajouté ou `0` si aucun travail n’a été ajouté.
 */
function job_queue_add(
	$function,
	$description,
	$arguments = [],
	$file = '',
	$no_duplicate = false,
	$time = 0,
	$priority = 0
) {
	include_spip('inc/queue');

	return queue_add_job($function, $description, $arguments, $file, $no_duplicate, $time, $priority);
}

/**
 * Supprimer une tache de la file d'attente
 *
 * @param int $id_job
 *  id of jonb to delete
 * @return bool
 */
function job_queue_remove($id_job) {
	include_spip('inc/queue');

	return queue_remove_job($id_job);
}

/**
 * Associer une tache a un/des objets de SPIP
 *
 * @param int $id_job
 *     id of job to link
 * @param array $objets
 *     can be a simple array('objet'=>'article', 'id_objet'=>23)
 *     or an array of simple array to link multiples objet in one time
 */
function job_queue_link($id_job, $objets) {
	include_spip('inc/queue');

	return queue_link_job($id_job, $objets);
}


/**
 * Renvoyer le temps de repos restant jusqu'au prochain job
 *
 * @staticvar int $queue_next_job_time
 * @see queue_set_next_job_time()
 * @param int|bool $force
 *    Utilisée par `queue_set_next_job_time()` pour mettre à jour la valeur :
 *
 *    - si `true`, force la relecture depuis le fichier
 *    - si int, affecte la static directement avec la valeur
 * @return int
 *
 *  - `0` si un job est à traiter
 *  - `null` si la queue n'est pas encore initialisée
 */
function queue_sleep_time_to_next_job($force = null) {
	static $queue_next_job_time = -1;
	if ($force === true) {
		$queue_next_job_time = -1;
	} elseif ($force) {
		$queue_next_job_time = $force;
	}

	if ($queue_next_job_time == -1) {
		if (!defined('_JQ_NEXT_JOB_TIME_FILENAME')) {
			define('_JQ_NEXT_JOB_TIME_FILENAME', _DIR_TMP . 'job_queue_next.txt');
		}
		// utiliser un cache memoire si dispo
		if (function_exists('cache_get') and defined('_MEMOIZE_MEMORY') and _MEMOIZE_MEMORY) {
			$queue_next_job_time = cache_get(_JQ_NEXT_JOB_TIME_FILENAME);
		} else {
			$queue_next_job_time = null;
			if (lire_fichier(_JQ_NEXT_JOB_TIME_FILENAME, $contenu)) {
				$queue_next_job_time = intval($contenu);
			}
		}
	}

	if (is_null($queue_next_job_time)) {
		return null;
	}
	if (!$_SERVER['REQUEST_TIME']) {
		$_SERVER['REQUEST_TIME'] = time();
	}

	return $queue_next_job_time - $_SERVER['REQUEST_TIME'];
}


/**
 * Transformation XML des `&` en `&amp;`
 *
 * @pipeline post_typo
 * @param string $u
 * @return string
 */
function quote_amp($u) {
	return preg_replace(
		'/&(?![a-z]{0,4}\w{2,3};|#x?[0-9a-f]{2,6};)/i',
		'&amp;',
		$u
	);
}


/**
 * Produit une balise `<script>` valide
 *
 * @example
 *     ```
 *     echo http_script('alert("ok");');
 *     echo http_script('','js/jquery.js');
 *     ```
 *
 * @param string $script
 *     Code source du script
 * @param string $src
 *     Permet de faire appel à un fichier javascript distant
 * @param string $noscript
 *     Contenu de la balise  `<noscript>`
 * @return string
 *     Balise HTML `<script>` et son contenu
 **/
function http_script($script, $src = '', $noscript = '') {
	static $done = [];

	if ($src && !isset($done[$src])) {
		$done[$src] = true;
		$src = find_in_path($src, _JAVASCRIPT);
		$src = " src='$src'";
	} else {
		$src = '';
	}
	if ($script) {
		$script = ("/*<![CDATA[*/\n" .
			preg_replace(',</([^>]*)>,', '<\/\1>', $script) .
			'/*]]>*/');
	}
	if ($noscript) {
		$noscript = "<noscript>\n\t$noscript\n</noscript>\n";
	}

	return ($src or $script or $noscript)
		? "<script type='text/javascript'$src>$script</script>$noscript"
		: '';
}


/**
 * Sécurise du texte à écrire dans du PHP ou du Javascript.
 *
 * Transforme n'importe quel texte en une chaîne utilisable
 * en PHP ou Javascript en toute sécurité, à l'intérieur d'apostrophes
 * simples (`'` uniquement ; pas `"`)
 *
 * Utile particulièrement en filtre dans un squelettes
 * pour écrire un contenu dans une variable JS ou PHP.
 *
 * Échappe les apostrophes (') du contenu transmis.
 *
 * @link https://www.spip.net/4281
 * @example
 *     PHP dans un squelette
 *     ```
 *     $x = '[(#TEXTE|texte_script)]';
 *     ```
 *
 *     JS dans un squelette (transmettre une chaîne de langue)
 *     ```
 *     $x = '<:afficher_calendrier|texte_script:>';
 *     ```
 *
 * @filtre
 * @param string $texte
 *     Texte à échapper
 * @return string
 *     Texte échappé
 **/
function texte_script(string $texte): string {
	return str_replace('\'', '\\\'', str_replace('\\', '\\\\', $texte));
}


/**
 * Gestion des chemins (ou path) de recherche de fichiers par SPIP
 *
 * Empile de nouveaux chemins (à la suite de ceux déjà présents, mais avant
 * le répertoire `squelettes` ou les dossiers squelettes), si un répertoire
 * (ou liste de répertoires séparés par `:`) lui est passé en paramètre.
 *
 * Ainsi, si l'argument est de la forme `dir1:dir2:dir3`, ces 3 chemins sont placés
 * en tête du path, dans cet ordre (hormis `squelettes` & la globale
 * `$dossier_squelette` si définie qui resteront devant)
 *
 * Retourne dans tous les cas la liste des chemins.
 *
 * @note
 *     Cette fonction est appelée à plusieurs endroits et crée une liste
 *     de chemins finale à peu près de la sorte :
 *
 *     - dossiers squelettes (si globale précisée)
 *     - squelettes/
 *     - plugins (en fonction de leurs dépendances) : ceux qui dépendent
 *       d'un plugin sont devant eux (ils peuvent surcharger leurs fichiers)
 *     - racine du site
 *     - squelettes-dist/
 *     - prive/
 *     - ecrire/
 *
 * @param string|array $dir_path
 *     - Répertoire(s) à empiler au path
 *     - '' provoque un recalcul des chemins.
 * @return array
 *     Liste des chemins, par ordre de priorité.
 **/
function _chemin($dir_path = null) {
	static $path_base = null;
	static $path_full = null;
	if ($path_base == null) {
		// Chemin standard depuis l'espace public
		$path = defined('_SPIP_PATH') ? _SPIP_PATH :
			_DIR_RACINE . ':' .
			_DIR_RACINE . 'squelettes-dist/:' .
			_DIR_RACINE . 'prive/:' .
			_DIR_RESTREINT;
		// Ajouter squelettes/
		if (@is_dir(_DIR_RACINE . 'squelettes')) {
			$path = _DIR_RACINE . 'squelettes/:' . $path;
		}
		foreach (explode(':', $path) as $dir) {
			if (strlen($dir) and substr($dir, -1) != '/') {
				$dir .= '/';
			}
			$path_base[] = $dir;
		}
		$path_full = $path_base;
		// Et le(s) dossier(s) des squelettes nommes
		if (strlen($GLOBALS['dossier_squelettes'])) {
			foreach (array_reverse(explode(':', $GLOBALS['dossier_squelettes'])) as $d) {
				array_unshift($path_full, ($d[0] == '/' ? '' : _DIR_RACINE) . $d . '/');
			}
		}
		$GLOBALS['path_sig'] = md5(serialize($path_full));
	}
	if ($dir_path === null) {
		return $path_full;
	}

	if (is_array($dir_path) or strlen($dir_path)) {
		$tete = '';
		if (reset($path_base) == _DIR_RACINE . 'squelettes/') {
			$tete = array_shift($path_base);
		}
		$dirs = (is_array($dir_path) ? $dir_path : explode(':', $dir_path));
		$dirs = array_reverse($dirs);
		foreach ($dirs as $dir_path) {
			if (substr($dir_path, -1) != '/') {
				$dir_path .= '/';
			}
			if (!in_array($dir_path, $path_base)) {
				array_unshift($path_base, $dir_path);
			}
		}
		if (strlen($tete)) {
			array_unshift($path_base, $tete);
		}
	}
	$path_full = $path_base;
	// Et le(s) dossier(s) des squelettes nommes
	if (strlen($GLOBALS['dossier_squelettes'])) {
		foreach (array_reverse(explode(':', $GLOBALS['dossier_squelettes'])) as $d) {
			array_unshift($path_full, ((isset($d[0]) and $d[0] == '/') ? '' : _DIR_RACINE) . $d . '/');
		}
	}

	$GLOBALS['path_sig'] = md5(serialize($path_full));

	return $path_full;
}

/**
 * Retourne la liste des chemins connus de SPIP, dans l'ordre de priorité
 *
 * Recalcule la liste si le nom ou liste de dossier squelettes a changé.
 *
 * @uses _chemin()
 *
 * @return array Liste de chemins
 **/
function creer_chemin() {
	$path_a = _chemin();
	static $c = '';

	// on calcule le chemin si le dossier skel a change
	if ($c != $GLOBALS['dossier_squelettes']) {
		// assurer le non plantage lors de la montee de version :
		$c = $GLOBALS['dossier_squelettes'];
		$path_a = _chemin(''); // forcer un recalcul du chemin
	}

	return $path_a;
}


function lister_themes_prives() {
	static $themes = null;
	if (is_null($themes)) {
		// si pas encore definie
		if (!defined('_SPIP_THEME_PRIVE')) {
			define('_SPIP_THEME_PRIVE', 'spip');
		}
		$themes = [_SPIP_THEME_PRIVE];
		// lors d'une installation neuve, prefs n'est pas definie.
		if (isset($GLOBALS['visiteur_session']['prefs'])) {
			$prefs = $GLOBALS['visiteur_session']['prefs'];
		} else {
			$prefs = [];
		}
		if (is_string($prefs)) {
			$prefs = unserialize($GLOBALS['visiteur_session']['prefs']);
		}
		if (
			((isset($prefs['theme']) and $theme = $prefs['theme'])
				or (isset($GLOBALS['theme_prive_defaut']) and $theme = $GLOBALS['theme_prive_defaut']))
			and $theme != _SPIP_THEME_PRIVE
		) {
			array_unshift($themes, $theme);
		} // placer le theme choisi en tete
	}

	return $themes;
}

function find_in_theme($file, $subdir = '', $include = false) {
	static $themefiles = [];
	if (isset($themefiles["$subdir$file"])) {
		return $themefiles["$subdir$file"];
	}
	// on peut fournir une icone generique -xx.svg qui fera le job dans toutes les tailles, et qui est prioritaire sur le png
	// si il y a un .svg a la bonne taille (-16.svg) a cote, on l'utilise en remplacement du -16.png
	if (
		preg_match(',-(\d+)[.](png|gif|svg)$,', $file, $m)
		and $file_svg_generique = substr($file, 0, -strlen($m[0])) . '-xx.svg'
		and $f = find_in_theme("$file_svg_generique")
	) {
		if ($fsize = substr($f, 0, -6) . $m[1] . '.svg' and file_exists($fsize)) {
			return $themefiles["$subdir$file"] = $fsize;
		}
		else {
			return $themefiles["$subdir$file"] = "$f?" . $m[1] . 'px';
		}
	}

	$themes = lister_themes_prives();
	foreach ($themes as $theme) {
		if ($f = find_in_path($file, "prive/themes/$theme/$subdir", $include)) {
			return $themefiles["$subdir$file"] = $f;
		}
	}
	spip_log("$file introuvable dans le theme prive " . reset($themes), 'theme');

	return $themefiles["$subdir$file"] = '';
}


/**
 * Cherche une image dans les dossiers d'images
 *
 * Cherche en priorité dans les thèmes d'image (prive/themes/X/images)
 * et si la fonction n'en trouve pas, gère le renommage des icones (ex: 'supprimer' => 'del')
 * de facon temporaire le temps de la migration, et cherche de nouveau.
 *
 * Si l'image n'est toujours pas trouvée, on la cherche dans les chemins,
 * dans le répertoire défini par la constante `_NOM_IMG_PACK`
 *
 * @see find_in_theme()
 * @see inc_icone_renommer_dist()
 *
 * @param string $icone
 *     Nom de l'icone cherchée
 * @return string
 *     Chemin complet de l'icone depuis la racine si l'icone est trouée,
 *     sinon chaîne vide.
 **/
function chemin_image($icone) {
	static $icone_renommer;
	if ($p = strpos($icone, '?')) {
		$icone = substr($icone, 0, $p);
	}
	// gerer le cas d'un double appel en evitant de refaire le travail inutilement
	if (strpos($icone, '/') !== false and file_exists($icone)) {
		return $icone;
	}

	// si c'est un nom d'image complet (article-24.png) essayer de le renvoyer direct
	if (preg_match(',[.](png|gif|jpg|webp|svg)$,', $icone) and $f = find_in_theme("images/$icone")) {
		return $f;
	}
	// sinon passer par le module de renommage
	if (is_null($icone_renommer)) {
		$icone_renommer = charger_fonction('icone_renommer', 'inc', true);
	}
	if ($icone_renommer) {
		[$icone, $fonction] = $icone_renommer($icone, '');
		if (file_exists($icone)) {
			return $icone;
		}
	}

	return find_in_path($icone, _NOM_IMG_PACK);
}

//
// chercher un fichier $file dans le SPIP_PATH
// si on donne un sous-repertoire en 2e arg optionnel, il FAUT le / final
// si 3e arg vrai, on inclut si ce n'est fait.
$GLOBALS['path_sig'] = '';
$GLOBALS['path_files'] = null;

/**
 * Recherche un fichier dans les chemins de SPIP (squelettes, plugins, core)
 *
 * Retournera le premier fichier trouvé (ayant la plus haute priorité donc),
 * suivant l'ordre des chemins connus de SPIP.
 *
 * @api
 * @see  charger_fonction()
 * @uses creer_chemin() Pour la liste des chemins.
 * @example
 *     ```
 *     $f = find_in_path('css/perso.css');
 *     $f = find_in_path('perso.css', 'css');
 *     ```
 *
 * @param string $file
 *     Fichier recherché
 * @param string $dirname
 *     Répertoire éventuel de recherche (est aussi extrait automatiquement de $file)
 * @param bool|string $include
 *     - false : ne fait rien de plus
 *     - true : inclut le fichier (include_once)
 *     - 'require' : idem, mais tue le script avec une erreur si le fichier n'est pas trouvé.
 * @return string|bool
 *     - string : chemin du fichier trouvé
 *     - false : fichier introuvable
 **/
function find_in_path($file, $dirname = '', $include = false) {
	static $dirs = [];
	static $inc = []; # cf https://git.spip.net/spip/spip/commit/42e4e028e38c839121efaee84308d08aee307eec
	static $c = '';

	if (!$file and !strlen($file)) {
		return false;
	}

	// on calcule le chemin si le dossier skel a change
	if ($c != $GLOBALS['dossier_squelettes']) {
		// assurer le non plantage lors de la montee de version :
		$c = $GLOBALS['dossier_squelettes'];
		creer_chemin(); // forcer un recalcul du chemin et la mise a jour de path_sig
	}

	if (isset($GLOBALS['path_files'][$GLOBALS['path_sig']][$dirname][$file])) {
		if (!$GLOBALS['path_files'][$GLOBALS['path_sig']][$dirname][$file]) {
			return false;
		}
		if ($include and !isset($inc[$dirname][$file])) {
			include_once _ROOT_CWD . $GLOBALS['path_files'][$GLOBALS['path_sig']][$dirname][$file];
			$inc[$dirname][$file] = $inc[''][$dirname . $file] = true;
		}

		return $GLOBALS['path_files'][$GLOBALS['path_sig']][$dirname][$file];
	}

	$a = strrpos($file, '/');
	if ($a !== false) {
		$dirname .= substr($file, 0, ++$a);
		$file = substr($file, $a);
	}

	foreach (creer_chemin() as $dir) {
		if (!isset($dirs[$a = $dir . $dirname])) {
			$dirs[$a] = (is_dir(_ROOT_CWD . $a) || !$a);
		}
		if ($dirs[$a]) {
			if (file_exists(_ROOT_CWD . ($a .= $file))) {
				if ($include and !isset($inc[$dirname][$file])) {
					include_once _ROOT_CWD . $a;
					$inc[$dirname][$file] = $inc[''][$dirname . $file] = true;
				}
				if (!defined('_SAUVER_CHEMIN')) {
					// si le chemin n'a pas encore ete charge, ne pas lever le flag, ne pas cacher
					if (is_null($GLOBALS['path_files'])) {
						return $a;
					}
					define('_SAUVER_CHEMIN', true);
				}

				return $GLOBALS['path_files'][$GLOBALS['path_sig']][$dirname][$file] = $GLOBALS['path_files'][$GLOBALS['path_sig']][''][$dirname . $file] = $a;
			}
		}
	}

	if ($include) {
		spip_log("include_spip $dirname$file non trouve");
		if ($include === 'required') {
			echo '<pre>',
			'<strong>Erreur Fatale</strong><br />';
			if (function_exists('debug_print_backtrace')) {
				echo debug_print_backtrace();
			}
			echo '</pre>';
			die("Erreur interne: ne peut inclure $dirname$file");
		}
	}

	if (!defined('_SAUVER_CHEMIN')) {
		// si le chemin n'a pas encore ete charge, ne pas lever le flag, ne pas cacher
		if (is_null($GLOBALS['path_files'])) {
			return false;
		}
		define('_SAUVER_CHEMIN', true);
	}

	return $GLOBALS['path_files'][$GLOBALS['path_sig']][$dirname][$file] = $GLOBALS['path_files'][$GLOBALS['path_sig']][''][$dirname . $file] = false;
}

function clear_path_cache() {
	$GLOBALS['path_files'] = [];
	spip_unlink(_CACHE_CHEMIN);
}

function load_path_cache() {
	// charger le path des plugins
	if (@is_readable(_CACHE_PLUGINS_PATH)) {
		include_once(_CACHE_PLUGINS_PATH);
	}
	$GLOBALS['path_files'] = [];
	// si le visiteur est admin,
	// on ne recharge pas le cache pour forcer sa mise a jour
	if (
		// la session n'est pas encore chargee a ce moment, on ne peut donc pas s'y fier
		//AND (!isset($GLOBALS['visiteur_session']['statut']) OR $GLOBALS['visiteur_session']['statut']!='0minirezo')
		// utiliser le cookie est un pis aller qui marche 'en general'
		// on blinde par un second test au moment de la lecture de la session
		// !isset($_COOKIE[$GLOBALS['cookie_prefix'].'_admin'])
		// et en ignorant ce cache en cas de recalcul explicite
		!_request('var_mode')
	) {
		// on essaye de lire directement sans verrou pour aller plus vite
		if ($contenu = spip_file_get_contents(_CACHE_CHEMIN)) {
			// mais si semble corrompu on relit avec un verrou
			if (!$GLOBALS['path_files'] = unserialize($contenu)) {
				lire_fichier(_CACHE_CHEMIN, $contenu);
				if (!$GLOBALS['path_files'] = unserialize($contenu)) {
					$GLOBALS['path_files'] = [];
				}
			}
		}
	}
}

function save_path_cache() {
	if (
		defined('_SAUVER_CHEMIN')
		and _SAUVER_CHEMIN
	) {
		ecrire_fichier(_CACHE_CHEMIN, serialize($GLOBALS['path_files']));
	}
}


/**
 * Trouve tous les fichiers du path correspondants à un pattern
 *
 * Pour un nom de fichier donné, ne retourne que le premier qui sera trouvé
 * par un `find_in_path()`
 *
 * @api
 * @uses creer_chemin()
 * @uses preg_files()
 *
 * @param string $dir
 * @param string $pattern
 * @param bool $recurs
 * @return array
 */
function find_all_in_path($dir, $pattern, $recurs = false) {
	$liste_fichiers = [];
	$maxfiles = 10000;

	// cas borderline si dans mes_options on appelle redirige_par_entete qui utilise _T et charge un fichier de langue
	// on a pas encore inclus flock.php
	if (!function_exists('preg_files')) {
		include_once _ROOT_RESTREINT . 'inc/flock.php';
	}

	// Parcourir le chemin
	foreach (creer_chemin() as $d) {
		$f = $d . $dir;
		if (@is_dir($f)) {
			$liste = preg_files($f, $pattern, $maxfiles - count($liste_fichiers), $recurs === true ? [] : $recurs);
			foreach ($liste as $chemin) {
				$nom = basename($chemin);
				// ne prendre que les fichiers pas deja trouves
				// car find_in_path prend le premier qu'il trouve,
				// les autres sont donc masques
				if (!isset($liste_fichiers[$nom])) {
					$liste_fichiers[$nom] = $chemin;
				}
			}
		}
	}

	return $liste_fichiers;
}

/**
 * Prédicat sur les scripts de ecrire qui n'authentifient pas par cookie
 * et beneficient d'une exception
 *
 * @param string $nom
 * @param bool $strict
 * @return bool
 */
function autoriser_sans_cookie($nom, $strict = false) {
	static $autsanscookie = ['install', 'base_repair'];

	if (in_array($nom, $autsanscookie)) {
		if (test_espace_prive()) {
			include_spip('base/connect_sql');
			if (!$strict or !spip_connect()) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Charger la fonction de gestion des urls si elle existe
 * @param $quoi : 'page' 'objet' 'decoder' ou objet spip pour lequel on cherche la fonction url par defaut (si type==='defaut')
 * @param $type : type des urls (par defaut la meta type_urls) ou 'defaut' pour trouver la fonction par defaut d'un type d'objet
 * @return string
 */
function charger_fonction_url(string $quoi, string $type = '') {
	if ($type === 'defaut') {
		$objet = objet_type($quoi);
		if (
			$f = charger_fonction('generer_' . $objet . '_url', 'urls', true)
			// deprecated
			or $f = charger_fonction('generer_url_' . $objet, 'urls', true)
		) {
			return $f;
		}
		return '';
	}

	$url_type = $type;
	if (!$url_type) {
		$url_type = $GLOBALS['type_urls'] ?? $GLOBALS['meta']['type_urls'] ?? 'page'; // sinon type "page" par défaut
	}

	// inclure le module d'url
	include_spip('urls/' . $url_type);

	switch ($quoi) {
		case 'page':
			if (
				 function_exists($f = "urls_{$url_type}_generer_url_page")
				or function_exists($f .= '_dist')
				// ou une fonction custom utilisateur independante du type d'url
				or function_exists($f = 'generer_url_page')
				or function_exists($f .= '_dist')
			) {
				return $f;
			}
			// pas de compat ancienne version ici, c'est une nouvelle feature
			return '';
		case 'objet':
		case 'decoder':
		default:
			$fquoi = ($quoi === 'objet' ? 'generer_url_objet' : 'decoder_url');
			if (
				function_exists($f = "urls_{$url_type}_{$fquoi}")
				or function_exists($f .= '_dist')
			) {
				return $f;
			}
			// est-ce qu'on a une ancienne fonction urls_xxx_dist() ?
			// c'est un ancien module d'url, on appelle l'ancienne fonction qui fait tout
			if ($f = charger_fonction($url_type, 'urls', true)) {
				return $f;
			}
			// sinon on se rabat sur les urls page si ce n'est pas un type demande explicitement
			if (!$type and $url_type !== 'page') {
				return charger_fonction_url($quoi, 'page');
			}
			return '';
	}
}


/**
 * Fonction codant  les URLs des objets SQL mis en page par SPIP
 *
 * @api
 * @param int|string|null $id
 *   numero de la cle primaire si nombre
 * @param string $entite
 *   surnom de la table SQL (donne acces au nom de cle primaire)
 * @param string $args
 *   query_string a placer apres cle=$id&....
 * @param string $ancre
 *   ancre a mettre a la fin de l'URL a produire
 * @param ?bool $public
 *   produire l'URL publique ou privee (par defaut: selon espace)
 *   si string : serveur de base de donnee (nom du connect)
 * @param string $type
 *   fichier dans le repertoire ecrire/urls determinant l'apparence
 * @param string $connect
 *   fichier dans le repertoire ecrire/urls determinant l'apparence
 * @return string
 *   url codee ou fonction de decodage
 *   array : derogatoire, la fonction d'url retourne (objet,id_objet) utilises par nettoyer_raccourcis_typo() pour generer un lien titre
 *           (cas des raccourcis personalises [->spip20] : il faut implementer une fonction generer_spip_url et une fonction generer_spip_url_ecrire)
 */
function generer_objet_url($id, string $entite, string $args = '', string $ancre = '', ?bool $public = null, string $type = '', string $connect = ''): string {
	if ($public === null) {
		$public = !test_espace_prive();
	}
	$id = intval($id);
	$entite = objet_type($entite); // cas particulier d'appels sur objet/id_objet...

	if (!$public) {
		if (!$entite) {
			return '';
		}
		if (!function_exists('generer_objet_url_ecrire')) {
			include_spip('inc/urls');
		}
		$res = generer_objet_url_ecrire($id, $entite, $args, $ancre, false, $connect);
	} else {
		$f = charger_fonction_url('objet', $type ?? '');

		// @deprecated si $entite='', on veut la fonction de passage URL ==> id
		// @see charger_fonction_url
		if (!$entite) {
			return $f;
		}

		// mais d'abord il faut tester le cas des urls sur une
		// base distante
		if (
			$connect
			and $g = charger_fonction('connect', 'urls', true)
		) {
			$f = $g;
		}

		$res = $f(intval($id), $entite, $args ?: '', $ancre ?: '', $connect);
	}
	if ($res) {
		return $res;
	}

	// On a ete gentil mais la ....
	spip_log("generer_objet_url: entite $entite ($f) inconnue $type $public $connect", _LOG_ERREUR);

	return '';
}

/**
 * @deprecated 4.1
 * @see generer_objet_url
 */
function generer_url_entite($id = 0, $entite = '', $args = '', $ancre = '', $public = null, $type = null) {
	if ($public and is_string($public)) {
		return generer_objet_url(intval($id), $entite, $args ?: '', $ancre ?: '', true, $type ?? '', $public);
	}
	return generer_objet_url(intval($id), $entite, $args ?: '', $ancre ?: '', $public, $type ?? '');
}

/**
 * Generer l'url vers la page d'edition dans ecrire/
 * @param int|string|null $id
 * @param string $entite
 * @param string $args
 * @param string $ancre
 * @return string
 */
function generer_objet_url_ecrire_edit($id, string $entite, string $args = '', string $ancre = ''): string {
	$id = intval($id);
	$exec = objet_info($entite, 'url_edit');
	$url = generer_url_ecrire($exec, $args);
	if (intval($id)) {
		$url = parametre_url($url, id_table_objet($entite), $id);
	} else {
		$url = parametre_url($url, 'new', 'oui');
	}
	if ($ancre) {
		$url = ancre_url($url, $ancre);
	}

	return $url;
}

/**
 * @deprecated 4.1
 * @see generer_objet_url_ecrire_edit
 */
function generer_url_ecrire_entite_edit($id, $entite, $args = '', $ancre = '') {
	return generer_objet_url_ecrire_edit(intval($id), $entite, $args, $ancre);
}


function urls_connect_dist($i, &$entite, $args = '', $ancre = '', $public = null) {
	include_spip('base/connect_sql');
	$id_type = id_table_objet($entite, $public);

	return _DIR_RACINE . get_spip_script('./')
	. '?' . _SPIP_PAGE . "=$entite&$id_type=$i&connect=$public"
	. (!$args ? '' : "&$args")
	. (!$ancre ? '' : "#$ancre");
}


/**
 * Transformer les caractères utf8 d'une URL (farsi par exemple) selon la RFC 1738
 *
 * @param string $url
 * @return string
 */
function urlencode_1738($url) {
	if (preg_match(',[^\x00-\x7E],sS', $url)) {
		$uri = '';
		for ($i = 0; $i < strlen($url); $i++) {
			if (ord($a = $url[$i]) > 127) {
				$a = rawurlencode($a);
			}
			$uri .= $a;
		}
		$url = $uri;
	}

	return quote_amp($url);
}

/**
 * Generer l'url absolue vers un objet
 *
 * @param int|string|null $id
 * @param string $entite
 * @param string $args
 * @param string $ancre
 * @param ?bool $public
 * @param string $type
 * @param string $connect
 * @return string
 */
function generer_objet_url_absolue($id = 0, string $entite = '', string $args = '', string $ancre = '', ?bool $public = null, string $type = '', string $connect = ''): string {
	$id = intval($id);
	$h = generer_objet_url($id, $entite, $args, $ancre, $public, $type, $connect);
	if (!preg_match(',^\w+:,', $h)) {
		include_spip('inc/filtres_mini');
		$h = url_absolue($h);
	}

	return $h;
}

/**
 * @deprecated 4.1
 * @see  generer_objet_url_absolue
 */
function generer_url_entite_absolue($id = 0, $entite = '', $args = '', $ancre = '', $connect = null) {
	return generer_objet_url_absolue(intval($id), $entite, $args, $ancre, true, '', $connect);
}


/**
 * Tester qu'une variable d'environnement est active
 *
 * Sur certains serveurs, la valeur 'Off' tient lieu de false dans certaines
 * variables d'environnement comme `$_SERVER['HTTPS']` ou `ini_get('display_errors')`
 *
 * @param string|bool $truc
 *     La valeur de la variable d'environnement
 * @return bool
 *     true si la valeur est considérée active ; false sinon.
 **/
function test_valeur_serveur($truc) {
	if (!$truc) {
		return false;
	}

	return (strtolower($truc) !== 'off');
}

//
// Fonctions de fabrication des URL des scripts de Spip
//
/**
 * Calcule l'url de base du site
 *
 * Calcule l'URL de base du site, en priorité sans se fier à la méta (adresse_site) qui
 * peut être fausse (sites avec plusieurs noms d’hôtes, déplacements, erreurs).
 * En dernier recours, lorsqu'on ne trouve rien, on utilise adresse_site comme fallback.
 *
 * @note
 *     La globale `$profondeur_url` doit être initialisée de manière à
 *     indiquer le nombre de sous-répertoires de l'url courante par rapport à la
 *     racine de SPIP : par exemple, sur ecrire/ elle vaut 1, sur sedna/ 1, et à
 *     la racine 0. Sur url/perso/ elle vaut 2
 *
 * @param int|boo|array $profondeur
 *    - si non renseignée : retourne l'url pour la profondeur $GLOBALS['profondeur_url']
 *    - si int : indique que l'on veut l'url pour la profondeur indiquée
 *    - si bool : retourne le tableau static complet
 *    - si array : réinitialise le tableau static complet avec la valeur fournie
 * @return string|array
 */
function url_de_base($profondeur = null) {

	static $url = [];
	if (is_array($profondeur)) {
		return $url = $profondeur;
	}
	if ($profondeur === false) {
		return $url;
	}

	if (is_null($profondeur)) {
		$profondeur = $GLOBALS['profondeur_url'] ?? (_DIR_RESTREINT ? 0 : 1);
	}

	if (isset($url[$profondeur])) {
		return $url[$profondeur];
	}

	$http = 'http';

	if (
		isset($_SERVER['SCRIPT_URI'])
		and substr($_SERVER['SCRIPT_URI'], 0, 5) == 'https'
	) {
		$http = 'https';
	} elseif (
		isset($_SERVER['HTTPS'])
		and test_valeur_serveur($_SERVER['HTTPS'])
	) {
		$http = 'https';
	}

	// note : HTTP_HOST contient le :port si necessaire
	if ($host = $_SERVER['HTTP_HOST'] ?? null) {
		// Filtrer $host pour proteger d'attaques d'entete HTTP
		$host = (filter_var($host, FILTER_SANITIZE_URL) ?: null);
	}

	// si on n'a pas trouvé d'hôte du tout, en dernier recours on utilise adresse_site comme fallback
	if (is_null($host) and isset($GLOBALS['meta']['adresse_site'])) {
		$host = $GLOBALS['meta']['adresse_site'];
		if ($scheme = parse_url($host, PHP_URL_SCHEME)) {
			$http = $scheme;
			$host = str_replace("{$scheme}://", '', $host);
		}
	}
	if (
		isset($_SERVER['SERVER_PORT'])
		and $port = $_SERVER['SERVER_PORT']
		and strpos($host, ':') == false
	) {
		if (!defined('_PORT_HTTP_STANDARD')) {
			define('_PORT_HTTP_STANDARD', '80');
		}
		if (!defined('_PORT_HTTPS_STANDARD')) {
			define('_PORT_HTTPS_STANDARD', '443');
		}
		if ($http == 'http' and !in_array($port, explode(',', _PORT_HTTP_STANDARD))) {
			$host .= ":$port";
		}
		if ($http == 'https' and !in_array($port, explode(',', _PORT_HTTPS_STANDARD))) {
			$host .= ":$port";
		}
	}

	if (!$GLOBALS['REQUEST_URI']) {
		if (isset($_SERVER['REQUEST_URI'])) {
			$GLOBALS['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
		} else {
			$GLOBALS['REQUEST_URI'] = (php_sapi_name() !== 'cli') ? $_SERVER['PHP_SELF'] : '';
			if (
				!empty($_SERVER['QUERY_STRING'])
				and !strpos($_SERVER['REQUEST_URI'], '?')
			) {
				$GLOBALS['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
			}
		}
	}

	// Et nettoyer l'url
	$GLOBALS['REQUEST_URL'] = (filter_var($GLOBALS['REQUEST_URL'], FILTER_SANITIZE_URL) ?: '');

	$url[$profondeur] = url_de_($http, $host, $GLOBALS['REQUEST_URI'], $profondeur);

	return $url[$profondeur];
}

/**
 * fonction testable de construction d'une url appelee par url_de_base()
 *
 * @param string $http
 * @param string $host
 * @param string $request
 * @param int $prof
 * @return string
 */
function url_de_($http, $host, $request, $prof = 0) {
	$prof = max($prof, 0);

	$myself = ltrim($request, '/');
	# supprimer la chaine de GET
	[$myself] = explode('?', $myself);
	// vieux mode HTTP qui envoie après le nom de la methode l'URL compléte
	// protocole, "://", nom du serveur avant le path dans _SERVER["REQUEST_URI"]
	if (strpos($myself, '://') !== false) {
		$myself = explode('://', $myself);
		array_shift($myself);
		$myself = implode('://', $myself);
		$myself = explode('/', $myself);
		array_shift($myself);
		$myself = implode('/', $myself);
	}
	$url = join('/', array_slice(explode('/', $myself), 0, -1 - $prof)) . '/';

	$url = $http . '://' . rtrim($host, '/') . '/' . ltrim($url, '/');

	return $url;
}


// Pour une redirection, la liste des arguments doit etre separee par "&"
// Pour du code XHTML, ca doit etre &amp;
// Bravo au W3C qui n'a pas ete capable de nous eviter ca
// faute de separer proprement langage et meta-langage

// Attention, X?y=z et "X/?y=z" sont completement differents!
// http://httpd.apache.org/docs/2.0/mod/mod_dir.html

/**
 * Crée une URL vers un script de l'espace privé
 *
 * @example
 *     ```
 *     generer_url_ecrire('admin_plugin')
 *     ```
 *
 * @param string $script
 *     Nom de la page privée (xx dans exec=xx)
 * @param string $args
 *     Arguments à transmettre, tel que `arg1=yy&arg2=zz`
 * @param bool $no_entities
 *     Si false : transforme les `&` en `&amp;`
 * @param bool|string $rel
 *     URL relative ?
 *
 *     - false : l’URL sera complète et contiendra l’URL du site
 *     - true : l’URL sera relavive.
 *     - string : on transmet l'url à la fonction
 * @return string URL
 **/
function generer_url_ecrire(?string $script = '', $args = '', $no_entities = false, $rel = false) {
	$script ??= '';
	if (!$rel) {
		$rel = url_de_base() . _DIR_RESTREINT_ABS . _SPIP_ECRIRE_SCRIPT;
	} else {
		if (!is_string($rel)) {
			$rel = _DIR_RESTREINT ?: './' . _SPIP_ECRIRE_SCRIPT;
		}
	}

	[$script, $ancre] = array_pad(explode('#', $script), 2, null);
	if ($script and ($script <> 'accueil' or $rel)) {
		$args = "?exec=$script" . (!$args ? '' : "&$args");
	} elseif ($args) {
		$args = "?$args";
	}
	if ($ancre) {
		$args .= "#$ancre";
	}

	return $rel . ($no_entities ? $args : str_replace('&', '&amp;', $args));
}

//
// Adresse des scripts publics (a passer dans inc-urls...)
//


/**
 * Retourne le nom du fichier d'exécution de SPIP
 *
 * @see _SPIP_SCRIPT
 * @note
 *   Detecter le fichier de base, a la racine, comme etant spip.php ou ''
 *   dans le cas de '', un $default = './' peut servir (comme dans urls/page.php)
 *
 * @param string $default
 *     Script par défaut
 * @return string
 *     Nom du fichier (constante _SPIP_SCRIPT), sinon nom par défaut
 **/
function get_spip_script($default = '') {
	if (!defined('_SPIP_SCRIPT')) {
		return 'spip.php';
	}
	# cas define('_SPIP_SCRIPT', '');
	if (_SPIP_SCRIPT) {
		return _SPIP_SCRIPT;
	} else {
		return $default;
	}
}

/**
 * Crée une URL vers une page publique de SPIP
 *
 * @example
 *     ```
 *     generer_url_public("rubrique","id_rubrique=$id_rubrique")
 *     ```
 *
 * @param string $script
 *     Nom de la page
 * @param string|array $args
 *     Arguments à transmettre a l'URL,
 *      soit sous la forme d'un string tel que `arg1=yy&arg2=zz`
 *      soit sous la forme d'un array tel que array( `arg1` => `yy`, `arg2` => `zz` )
 * @param bool $no_entities
 *     Si false : transforme les `&` en `&amp;`
 * @param bool $rel
 *     URL relative ?
 *
 *     - false : l’URL sera complète et contiendra l’URL du site
 *     - true : l’URL sera relavive.
 * @param string $action
 *     - Fichier d'exécution public (spip.php par défaut)
 * @return string URL
 **/
function generer_url_public($script = '', $args = '', $no_entities = false, $rel = true, $action = '') {
	// si le script est une action (spip_pass, spip_inscription),
	// standardiser vers la nouvelle API

	if (is_array($args)) {
		$args = http_build_query($args);
	}

	$url = '';
	if ($f = charger_fonction_url('page')) {
		$url = $f($script, $args);
		if ($url and !$rel) {
			include_spip('inc/filtres_mini');
			$url = url_absolue($url);
		}
	}
	if (!$url) {
		if (!$action) {
			$action = get_spip_script();
		}
		if ($script) {
			$action = parametre_url($action, _SPIP_PAGE, $script, '&');
		}
		if ($args) {
			$action .= (strpos($action, '?') !== false ? '&' : '?') . $args;
		}
		// ne pas generer une url avec /./?page= en cas d'url absolue et de _SPIP_SCRIPT vide
		$url = ($rel ? _DIR_RACINE . $action : rtrim(url_de_base(), '/') . preg_replace(',^/[.]/,', '/', "/$action"));
	}

	if (!$no_entities) {
		$url = quote_amp($url);
	}

	return $url;
}

function generer_url_prive($script, $args = '', $no_entities = false) {

	return generer_url_public($script, $args, $no_entities, false, _DIR_RESTREINT_ABS . 'prive.php');
}

// Pour les formulaires en methode POST,
// mettre le nom du script a la fois en input-hidden et dans le champ action:
// 1) on peut ainsi memoriser le signet comme si c'etait un GET
// 2) ca suit http://en.wikipedia.org/wiki/Representational_State_Transfer

/**
 * Retourne un formulaire (POST par défaut) vers un script exec
 * de l’interface privée
 *
 * @param string $script
 *     Nom de la page exec
 * @param string $corps
 *     Contenu du formulaire
 * @param string $atts
 *     Si présent, remplace les arguments par défaut (method=post) par ceux indiqués
 * @param string $submit
 *     Si indiqué, un bouton de soumission est créé avec texte sa valeur.
 * @return string
 *     Code HTML du formulaire
 **/
function generer_form_ecrire($script, $corps, $atts = '', $submit = '') {

	$script1 = explode('&', $script);
	$script1 = reset($script1);

	return "<form action='"
	. ($script ? generer_url_ecrire($script) : '')
	. "' "
	. ($atts ?: " method='post'")
	. "><div>\n"
	. "<input type='hidden' name='exec' value='$script1' />"
	. $corps
	. (!$submit ? '' :
		("<div style='text-align: " . $GLOBALS['spip_lang_right'] . "'><input class='fondo submit btn' type='submit' value=\"" . entites_html($submit) . '" /></div>'))
	. "</div></form>\n";
}

/**
 * Générer un formulaire pour lancer une action vers $script
 *
 * Attention, JS/Ajax n'aime pas le melange de param GET/POST
 * On n'applique pas la recommandation ci-dessus pour les scripts publics
 * qui ne sont pas destines a etre mis en signets
 *
 * @param string $script
 * @param string $corps
 * @param string $atts
 * @param bool $public
 * @return string
 */
function generer_form_action($script, $corps, $atts = '', $public = false) {
	// si l'on est dans l'espace prive, on garde dans l'url
	// l'exec a l'origine de l'action, qui permet de savoir si il est necessaire
	// ou non de proceder a l'authentification (cas typique de l'install par exemple)
	$h = (_DIR_RACINE and !$public)
		? generer_url_ecrire(_request('exec'))
		: generer_url_public();

	return "\n<form action='" .
	$h .
	"'" .
	$atts .
	">\n" .
	'<div>' .
	"\n<input type='hidden' name='action' value='$script' />" .
	$corps .
	'</div></form>';
}

/**
 * Créer une URL
 *
 * @param  string $script
 *     Nom du script à exécuter
 * @param  string $args
 *     Arguments à transmettre a l'URL sous la forme `arg1=yy&arg2=zz`
 * @param bool $no_entities
 *     Si false : transforme les & en &amp;
 * @param boolean $public
 *     URL relative ? false : l’URL sera complète et contiendra l’URL du site.
 *     true : l’URL sera relative.
 * @return string
 *     URL
 */
function generer_url_action($script, $args = '', $no_entities = false, $public = false) {
	// si l'on est dans l'espace prive, on garde dans l'url
	// l'exec a l'origine de l'action, qui permet de savoir si il est necessaire
	// ou non de proceder a l'authentification (cas typique de l'install par exemple)
	$url = (_DIR_RACINE and !$public)
		? generer_url_ecrire(_request('exec'))
		: generer_url_public('', '', false, false);
	$url = parametre_url($url, 'action', $script);
	if ($args) {
		$url .= quote_amp('&' . $args);
	}

	if ($no_entities) {
		$url = str_replace('&amp;', '&', $url);
	}

	return $url;
}


/**
 * Créer une URL
 *
 * @param  string $script
 *     Nom du script à exécuter
 * @param  string $args
 *     Arguments à transmettre a l'URL sous la forme `arg1=yy&arg2=zz`
 * @param bool $no_entities
 *     Si false : transforme les & en &amp;
 * @param boolean $public
 *     URL public ou relative a l'espace ou l'on est ?
 * @return string
 *     URL
 */
function generer_url_api(string $script, string $path, string $args, bool $no_entities = false, ?bool $public = null) {
	if (is_null($public)) {
		$public = (_DIR_RACINE ? false : '');
	}
	if (substr($script, -4) !== '.api') {
		$script .= '.api';
	}
	$url =
		(($public ? _DIR_RACINE : _DIR_RESTREINT) ?: './')
	. $script . '/'
	. ($path ? trim($path, '/') : '')
	. ($args ? '?' . quote_amp($args) : '');

	if ($no_entities) {
		$url = str_replace('&amp;', '&', $url);
	}

	return $url;
}


/**
 * Fonction d'initialisation groupée pour compatibilité ascendante
 *
 * @param string $pi Répertoire permanent inaccessible
 * @param string $pa Répertoire permanent accessible
 * @param string $ti Répertoire temporaire inaccessible
 * @param string $ta Répertoire temporaire accessible
 */
function spip_initialisation($pi = null, $pa = null, $ti = null, $ta = null) {
	spip_initialisation_core($pi, $pa, $ti, $ta);
	spip_initialisation_suite();
}

/**
 * Fonction d'initialisation, appellée dans inc_version ou mes_options
 *
 * Elle définit les répertoires et fichiers non partageables
 * et indique dans $test_dirs ceux devant être accessibles en écriture
 * mais ne touche pas à cette variable si elle est déjà définie
 * afin que mes_options.php puisse en spécifier d'autres.
 *
 * Elle définit ensuite les noms des fichiers et les droits.
 * Puis simule un register_global=on sécurisé.
 *
 * @param string $pi Répertoire permanent inaccessible
 * @param string $pa Répertoire permanent accessible
 * @param string $ti Répertoire temporaire inaccessible
 * @param string $ta Répertoire temporaire accessible
 */
function spip_initialisation_core($pi = null, $pa = null, $ti = null, $ta = null) {
	static $too_late = 0;
	if ($too_late++) {
		return;
	}

	// Declaration des repertoires

	// le nom du repertoire plugins/ activables/desactivables
	if (!defined('_DIR_PLUGINS')) {
		define('_DIR_PLUGINS', _DIR_RACINE . 'plugins/');
	}

	// le nom du repertoire des extensions/ permanentes du core, toujours actives
	if (!defined('_DIR_PLUGINS_DIST')) {
		define('_DIR_PLUGINS_DIST', _DIR_RACINE . 'plugins-dist/');
	}

	// le nom du repertoire des librairies
	if (!defined('_DIR_LIB')) {
		define('_DIR_LIB', _DIR_RACINE . 'lib/');
	}

	if (!defined('_DIR_IMG')) {
		define('_DIR_IMG', $pa);
	}
	if (!defined('_DIR_LOGOS')) {
		define('_DIR_LOGOS', $pa);
	}
	if (!defined('_DIR_IMG_ICONES')) {
		define('_DIR_IMG_ICONES', _DIR_LOGOS . 'icones/');
	}

	if (!defined('_DIR_DUMP')) {
		define('_DIR_DUMP', $ti . 'dump/');
	}
	if (!defined('_DIR_SESSIONS')) {
		define('_DIR_SESSIONS', $ti . 'sessions/');
	}
	if (!defined('_DIR_TRANSFERT')) {
		define('_DIR_TRANSFERT', $ti . 'upload/');
	}
	if (!defined('_DIR_CACHE')) {
		define('_DIR_CACHE', $ti . 'cache/');
	}
	if (!defined('_DIR_CACHE_XML')) {
		define('_DIR_CACHE_XML', _DIR_CACHE . 'xml/');
	}
	if (!defined('_DIR_SKELS')) {
		define('_DIR_SKELS', _DIR_CACHE . 'skel/');
	}
	if (!defined('_DIR_AIDE')) {
		define('_DIR_AIDE', _DIR_CACHE . 'aide/');
	}
	if (!defined('_DIR_TMP')) {
		define('_DIR_TMP', $ti);
	}

	if (!defined('_DIR_VAR')) {
		define('_DIR_VAR', $ta);
	}

	if (!defined('_DIR_ETC')) {
		define('_DIR_ETC', $pi);
	}
	if (!defined('_DIR_CONNECT')) {
		define('_DIR_CONNECT', $pi);
	}
	if (!defined('_DIR_CHMOD')) {
		define('_DIR_CHMOD', $pi);
	}

	if (!isset($GLOBALS['test_dirs'])) {
		// Pas $pi car il est bon de le mettre hors ecriture apres intstall
		// il sera rajoute automatiquement si besoin a l'etape 2 de l'install
	$GLOBALS['test_dirs'] = [$pa, $ti, $ta];
	}

	// Declaration des fichiers

	if (!defined('_CACHE_PLUGINS_PATH')) {
		define('_CACHE_PLUGINS_PATH', _DIR_CACHE . 'charger_plugins_chemins.php');
	}
	if (!defined('_CACHE_PLUGINS_OPT')) {
		define('_CACHE_PLUGINS_OPT', _DIR_CACHE . 'charger_plugins_options.php');
	}
	if (!defined('_CACHE_PLUGINS_FCT')) {
		define('_CACHE_PLUGINS_FCT', _DIR_CACHE . 'charger_plugins_fonctions.php');
	}
	if (!defined('_CACHE_PIPELINES')) {
		define('_CACHE_PIPELINES', _DIR_CACHE . 'charger_pipelines.php');
	}
	if (!defined('_CACHE_CHEMIN')) {
		define('_CACHE_CHEMIN', _DIR_CACHE . 'chemin.txt');
	}

	# attention .php obligatoire pour ecrire_fichier_securise
	if (!defined('_FILE_META')) {
		define('_FILE_META', $ti . 'meta_cache.php');
	}
	if (!defined('_DIR_LOG')) {
		define('_DIR_LOG', _DIR_TMP . 'log/');
	}
	if (!defined('_FILE_LOG')) {
		define('_FILE_LOG', 'spip');
	}
	if (!defined('_FILE_LOG_SUFFIX')) {
		define('_FILE_LOG_SUFFIX', '.log');
	}

	// Le fichier de connexion a la base de donnees
	// tient compte des anciennes versions (inc_connect...)
	if (!defined('_FILE_CONNECT_INS')) {
		define('_FILE_CONNECT_INS', 'connect');
	}
	if (!defined('_FILE_CONNECT')) {
		define(
			'_FILE_CONNECT',
			(@is_readable($f = _DIR_CONNECT . _FILE_CONNECT_INS . '.php') ? $f
			: (@is_readable($f = _DIR_RESTREINT . 'inc_connect.php') ? $f
			: false))
		);
	}

	// Le fichier de reglages des droits
	if (!defined('_FILE_CHMOD_INS')) {
		define('_FILE_CHMOD_INS', 'chmod');
	}
	if (!defined('_FILE_CHMOD')) {
		define(
			'_FILE_CHMOD',
			(@is_readable($f = _DIR_CHMOD . _FILE_CHMOD_INS . '.php') ? $f
			: false)
		);
	}

	if (!defined('_FILE_LDAP')) {
		define('_FILE_LDAP', 'ldap.php');
	}

	if (!defined('_FILE_TMP_SUFFIX')) {
		define('_FILE_TMP_SUFFIX', '.tmp.php');
	}
	if (!defined('_FILE_CONNECT_TMP')) {
		define('_FILE_CONNECT_TMP', _DIR_CONNECT . _FILE_CONNECT_INS . _FILE_TMP_SUFFIX);
	}
	if (!defined('_FILE_CHMOD_TMP')) {
		define('_FILE_CHMOD_TMP', _DIR_CHMOD . _FILE_CHMOD_INS . _FILE_TMP_SUFFIX);
	}

	// Definition des droits d'acces en ecriture
	if (!defined('_SPIP_CHMOD') and _FILE_CHMOD) {
		include_once _FILE_CHMOD;
	}

	// Se mefier des fichiers mal remplis!
	if (!defined('_SPIP_CHMOD')) {
		define('_SPIP_CHMOD', 0777);
	}

	if (!defined('_DEFAULT_CHARSET')) {
		/** Le charset par défaut lors de l'installation */
		define('_DEFAULT_CHARSET', 'utf-8');
	}
	if (!defined('_ROOT_PLUGINS')) {
		define('_ROOT_PLUGINS', _ROOT_RACINE . 'plugins/');
	}
	if (!defined('_ROOT_PLUGINS_DIST')) {
		define('_ROOT_PLUGINS_DIST', _ROOT_RACINE . 'plugins-dist/');
	}
	if (!defined('_ROOT_PLUGINS_SUPPL') && defined('_DIR_PLUGINS_SUPPL') && _DIR_PLUGINS_SUPPL) {
		define('_ROOT_PLUGINS_SUPPL', _ROOT_RACINE . str_replace(_DIR_RACINE, '', _DIR_PLUGINS_SUPPL));
	}

	// La taille des Log
	if (!defined('_MAX_LOG')) {
		define('_MAX_LOG', 100);
	}

	// Sommes-nous dans l'empire du Mal ?
	// (ou sous le signe du Pingouin, ascendant GNU ?)
	if (isset($_SERVER['SERVER_SOFTWARE']) and strpos($_SERVER['SERVER_SOFTWARE'], '(Win') !== false) {
		if (!defined('_OS_SERVEUR')) {
			define('_OS_SERVEUR', 'windows');
		}
		if (!defined('_SPIP_LOCK_MODE')) {
			define('_SPIP_LOCK_MODE', 1);
		} // utiliser le flock php
	} else {
		if (!defined('_OS_SERVEUR')) {
			define('_OS_SERVEUR', '');
		}
		if (!defined('_SPIP_LOCK_MODE')) {
			define('_SPIP_LOCK_MODE', 1);
		} // utiliser le flock php
		#if (!defined('_SPIP_LOCK_MODE')) define('_SPIP_LOCK_MODE',2); // utiliser le nfslock de spip mais link() est tres souvent interdite
	}

	// Langue par defaut
	if (!defined('_LANGUE_PAR_DEFAUT')) {
		define('_LANGUE_PAR_DEFAUT', 'fr');
	}

	//
	// Module de lecture/ecriture/suppression de fichiers utilisant flock()
	// (non surchargeable en l'etat ; attention si on utilise include_spip()
	// pour le rendre surchargeable, on va provoquer un reecriture
	// systematique du noyau ou une baisse de perfs => a etudier)
	include_once _ROOT_RESTREINT . 'inc/flock.php';

	// charger tout de suite le path et son cache
	load_path_cache();

	// *********** traiter les variables ************

	//
	// Securite
	//

	// Ne pas se faire manger par un bug php qui accepte ?GLOBALS[truc]=toto
	if (isset($_REQUEST['GLOBALS'])) {
		die();
	}
	// nettoyer les magic quotes \' et les caracteres nuls %00
	spip_desinfecte($_GET);
	spip_desinfecte($_POST);
	spip_desinfecte($_COOKIE);
	spip_desinfecte($_REQUEST);

	// appliquer le cookie_prefix
	if ($GLOBALS['cookie_prefix'] != 'spip') {
		include_spip('inc/cookie');
		recuperer_cookies_spip($GLOBALS['cookie_prefix']);
	}

	// Compatibilite avec serveurs ne fournissant pas $REQUEST_URI
	if (isset($_SERVER['REQUEST_URI'])) {
		$GLOBALS['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
	} else {
		$GLOBALS['REQUEST_URI'] = (php_sapi_name() !== 'cli') ? $_SERVER['PHP_SELF'] : '';
		if (
			!empty($_SERVER['QUERY_STRING'])
			and !strpos($_SERVER['REQUEST_URI'], '?')
		) {
			$GLOBALS['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
		}
	}

	// Duree de validite de l'alea pour les cookies et ce qui s'ensuit.
	if (!defined('_RENOUVELLE_ALEA')) {
		define('_RENOUVELLE_ALEA', 12 * 3600);
	}
	if (!defined('_DUREE_COOKIE_ADMIN')) {
		define('_DUREE_COOKIE_ADMIN', 14 * 24 * 3600);
	}

	// charger les meta si possible et renouveller l'alea au besoin
	// charge aussi effacer_meta et ecrire_meta
	$inc_meta = charger_fonction('meta', 'inc');
	$inc_meta();

	// nombre de repertoires depuis la racine
	// on compare a l'adresse de spip.php : $_SERVER["SCRIPT_NAME"]
	// ou a defaut celle donnee en meta ; (mais si celle-ci est fausse
	// le calcul est faux)
	if (!_DIR_RESTREINT) {
		$GLOBALS['profondeur_url'] = 1;
	} else {
		$uri = isset($_SERVER['REQUEST_URI']) ? explode('?', $_SERVER['REQUEST_URI']) : '';
		$uri_ref = $_SERVER['SCRIPT_NAME'];
		if (
			!$uri_ref
			// si on est appele avec un autre ti, on est sans doute en mutu
			// si jamais c'est de la mutu avec sous rep, on est perdu si on se fie
			// a spip.php qui est a la racine du spip, et vue qu'on sait pas se reperer
			// s'en remettre a l'adresse du site. alea jacta est.
			or $ti !== _NOM_TEMPORAIRES_INACCESSIBLES
		) {
			if (isset($GLOBALS['meta']['adresse_site'])) {
				$uri_ref = parse_url($GLOBALS['meta']['adresse_site']);
				$uri_ref = ($uri_ref['path'] ?? '') . '/';
			} else {
				$uri_ref = '';
			}
		}
		if (!$uri or !$uri_ref) {
			$GLOBALS['profondeur_url'] = 0;
		} else {
			$GLOBALS['profondeur_url'] = max(
				0,
				substr_count($uri[0], '/')
				- substr_count($uri_ref, '/')
			);
		}
	}
	// s'il y a un cookie ou PHP_AUTH, initialiser visiteur_session
	if (_FILE_CONNECT) {
		if (
			verifier_visiteur() == '0minirezo'
			// si c'est un admin sans cookie admin, il faut ignorer le cache chemin !
			and !isset($_COOKIE['spip_admin'])
		) {
			clear_path_cache();
		}
	}
}

/**
 * Complements d'initialisation non critiques pouvant etre realises
 * par les plugins
 *
 */
function spip_initialisation_suite() {
	static $too_late = 0;
	if ($too_late++) {
		return;
	}

	// taille mini des login
	if (!defined('_LOGIN_TROP_COURT')) {
		define('_LOGIN_TROP_COURT', 4);
	}

	// la taille maxi des logos (0 : pas de limite) (pas de define par defaut, ce n'est pas utile)
	#if (!defined('_LOGO_MAX_SIZE')) define('_LOGO_MAX_SIZE', 0); # poids en ko
	#if (!defined('_LOGO_MAX_WIDTH')) define('_LOGO_MAX_WIDTH', 0); # largeur en pixels
	#if (!defined('_LOGO_MAX_HEIGHT')) define('_LOGO_MAX_HEIGHT', 0); # hauteur en pixels

	// la taille maxi des images (0 : pas de limite) (pas de define par defaut, ce n'est pas utile)
	#if (!defined('_DOC_MAX_SIZE')) define('_DOC_MAX_SIZE', 0); # poids en ko
	#if (!defined('_IMG_MAX_SIZE')) define('_IMG_MAX_SIZE', 0); # poids en ko
	#if (!defined('_IMG_MAX_WIDTH')) define('_IMG_MAX_WIDTH', 0); # largeur en pixels
	#if (!defined('_IMG_MAX_HEIGHT')) define('_IMG_MAX_HEIGHT', 0); # hauteur en pixels

	if (!defined('_PASS_LONGUEUR_MINI')) {
		define('_PASS_LONGUEUR_MINI', 6);
	}

	// largeur maximale des images dans l'administration
	if (!defined('_IMG_ADMIN_MAX_WIDTH')) {
		define('_IMG_ADMIN_MAX_WIDTH', 768);
	}

	// Qualite des images calculees automatiquement. C'est un nombre entre 0 et 100, meme pour imagick (on ramene a 0..1 par la suite)
	if (!defined('_IMG_QUALITE')) {
		define('_IMG_QUALITE', 85);
	} # valeur par defaut
	if (!defined('_IMG_GD_QUALITE')) {
		define('_IMG_GD_QUALITE', _IMG_QUALITE);
	} # surcharge pour la lib GD
	if (!defined('_IMG_CONVERT_QUALITE')) {
		define('_IMG_CONVERT_QUALITE', _IMG_QUALITE);
	} # surcharge pour imagick en ligne de commande
	// Historiquement la valeur pour imagick semble differente. Si ca n'est pas necessaire, il serait preferable de garder _IMG_QUALITE
	if (!defined('_IMG_IMAGICK_QUALITE')) {
		define('_IMG_IMAGICK_QUALITE', 75);
	} # surcharge pour imagick en PHP

	if (!defined('_COPIE_LOCALE_MAX_SIZE')) {
		define('_COPIE_LOCALE_MAX_SIZE', 33_554_432);
	} // poids en octet

	// qq chaines standard
	if (!defined('_ACCESS_FILE_NAME')) {
		define('_ACCESS_FILE_NAME', '.htaccess');
	}
	if (!defined('_AUTH_USER_FILE')) {
		define('_AUTH_USER_FILE', '.htpasswd');
	}
	if (!defined('_SPIP_DUMP')) {
		define('_SPIP_DUMP', 'dump@nom_site@@stamp@.xml');
	}
	if (!defined('_CACHE_RUBRIQUES')) {
		/** Fichier cache pour le navigateur de rubrique du bandeau */
		define('_CACHE_RUBRIQUES', _DIR_TMP . 'menu-rubriques-cache.txt');
	}
	if (!defined('_CACHE_RUBRIQUES_MAX')) {
		/** Nombre maxi de rubriques enfants affichées pour chaque rubrique du navigateur de rubrique du bandeau */
		define('_CACHE_RUBRIQUES_MAX', 500);
	}

	if (!defined('_CACHE_CONTEXTES_AJAX_SUR_LONGUEUR')) {
		/**
		 * Basculer les contextes ajax en fichier si la longueur d’url est trop grande
		 * @var int Nombre de caractères */
		define('_CACHE_CONTEXTES_AJAX_SUR_LONGUEUR', 2000);
	}

	if (!defined('_EXTENSION_SQUELETTES')) {
		define('_EXTENSION_SQUELETTES', 'html');
	}

	if (!defined('_DOCTYPE_ECRIRE')) {
		/** Définit le doctype de l’espace privé */
		define('_DOCTYPE_ECRIRE', "<!DOCTYPE html>\n");
	}
	if (!defined('_DOCTYPE_AIDE')) {
		/** Définit le doctype de l’aide en ligne */
		define(
			'_DOCTYPE_AIDE',
			"<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.01 Frameset//EN' 'http://www.w3.org/TR/1999/REC-html401-19991224/frameset.dtd'>"
		);
	}

	if (!defined('_SPIP_SCRIPT')) {
		/** L'adresse de base du site ; on peut mettre '' si la racine est gerée par
		 * le script de l'espace public, alias index.php */
		define('_SPIP_SCRIPT', 'spip.php');
	}
	if (!defined('_SPIP_PAGE')) {
		/** Argument page, personalisable en cas de conflit avec un autre script */
		define('_SPIP_PAGE', 'page');
	}

	// le script de l'espace prive
	// Mettre a "index.php" si DirectoryIndex ne le fait pas ou pb connexes:
	// les anciens IIS n'acceptent pas les POST sur ecrire/ (#419)
	// meme pb sur thttpd cf. https://forum.spip.net/fr_184153.html
	if (!defined('_SPIP_ECRIRE_SCRIPT')) {
		if (!empty($_SERVER['SERVER_SOFTWARE']) and preg_match(',IIS|thttpd,', $_SERVER['SERVER_SOFTWARE'])) {
			define('_SPIP_ECRIRE_SCRIPT', 'index.php');
		} else {
			define('_SPIP_ECRIRE_SCRIPT', '');
		}
	}


	if (!defined('_SPIP_AJAX')) {
		define('_SPIP_AJAX', ((!isset($_COOKIE['spip_accepte_ajax']))
			? 1
			: (($_COOKIE['spip_accepte_ajax'] != -1) ? 1 : 0)));
	}

	// La requete est-elle en ajax ?
	if (!defined('_AJAX')) {
		define(
			'_AJAX',
			(isset($_SERVER['HTTP_X_REQUESTED_WITH']) # ajax jQuery
				or !empty($_REQUEST['var_ajax_redir']) # redirection 302 apres ajax jQuery
				or !empty($_REQUEST['var_ajaxcharset']) # compat ascendante pour plugins
				or !empty($_REQUEST['var_ajax']) # forms ajax & inclure ajax de spip
			)
			and empty($_REQUEST['var_noajax']) # horrible exception, car c'est pas parce que la requete est ajax jquery qu'il faut tuer tous les formulaires ajax qu'elle contient
		);
	}

	# nombre de pixels maxi pour calcul de la vignette avec gd
	# au dela de 5500000 on considere que php n'est pas limite en memoire pour cette operation
	# les configurations limitees en memoire ont un seuil plutot vers 1MPixel
	if (!defined('_IMG_GD_MAX_PIXELS')) {
		define(
			'_IMG_GD_MAX_PIXELS',
			(isset($GLOBALS['meta']['max_taille_vignettes']) and $GLOBALS['meta']['max_taille_vignettes'])
			? $GLOBALS['meta']['max_taille_vignettes']
			: 0
		);
	}

	// Protocoles a normaliser dans les chaines de langues
	if (!defined('_PROTOCOLES_STD')) {
		define('_PROTOCOLES_STD', 'http|https|ftp|mailto|webcal');
	}

	init_var_mode();
}

/**
 * Repérer les variables d'URL spéciales `var_mode` qui conditionnent
 * la validité du cache ou certains affichages spéciaux.
 *
 * Le paramètre d'URL `var_mode` permet de
 * modifier la pérennité du cache, recalculer des urls
 * ou d'autres petit caches (trouver_table, css et js compactes ...),
 * d'afficher un écran de débug ou des traductions non réalisées.
 *
 * En fonction de ces paramètres dans l'URL appelante, on définit
 * da constante `_VAR_MODE` qui servira ensuite à SPIP.
 *
 * Le paramètre `var_mode` accepte ces valeurs :
 *
 * - `calcul` : force un calcul du cache de la page (sans forcément recompiler les squelettes)
 * - `recalcul` : force un calcul du cache de la page en recompilant au préabable les squelettes
 * - `inclure` : modifie l'affichage en ajoutant visuellement le nom de toutes les inclusions qu'elle contient
 * - `debug` :  modifie l'affichage activant le mode "debug"
 * - `preview` : modifie l'affichage en ajoutant aux boucles les éléments prévisualisables
 * - `traduction` : modifie l'affichage en affichant des informations sur les chaînes de langues utilisées
 * - `urls` : permet de recalculer les URLs des objets appelés dans la page par les balises `#URL_xx`
 * - `images` : permet de recalculer les filtres d'images utilisés dans la page
 *
 * En dehors des modes `calcul` et `recalcul`, une autorisation 'previsualiser' ou 'debug' est testée.
 *
 * @note
 *     Il éxiste également le paramètre `var_profile` qui modifie l'affichage pour incruster
 *     le nombre de requêtes SQL utilisées dans la page, qui peut se compléter avec le paramètre
 * `   var_mode` (calcul ou recalcul).
 */
function init_var_mode() {
	static $done = false;
	if (!$done) {
		if (isset($_GET['var_mode'])) {
			$var_mode = explode(',', $_GET['var_mode']);
			// tout le monde peut calcul/recalcul
			if (!defined('_VAR_MODE')) {
				if (in_array('recalcul', $var_mode)) {
					define('_VAR_MODE', 'recalcul');
				} elseif (in_array('calcul', $var_mode)) {
					define('_VAR_MODE', 'calcul');
				}
			}
			$var_mode = array_diff($var_mode, ['calcul', 'recalcul']);
			if ($var_mode) {
				include_spip('inc/autoriser');
				// autoriser preview si preview seulement, et sinon autoriser debug
				if (
					autoriser(
						($_GET['var_mode'] == 'preview')
						? 'previsualiser'
						: 'debug'
					)
				) {
					if (in_array('traduction', $var_mode)) {
						// forcer le calcul pour passer dans traduire
						if (!defined('_VAR_MODE')) {
							define('_VAR_MODE', 'calcul');
						}
						// et ne pas enregistrer de cache pour ne pas trainer les surlignages sur d'autres pages
						if (!defined('_VAR_NOCACHE')) {
							define('_VAR_NOCACHE', true);
						}
						$var_mode = array_diff($var_mode, ['traduction']);
					}
					if (in_array('preview', $var_mode)) {
						// basculer sur les criteres de preview dans les boucles
						if (!defined('_VAR_PREVIEW')) {
							define('_VAR_PREVIEW', true);
						}
						// forcer le calcul
						if (!defined('_VAR_MODE')) {
							define('_VAR_MODE', 'calcul');
						}
						// et ne pas enregistrer de cache
						if (!defined('_VAR_NOCACHE')) {
							define('_VAR_NOCACHE', true);
						}
						$var_mode = array_diff($var_mode, ['preview']);
					}
					if (in_array('inclure', $var_mode)) {
						// forcer le compilo et ignorer les caches existants
						if (!defined('_VAR_MODE')) {
							define('_VAR_MODE', 'calcul');
						}
						if (!defined('_VAR_INCLURE')) {
							define('_VAR_INCLURE', true);
						}
						// et ne pas enregistrer de cache
						if (!defined('_VAR_NOCACHE')) {
							define('_VAR_NOCACHE', true);
						}
						$var_mode = array_diff($var_mode, ['inclure']);
					}
					if (in_array('urls', $var_mode)) {
						// forcer le compilo et ignorer les caches existants
						if (!defined('_VAR_MODE')) {
							define('_VAR_MODE', 'calcul');
						}
						if (!defined('_VAR_URLS')) {
							define('_VAR_URLS', true);
						}
						$var_mode = array_diff($var_mode, ['urls']);
					}
					if (in_array('images', $var_mode)) {
						// forcer le compilo et ignorer les caches existants
						if (!defined('_VAR_MODE')) {
							define('_VAR_MODE', 'calcul');
						}
						// indiquer qu'on doit recalculer les images
						if (!defined('_VAR_IMAGES')) {
							define('_VAR_IMAGES', true);
						}
						$var_mode = array_diff($var_mode, ['images']);
					}
					if (in_array('debug', $var_mode)) {
						if (!defined('_VAR_MODE')) {
							define('_VAR_MODE', 'debug');
						}
						// et ne pas enregistrer de cache
						if (!defined('_VAR_NOCACHE')) {
							define('_VAR_NOCACHE', true);
						}
						$var_mode = array_diff($var_mode, ['debug']);
					}
					if (count($var_mode) and !defined('_VAR_MODE')) {
						define('_VAR_MODE', reset($var_mode));
					}
					if (isset($GLOBALS['visiteur_session']['nom'])) {
						spip_log($GLOBALS['visiteur_session']['nom']
							. ' ' . _VAR_MODE);
					}
				} // pas autorise ?
				else {
					// si on n'est pas connecte on se redirige, si on est pas en cli et pas deja en train de se loger
					if (
						!$GLOBALS['visiteur_session']
						and !empty($_SERVER['HTTP_HOST'])
						and !empty($_SERVER['REQUEST_METHOD'])
						and $_SERVER['REQUEST_METHOD'] === 'GET'
					) {
						$self = self('&', true);
						if (strpos($self, 'page=login') === false) {
							include_spip('inc/headers');
							$redirect = parametre_url(self('&', true), 'var_mode', $_GET['var_mode'], '&');
							redirige_par_entete(generer_url_public('login', 'url=' . rawurlencode($redirect), true));
						}
					}
					// sinon tant pis
				}
			}
		}
		if (!defined('_VAR_MODE')) {
			/**
			 * Indique le mode de calcul ou d'affichage de la page.
			 * @see init_var_mode()
			 */
			define('_VAR_MODE', false);
		}
		$done = true;
	}
}

/**
 * Supprimer les éventuels caracteres nuls %00, qui peuvent tromper
 * la commande is_readable('chemin/vers/fichier/interdit%00truc_normal').
 *
 * Cette fonction est appliquée par SPIP à son initialisation sur GET/POST/COOKIES/GLOBALS
 * @param array $t le tableau ou la chaine à desinfecter (passage par référence)
 * @param bool $deep = true : appliquer récursivement
**/
function spip_desinfecte(&$t, $deep = true) {
	foreach ($t as $key => $val) {
		if (is_string($t[$key])) {
			$t[$key] = str_replace(chr(0), '-', $t[$key]);
		} // traiter aussi les "texte_plus" de article_edit
		else {
			if ($deep and is_array($t[$key]) and $key !== 'GLOBALS') {
				spip_desinfecte($t[$key], $deep);
			}
		}
	}
}

/**
 * Retourne le statut du visiteur s'il s'annonce.
 *
 * Pour que cette fonction marche depuis mes_options,
 * il faut forcer l'init si ce n'est fait
 * mais on risque de perturber des plugins en initialisant trop tot
 * certaines constantes.
 * @return string|0|false
**/
function verifier_visiteur() {
	@spip_initialisation_core(
		(_DIR_RACINE . _NOM_PERMANENTS_INACCESSIBLES),
		(_DIR_RACINE . _NOM_PERMANENTS_ACCESSIBLES),
		(_DIR_RACINE . _NOM_TEMPORAIRES_INACCESSIBLES),
		(_DIR_RACINE . _NOM_TEMPORAIRES_ACCESSIBLES)
	);

	// Demarrer une session NON AUTHENTIFIEE si on donne son nom
	// dans un formulaire sans login (ex: #FORMULAIRE_FORUM)
	// Attention on separe bien session_nom et nom, pour eviter
	// les melanges entre donnees SQL et variables plus aleatoires
	$variables_session = ['session_nom', 'session_email'];
	foreach ($variables_session as $var) {
		if (_request($var) !== null) {
			$init = true;
			break;
		}
	}
	if (isset($init)) {
		#@spip_initialisation_suite();
		$session = charger_fonction('session', 'inc');
		$session();
		include_spip('inc/texte');
		foreach ($variables_session as $var) {
			if (($a = _request($var)) !== null) {
				$GLOBALS['visiteur_session'][$var] = safehtml($a);
			}
		}
		if (!isset($GLOBALS['visiteur_session']['id_auteur'])) {
			$GLOBALS['visiteur_session']['id_auteur'] = 0;
		}
		$session($GLOBALS['visiteur_session']);

		return 0;
	}

	$h = (isset($_SERVER['PHP_AUTH_USER']) and !$GLOBALS['ignore_auth_http']);
	if ($h or isset($_COOKIE['spip_session']) or isset($_COOKIE[$GLOBALS['cookie_prefix'] . '_session'])) {
		$session = charger_fonction('session', 'inc');
		if ($session()) {
			return $GLOBALS['visiteur_session']['statut'];
		}
		if ($h and isset($_SERVER['PHP_AUTH_PW'])) {
			include_spip('inc/auth');
			$h = lire_php_auth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
		}
		if ($h) {
			$GLOBALS['visiteur_session'] = $h;

			return $GLOBALS['visiteur_session']['statut'];
		}
	}

	// au moins son navigateur nous dit la langue preferee de cet inconnu
	include_spip('inc/lang');
	utiliser_langue_visiteur();

	return false;
}


/**
 * Sélectionne la langue donnée en argument et mémorise la courante
 *
 * Restaure l'ancienne langue si appellée sans argument.
 *
 * @note
 *     On pourrait économiser l'empilement en cas de non changemnt
 *     et lui faire retourner `False` pour prevenir l'appelant
 *     Le noyau de Spip sait le faire, mais pour assurer la compatibilité
 *     cette fonction retourne toujours non `False`
 *
 * @uses changer_langue()
 * @param null|string $lang
 *     - string : Langue à appliquer,
 *     - null : Pour restituer la dernière langue mémorisée.
 * @return string
 *     - string Langue utilisée.
 **/
function lang_select($lang = null) {
	static $pile_langues = [];
	if (!function_exists('changer_langue')) {
		include_spip('inc/lang');
	}
	if ($lang === null) {
		$lang = array_pop($pile_langues);
	} else {
		array_push($pile_langues, $GLOBALS['spip_lang']);
	}
	if (isset($GLOBALS['spip_lang']) and $lang == $GLOBALS['spip_lang']) {
		return $lang;
	}
	changer_langue($lang);

	return $lang;
}

/**
 * Renvoie une chaîne qui identifie la session courante
 *
 * Permet de savoir si on peut utiliser un cache enregistré pour cette session.
 * Cette chaîne est courte (8 cars) pour pouvoir être utilisée dans un nom
 * de fichier cache.
 *
 * @pipeline_appel definir_session
 *
 * @param bool $force
 * @return string
 *     Identifiant de la session
 **/
function spip_session($force = false) {
	static $session;
	if ($force or !isset($session)) {
		$s = pipeline(
			'definir_session',
			$GLOBALS['visiteur_session']
				? serialize($GLOBALS['visiteur_session'])
				. '_' . @$_COOKIE['spip_session']
				: ''
		);
		$session = $s ? substr(md5($s), 0, 8) : '';
	}

	#spip_log('session: '.$session);
	return $session;
}


/**
 * Retourne un lien vers une aide
 *
 * Aide, aussi depuis l'espace privé à présent.
 * Surchargeable mais pas d'erreur fatale si indisponible.
 *
 * @param string $aide
 *    Cle d'identification de l'aide desiree
 * @param bool $distante
 *    Generer une url locale (par defaut)
 *    ou une url distante [directement sur spip.net]
 * @return
 *    Lien sur une icone d'aide
 **/
function aider($aide = '', $distante = false) {
	$aider = charger_fonction('aide', 'inc', true);

	return $aider ? $aider($aide, '', [], $distante) : '';
}

/**
 * Page `exec=info` : retourne le contenu de la fonction php `phpinfo()`
 *
 * Si l’utiliseur est un webmestre.
 */
function exec_info_dist() {

	include_spip('inc/autoriser');
	if (autoriser('phpinfos')) {
		$cookies_masques = ['spip_session', 'PHPSESSID'];
		$cookies_backup = [];
		foreach ($cookies_masques as $k) {
			if (!empty($_COOKIE[$k])) {
				$cookies_backup[$k] = $_COOKIE[$k];
				$_COOKIE[$k] = '******************************';
			}
		}
		phpinfo();
		foreach ($cookies_backup as $k => $v) {
			$_COOKIE[$k] = $v;
		}
	} else {
		include_spip('inc/filtres');
		sinon_interdire_acces();
	}
}

/**
 * Génère une erreur de squelette
 *
 * Génère une erreur de squelette qui sera bien visible par un
 * administrateur authentifié lors d'une visite de la page en erreur
 *
 * @param bool|string|array $message
 *     - Message d'erreur (string|array)
 *     - false pour retourner le texte des messages d'erreurs
 *     - vide pour afficher les messages d'erreurs
 * @param string|array|object $lieu
 *     Lieu d'origine de l'erreur
 * @return null|string
 *     - Rien dans la plupart des cas
 *     - string si $message à false.
 **/
function erreur_squelette($message = '', $lieu = '') {
	$debusquer = charger_fonction('debusquer', 'public');
	if (is_array($lieu)) {
		include_spip('public/compiler');
		$lieu = reconstruire_contexte_compil($lieu);
	}

	return $debusquer($message, $lieu);
}

/**
 * Calcule un squelette avec un contexte et retourne son contenu
 *
 * La fonction de base de SPIP : un squelette + un contexte => une page.
 * $fond peut etre un nom de squelette, ou une liste de squelette au format array.
 * Dans ce dernier cas, les squelettes sont tous evalues et mis bout a bout
 * $options permet de selectionner les options suivantes :
 *
 * - trim => true (valeur par defaut) permet de ne rien renvoyer si le fond ne produit que des espaces ;
 * - raw  => true permet de recuperer la strucure $page complete avec entetes et invalideurs
 *          pour chaque $fond fourni.
 *
 * @api
 * @param string /array $fond
 *     - Le ou les squelettes à utiliser, sans l'extension, {@example prive/liste/auteurs}
 *     - Le fichier sera retrouvé dans la liste des chemins connus de SPIP (squelettes, plugins, spip)
 * @param array $contexte
 *     - Informations de contexte envoyées au squelette, {@example array('id_rubrique' => 8)}
 *     - La langue est transmise automatiquement (sauf option étoile).
 * @param array $options
 *     Options complémentaires :
 *
 *     - trim   : applique un trim sur le résultat (true par défaut)
 *     - raw    : retourne un tableau d'information sur le squelette (false par défaut)
 *     - etoile : ne pas transmettre la langue au contexte automatiquement (false par défaut),
 *                équivalent de INCLURE*
 *     - ajax   : gere les liens internes du squelette en ajax (équivalent du paramètre {ajax})
 * @param string $connect
 *     Non du connecteur de bdd a utiliser
 * @return string|array
 *     - Contenu du squelette calculé
 *     - ou tableau d'information sur le squelette.
 */
function recuperer_fond($fond, $contexte = [], $options = [], string $connect = '') {
	if (!function_exists('evaluer_fond')) {
		include_spip('public/assembler');
	}
	// assurer la compat avec l'ancienne syntaxe
	// (trim etait le 3eme argument, par defaut a true)
	if (!is_array($options)) {
		$options = ['trim' => $options];
	}
	if (!isset($options['trim'])) {
		$options['trim'] = true;
	}

	if (isset($contexte['connect'])) {
		$connect = $contexte['connect'];
		unset($contexte['connect']);
	}

	$texte = '';
	$pages = [];
	$lang_select = '';
	if (!isset($options['etoile']) or !$options['etoile']) {
		// Si on a inclus sans fixer le critere de lang, on prend la langue courante
		if (!isset($contexte['lang'])) {
			$contexte['lang'] = $GLOBALS['spip_lang'];
		}

		if ($contexte['lang'] != $GLOBALS['meta']['langue_site']) {
			$lang_select = lang_select($contexte['lang']);
		}
	}

	if (!isset($GLOBALS['_INC_PUBLIC'])) {
		$GLOBALS['_INC_PUBLIC'] = 0;
	}

	$GLOBALS['_INC_PUBLIC']++;

	// fix #4235
	$cache_utilise_session_appelant	= ($GLOBALS['cache_utilise_session'] ?? null);


	foreach (is_array($fond) ? $fond : [$fond] as $f) {
		unset($GLOBALS['cache_utilise_session']);	// fix #4235

		$page = evaluer_fond($f, $contexte, $connect);
		if ($page === '') {
			$c = $options['compil'] ?? '';
			$a = ['fichier' => $f];
			$erreur = _T('info_erreur_squelette2', $a); // squelette introuvable
			erreur_squelette($erreur, $c);
			// eviter des erreurs strictes ensuite sur $page['cle'] en PHP >= 5.4
			$page = ['texte' => '', 'erreur' => $erreur];
		}

		$page = pipeline('recuperer_fond', [
			'args' => ['fond' => $f, 'contexte' => $contexte, 'options' => $options, 'connect' => $connect],
			'data' => $page
		]);
		if (isset($options['ajax']) and $options['ajax']) {
			if (!function_exists('encoder_contexte_ajax')) {
				include_spip('inc/filtres');
			}
			$page['texte'] = encoder_contexte_ajax(
				array_merge(
					$contexte,
					['fond' => $f],
					($connect ? ['connect' => $connect] : [])
				),
				'',
				$page['texte'],
				$options['ajax']
			);
		}

		if (isset($options['raw']) and $options['raw']) {
			$pages[] = $page;
		} else {
			$texte .= $options['trim'] ? rtrim($page['texte'] ?? '') : $page['texte'];
		}

		// contamination de la session appelante, pour les inclusions statiques
		if (isset($page['invalideurs']['session'])) {
			$cache_utilise_session_appelant = $page['invalideurs']['session'];
		}
	}

	// restaurer le sessionnement du contexte appelant,
	// éventuellement contaminé si on vient de récupérer une inclusion statique sessionnée
	if (isset($cache_utilise_session_appelant)) {
		$GLOBALS['cache_utilise_session'] = $cache_utilise_session_appelant;
	}

	$GLOBALS['_INC_PUBLIC']--;

	if ($lang_select) {
		lang_select();
	}
	if (isset($options['raw']) and $options['raw']) {
		return is_array($fond) ? $pages : reset($pages);
	} else {
		return $options['trim'] ? ltrim($texte) : $texte;
	}
}

/**
 * Trouve un squelette dans le repertoire modeles/
 *
 * @param  $nom
 * @return string
 */
function trouve_modele($nom) {
	return trouver_fond($nom, 'modeles/');
}

/**
 * Trouver un squelette dans le chemin
 * on peut specifier un sous-dossier dans $dir
 * si $pathinfo est a true, retourne un tableau avec
 * les composantes du fichier trouve
 * + le chemin complet sans son extension dans fond
 *
 * @param string $nom
 * @param string $dir
 * @param bool $pathinfo
 * @return array|string
 */
function trouver_fond($nom, $dir = '', $pathinfo = false) {
	$f = find_in_path($nom . '.' . _EXTENSION_SQUELETTES, $dir ? rtrim($dir, '/') . '/' : '');
	if (!$pathinfo) {
		return $f;
	}
	// renvoyer un tableau detaille si $pathinfo==true
	$p = pathinfo($f);
	if (!isset($p['extension']) or !$p['extension']) {
		$p['extension'] = _EXTENSION_SQUELETTES;
	}
	if (!isset($p['extension']) or !$p['filename']) {
		$p['filename'] = ($p['basename'] ? substr($p['basename'], 0, -strlen($p['extension']) - 1) : '');
	}
	$p['fond'] = ($f ? substr($f, 0, -strlen($p['extension']) - 1) : '');

	return $p;
}

/**
 * Teste, pour un nom de page de l'espace privé, s'il est possible
 * de générer son contenu.
 *
 * Dans ce cas, on retourne la fonction d'exécution correspondante à utiliser
 * (du répertoire `ecrire/exec`). Deux cas particuliers et prioritaires :
 * `fond` est retourné si des squelettes existent.
 *
 * - `fond` : pour des squelettes de `prive/squelettes/contenu`
 *          ou pour des objets éditoriaux dont les squelettes seront échaffaudés
 *
 * @param string $nom
 *     Nom de la page
 * @return string
 *     Nom de l'exec, sinon chaîne vide.
 **/
function tester_url_ecrire($nom) {
	static $exec = [];
	if (isset($exec[$nom])) {
		return $exec[$nom];
	}
	// tester si c'est une page en squelette
	if (trouver_fond($nom, 'prive/squelettes/contenu/')) {
		return $exec[$nom] = 'fond';
	} // echafaudage d'un fond !
	elseif (include_spip('public/styliser_par_z') and z_echafaudable($nom)) {
		return $exec[$nom] = 'fond';
	}
	// attention, il ne faut pas inclure l'exec ici
	// car sinon #URL_ECRIRE provoque des inclusions
	// et des define intrusifs potentiels
	return $exec[$nom] = ((find_in_path("{$nom}.php", 'exec/') or charger_fonction($nom, 'exec', true)) ? $nom : '');
}

/**
 * Indique si le code HTML5 est permis sur le site public
 *
 * @return bool
 *     true si la constante _VERSION_HTML n'est pas définie ou égale à html5
 **/
function html5_permis() {
	return (!defined('_VERSION_HTML')
		or _VERSION_HTML !== 'html4');
}

/**
 * Lister les formats image acceptes par les lib et fonctions images
 * @param bool $gd
 * @param bool $svg_allowed
 * @return array
 */
function formats_image_acceptables($gd = null, $svg_allowed = true) {
	$formats = null;
	if (!is_null($gd)) {
		$config = ($gd ? 'gd_formats' : 'formats_graphiques');
		if (isset($GLOBALS['meta'][$config])) {
			$formats = $GLOBALS['meta'][$config];
			$formats = explode(',', $formats);
			$formats = array_filter($formats);
			$formats = array_map('trim', $formats);
		}
	}
	if (is_null($formats)) {
		include_spip('inc/filtres_images_lib_mini');
		$formats = _image_extensions_acceptees_en_entree();
	}

	if ($svg_allowed) {
		if (!in_array('svg', $formats)) {
			$formats[] = 'svg';
		}
	}
	else {
		$formats = array_diff($formats, ['svg']);
	}
	return $formats;
}

/**
 * Extension de la fonction getimagesize pour supporter aussi les images SVG
 * @param string $fichier
 * @return array|bool
 */
function spip_getimagesize($fichier) {
	if (!$imagesize = @getimagesize($fichier)) {
		include_spip('inc/svg');
		if ($attrs = svg_lire_attributs($fichier)) {
			[$width, $height, $viewbox] = svg_getimagesize_from_attr($attrs);
			$imagesize = [
				$width,
				$height,
				IMAGETYPE_SVG,
				"width=\"{$width}\" height=\"{$height}\"",
				'mime' => 'image/svg+xml'
			];
		}
	}
	return $imagesize;
}

/**
 * Poser une alerte qui sera affiche aux auteurs de bon statut ('' = tous)
 * au prochain passage dans l'espace prive
 * chaque alerte doit avoir un nom pour eviter duplication a chaque hit
 * les alertes affichees une fois sont effacees
 *
 * @param string $nom
 * @param string $message
 * @param string $statut
 */
function avertir_auteurs($nom, $message, $statut = '') {
	$alertes = $GLOBALS['meta']['message_alertes_auteurs'];
	if (
		!$alertes
		or !is_array($alertes = unserialize($alertes))
	) {
		$alertes = [];
	}

	if (!isset($alertes[$statut])) {
		$alertes[$statut] = [];
	}
	$alertes[$statut][$nom] = $message;
	ecrire_meta('message_alertes_auteurs', serialize($alertes));
}

/**
 * Nettoie une chaine pour servir comme classes CSS.
 *
 * @note
 *     les classes CSS acceptent théoriquement tous les caractères sauf NUL.
 *     Ici, on limite (enlève) les caractères autres qu’alphanumérique, espace, - + _ @
 *
 * @param string|string[] $classes
 * @return string|string[]
 */
function spip_sanitize_classname($classes) {
	if (is_array($classes)) {
		return array_map('spip_sanitize_classname', $classes);
	}
	return preg_replace('/[^ 0-9a-z_\-+@]/i', '', $classes);
}


/**
 * Compare 2 numéros de version entre elles.
 *
 * Cette fonction est identique (arguments et retours) a la fonction PHP
 * version_compare() qu'elle appelle. Cependant, cette fonction reformate
 * les numeros de versions pour ameliorer certains usages dans SPIP ou bugs
 * dans PHP. On permet ainsi de comparer 3.0.4 à 3.0.* par exemple.
 *
 * @param string $v1
 *    Numero de version servant de base a la comparaison.
 *    Ce numero ne peut pas comporter d'etoile.
 * @param string $v2
 *    Numero de version a comparer.
 *    Il peut posseder des etoiles tel que 3.0.*
 * @param string $op
 *    Un operateur eventuel (<, >, <=, >=, =, == ...)
 * @return int|bool
 *    Sans operateur : int. -1 pour inferieur, 0 pour egal, 1 pour superieur
 *    Avec operateur : bool.
 **/
function spip_version_compare($v1, $v2, $op = null) {
	$v1 = strtolower(preg_replace(',([0-9])[\s.-]?(dev|alpha|a|beta|b|rc|pl|p),i', '\\1.\\2', $v1));
	$v2 = strtolower(preg_replace(',([0-9])[\s.-]?(dev|alpha|a|beta|b|rc|pl|p),i', '\\1.\\2', $v2));
	$v1 = str_replace('rc', 'RC', $v1); // certaines versions de PHP ne comprennent RC qu'en majuscule
	$v2 = str_replace('rc', 'RC', $v2); // certaines versions de PHP ne comprennent RC qu'en majuscule

	$v1 = explode('.', $v1);
	$v2 = explode('.', $v2);
	// $v1 est toujours une version, donc sans etoile
	while (count($v1) < count($v2)) {
		$v1[] = '0';
	}

	// $v2 peut etre une borne, donc accepte l'etoile
	$etoile = false;
	foreach ($v1 as $k => $v) {
		if (!isset($v2[$k])) {
			$v2[] = ($etoile and (is_numeric($v) or $v == 'pl' or $v == 'p')) ? $v : '0';
		} else {
			if ($v2[$k] == '*') {
				$etoile = true;
				$v2[$k] = $v;
			}
		}
	}
	$v1 = implode('.', $v1);
	$v2 = implode('.', $v2);

	return $op ? version_compare($v1, $v2, $op) : version_compare($v1, $v2);
}
