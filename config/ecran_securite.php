<?php

/*
 * ecran_securite.php
 * ------------------
 */

define('_ECRAN_SECURITE', '0.6'); // 27 juillet 2009

/*
 * Ce petit script est inclus automatiquement par SPIP s'il est present
 * dans le repertoire config/ ; il peut aussi etre inclus pour tous les
 * codes php, avec la commande auto_prepend_file dans php.ini
 * Il essaie de mettre un ecran devant certains des trous de
 * securite connus d'anciennes versions de SPIP (et qui ont ete bouches dans
 * les versions officielles).
 *
 * Ce fichier ne se substitue pas a une veritable mise a niveau de votre
 * version de SPIP, mais il peut permettre de bloquer certaines attaques en
 * attendant une migration propre.
 *
 * Installation :
 *
 * trois possibilites :
 *
 * -- deposer le fichier ecran_securite.php dans le repertoire config/ ;
 *
 * -- dans php.ini :
 *    auto_prepend_file '/chemin/vers/ecran_securite.php'
 *    (exemple: /usr/share/php/ecran_securite/ecran_securite.php)
 *
 * -- dans httpd.conf :
 *    php_admin_value auto_prepend_file '/chemin/vers/ecran_securite.php'
 *
 * Le fichier ecran_securite.php sera charge a chaque "hit" sur le serveur.
 *
 *
 * L'ecran de securite reagit aux reglages suivants :
 *
 * -- define('_ECRAN_SECURITE_LOAD', X);
 *    protection anti-bots quand la charge serveur excede X
 *    valeur par defaut : 4 ; desactiver : 0
 *
 */


/*     - interdit de passer une variable id_article (ou id_xxx) qui ne
 *       soit pas numerique (ce qui bloque l'exploitation de divers trous
 *       de securite, dont celui de toutes les versions < 1.8.2f)
 *       (sauf pour id_table, qui n'est pas numerique jusqu'a [5743])
 */
foreach ($_GET as $var => $val)
	if (strncmp($var,"id_",3)==0 AND $var!='id_table')
		$_GET[$var] = is_array($_GET[$var])?array_map('intval',$_GET[$var]):intval($_GET[$var]);
foreach ($_POST as $var => $val)
	if (strncmp($var,"id_",3)==0 AND $var!='id_table')
		$_POST[$var] = is_array($_POST[$var])?array_map('intval',$_POST[$var]):intval($_POST[$var]);
foreach ($GLOBALS as $var => $val)
	if (strncmp($var,"id_",3)==0 AND $var!='id_table')
		$GLOBALS[$var] = is_array($GLOBALS[$var])?array_map('intval',$GLOBALS[$var]):intval($GLOBALS[$var]);


/*     - interdit la variable $cjpeg_command, qui etait utilisee sans
 *       precaution dans certaines versions de dev (1.8b2 -> 1.8b5)
 *
 */
$cjpeg_command='';

/*     - controle la variable $lang (XSS)
 *
 */
if (isset($_GET['lang']))
	$GLOBALS['lang'] = $_GET['lang'] = htmlentities($_GET['lang']);
if (isset($_POST['lang']))
	$GLOBALS['lang'] = $_POST['lang'] = htmlentities($_POST['lang']);

/*     - filtre l'acces a spip_acces_doc (injection SQL en 1.8.2x)
 *
 */
if (preg_match(',^(.*/)?spip_acces_doc\.,', $REQUEST_URI)) {
	$file = addslashes($_GET['file']);
}

/*
 *     - agenda joue a l'injection php
 */
if (isset($_REQUEST['partie_cal'])
AND $_REQUEST['partie_cal'] !== htmlentities($_REQUEST['partie_cal']))
    die("No thanks");
if (isset($_REQUEST['echelle'])
AND $_REQUEST['echelle'] !== htmlentities($_REQUEST['echelle']))
    die("No thanks");
    

/*     - bloque les requetes contenant %00 (manipulation d'include)
 *
 */
if (strpos(
	@get_magic_quotes_gpc() ?
		stripslashes(serialize($_REQUEST)) : serialize($_REQUEST),
	chr(0)
) !== false) die();

/*     - bloque les requetes fond=formulaire_
 *
 */
if (isset($_REQUEST['fond'])
AND preg_match(',^formulaire_,i', $_REQUEST['fond']))
	die();

/*     - bloque les requetes du type ?GLOBALS[type_urls]=toto (bug vieux php)
 *
 */
if (isset($_REQUEST['GLOBALS'])) die();

/*     - bloque les requetes des bots sur:
 *       les agenda
 *       les paginations entremelees
 */
if (strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'bot')
AND (
  (isset($_REQUEST['echelle']) AND isset($_REQUEST['partie_cal']) AND isset($_REQUEST['type']))
  OR (strpos($_SERVER['REQUEST_URI'],'debut_') AND preg_match(',[?&]debut_.*&debut_,', $_SERVER['REQUEST_URI']))
)
) {
  header('HTTP/1.1 403 Forbidden');
  die ("Acc&egrave;s robot agenda/double pagination refus&eacute;.");
}

/*
 * Bloque une vieille page de tests de CFG (<1.11)
 */
if (isset($_REQUEST['page']) AND $_REQUEST['page']=='test_cfg') {
	die();
}         

/* Parade antivirale contre un cheval de troie */
if(!function_exists('tmp_lkojfghx')){
function tmp_lkojfghx(){}
function tmp_lkojfghx2($a=0,$b=0,$c=0,$d=0){
	// si jamais on est arrive ici sur une erreur php
	// et qu'un autre gestionnaire d'erreur est defini, l'appeller
	if($b&&$GLOBALS['tmp_xhgfjokl'])
		call_user_func($GLOBALS['tmp_xhgfjokl'],$a,$b,$c,$d);
}
}
if (isset($_POST['tmp_lkojfghx3'])){	die();}


/*
 * Bloque les bots quand le load deborde
 *
 */
if (!defined('_ECRAN_SECURITE_LOAD'))
	define('_ECRAN_SECURITE_LOAD', 4);

if (
  defined('_ECRAN_SECURITE_LOAD')
  AND _ECRAN_SECURITE_LOAD>0
  AND $_SERVER['REQUEST_METHOD'] === 'GET'
  AND strpos($_SERVER['HTTP_USER_AGENT'], 'bot')!==FALSE
  AND (
    (function_exists('sys_getloadavg') AND $load = array_shift(sys_getloadavg()))
    OR (@is_readable('/proc/loadavg') AND $load = floatval(file_get_contents('/proc/loadavg')))
  )
  AND $load > _ECRAN_SECURITE_LOAD // eviter l'evaluation suivante si de toute facon le load est inferieur a la limite
  AND rand(0, $load*$load) > _ECRAN_SECURITE_LOAD*_ECRAN_SECURITE_LOAD
  ) {
	header("HTTP/1.0 503 Service Unavailable");
	header("Retry-After: 300");
	header("Expires: 0");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");
	header("Content-Type: text/html");
	die("<html><title>Error 503: Site temporarily unavailable</title><body><h1>Error 503</h1><p>Site temporarily unavailable (load average $load)</p></body></html>");
}


