<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/
#

if (defined('_ECRIRE_INC_VERSION')) return;
define('_ECRIRE_INC_VERSION', "1");

# masquer les eventuelles erreurs sur les premiers define
error_reporting(E_ALL ^ E_NOTICE);

# version PHP minimum exigee (cf. inc/utils)
define ('_PHP_MIN', '5.1.0');

# le nom du repertoire ecrire/
if (!defined('_DIR_RESTREINT_ABS')) define('_DIR_RESTREINT_ABS', 'ecrire/');
# sommes-nous dans ecrire/ ?
define('_DIR_RESTREINT',
 (!is_dir(_DIR_RESTREINT_ABS) ? "" : _DIR_RESTREINT_ABS));
# ou inversement ?
define('_DIR_RACINE', _DIR_RESTREINT ? '' : '../');

# chemins absolus
define('_ROOT_RACINE', dirname(dirname(__FILE__)).'/');
define('_ROOT_CWD', getcwd().'/');
define('_ROOT_RESTREINT', _ROOT_CWD . _DIR_RESTREINT);

// Icones
# nom du dossier images
if (!defined('_NOM_IMG_PACK')) define('_NOM_IMG_PACK', 'images/');
# le chemin http (relatif) vers les images standard
define('_DIR_IMG_PACK', (_DIR_RACINE . 'prive/' . _NOM_IMG_PACK));

# le chemin php (absolu) vers les images standard (pour hebergement centralise)
define('_ROOT_IMG_PACK', dirname(dirname(__FILE__)) . '/prive/' . _NOM_IMG_PACK);

# le nom du repertoire des  bibliotheques JavaScript
if (!defined('_JAVASCRIPT')) define('_JAVASCRIPT', 'javascript/'); // utilisable avec #CHEMIN et find_in_path
define('_DIR_JAVASCRIPT', (_DIR_RACINE . 'prive/' . _JAVASCRIPT));

# Le nom des 4 repertoires modifiables par les scripts lances par httpd
# Par defaut ces 4 noms seront suffixes par _DIR_RACINE (cf plus bas)
# mais on peut les mettre ailleurs et changer completement les noms

# le nom du repertoire des fichiers Temporaires Inaccessibles par http://
if (!defined('_NOM_TEMPORAIRES_INACCESSIBLES')) define('_NOM_TEMPORAIRES_INACCESSIBLES', "tmp/");
# le nom du repertoire des fichiers Temporaires Accessibles par http://
if (!defined('_NOM_TEMPORAIRES_ACCESSIBLES')) define('_NOM_TEMPORAIRES_ACCESSIBLES', "local/");
# le nom du repertoire des fichiers Permanents Inaccessibles par http://
if (!defined('_NOM_PERMANENTS_INACCESSIBLES')) define('_NOM_PERMANENTS_INACCESSIBLES', "config/");
# le nom du repertoire des fichiers Permanents Accessibles par http://
if (!defined('_NOM_PERMANENTS_ACCESSIBLES')) define('_NOM_PERMANENTS_ACCESSIBLES', "IMG/");


// Le nom du fichier de personnalisation
if (!defined('_NOM_CONFIG')) define('_NOM_CONFIG', 'mes_options');

// Son emplacement absolu si on le trouve
if (@file_exists($f = _ROOT_RACINE . _NOM_PERMANENTS_INACCESSIBLES . _NOM_CONFIG . '.php')
OR (@file_exists($f = _ROOT_RESTREINT . _NOM_CONFIG . '.php'))) {
	define('_FILE_OPTIONS', $f);
} else define('_FILE_OPTIONS', '');

// les modules par defaut pour la traduction.
// Constante utilisee par le compilateur et le decompilateur
// sa valeur etant traitee par inc_traduire_dist

if (!defined('MODULES_IDIOMES')) define('MODULES_IDIOMES', 'public|spip|ecrire');

// *** Fin des define *** //


// Inclure l'ecran de securite
if (!defined('_ECRAN_SECURITE')
AND @file_exists($f = _ROOT_RACINE . _NOM_PERMANENTS_INACCESSIBLES . 'ecran_securite.php'))
	include $f;

/*
 * detecteur de robot d'indexation
 * utilise en divers endroits, centralise ici si l'ecran l'a pas deja fait
 */
if (!defined('_IS_BOT'))
	define('_IS_BOT',
		isset($_SERVER['HTTP_USER_AGENT'])
		AND preg_match(',bot|slurp|crawler|spider|webvac|yandex|INA dlweb|EC2LinkFinder|80legs,i',
			$_SERVER['HTTP_USER_AGENT'])
	);

//
// *** Parametrage par defaut de SPIP ***
//
// Les globales qui suivent peuvent etre modifiees
// dans le fichier de personnalisation indique ci-dessus.
// Il suffit de copier les lignes ci-dessous, et ajouter le marquage de debut
// et fin de fichier PHP ("< ?php" et "? >", sans les espaces)
// Ne pas les rendre indefinies.

# comment on logge, defaut 4 tmp/spip.log de 100k, 0 ou 0 suppriment le log
$nombre_de_logs = 4;
$taille_des_logs = 100;

// Definir les niveaux de log
defined('_LOG_HS') || define('_LOG_HS', 0);
defined('_LOG_ALERTE_ROUGE') || define('_LOG_ALERTE_ROUGE', 1);
defined('_LOG_CRITIQUE') || define('_LOG_CRITIQUE', 2);
defined('_LOG_ERREUR') || define('_LOG_ERREUR', 3);
defined('_LOG_AVERTISSEMENT') || define('_LOG_AVERTISSEMENT', 4);
defined('_LOG_INFO_IMPORTANTE') || define ('_LOG_INFO_IMPORTANTE', 5);
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
// 'page': spip.php?article123 [c'est la valeur par defaut pour SPIP 2.0]
// 'html': article123.html
// 'propres': Titre-de-l-article
// 'propres2' : Titre-de-l-article.html (base sur 'propres')
// 'arbo' : /article/Titre
$type_urls = 'page'; // 'page' => surcharge possible par configuration

#la premiere date dans le menu deroulant de date de publication
# null: automatiquement (affiche les 8 dernieres annees)
# 0: affiche un input libre
# 1997: le menu commence a 1997 jusqu'a annee en cours
$debut_date_publication = null;



//
// On note le numero IP du client dans la variable $ip
//
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
if (isset($_SERVER['REMOTE_ADDR'])) $ip = $_SERVER['REMOTE_ADDR'];

// Pour renforcer la privacy, decommentez la ligne ci-dessous (ou recopiez-la
// dans le fichier config/mes_options) : SPIP ne pourra alors conserver aucun
// numero IP, ni temporairement lors des visites (pour gerer les statistiques
// ou dans spip.log), ni dans les forums (responsabilite)
# $ip = substr(md5($ip),0,16);


// faut-il faire des connexions completes rappelant le nom du serveur et/ou de
// la base MySQL ? (utile si vos squelettes appellent d'autres bases MySQL)
// (A desactiver en cas de soucis de connexion chez certains hebergeurs)
// Note: un test a l'installation peut aussi avoir desactive
// $mysql_rappel_nom_base directement dans le fichier inc_connect
$mysql_rappel_connexion = true;
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

// Quota : la variable $quota_cache, si elle est > 0, indique la taille
// totale maximale desiree des fichiers contenus dans le cache ; ce quota n'est
// pas "dur" : si le site necessite un espace plus important, il le prend
$quota_cache = 10;

//
// Serveurs externes
//
# aide en ligne
$home_server = 'http://www.spip.net';
$help_server = array($home_server . '/aide');
# glossaire pour raccourci [?X]. Aussi: [?X#G] et definir glossaire_G
$url_glossaire_externe =  "http://@lang@.wikipedia.org/wiki/%s";

# TeX
$tex_server = 'http://math.spip.org/tex.php';
# MathML (pas pour l'instant: manque un bon convertisseur)
// $mathml_server = 'http://arno.rezo.net/tex2mathml/latex.php';

// Produire du TeX ou du MathML ?
$traiter_math = 'tex';

// Appliquer un indenteur XHTML aux espaces public et/ou prive ?
$xhtml = false;
$xml_indent = false;

// Vignettes de previsulation des referers
// dans les statistiques
// 2 de trouves, possibilite de switcher
// - Alexa, equivalent Thumbshots, avec vignettes beaucoup plus grandes mais avertissement si pas de preview
//   Pour Alexa, penser a indiquer l'url du site dans l'id.
//   Dans Alexa, si on supprimer size=small, alors vignettes tres grandes
// - apercite.fr : on conserve exactement la même expression pour insérer l'url du site
$source_vignettes = "http://www.apercite.fr/api/apercite/120x90/oui/oui/http://";
// $source_vignettes = "http://pthumbnails.alexa.com/image_server.cgi?id=www.monsite.net&size=small&url=http://";

$formats_logos =  array ('gif', 'jpg', 'png');

// Controler les dates des item dans les flux RSS ?
$controler_dates_rss = true;


//
// Pipelines & plugins
//
# les pipeline standards (traitements derivables aka points d'entree)
# ils seront compiles par la suite
# note: un pipeline non reference se compile aussi, mais uniquement
# lorsqu'il est rencontre
// http://doc.spip.org/@Tuto-Se-servir-des-points-d-entree
$spip_pipeline = array();

# la matrice standard (fichiers definissant les fonctions a inclure)
$spip_matrice = array ();
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
	"info_redacteurs" =>'1comite',
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
	'spip'=>'spip',
	'ldap'=>'ldap'
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
$spip_version_branche = "3.0.17";
// version des signatures de fonctions PHP
// (= numero SVN de leur derniere modif cassant la compatibilite et/ou necessitant un recalcul des squelettes)
$spip_version_code = 17873;
// version de la base SQL (= numero SVN de sa derniere modif)
$spip_version_base = 19268;

// version de l'interface a la base
$spip_sql_version = 1;

// version de spip en chaine
// 1.xxyy : xx00 versions stables publiees, xxyy versions de dev
// (ce qui marche pour yy ne marchera pas forcement sur une version plus ancienne)
$spip_version_affichee = "$spip_version_branche";

// ** Securite **
$visiteur_session = $auteur_session = $connect_statut = $connect_toutes_rubriques =  $hash_recherche = $hash_recherche_strict = $ldap_present ='';
$meta = $connect_id_rubrique = array();

// *** Fin des globales *** //

//
// Charger les fonctions liees aux serveurs Http et Sql.
//
require_once _ROOT_RESTREINT . 'inc/utils.php';
require_once _ROOT_RESTREINT . 'base/connect_sql.php';

// Definition personnelles eventuelles

if (_FILE_OPTIONS) {include_once _FILE_OPTIONS;}

// Masquer les warning
if (!defined('E_DEPRECATED')) define('E_DEPRECATED', 8192); // compatibilite PHP 5.3
if (!defined('SPIP_ERREUR_REPORT')) define('SPIP_ERREUR_REPORT', E_ALL ^ E_NOTICE ^ E_DEPRECATED);
error_reporting(SPIP_ERREUR_REPORT);

// Initialisations critiques non surchargeables par les plugins
// INITIALISER LES REPERTOIRES NON PARTAGEABLES ET LES CONSTANTES
// (charge aussi inc/flock)
//
// mais l'inclusion precedente a peut-etre deja appele cette fonction
// ou a defini certaines des constantes que cette fonction doit definir
// ===> on execute en neutralisant les messages d'erreur

spip_initialisation_core(
	(_DIR_RACINE  . _NOM_PERMANENTS_INACCESSIBLES),
	(_DIR_RACINE  . _NOM_PERMANENTS_ACCESSIBLES),
	(_DIR_RACINE  . _NOM_TEMPORAIRES_INACCESSIBLES),
	(_DIR_RACINE  . _NOM_TEMPORAIRES_ACCESSIBLES)
);


// chargement des plugins : doit arriver en dernier
// car dans les plugins on peut inclure inc-version
// qui ne sera pas execute car _ECRIRE_INC_VERSION est defini
// donc il faut avoir tout fini ici avant de charger les plugins

if (@is_readable(_CACHE_PLUGINS_OPT) AND @is_readable(_CACHE_PLUGINS_PATH)){
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

// niveau maxi d'enregistrement des logs
defined('_LOG_FILTRE_GRAVITE') || define('_LOG_FILTRE_GRAVITE', _LOG_INFO_IMPORTANTE);

if (!defined('_OUTILS_DEVELOPPEURS'))
	define('_OUTILS_DEVELOPPEURS',false);

// charger systematiquement inc/autoriser dans l'espace restreint
if (test_espace_prive())
	include_spip('inc/autoriser');
//
// Installer Spip si pas installe... sauf si justement on est en train
//
if (!(_FILE_CONNECT
OR autoriser_sans_cookie(_request('exec'))
OR _request('action') == 'cookie'
OR _request('action') == 'converser'
OR _request('action') == 'test_dirs')) {

	// Si on peut installer, on lance illico
	if (test_espace_prive()) {
		include_spip('inc/headers');
		redirige_url_ecrire("install");
	} else {
	// Si on est dans le site public, dire que qq s'en occupe
		include_spip('inc/minipres');
		utiliser_langue_visiteur();
		echo minipres(_T('info_travaux_titre'), "<p style='text-align: center;'>"._T('info_travaux_texte')."</p>");
		exit;
	}
	// autrement c'est une install ad hoc (spikini...), on sait pas faire
}


// Vanter notre art de la composition typographique
// La globale $spip_header_silencieux permet de rendre le header minimal pour raisons de securite
if (!defined('_HEADER_COMPOSED_BY')) define('_HEADER_COMPOSED_BY', "Composed-By: SPIP");
if (!headers_sent()) {
	header("Vary: Cookie, Accept-Encoding");
	if (!isset($GLOBALS['spip_header_silencieux']) OR !$GLOBALS['spip_header_silencieux'])
		header(_HEADER_COMPOSED_BY . " $spip_version_affichee @ www.spip.net" . (isset($GLOBALS['meta']['plugin_header'])?(" + ".$GLOBALS['meta']['plugin_header']):""));
	else // header minimal
		header(_HEADER_COMPOSED_BY . " @ www.spip.net");
}

spip_log($_SERVER['REQUEST_METHOD'].' '.self() . ' - '._FILE_CONNECT,_LOG_DEBUG);

?>
