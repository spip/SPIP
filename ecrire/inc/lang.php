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

/**
 * Gestion des langues et choix de langue 
 *
 * @package SPIP\Langue
**/
if (!defined('_ECRIRE_INC_VERSION')) return;



/**
 * Changer la langue courante
 *
 * Définit la langue utilisée par la langue désignée
 * si elle fait partie des langues utilisables dans le site.
 *
 * Cette fonction définit les globales :
 * spip_lang, spip_lang_rtl, spip_lang_right, spip_lang_left
 * 
 * @param string $lang
 *     La langue à utiliser
 * @return string|bool
 *     string : La langue qui a été utilisée si trouvée
 *     false : aucune langue ne correspondait à la demande
**/
function changer_langue($lang) {
	global $spip_lang_rtl, $spip_lang_right, $spip_lang_left;

	$liste_langues = ',' . @$GLOBALS['meta']['langues_proposees']
	. ',' . @$GLOBALS['meta']['langues_multilingue'] . ',';

	// Si la langue demandee n'existe pas, on essaie d'autres variantes
	// Exemple : 'pt-br' => 'pt_br' => 'pt'
	$lang = str_replace('-', '_', trim($lang));
	if (!$lang)
		return false;

	if (strpos($liste_langues,",$lang,")!==false
	OR ($lang = preg_replace(',_.*,', '', $lang)
	AND strpos($liste_langues,",$lang,")!==false)) {

		$spip_lang_rtl =   lang_dir($lang, '', '_rtl');
		$spip_lang_right = $spip_lang_rtl ? 'left' : 'right';
		$spip_lang_left =  $spip_lang_rtl ? 'right' : 'left';

		return $GLOBALS['spip_lang'] = $lang;
	} else
		return false;
}

//
// Gestion des blocs multilingues
// Selection dans un tableau dont les index sont des noms de langues
// de la valeur associee a la langue en cours
// si absente, retourne le premier
// remarque : on pourrait aussi appeler un service de traduction externe
// ou permettre de choisir une langue "plus proche",
// par exemple le francais pour l'espagnol, l'anglais pour l'allemand, etc.

function choisir_traduction ($trads, $lang='') {
	$k = approcher_langue($trads, $lang);
	return $k ? $trads[$k] : array_shift($trads);
}

// retourne son 2e argument si c'est un index du premier
// ou un index approchant sinon et si possible, 
// la langue X etant consideree comme une approche de X_Y
function approcher_langue ($trads, $lang='') {

	if (!$lang) $lang = $GLOBALS['spip_lang']; 

	if (isset($trads[$lang])) {
		return $lang;
	}
	// cas des langues xx_yy
	else {
		$r = explode('_', $lang);
		if (isset($trads[$r[0]]))
			return $r[0];
	}
	return '';
}

// http://doc.spip.org/@traduire_nom_langue
function traduire_nom_langue($lang) {
	include_spip('inc/lang_liste');
	include_spip('inc/charsets');
	return html2unicode(isset($GLOBALS['codes_langues'][$lang]) ? $GLOBALS['codes_langues'][$lang] : $lang);
}

//
// Filtres de langue
//

// Donne la direction d'ecriture a partir de la langue. Retourne 'gaucher' si
// la langue est arabe, persan, kurde, pachto, ourdou (langues ecrites en
// alphabet arabe a priori), hebreu, yiddish (langues ecrites en alphabet
// hebreu a priori), 'droitier' sinon.
// C'est utilise par #LANG_DIR, #LANG_LEFT, #LANG_RIGHT.
// http://doc.spip.org/@lang_dir
function lang_dir($lang='', $droitier='ltr', $gaucher='rtl') {
	static $lang_rtl = array('ar', 'fa', 'ku', 'ps', 'ur', 'he', 'heb', 'hbo', 'yi');

	return in_array(($lang ? $lang : $GLOBALS['spip_lang']), $lang_rtl) ?
		$gaucher : $droitier;
}

// typo francaise ou anglaise ?
// $lang_objet est fixee dans l'interface privee pour editer
// un texte anglais en interface francaise (ou l'inverse) ;
// sinon determiner la typo en fonction de la langue courante

// http://doc.spip.org/@lang_typo
function lang_typo($lang='') {
	if (!$lang) {
		$lang = isset($GLOBALS['lang_objet'])
			? $GLOBALS['lang_objet']
			: $GLOBALS['spip_lang'];
	}
	if ($lang == 'eo'
	  OR $lang == 'fr'
	  OR strncmp($lang, 'fr_', 3)==0
	  OR $lang == 'cpf')
		return 'fr';
	else
		return 'en';
}

// gestion de la globale $lang_objet pour que les textes soient affiches
// avec les memes typo et direction dans l'espace prive que dans le public
// http://doc.spip.org/@changer_typo
function changer_typo($lang = '') {
	if ($lang)
		$GLOBALS['lang_objet'] = $lang;
	else
		unset($GLOBALS['lang_objet']);
}

//
// Afficher un menu de selection de langue
// - 'var_lang_ecrire' = langue interface privee,
// pour var_lang' = langue de l'article, espace public, voir les squelettes
// pour 'changer_lang' (langue de l'article, espace prive), c'est en Ajax
// 
// http://doc.spip.org/@menu_langues
function menu_langues($nom_select, $default='') {
	include_spip('inc/actions');

	$langues = liste_options_langues($nom_select);
	$ret = "";
	if (!count($langues))
		return '';

	if (!$default) $default = $GLOBALS['spip_lang'];
	foreach ($langues as $l) {
		$selected = ($l == $default) ? ' selected=\'selected\'' : '';
		$ret .= "<option value='$l'$selected>[".$l."] ".traduire_nom_langue($l)."</option>\n";
	}

	if (!test_espace_prive()) {
		$cible = self();
		$base = '';
	} else {
		$cible = self();
		$base = spip_connect() ? 'base' : '';
	}

	$change = ' onchange="this.parentNode.parentNode.submit()"';
	return generer_action_auteur('converser',$base, $cible,
		(select_langues($nom_select, $change, $ret)
		 . "<noscript><div style='display:inline'><input type='submit' class='fondo' value='". _T('bouton_changer')."' /></div></noscript>"),
				     " method='post'");
}

// http://doc.spip.org/@select_langues
function select_langues($nom_select, $change, $options, $label="")
{
	static $cpt = 0;
	$id = "menu_langues" . $cpt++;
	return
		"<label for='$id'>".($label?$label:_T('info_langues'))."</label> ".
		"<select name='$nom_select' id='$id' "
	  . ((!test_espace_prive()) ?
	     ("class='forml menu_langues'") :
	     (($nom_select == 'var_lang_ecrire')  ?
	      ("class='lang_ecrire'") :
	      "class='fondl'"))
	  . $change
	  . ">\n"
	  . $options
	  . "</select>";
}

/**
 * Lister les langues disponibles
 *
 * Retourne un tableau de langue utilisables, triées par code de langue,
 * mais pas le même tableau en fonction du paramètre $nom_select.
 * 
 * @param string $nom_select
 *     Attribut name du select
 *     Selon son nom, retourne une liste différente :
 *     - var_lang ou changer_lang :
 *         liste des langues sélectionnées dans la config multilinguisme
 *     - var_lang_ecrire :
 *         toutes les langues présentes en fichier de langue
 * @return array
 *     Liste des langues
 */
function liste_options_langues($nom_select) {

	switch($nom_select) {
		# #MENU_LANG
		case 'var_lang':
		# menu de changement de la langue d'un article
		# les langues selectionnees dans la configuration "multilinguisme"
		case 'changer_lang':
			$langues = explode(',', $GLOBALS['meta']['langues_multilingue']);
			break;
	# menu de l'interface (privee, installation et panneau de login)
	# les langues presentes sous forme de fichiers de langue
	# on force la relecture du repertoire des langues pour etre synchrone.
		case 'var_lang_ecrire':
		default:
			$GLOBALS['meta']['langues_proposees'] = '';
			init_langues();
			$langues = explode(',', $GLOBALS['meta']['langues_proposees']);
			break;

# dernier choix possible : toutes les langues = langues_proposees 
# + langues_multilingues ; mais, ne sert pas
#			$langues = explode(',', $GLOBALS['all_langs']);
	}
	if (count($langues) <= 1) return array();
	sort($langues);
	return $langues;
}



/**
 * Redirige sur la bonne langue lorsque l'option forcer_lang est active
 * 
 * Cette fonction est appelee depuis ecrire/public.php si on a installé
 * la variable de personnalisation $forcer_lang ; elle renvoie le brouteur
 * si necessaire vers l'URL xxxx?lang=ll
 *
 * @return void
**/
function verifier_lang_url() {
	global $spip_lang;

	// quelle langue est demandee ?
	$lang_demandee = (test_espace_prive()?$GLOBALS['spip_lang']:$GLOBALS['meta']['langue_site']);
	if (isset($_COOKIE['spip_lang_ecrire']))
		$lang_demandee = $_COOKIE['spip_lang_ecrire'];
	if (!test_espace_prive() AND isset($_COOKIE['spip_lang']))
		$lang_demandee = $_COOKIE['spip_lang'];
	if (isset($_GET['lang']))
		$lang_demandee = $_GET['lang'];

	// Renvoyer si besoin (et si la langue demandee existe)
	if ($spip_lang != $lang_demandee
	AND changer_langue($lang_demandee)
	AND $lang_demandee != @$_GET['lang']) {
		$destination = parametre_url(self(),'lang', $lang_demandee, '&');
		// ici on a besoin des var_truc
		foreach ($_GET as $var => $val) {
			if (!strncmp('var_', $var, 4))
				$destination = parametre_url($destination, $var, $val, '&');
		}
		include_spip('inc/headers');
		redirige_par_entete($destination);
	}

	// Subtilite : si la langue demandee par cookie est la bonne
	// alors on fait comme si $lang etait passee dans l'URL
	// (pour criteres {lang}).
	$GLOBALS['lang'] = $_GET['lang'] = $spip_lang;
}


/**
 * Utilise la langue du site
 *
 * Change la langue en cours d'utilisation par la langue du site
 * si ce n'est pas déjà le cas.
 *
 * Note : Cette fonction initialise la globale spip_lang au chargement de inc/lang
 *
 * @return string
 *     La langue sélectionnée
**/
function utiliser_langue_site() {
	// s'il existe une langue du site (en gros tout le temps en théorie)
	if (isset($GLOBALS['meta']['langue_site'])
	  // et si spip_langue est pas encore définie (ce que va faire changer_langue())
	  // ou qu'elle n'est pas identique à la langue du site
	  AND (!isset($GLOBALS['spip_lang'])
	    OR $GLOBALS['spip_lang']!=$GLOBALS['meta']['langue_site']))
	{
		return changer_langue($GLOBALS['meta']['langue_site']);//@:install
	}
	// en theorie là, la globale est définie, sinon c'est un problème.
	if (!isset($GLOBALS['spip_lang'])) {
		spip_log("La globale spip_lang est indéfinie dans utiliser_langue_site() !", _LOG_ERREUR);
	}
	return $GLOBALS['spip_lang'];
}

/**
 * Initialise la langue pour un visiteur du site 
 *
 * La langue est choisie dans cet ordre :
 * - Dans le cookie 'spip_lang' ou 'spip_lang_ecrire' s'il existe (selon l'espace public ou privé).
 * - Sinon dans la session du visiteur.
 * - Sinon dans une des langues définie en préférence du navigateur
 * - Sinon la langue du site
 * 
 * @return string
 *     La langue utilisée
**/
function utiliser_langue_visiteur() {

	$l = (!test_espace_prive()  ? 'spip_lang' : 'spip_lang_ecrire');
	if (isset($_COOKIE[$l]))
		if (changer_langue($l = $_COOKIE[$l]))
			return $l;

	if (isset($GLOBALS['visiteur_session']['lang']))
		if (changer_langue($l = $GLOBALS['visiteur_session']['lang']))
			return $l;

	foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $s)  {
		if (preg_match('#^([a-z]{2,3})(-[a-z]{2,3})?(;q=[0-9.]+)?$#i', trim($s), $r)) {
			if (changer_langue($l=strtolower($r[1])))
				return $l;
		}
	}

	return utiliser_langue_site();
}


/**
 * Retourne le répertoire contenant le module de langue indiqué
 * 
 * Note : pourrait en donner une liste... compliqué
 * 
 * @param string $module
 *     Nom du module de lang
 * @param string $lang
 *     Langue du module de langue
 * @return string
 *     Adresse du répertoire contenant le module
 *     Retourne ecrire/lang/ s'il rien ne semble correspondre.
**/
function repertoire_lang($module='spip', $lang='fr') {
	# valeur forcee (par ex.sur spip.net), old style, a faire disparaitre
	if (defined('_DIR_LANG'))
		return _DIR_LANG;

	# regarder s'il existe une v.f. qq part
	if ($f = find_in_path($module.'_'.$lang . '.php', 'lang/'))
		return dirname($f).'/';

	# sinon, je ne sais trop pas quoi dire...
	return _DIR_RESTREINT . 'lang/';
}


/**
 * Initialisation des listes de langues
 *
 * Initialise les métas :
 * - langues_proposees : liste des traductions disponibles
 * - langue_site       : langue par défaut du site
 *
 * Lorsque ces métas n'existent pas encore (c'est à dire à l'installation),
 * elles sont calculées en obtenant la liste des langues
 * dans les fichiers de lang 
 * 
 * @return void
**/
function init_langues() {

	// liste des langues dans les meta, sauf a l'install
	$all_langs = @$GLOBALS['meta']['langues_proposees'];

	$tout = array();
	if (!$all_langs) {
		if ($d = @opendir(repertoire_lang())) {
			while (($f = readdir($d)) !== false) {
				if (preg_match(',^spip_([a-z_]+)\.php[3]?$,', $f, $regs))
					$tout[] = $regs[1];
			}
			closedir($d);
			sort($tout);
			$tout = join(',', $tout);
			// Si les langues n'ont pas change, ne rien faire
			if ($tout != $all_langs) {
				$GLOBALS['meta']['langues_proposees'] =	$tout;
				include_spip('inc/meta');
				ecrire_meta('langues_proposees', $tout);
			} else $tout = '';
		}
	}
	if (!isset($GLOBALS['meta']['langue_site'])) {
		// Initialisation : le francais si dispo, sinon la premiere langue trouvee
		$GLOBALS['meta']['langue_site'] = $tout =
		(!$all_langs OR (strpos(',fr,',",$all_langs,")!==false))
		  ? 'fr' :  substr($all_langs,0,strpos($all_langs,','));
		ecrire_meta('langue_site', $tout);
	}
}

/**
 * Retourne une balise <html>
 *
 * Retourne une balise HTML contenant les attributs 'lang' et 'dir'
 * définis sur la langue en cours d'utilisation,
 * ainsi que des classes CSS de ces du nom de la langue et direction choisie.
 *
 * @return string
 *     Code html de la balise <html>
**/
function html_lang_attributes()
{
	$lang = $GLOBALS['spip_lang'];
	$dir = ($GLOBALS['spip_lang_rtl'] ? 'rtl' : 'ltr');
	return  "<html class='$dir $lang no-js' xmlns='http://www.w3.org/1999/xhtml' lang='$lang' dir='$dir'>\n" ;
}

// initialise les globales (liste des langue, langue du site, spip_lang...)
init_langues();
utiliser_langue_site();
?>
