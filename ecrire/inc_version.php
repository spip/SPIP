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

// Icones
# nom du dossier images
define('_NOM_IMG_PACK', 'images/');
# le chemin http (relatif) vers les images standard
define('_DIR_IMG_PACK', (_DIR_RACINE . 'dist/' . _NOM_IMG_PACK));
# le chemin des vignettes de type de document
define('_DIR_IMG_ICONES_DIST', _DIR_RACINE . "dist/vignettes/");
# le chemin des icones de la barre d'edition des formulaires
define('_DIR_IMG_ICONES_BARRE', _DIR_RACINE . "dist/icones_barre/");

# le chemin php (absolu) vers les images standard (pour hebergement centralise)
define('_ROOT_IMG_PACK', dirname(dirname(__FILE__)) . '/dist/' . _NOM_IMG_PACK);
define('_ROOT_IMG_ICONES_DIST', dirname(dirname(__FILE__)) . '/dist/vignettes/');

# le nom du repertoire des  bibliotheques JavaScript
define('_JAVASCRIPT', 'javascript/'); // utilisable avec #CHEMIN et find_in_path
define('_DIR_JAVASCRIPT', (_DIR_RACINE . 'dist/' . _JAVASCRIPT));

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

// Prefixe des cookies
// (a modifier pour installer des sites SPIP dans des sous-repertoires)
$cookie_prefix = "spip";

// Dossier des squelettes
// (a modifier si l'on veut passer rapidement d'un jeu de squelettes a un autre)
$dossier_squelettes = "";

// faut-il autoriser SPIP a compresser les pages a la volee quand le
// navigateur l'accepte (valable pour apache >= 1.3 seulement) ?
// du point de vue d'un webmestre : oui pour sa bande passante
// du point de vue de l'ecologie generale du serveur : il faut s'en remettre a la config apache
// true permet au webmestre de configurer dans le configurateur
// false force a non sans permettre de l'activer
$auto_compress = true;

// Pour le javascript, trois modes : parano (-1), prive (0), ok (1)
// parano le refuse partout, ok l'accepte partout
// le mode par defaut le signale en rouge dans l'espace prive
// Si < 1, les fichiers SVG sont traites s'ils emanent d'un redacteur
$filtrer_javascript = 0;
// PS: dans les forums, petitions, flux syndiques... c'est *toujours* securise

// Type d'URLs
// 'page': spip.php?article123 [c'est la valeur par defaut pour SPIP 1.9]
// 'html': article123.html
// 'propres': Titre-de-l-article <http://lab.spip.net/spikini/UrlsPropres>
// 'propres2' : Titre-de-l-article.html (base sur 'propres')
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
	'accueil_encours' => '',
	'accueil_gadgets' => '',
	'accueil_informations' => '',
	 # cf. public/assembler
	'affichage_final' => '|f_surligne|f_tidy|f_admin|f_msie',
	'affiche_droite' => '',
	'affiche_gauche' => '',
	'affiche_milieu' => '',
	'boite_infos' => 'f_boite_infos',
	'ajouter_boutons' => '',
	'ajouter_onglets' => '',
	'body_prive' => '',
	'definir_session' => '',
	'delete_all' => '',
	'delete_statistiques' => '',
	'exec_init' => '',
	'header_prive' => '|f_jQuery',
	'insert_head' => '|f_jQuery',
	'jquery_plugins' => '',
#	'insert_js' => '',
	'lister_tables_noexport' => '',
#	'verifie_js_necessaire' => '',
	'mots_indexation' => '',
	'nettoyer_raccourcis_typo' => '',
	'pre_boucle' => '',
	'post_boucle' => '',
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
	'calculer_rubriques' => '',
	'autoriser' => '',
	'notifications' => '',
	'afficher_contenu_objet' => '',
	'editer_contenu_objet' => '',
	'creer_chaine_url' => '|creer_chaine_url',
	'rechercher_liste_des_champs' => '' # inc/recherche; pas stabilise !
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

// Liste des statuts. 
$liste_des_statuts = array(
			"info_administrateurs" => '0minirezo',
			"info_redacteurs" =>'1comite',
			"info_visiteurs" => '6forum',
			"info_statut_site_4" => '5poubelle'
			);

$liste_des_etats = array(
			'texte_statut_en_cours_redaction' => 'prepa',
			'texte_statut_propose_evaluation' => 'prop',
			'texte_statut_publie' => 'publie',
			'texte_statut_poubelle' => 'poubelle',
			'texte_statut_refuse' => 'refuse' 
			);

$liste_des_forums = array(
			'bouton_radio_modere_posteriori' => 'pos',
			'bouton_radio_modere_priori' => 'pri',
			'bouton_radio_modere_abonnement' => 'abo',
			'info_pas_de_forum' => 'non'
);

// Experimental : pour supprimer systematiquement l'affichage des numeros
// de classement des titres, recopier la ligne suivante dans mes_options :
# $table_des_traitements['TITRE'][]= 'typo(supprimer_numero(%s))';

// Droits d'acces maximum par defaut
@umask(0);

// version des signatures de fonctions PHP
// (= numero SVN de leur derniere modif cassant la compatibilite et/ou necessitant un recalcul des squelettes)
$spip_version_code = 11493;
// version de la base SQL (= numero SVN de sa derniere modif)
$spip_version = 11431;

// version de l'interface a la base
$spip_sql_version = 1;

// version de spip en chaine
// 1.xxyy : xx00 versions stables publiees, xxyy versions de dev
// (ce qui marche pour yy ne marchera pas forcement sur une version plus ancienne)
$spip_version_affichee = '1.9.3 dev';

// ** Securite **
$visiteur_session = $auteur_session = $connect_statut = $connect_toutes_rubriques =  $hash_recherche = $hash_recherche_strict = $ldap_present ='';
$meta = $connect_id_rubrique = array();

// *** Fin des globales *** //

//
// Charger les fonctions liees aux serveurs Http et Sql.
//
require_once _DIR_RESTREINT . 'inc/utils.php';
require_once _DIR_RESTREINT . 'base/connect_sql.php';

// Definition personnelles eventuelles

if (_FILE_OPTIONS) include_once _FILE_OPTIONS;

// Masquer les warning
define('SPIP_ERREUR_REPORT',E_ALL ^ E_NOTICE);
define('SPIP_ERREUR_REPORT_INCLUDE_PLUGINS',0);
error_reporting(SPIP_ERREUR_REPORT);

//
// INITIALISER LES REPERTOIRES NON PARTAGEABLES ET LES CONSTANTES
// (charge aussi inc/flock)
//
// mais l'inclusion precedente a peut-etre deja appele cette fonction
// ou a defini certaines des constantes que cette fonction doit definir
// ===> on execute en neutralisant les messages d'erreur

@spip_initialisation(
	(_DIR_RACINE  . _NOM_PERMANENTS_INACCESSIBLES),
	(_DIR_RACINE  . _NOM_PERMANENTS_ACCESSIBLES),
	(_DIR_RACINE  . _NOM_TEMPORAIRES_INACCESSIBLES),
	(_DIR_RACINE  . _NOM_TEMPORAIRES_ACCESSIBLES)
);

define('_FILE_JQUERY', "\n<script src=\"".generer_url_public('jquery.js')
       . "\" type=\"text/javascript\"></script>\n");

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
	if (verif_plugin()) {
		if (@is_readable(_DIR_TMP."charger_plugins_options.php"))
			include_once(_DIR_TMP."charger_plugins_options.php");
		else
			spip_log("generation de charger_plugins_options.php impossible; pipeline desactives");
	}
}

if (!defined('_OUTILS_DEVELOPPEURS'))
	define('_OUTILS_DEVELOPPEURS',true);

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

@header("Vary: Cookie, Accept-Encoding");
// si un buffer est deja ouvert, stop
if (!test_espace_prive()
AND $flag_ob
AND strlen(ob_get_contents())==0
AND !headers_sent()) {
	if (
	($GLOBALS['auto_compress']!=false)# AND $GLOBALS['meta']['auto_compress']=='oui')
	&& function_exists('ob_gzhandler')
	// special bug de proxy
	&& !(isset($_SERVER['HTTP_VIA']) AND preg_match(",NetCache|Hasd_proxy,i", $_SERVER['HTTP_VIA']))
	// special bug Netscape Win 4.0x
	&& (strpos($_SERVER['HTTP_USER_AGENT'], 'Mozilla/4.0') === false)
	// special bug Apache2x
	#&& !preg_match(",Apache(-[^ ]+)?/2,i", $_SERVER['SERVER_SOFTWARE'])
	// test suspendu: http://article.gmane.org/gmane.comp.web.spip.devel/32038/
	#&& !($GLOBALS['flag_sapi_name'] AND preg_match(",^apache2,", @php_sapi_name()))
	// si la compression est deja commencee, stop
	&& !@ini_get("zlib.output_compression")
	&& !@ini_get("output_handler")
	&& !isset($_GET['var_mode']) # bug avec le debugueur qui appelle ob_end_clean()
	)
		ob_start('ob_gzhandler');
}

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
