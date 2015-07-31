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
 * Fonctions déclarées dans des pipelines (espace public)
 *
 * @package SPIP\Pipelines
**/
if (!defined('_ECRIRE_INC_VERSION')) return;
if (test_espace_prive())
	include_spip('inc/pipelines_ecrire');



/**
 * Inserer jQuery et ses plugins
 *
 * La fonction ajoute les balises scripts dans le texte qui appelent
 * les scripts jQuery ainsi que certains de ses plugins. La liste
 * des js chargée peut être complété par le pipeline 'jquery_plugins'
 *
 * Cette fonction est appelée par le pipeline insert_head
 * 
 * @internal
 *     Ne pas vérifier ici qu'on ne doublonne pas #INSERT_HEAD
 *     car cela empêche un double appel (multi calcul en cache cool,
 *     ou erreur de l'espace privé)
 *
 * @see f_jQuery_prive()
 * @link http://doc.spip.org/@f_jQuery
 * 
 * @param string $texte    Contenu qui sera inséré dans le head HTML
 * @return string          Contenu qui sera inséré dans le head HTML
**/
function f_jQuery ($texte) {
	$x = '';
	$jquery_plugins = pipeline('jquery_plugins',
		array(
		'javascript/jquery.js',
		'javascript/jquery.form.js',
		'javascript/jquery.autosave.js',
		'javascript/jquery.placeholder-label.js',
		'javascript/ajaxCallback.js',
		'javascript/jquery.cookie.js'
		));
	foreach (array_unique($jquery_plugins) as $script)
		if ($script = find_in_path($script))
			$x .= "\n<script src=\"$script\" type=\"text/javascript\"></script>\n";

	$texte = $x.$texte;
	return $texte;
}


/**
 * Traiter var_recherche ou le referrer pour surligner les mots
 *
 * Surligne les mots de la recherche (si var_recherche est présent)
 * ou des réferers (si la constante _SURLIGNE_RECHERCHE_REFERERS est
 * définie à true) dans un texte HTML
 *
 * Cette fonction est appelée par le pipeline affichage_final
 * 
 * @param string $texte   Contenu de la page envoyée au navigateur
 * @return string         Contenu de la page envoyée au navigateur
**/
function f_surligne ($texte) {
	if (!$GLOBALS['html']) return $texte;
	$rech = _request('var_recherche');
	if (!$rech
	  AND (!defined('_SURLIGNE_RECHERCHE_REFERERS')
	       OR !_SURLIGNE_RECHERCHE_REFERERS
	       OR !isset($_SERVER['HTTP_REFERER'])))
		return $texte;
	include_spip('inc/surligne');
	return surligner_mots($texte, $rech);
}

/**
 * Indente un code HTML
 *
 * Indente et valide un code HTML si la globale 'xhtml' est
 * définie à true.
 *
 * Cette fonction est appelée par le pipeline affichage_final
 * 
 * @param string $texte   Contenu de la page envoyée au navigateur
 * @return string         Contenu de la page envoyée au navigateur
 **/
function f_tidy ($texte) {
	/**
	 * Indentation à faire ?
	 *
	 * - true : actif.
	 * - false par défaut.
	 */
	global $xhtml;

	if ($xhtml # tidy demande
	AND $GLOBALS['html'] # verifie que la page avait l'entete text/html
	AND strlen($texte)
	AND !headers_sent()) {
		# Compatibilite ascendante
		if (!is_string($xhtml)) $xhtml ='tidy';

		if (!$f = charger_fonction($xhtml, 'inc', true)) {
			spip_log("tidy absent, l'indenteur SPIP le remplace");
			$f = charger_fonction('sax', 'xml');
		}
		return $f($texte);
	}

	return $texte;
}


/**
 * Offre #INSERT_HEAD sur tous les squelettes (bourrin)
 *
 * À activer dans mes_options via :
 * $GLOBALS['spip_pipeline']['affichage_final'] .= '|f_insert_head';
 *
 * Ajoute le contenu du pipeline insert head dans la page HTML
 * si cela n'a pas été fait.
 *
 * @param string $texte   Contenu de la page envoyée au navigateur
 * @return string         Contenu de la page envoyée au navigateur
**/
function f_insert_head($texte) {
	if (!$GLOBALS['html']) return $texte;
	include_spip('public/admin'); // pour strripos

	($pos = stripos($texte, '</head>'))
	    || ($pos = stripos($texte, '<body>'))
	    || ($pos = 0);

	if (false === strpos(substr($texte, 0,$pos), '<!-- insert_head -->')) {
		$insert = "\n".pipeline('insert_head','<!-- f_insert_head -->')."\n";
		$texte = substr_replace($texte, $insert, $pos, 0);
	}

	return $texte;
}


/**
 * Insérer au besoin les boutons admins
 * 
 * Cette fonction est appelée par le pipeline affichage_final
 *
 * @param string $texte   Contenu de la page envoyée au navigateur
 * @return string         Contenu de la page envoyée au navigateur
**/
function f_admin ($texte) {
	if (defined('_VAR_PREVIEW') AND _VAR_PREVIEW AND $GLOBALS['html']) {
		include_spip('inc/filtres'); // pour http_img_pack
		$x = "<div class='spip-previsu' "
		     . http_style_background('preview-32.png')
		     . ">"
		     . _T('previsualisation')
		     . "</div>";
		if (!$pos = stripos($texte, '</body>'))
			$pos = strlen($texte);
		$texte = substr_replace($texte, $x, $pos, 0);
	}

	if (isset($GLOBALS['affiche_boutons_admin']) AND $GLOBALS['affiche_boutons_admin']) {
		include_spip('public/admin');
		$texte = affiche_boutons_admin($texte);
	}
	if (_request('var_mode')=='noajax'){
		$texte = preg_replace(',(class=[\'"][^\'"]*)ajax([^\'"]*[\'"]),Uims',"\\1\\2",$texte);
	}
	return $texte;
}

/**
 * Actions sur chaque inclusion
 *
 * Appelle f_afficher_blocs_ecrire() sur les inclusions dans l'espace privé.
 * Ne change rien dans l'espace public.
 * 
 * Cette fonction est appelée par le pipeline recuperer_fond 
 *
 * @see f_afficher_blocs_ecrire()
 * 
 * @param  array $flux  Description et contenu de l'inclusion
 * @return array $flux  Description et contenu de l'inclusion
**/
function f_recuperer_fond($flux) {
	if (!test_espace_prive()) return $flux;
	return f_afficher_blocs_ecrire($flux);
}

/**
 * Gérer le lancement du cron si des tâches sont en attente
 * 
 * Cette fonction est appelée par le pipeline affichage_final
 *
 * @param string $texte   Contenu de la page envoyée au navigateur
 * @return string         Contenu de la page envoyée au navigateur
 */
function f_queue(&$texte){

	// eviter une inclusion si rien a faire
	if (_request('action')=='cron'
		OR queue_sleep_time_to_next_job()
	  OR defined('_DEBUG_BLOCK_QUEUE')){
		return $texte;
	}

	include_spip('inc/queue');
	$code = queue_affichage_cron();

	// si rien a afficher
	// ou si on est pas dans une page html, on ne sait rien faire de mieux
	if (!$code OR !$GLOBALS['html'])
		return $texte;

	// inserer avant le </body> fermant si on peut, a la fin de la page sinon
	if (($p=strpos($texte,'</body>'))!==FALSE)
		$texte = substr($texte,0,$p).$code.substr($texte,$p);
	else
		$texte .= $code;

	return $texte;
}

?>
