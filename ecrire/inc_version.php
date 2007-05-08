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


if (defined("_ECRIRE_INC_VERSION")) return;
define("_ECRIRE_INC_VERSION", "1");

# masquer les eventuelles erreurs sur les premiers define
error_reporting(E_ALL ^ E_NOTICE);
# compatibilite anciennes versions
# si vous n'avez aucun fichier .php3, redefinissez a ""
# ca fera foncer find_in_path
define('_EXTENSION_PHP', '.php3');
#define('_EXTENSION_PHP', '');

# le nom du repertoire ecrire/
define('_DIR_RESTREINT_ABS', 'ecrire/');
# sommes-nous dans ecrire/ ?
define('_DIR_RESTREINT',
 (!is_dir(_DIR_RESTREINT_ABS) ? "" : _DIR_RESTREINT_ABS));
# ou inversement ?
define('_DIR_RACINE', _DIR_RESTREINT ? '' : '../');

// nombre de repertoires depuis la racine
$profondeur_url = _DIR_RESTREINT ? 0 : 1;

# le chemin http (relatif) vers les bibliotheques JavaScript
define('_DIR_JAVASCRIPT', (_DIR_RACINE . 'dist/javascript/'));

	// Icones
# le chemin http (relatif) vers les images standard
define('_DIR_IMG_PACK', (_DIR_RACINE . 'dist/images/'));
# le chemin des vignettes de type de document
define('_DIR_IMG_ICONES_DIST', _DIR_RACINE . "dist/vignettes/");
# le chemin des icones de la barre d'edition des formulaires
define('_DIR_IMG_ICONES_BARRE', _DIR_RACINE . "dist/icones_barre/");

# le chemin php (absolu) vers les images standard (pour hebergement centralise)
define('_ROOT_IMG_PACK', dirname(dirname(__FILE__)) . '/dist/images/');
define('_ROOT_IMG_ICONES_DIST', dirname(dirname(__FILE__)) . '/dist/vignettes/');


# Le nom des 4 repertoires modifiables par les scripts lances par httpd
# Par defaut ces 4 noms seront suffixes par _DIR_RACINE (cf plus bas)
# mais on peut les mettre ailleurs et changer completement les noms

# le nom du repertoire des fichiers Temporaires Inaccessibles par http://
define('_NOM_TEMPORAIRES_INACCESSIBLES', "tmp/");
# le nom du repertoire des fichiers Temporaires Accessibles par http://
define('_NOM_TEMPORAIRES_ACCESSIBLES', "local/");
# le nom du repertoire des fichiers Permanents Inaccessibles par http://
define('_NOM_PERMANENTS_INACCESSIBLES', "config/");
# le nom du repertoire des fichiers Permanents Accessibles par http://
define('_NOM_PERMANENTS_ACCESSIBLES', "IMG/");

// Le nom du fichier de personnalisation
define('_NOM_CONFIG', 'mes_options');

// Son emplacement absolu si on le trouve

if (@file_exists($f = _DIR_RESTREINT . _NOM_CONFIG . '.php')
OR (_EXTENSION_PHP
	AND @file_exists($f = _DIR_RESTREINT . _NOM_CONFIG . _EXTENSION_PHP))
OR (@file_exists($f = _DIR_RACINE . _NOM_PERMANENTS_INACCESSIBLES . _NOM_CONFIG . '.php'))) {
	define('_FILE_OPTIONS', $f);
} else define('_FILE_OPTIONS', '');

// *** Fin des define *** //

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

// Prefixe des tables dans la base de donnees
// (a modifier pour avoir plusieurs sites SPIP dans une seule base)
$table_prefix = "spip";

// Prefixe et chemin des cookies
// (a modifier pour installer des sites SPIP dans des sous-repertoires)
$cookie_prefix = "spip";
$cookie_path = "";

// Dossier des squelettes
// (a modifier si l'on veut passer rapidement d'un jeu de squelettes a un autre)
$dossier_squelettes = "";

// faut-il autoriser SPIP a compresser les pages a la volee quand le
// navigateur l'accepte (valable pour apache >= 1.3 seulement) ?
$auto_compress = true;

// Pour le javascript, trois modes : parano (-1), prive (0), ok (1)
// parano le refuse partout, ok l'accepte partout
// le mode par defaut le signale en rouge dans l'espace prive
$filtrer_javascript = 0;
// PS: dans les forums, petitions, flux syndiques... c'est *toujours* securise

// Type d'URLs
// 'page': spip.php?article123 [c'est la valeur par defaut pour SPIP 1.9]
// 'html': article123.html
// 'propres': Titre-de-l-article <http://lab.spip.net/spikini/UrlsPropres>
// 'propres2' : Titre-de-l-article.html (base sur 'propres')
// 'standard': article.php3?id_article=123 [urls SPIP < 1.9]
$type_urls = 'page';


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

// Creation des images avec ImageMagick : definir la constante de facon
// a preciser le chemin du binaire et les options souhaitees. Par defaut :
// define('_CONVERT_COMMAND', 'convert');
// define('_RESIZE_COMMAND', _CONVERT_COMMAND.' -quality 85 -resize %xx%y! %src %dest');

// Creation des vignettes avec netpbm/pnmscale
// Note: plus facile a installer par FTP,
// voir http://gallery.menalto.com/modules.php?op=modload&name=GalleryFAQ&file=index&myfaq=yes&id_cat=2#43
// par defaut :
// define('_PNMSCALE_COMMAND', 'pnmscale');

// faut-il passer les connexions MySQL en mode debug ?
$mysql_debug = false;

// faut-il faire des connexions completes rappelant le nom du serveur et/ou de
// la base MySQL ? (utile si vos squelettes appellent d'autres bases MySQL)
// (A desactiver en cas de soucis de connexion chez certains hebergeurs)
// Note: un test a l'installation peut aussi avoir desactive
// $mysql_rappel_nom_base directement dans le fichier inc_connect
$mysql_rappel_connexion = true;
$mysql_rappel_nom_base = true;

// faut-il afficher en rouge les chaines non traduites ?
$test_i18n = false;

// gestion des extras (voir inc_extra pour plus d'informations)
$champs_extra = false;
$champs_extra_proposes = false;

// faut-il ignorer l'authentification par auth http/remote_user ?
$ignore_auth_http = false;
$ignore_remote_user = true; # methode obsolete et risquee

// Faut-il "invalider" les caches quand on depublie ou modifie un article ?
# NB: cette option ne concerne pas les forums et petitions qui sont toujours
# invalidants. (fonctionnalite experimentale : decommenter ci-dessous)
#$invalider_caches = 'id_article,id_breve,id_rubrique,id_syndic';
$invalider_caches = '';

// Autre approche : tout invalider si modif
// Si votre site a des problemes de performance face a une charge tres elevee,
// il est recommande de mettre cette globale a false (dans mes_options).
$derniere_modif_invalide = true;

// Quota : la variable $quota_cache, si elle est > 0, indique la taille
// totale maximale desiree des fichiers contenus dans le CACHE/ ;
// ce quota n'est pas "dur", il ne s'applique qu'une fois par heure et
// fait redescendre le cache a la taille voulue ; valeur en Mo
// Si la variable vaut 0 aucun quota ne s'applique
$quota_cache = 10;

//
// Serveurs externes
//
# aide en ligne
$home_server = 'http://www.spip.net';
$help_server = $home_server . '/aide';
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
// 3 de trouves, possibilite de switcher
// - Thumbshots.org: le moins instrusif, quand il n'a pas, il renvoit un pixel vide
// - Girafa semble le plus complet, bicoz renvoit toujours la page d'accueil; mais avertissement si pas de preview
// - Alexa, equivalent Thumbshots, avec vignettes beaucoup plus grandes mais avertissement si pas de preview
//   Pour Alexa, penser a indiquer l'url du site dans l'id.
//   Dans Alexa, si on supprimer size=small, alors vignettes tres grandes
$source_vignettes = "http://open.thumbshots.org/image.pxf?url=http://";
// $source_vignettes = "http://msnsearch.srv.girafa.com/srv/i?s=MSNSEARCH&r=http://";
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
$spip_pipeline = array(
	'affichage_final' => '|f_surligne|f_tidy|f_admin', # cf. public/assembler
	'affiche_droite' => '',
	'affiche_gauche' => '',
	'affiche_milieu' => '',
	'ajouter_boutons' => '',
	'ajouter_onglets' => '',
	'body_prive' => '',
	'exec_init' => '',
	'header_prive' => '|f_jQuery',
	'insert_head' => '|f_jQuery',
	'mots_indexation' => '',
	'nettoyer_raccourcis_typo' => '',
	'pre_propre' => '|extraire_multi',
	'post_propre' => '',
	'pre_typo' => '|extraire_multi',
	'post_typo' => '|quote_amp',
	'pre_edition' => '|premiere_revision',
	'post_edition' => '|nouvelle_revision',
	'pre_syndication' => '',
	'post_syndication' => '',
	'pre_indexation' => '',
	'requete_dico' => '',
	'agenda_rendu_evenement' => '',
	'taches_generales_cron' => '',
	'calculer_rubriques' => ''
);

# pour activer #INSERT_HEAD sur tous les squelettes, qu'ils aient ou non
# la balise, decommenter la ligne ci-dessous (+ supprimer tmp/charger_pipelines)
# $spip_pipeline['affichage_final'] .= '|f_insert_head';

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
$tables_jointures = array();
$tables_des_serveurs_sql['localhost'] =  &$tables_principales;

// Experimental : pour supprimer systematiquement l'affichage des numeros
// de classement des titres, recopier la ligne suivante dans mes_options :
# $table_des_traitements['TITRE'][]= 'typo(supprimer_numero(%s))';

// Droits d'acces maximum par defaut
@umask(0);

// Version courante de SPIP
// Stockee sous forme de nombre decimal afin de faciliter les comparaisons
// (utilise pour les modifs de la base de donnees)

// version de la base
$spip_version = 1.935;

// version de spip en chaine
// et en numerique a incrementer sur les evolutions qui cassent la compatibilite descendante
// 1.xxyy : xx00 versions stables publiees, xxyy versions de dev
// (ce qui marche pour yy ne marchera pas forcement sur une version plus ancienne)
// type nouvelles fonctionnalites, deplacement de fonctions ...
$spip_version_affichee = '1.9.3 dev';
$spip_version_code = '1.9253';

// ** Securite **
$auteur_session = $connect_statut = $connect_toutes_rubriques =  $hash_recherche = $hash_recherche_strict = $ldap_present ='';
$connect_id_rubrique = array();

// definition des lots de fichier precharges en blocs pour reduire les find_in_path en usage courant
// mecanisme desactivable par 
define('_PREFETCH_PREFIXE_FICHIERS','prefetch-v01-noyau-');
#define('_PAS_DE_PRECHARGEMENT_PHP',1);
// scenario typique de service d'une page en cache
$GLOBALS['prefetch']['inc/meta']['fetch']='service_mini';
$GLOBALS['prefetch']['inc/session']['fetch']='service_mini';
$GLOBALS['prefetch']['public/assembler']['fetch']='service_mini';
$GLOBALS['prefetch']['public/cacher']['fetch']='service_mini';
$GLOBALS['prefetch']['public/stats']['fetch']='service_mini';

// scenario typique de calcul d'un squelette
$GLOBALS['prefetch']['inc/charsets']['fetch']='calcul_skel';
$GLOBALS['prefetch']['inc/filtres']['fetch']='calcul_skel';
$GLOBALS['prefetch']['base/abstract_sql']['fetch']='calcul_skel';
//$GLOBALS['prefetch']['base/auxiliaires']['fetch']='calcul_skel'; // appele parfois sur un hit en cache
$GLOBALS['prefetch']['base/db_mysql']['fetch']='calcul_skel';
$GLOBALS['prefetch']['base/serial']['fetch']='calcul_skel';
$GLOBALS['prefetch']['base/typedoc']['fetch']='calcul_skel';
//$GLOBALS['prefetch']['inc/actions']['fetch']='calcul_skel'; // appele parfois sur un hit en cache
$GLOBALS['prefetch']['inc/acces']['fetch']='calcul_skel';
$GLOBALS['prefetch']['inc/date']['fetch']='calcul_skel';
$GLOBALS['prefetch']['inc/texte']['fetch']='calcul_skel';
$GLOBALS['prefetch']['public/parametrer']['fetch']='calcul_skel';
$GLOBALS['prefetch']['public/styliser']['fetch']='calcul_skel';
$GLOBALS['prefetch']['public/composer']['fetch']='calcul_skel';
$GLOBALS['prefetch']['public/interfaces']['fetch']='calcul_skel';
$GLOBALS['prefetch']['inc/documents']['fetch']='calcul_skel';

// *** Fin des globales *** //

//
// Definitions des fonctions (charge aussi inc/flock)
//

require_once(_DIR_RESTREINT . 'inc/utils.php');

// Definition personnelles eventuelles

if (_FILE_OPTIONS) include_once _FILE_OPTIONS;

// Masquer les warning
define('SPIP_ERREUR_REPORT',E_ALL ^ E_NOTICE);
define('SPIP_ERREUR_REPORT_INCLUDE_PLUGINS',0);
error_reporting(SPIP_ERREUR_REPORT);

//
// INITIALISER LES REPERTOIRES NON PARTAGEABLES ET LES CONSTANTES
//
// mais l'inclusion precedente a peut-etre deja appelee cette fonction
// ou a defini certaines des constantes que cette fontion doit definir
// ===> on execute en neutralisant les messages d'erreur

@spip_initialisation(
       (_DIR_RACINE  . _NOM_PERMANENTS_INACCESSIBLES),
       (_DIR_RACINE  . _NOM_PERMANENTS_ACCESSIBLES),
       (_DIR_RACINE  . _NOM_TEMPORAIRES_INACCESSIBLES),
       (_DIR_RACINE  . _NOM_TEMPORAIRES_ACCESSIBLES)
       );

// chargement des plugins : doit arriver en dernier
// car dans les plugins on peut inclure inc-version
// qui ne sera pas execute car _ECRIRE_INC_VERSION est defini
// donc il faut avoir tout fini ici avant de charger les plugins

if (@is_readable(_DIR_TMP."charger_plugins_options.php")){
	// chargement optimise precompile
	include_once(_DIR_TMP."charger_plugins_options.php");
} else {
	include_spip('inc/plugin');
	// generer les fichiers php precompiles
	// de chargement des plugins et des pipelines
	verif_plugin();
	if (@is_readable(_DIR_TMP."charger_plugins_options.php")){
		include_once(_DIR_TMP."charger_plugins_options.php");
	}
	else
		spip_log("generation de charger_plugins_options.php impossible; pipeline desactives");
}

define('_OUTILS_DEVELOPPEURS',true);

// charger systematiquement inc/autoriser dans l'espace restreint
if (!_DIR_RESTREINT)
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
	if (!_DIR_RESTREINT) {
		include_spip('inc/headers');
		redirige_par_entete(generer_url_ecrire("install"));
	} else {
	// Si on est dans le site public, dire que qq s'en occupe
		include_spip('inc/minipres');
		utiliser_langue_visiteur();
		echo minipres(_T('info_travaux_titre'), "<p style='text-align: center;'>"._T('info_travaux_texte')."</p>");
		exit;
	}
	// autrement c'est une install ad hoc (spikini...), on sait pas faire
}

//
// Reglage de l'output buffering : si possible, generer une sortie
// compressee pour economiser de la bande passante ; sauf dans l'espace
// prive car sinon ca rame a l'affichage (a revoir...)
//

// si un buffer est deja ouvert, stop
if (_DIR_RESTREINT AND _request('action')===NULL AND $flag_ob AND strlen(ob_get_contents())==0 AND !headers_sent()) {
	@header("Vary: Cookie, Accept-Encoding");

	if (
	$GLOBALS['auto_compress']
	&& (phpversion()<>'4.0.4')
	&& function_exists("ob_gzhandler")
	// special bug de proxy
	&& !(isset($GLOBALS['HTTP_VIA']) AND preg_match(",NetCache|Hasd_proxy,i", $GLOBALS['HTTP_VIA']))
	// special bug Netscape Win 4.0x
	&& !preg_match(",Mozilla/4\.0[^ ].*Win,i", $GLOBALS['HTTP_USER_AGENT'])
	// special bug Apache2x
	&& !preg_match(",Apache(-[^ ]+)?/2,i", $GLOBALS['SERVER_SOFTWARE'])
	// test suspendu: http://article.gmane.org/gmane.comp.web.spip.devel/32038/
	#&& !($GLOBALS['flag_sapi_name'] AND preg_match(",^apache2,", @php_sapi_name()))
	// si la compression est deja commencee, stop
	&& !@ini_get("zlib.output_compression")
	&& !@ini_get("output_handler")
	&& !isset($GLOBALS['var_mode']) # bug avec le debugueur qui appelle ob_end_clean()
	)
		ob_start('ob_gzhandler');
}
else
	@header("Vary: Cookie");

// Vanter notre art de la composition typographique
// La globale $spip_header_silencieux permet de rendre le header minimal pour raisons de securite
define('_HEADER_COMPOSED_BY', "Composed-By: SPIP");

if (!headers_sent())
	if (!isset($GLOBALS['spip_header_silencieux']) OR !$GLOBALS['spip_header_silencieux'])
		@header(_HEADER_COMPOSED_BY . " $spip_version_affichee @ www.spip.net" . (isset($GLOBALS['meta']['plugin_header'])?(" + ".$GLOBALS['meta']['plugin_header']):""));
	else // header minimal
		@header(_HEADER_COMPOSED_BY . " @ www.spip.net");

# spip_log($_SERVER['REQUEST_METHOD'].' '.self() . ' - '._FILE_CONNECT);

?>
