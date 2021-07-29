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
 * Initialisation de SPIP
 *
 * @package SPIP\Core\Chargement
 **/

if (defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Indique que SPIP est chargé
 *
 * Cela permet des tests de sécurités pour les fichiers PHP
 * de SPIP et des plugins qui peuvent vérifier que SPIP est chargé
 * et donc que les fichiers ne sont pas appelés en dehors de l'usage de SPIP
 */
define('_ECRIRE_INC_VERSION', "1");

# masquer les eventuelles erreurs sur les premiers define
error_reporting(E_ALL ^ E_NOTICE);

/** version PHP minimum exigee (cf. inc/utils) */
define('_PHP_MIN', '7.3.0');

if (!defined('_DIR_RESTREINT_ABS')) {
	/** le nom du repertoire ecrire/ */
	define('_DIR_RESTREINT_ABS', 'ecrire/');
}

/** Chemin relatif pour aller dans ecrire
 *  vide si on est dans ecrire, 'ecrire/' sinon */
define('_DIR_RESTREINT',
(!is_dir(_DIR_RESTREINT_ABS) ? "" : _DIR_RESTREINT_ABS));

/** Chemin relatif pour aller à la racine */
define('_DIR_RACINE', _DIR_RESTREINT ? '' : '../');

/** chemin absolu vers la racine */
define('_ROOT_RACINE', dirname(__DIR__) . '/');
/** chemin absolu vers le repertoire de travail */
define('_ROOT_CWD', getcwd() . '/');
/** chemin absolu vers ecrire */
define('_ROOT_RESTREINT', _ROOT_CWD . _DIR_RESTREINT);

// Icones
if (!defined('_NOM_IMG_PACK')) {
	/** Nom du dossier images */
	define('_NOM_IMG_PACK', 'images/');
}
/** le chemin http (relatif) vers les images standard */
define('_DIR_IMG_PACK', (_DIR_RACINE . 'prive/' . _NOM_IMG_PACK));

/** le chemin php (absolu) vers les images standard (pour hebergement centralise) */
define('_ROOT_IMG_PACK', dirname(__DIR__) . '/prive/' . _NOM_IMG_PACK);

if (!defined('_JAVASCRIPT')) {
	/** Nom du repertoire des  bibliotheques JavaScript */
	define('_JAVASCRIPT', 'javascript/');
} // utilisable avec #CHEMIN et find_in_path
/** le nom du repertoire des  bibliotheques JavaScript du prive */
define('_DIR_JAVASCRIPT', (_DIR_RACINE . 'prive/' . _JAVASCRIPT));

# Le nom des 4 repertoires modifiables par les scripts lances par httpd
# Par defaut ces 4 noms seront suffixes par _DIR_RACINE (cf plus bas)
# mais on peut les mettre ailleurs et changer completement les noms

if (!defined('_NOM_TEMPORAIRES_INACCESSIBLES')) {
	/** Nom du repertoire des fichiers Temporaires Inaccessibles par http:// */
	define('_NOM_TEMPORAIRES_INACCESSIBLES', "tmp/");
}
if (!defined('_NOM_TEMPORAIRES_ACCESSIBLES')) {
	/** Nom du repertoire des fichiers Temporaires Accessibles par http:// */
	define('_NOM_TEMPORAIRES_ACCESSIBLES', "local/");
}
if (!defined('_NOM_PERMANENTS_INACCESSIBLES')) {
	/** Nom du repertoire des fichiers Permanents Inaccessibles par http:// */
	define('_NOM_PERMANENTS_INACCESSIBLES', "config/");
}
if (!defined('_NOM_PERMANENTS_ACCESSIBLES')) {
	/** Nom du repertoire des fichiers Permanents Accessibles par http:// */
	define('_NOM_PERMANENTS_ACCESSIBLES', "IMG/");
}


/** Le nom du fichier de personnalisation */
if (!defined('_NOM_CONFIG')) {
	define('_NOM_CONFIG', 'mes_options');
}

// Son emplacement absolu si on le trouve
if (@file_exists($f = _ROOT_RACINE . _NOM_PERMANENTS_INACCESSIBLES . _NOM_CONFIG . '.php')
	or (@file_exists($f = _ROOT_RESTREINT . _NOM_CONFIG . '.php'))
) {
	/** Emplacement absolu du fichier d'option */
	define('_FILE_OPTIONS', $f);
} else {
	define('_FILE_OPTIONS', '');
}

if (!defined('MODULES_IDIOMES')) {
	/**
	 * Modules par défaut pour la traduction.
	 *
	 * Constante utilisée par le compilateur et le décompilateur
	 * sa valeur etant traitée par inc_traduire_dist
	 */
	define('MODULES_IDIOMES', 'public|spip|ecrire');
}

// *** Fin des define *** //


// Inclure l'ecran de securite
if (!defined('_ECRAN_SECURITE')
	and @file_exists($f = _ROOT_RACINE . _NOM_PERMANENTS_INACCESSIBLES . 'ecran_securite.php')
) {
	include $f;
}


/*
 * Détecteur de robot d'indexation
 */
if (!defined('_IS_BOT')) {
	define('_IS_BOT',
		isset($_SERVER['HTTP_USER_AGENT'])
		and preg_match(
			// mots generiques
			',bot|slurp|crawler|spider|webvac|yandex|'
			// MSIE 6.0 est un botnet 99,9% du temps, on traite donc ce USER_AGENT comme un bot
			. 'MSIE 6\.0|'
			// UA plus cibles
			. '80legs|accoona|AltaVista|ASPSeek|Baidu|Charlotte|EC2LinkFinder|eStyle|facebook|flipboard|hootsuite|FunWebProducts|Google|Genieo|INA dlweb|InfegyAtlas|Java VM|LiteFinder|Lycos|MetaURI|Moreover|Rambler|Scooter|ScrubbyBloglines|Yahoo|Yeti'
			. ',i', (string)$_SERVER['HTTP_USER_AGENT'])
	);
}

if (!defined('_IS_CLI')) {
	define('_IS_CLI',
	  !isset($_SERVER['HTTP_HOST'])
	  and !strlen($_SERVER['DOCUMENT_ROOT'])
	  and !empty($_SERVER['argv'])
	  and empty($_SERVER['REQUEST_METHOD'])
	);
}

// *** Parametrage par defaut de SPIP ***
//
// Les globales qui suivent peuvent etre modifiees
// dans le fichier de personnalisation indique ci-dessus.
// Il suffit de copier les lignes ci-dessous, et ajouter le marquage de debut
// et fin de fichier PHP ("< ?php" et "? >", sans les espaces)
// Ne pas les rendre indefinies.

global
	$nombre_de_logs,
	$taille_des_logs,
	$table_prefix,
	$cookie_prefix,
	$dossier_squelettes,
	$filtrer_javascript,
	$type_urls,
	$debut_date_publication,
	$ip,
	$mysql_rappel_connexion,
	$mysql_rappel_nom_base,
	$test_i18n,
	$ignore_auth_http,
	$ignore_remote_user,
	$derniere_modif_invalide,
	$home_server,
	$help_server,
	$url_glossaire_externe,
	$tex_server,
	$traiter_math,
	$xhtml,
	$xml_indent,
	$source_vignettes,
	$formats_logos,
	$controler_dates_rss,
	$spip_pipeline,
	$spip_matrice,
	$plugins,
	$surcharges,
	$exceptions_des_tables,
	$tables_principales,
	$table_des_tables,
	$tables_auxiliaires,
	$table_primary,
	$table_date,
	$table_titre,
	$tables_jointures,
	$liste_des_statuts,
	$liste_des_etats,
	$liste_des_authentifications,
	$spip_version_branche,
	$spip_version_code,
	$spip_version_base,
	$spip_sql_version,
	$spip_version_affichee,
	$visiteur_session,
	$auteur_session,
	$connect_statut,
	$connect_toutes_rubriques,
	$hash_recherche,
	$hash_recherche_strict,
	$ldap_present,
	$meta,
	$connect_id_rubrique,
	$puce;

# comment on logge, defaut 4 tmp/spip.log de 100k, 0 ou 0 suppriment le log
$nombre_de_logs = 4;
$taille_des_logs = 100;

// Definir les niveaux de log
defined('_LOG_HS') || define('_LOG_HS', 0);
defined('_LOG_ALERTE_ROUGE') || define('_LOG_ALERTE_ROUGE', 1);
defined('_LOG_CRITIQUE') || define('_LOG_CRITIQUE', 2);
defined('_LOG_ERREUR') || define('_LOG_ERREUR', 3);
defined('_LOG_AVERTISSEMENT') || define('_LOG_AVERTISSEMENT', 4);
defined('_LOG_INFO_IMPORTANTE') || define('_LOG_INFO_IMPORTANTE', 5);
defined('_LOG_INFO') || define('_LOG_INFO', 6);
defined('_LOG_DEBUG') || define('_LOG_DEBUG', 7);

// on peut definir _LOG_FILTRE_GRAVITE dans mes_options.php

// Prefixe des tables dans la base de donnees
// (a modifier pour avoir plusieurs sites SPIP dans une seule base)
$table_prefix = "spip";

// Prefixe des cookies
// (a modifier pour installer des sites SPIP dans des sous-repertoires)
$cookie_prefix = "spip";

// Dossier des squelettes
// (a modifier si l'on veut passer rapidement d'un jeu de squelettes a un autre)
$dossier_squelettes = "";

// Pour le javascript, trois modes : parano (-1), prive (0), ok (1)
// parano le refuse partout, ok l'accepte partout
// le mode par defaut le signale en rouge dans l'espace prive
// Si < 1, les fichiers SVG sont traites s'ils emanent d'un redacteur
$filtrer_javascript = 0;
// PS: dans les forums, petitions, flux syndiques... c'est *toujours* securise

// Type d'URLs 
// inc/utils.php sélectionne le type 'page' (spip.php?article123) en l'absence
// d'autre configuration stockée en $GLOBALS['meta']['type_urls] 
// Pour les autres types: voir urls_etendues 
// $type_urls n'a plus de valeur par défaut en 3.1 mais permet de forcer une
// configuration d'urls dans les fichiers d'options.

#la premiere date dans le menu deroulant de date de publication
# null: automatiquement (affiche les 8 dernieres annees)
# 0: affiche un input libre
# 1997: le menu commence a 1997 jusqu'a annee en cours
$debut_date_publication = null;


//
// Prendre en compte les entetes HTTP_X_FORWARDED_XX
//
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) and $_SERVER['HTTP_X_FORWARDED_PROTO']==='https'){
	if (empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
		$_SERVER['HTTP_X_FORWARDED_HOST'] = $_SERVER['HTTP_HOST'];
	}
	if (empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
		$_SERVER['HTTP_X_FORWARDED_PORT'] = 443;
	}
}
if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])){
	if (isset($_SERVER['HTTP_X_FORWARDED_PORT']) and is_numeric($_SERVER['HTTP_X_FORWARDED_PORT'])){
		$_SERVER['SERVER_PORT'] = $_SERVER['HTTP_X_FORWARDED_PORT'];
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) and $_SERVER['HTTP_X_FORWARDED_PROTO']==='https'){
			$_SERVER['HTTPS'] = 'on';
			if (isset($_SERVER['REQUEST_SCHEME'])) {
				$_SERVER['REQUEST_SCHEME'] = 'https';
			}
		}
	}
	$host = $_SERVER['HTTP_X_FORWARDED_HOST'];
	if (strpos($host,',')!==false){
		$h = explode(',',$host);
		$host = trim(reset($h));
	}
	// securite sur le contenu de l'entete
	$host = strtr($host, "<>?\"\{\}\$'` \r\n", '____________');
	$_SERVER['HTTP_HOST'] = $host;
}
//
// On note le numero IP du client dans la variable $ip
//
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	if (strpos($ip,',')!==false){
		$ip = explode(',',$ip);
		$ip = reset($ip);
	}
	// ecraser $_SERVER['REMOTE_ADDR'] si elle est en localhost
	if (isset($_SERVER['REMOTE_ADDR']) AND $_SERVER['REMOTE_ADDR']==='127.0.0.1'){
		$_SERVER['REMOTE_ADDR'] = $ip;
	}
}
if (isset($_SERVER['REMOTE_ADDR'])) {
	$ip = $_SERVER['REMOTE_ADDR'];
}

// Pour renforcer la privacy, decommentez la ligne ci-dessous (ou recopiez-la
// dans le fichier config/mes_options) : SPIP ne pourra alors conserver aucun
// numero IP, ni temporairement lors des visites (pour gerer les statistiques
// ou dans spip.log), ni dans les forums (responsabilite)
# $ip = substr(md5($ip),0,16);


// faut-il faire des connexions Mysql rappelant le nom de la base MySQL ?
// (utile si vos squelettes appellent d'autres bases MySQL)
// (A desactiver en cas de soucis de connexion chez certains hebergeurs)
// Note: un test a l'installation peut aussi avoir desactive
// $mysql_rappel_nom_base directement dans le fichier inc_connect
$mysql_rappel_nom_base = true;

// faut-il afficher en rouge les chaines non traduites ?
$test_i18n = false;

// faut-il ignorer l'authentification par auth http/remote_user ?
$ignore_auth_http = false;
$ignore_remote_user = true; # methode obsolete et risquee

// Invalider les caches a chaque modification du contenu ?
// Si votre site a des problemes de performance face a une charge tres elevee,
// vous pouvez mettre cette globale a false (dans mes_options).
$derniere_modif_invalide = true;

//
// Serveurs externes
//
# Serveur de documentation officielle
$home_server = 'https://www.spip.net';
# glossaire pour raccourci [?X]. Aussi: [?X#G] et definir glossaire_G
$url_glossaire_externe = "https://@lang@.wikipedia.org/wiki/%s";

# TeX
$tex_server = 'https://math.spip.org/tex.php';
# MathML (pas pour l'instant: manque un bon convertisseur)
// $mathml_server = 'http://arno.rezo.net/tex2mathml/latex.php';

// Produire du TeX ou du MathML ?
$traiter_math = 'tex';

// Appliquer un indenteur XHTML aux espaces public et/ou prive ?
$xhtml = false;
$xml_indent = false;

$formats_logos = array('jpg', 'png', 'svg', 'gif', 'webp');

// Controler les dates des item dans les flux RSS ?
$controler_dates_rss = true;


//
// Pipelines & plugins
//
# les pipeline standards (traitements derivables aka points d'entree)
# ils seront compiles par la suite
# note: un pipeline non reference se compile aussi, mais uniquement
# lorsqu'il est rencontre
// https://programmer.spip.net/-Les-pipelines-
$spip_pipeline = array();

# la matrice standard (fichiers definissant les fonctions a inclure)
$spip_matrice = array();
# les plugins a activer
$plugins = array();  // voir le contenu du repertoire /plugins/
# les surcharges de include_spip()
$surcharges = array(); // format 'inc_truc' => '/plugins/chose/inc_truc2.php'

// Variables du compilateur de squelettes

$exceptions_des_tables = array();
$tables_principales = array();
$table_des_tables = array();
$tables_auxiliaires = array();
$table_primary = array();
$table_date = array();
$table_titre = array();
$tables_jointures = array();

// Liste des statuts.
$liste_des_statuts = array(
	"info_administrateurs" => '0minirezo',
	"info_redacteurs" => '1comite',
	"info_visiteurs" => '6forum',
	"texte_statut_poubelle" => '5poubelle'
);

$liste_des_etats = array(
	'texte_statut_en_cours_redaction' => 'prepa',
	'texte_statut_propose_evaluation' => 'prop',
	'texte_statut_publie' => 'publie',
	'texte_statut_poubelle' => 'poubelle',
	'texte_statut_refuse' => 'refuse'
);

// liste des methodes d'authentifications
$liste_des_authentifications = array(
	'spip' => 'spip',
	'ldap' => 'ldap'
);

// Experimental : pour supprimer systematiquement l'affichage des numeros
// de classement des titres, recopier la ligne suivante dans mes_options :
# $table_des_traitements['TITRE'][]= 'typo(supprimer_numero(%s), "TYPO", $connect)';

// Droits d'acces maximum par defaut
@umask(0);

// numero de branche, utilise par les plugins
// pour specifier les versions de SPIP necessaires
// il faut s'en tenir a un nombre de decimales fixe
// ex : 2.0.0, 2.0.0-dev, 2.0.0-beta, 2.0.0-beta2
$spip_version_branche = "4.1.0-dev";
define('_SPIP_VERSION_ID', 41000);
define('_SPIP_EXTRA_VERSION', '-dev');

// cette version dev accepte tous les plugins compatible avec la version ci-dessous
// a supprimer en phase beta/rc/release
define('_DEV_VERSION_SPIP_COMPAT',"4.0.99");
// version des signatures de fonctions PHP
// (= date de leur derniere modif cassant la compatibilite et/ou necessitant un recalcul des squelettes)
$spip_version_code = 20210708;
// version de la base SQL (= Date + numero incremental a 2 chiffres YYYYMMDDXX)
$spip_version_base = 2021021800;

// version de l'interface a la base
$spip_sql_version = 1;

// version de spip en chaine
// 1.xxyy : xx00 versions stables publiees, xxyy versions de dev
// (ce qui marche pour yy ne marchera pas forcement sur une version plus ancienne)
$spip_version_affichee = "$spip_version_branche";

// ** Securite **
$visiteur_session = $auteur_session = $connect_statut = $connect_toutes_rubriques = $hash_recherche = $hash_recherche_strict = $ldap_present = '';
$meta = $connect_id_rubrique = array();

// *** Fin des globales *** //

//
// Charger les fonctions liees aux serveurs Http et Sql.
//
require_once _ROOT_RESTREINT . 'inc/utils.php';
require_once _ROOT_RESTREINT . 'base/connect_sql.php';

// Definition personnelles eventuelles

if (_FILE_OPTIONS) {
	include_once _FILE_OPTIONS;
}

if (!defined('SPIP_ERREUR_REPORT')) {
	/** Masquer les warning */
	define('SPIP_ERREUR_REPORT', E_ALL ^ E_NOTICE ^ E_DEPRECATED);
}
error_reporting(SPIP_ERREUR_REPORT);

// Initialisations critiques non surchargeables par les plugins
// INITIALISER LES REPERTOIRES NON PARTAGEABLES ET LES CONSTANTES
// (charge aussi inc/flock)
//
// mais l'inclusion precedente a peut-etre deja appele cette fonction
// ou a defini certaines des constantes que cette fonction doit definir
// ===> on execute en neutralisant les messages d'erreur

spip_initialisation_core(
	(_DIR_RACINE . _NOM_PERMANENTS_INACCESSIBLES),
	(_DIR_RACINE . _NOM_PERMANENTS_ACCESSIBLES),
	(_DIR_RACINE . _NOM_TEMPORAIRES_INACCESSIBLES),
	(_DIR_RACINE . _NOM_TEMPORAIRES_ACCESSIBLES)
);


// chargement des plugins : doit arriver en dernier
// car dans les plugins on peut inclure inc-version
// qui ne sera pas execute car _ECRIRE_INC_VERSION est defini
// donc il faut avoir tout fini ici avant de charger les plugins

if (@is_readable(_CACHE_PLUGINS_OPT) and @is_readable(_CACHE_PLUGINS_PATH)) {
	// chargement optimise precompile
	include_once(_CACHE_PLUGINS_OPT);
} else {
	spip_initialisation_suite();
	include_spip('inc/plugin');
	// generer les fichiers php precompiles
	// de chargement des plugins et des pipelines
	actualise_plugins_actifs();
}

// Initialisations non critiques surchargeables par les plugins
spip_initialisation_suite();

if (!defined('_LOG_FILTRE_GRAVITE')) {
	/** niveau maxi d'enregistrement des logs */
	define('_LOG_FILTRE_GRAVITE', _LOG_INFO_IMPORTANTE);
}

if (!defined('_OUTILS_DEVELOPPEURS')) {
	/** Activer des outils pour développeurs ? */
	define('_OUTILS_DEVELOPPEURS', false);
}

// charger systematiquement inc/autoriser dans l'espace restreint
if (test_espace_prive()) {
	include_spip('inc/autoriser');
}
//
// Installer Spip si pas installe... sauf si justement on est en train
//
if (!(_FILE_CONNECT
	or autoriser_sans_cookie(_request('exec'))
	or _request('action') == 'cookie'
	or _request('action') == 'converser'
	or _request('action') == 'test_dirs')
) {

	// Si on peut installer, on lance illico
	if (test_espace_prive()) {
		include_spip('inc/headers');
		redirige_url_ecrire("install");
	} else {
		// Si on est dans le site public, dire que qq s'en occupe
		include_spip('inc/minipres');
		utiliser_langue_visiteur();
		echo minipres(_T('info_travaux_titre'), "<p style='text-align: center;'>" . _T('info_travaux_texte') . "</p>", array('status' => 503));
		exit;
	}
	// autrement c'est une install ad hoc (spikini...), on sait pas faire
}

// memoriser un tri sessionne eventuel
if (isset($_REQUEST['var_memotri'])
	and $t = $_REQUEST['var_memotri']
	and (strncmp($t, 'trisession', 10) == 0 or strncmp($t, 'senssession', 11) == 0)
) {
	if (!function_exists('session_set')) {
		include_spip('inc/session');
	}
	$t = preg_replace(",\W,","_", $t);
	if ($v = _request($t)) {
		session_set($t, $v);
	}
}

/**
 * Header "Composed-By"
 *
 * Vanter notre art de la composition typographique
 * La globale $spip_header_silencieux permet de rendre le header minimal pour raisons de securite
 */
if (!defined('_HEADER_COMPOSED_BY')) {
	define('_HEADER_COMPOSED_BY', "Composed-By: SPIP");
}
if (!headers_sent() and _HEADER_COMPOSED_BY) {
	if (!defined('_HEADER_VARY')) {
		define('_HEADER_VARY', "Vary: Cookie, Accept-Encoding");
	}
	if (_HEADER_VARY) {
		header(_HEADER_VARY);
	}
	if (!isset($GLOBALS['spip_header_silencieux']) or !$GLOBALS['spip_header_silencieux']) {
		include_spip('inc/filtres_mini');
		header(_HEADER_COMPOSED_BY . " $spip_version_affichee @ www.spip.net + " . url_absolue(_DIR_VAR . "config.txt"));
	} else {
		// header minimal
		header(_HEADER_COMPOSED_BY . " @ www.spip.net");
	}
}

$methode = (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : ((php_sapi_name() == 'cli') ? 'cli' : ''));
spip_log($methode . ' ' . self() . ' - ' . _FILE_CONNECT, _LOG_DEBUG);
