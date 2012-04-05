<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;
if (test_espace_prive())
	include_spip('inc/pipelines_ecrire');


// Inserer jQuery
// ne pas verifier ici qu'on ne doublonne pas #INSERT_HEAD
// car cela empeche un double appel (multi calcul en cache cool,
// ou erreur de l'espace prive)
// http://doc.spip.org/@f_jQuery
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

	$jqueryui_plugins = pipeline('jqueryui_plugins',array());
	// rajouter le core si des plugins demandes, mais sinon rien
	if (count($jqueryui_plugins)){
		$jqueryui_plugins = array_merge(array('jquery.ui.core'),$jqueryui_plugins);
		$jqueryui_plugins = jqueryui_dependances($jqueryui_plugins);
	}
	$scripts = array_unique(array_merge($jquery_plugins,$jqueryui_plugins));
	foreach ($scripts as $script)
		if ($script = find_in_path($script))
			$x .= "\n<script src=\"$script\" type=\"text/javascript\"></script>\n";

	$texte = $x.$texte;
	return $texte;
}

// Traiter var_recherche ou le referrer pour surligner les mots
// http://doc.spip.org/@f_surligne
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

// Valider/indenter a la demande.
// http://doc.spip.org/@f_tidy
function f_tidy ($texte) {
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

// Offre #INSERT_HEAD sur tous les squelettes (bourrin)
// a activer dans mes_options via :
// $spip_pipeline['affichage_final'] .= '|f_insert_head';
// http://doc.spip.org/@f_insert_head
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

// Inserer au besoin les boutons admins
// http://doc.spip.org/@f_admin
function f_admin ($texte) {
	if ($GLOBALS['affiche_boutons_admin']) {
		include_spip('public/admin');
		$texte = affiche_boutons_admin($texte);
	}
	if (_request('var_mode')=='noajax'){
		$texte = preg_replace(',(class=[\'"][^\'"]*)ajax([^\'"]*[\'"]),Uims',"\\1\\2",$texte);
	}
	return $texte;
}

function f_recuperer_fond($flux) {
	if (!test_espace_prive()) return $flux;
	return f_afficher_blocs_ecrire($flux);
}

// gerer le lancement du cron
// si des taches sont en attentes
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


/**
 * Gérer les dépendances de la lib jquery ui
 *
 * @param array $plugins
 * @return array
 */
function jqueryui_dependances($plugins){

	/**
	 * Gestion des dépendances inter plugins
	 */
	$dependance_core = array(
							'jquery.ui.mouse',
							'jquery.ui.widget',
							'jquery.ui.datepicker'
	);

	/**
	 * Dépendances à widget
	 * Si un autre plugin est dépendant d'un de ceux là, on ne les ajoute pas
	 */
	$dependance_widget = array(
							'jquery.ui.mouse',
							'jquery.ui.accordion',
							'jquery.ui.autocomplete',
							'jquery.ui.button',
							'jquery.ui.dialog',
							'jquery.ui.tabs',
							'jquery.ui.progressbar'
							);

	$dependance_mouse = array(
							'jquery.ui.draggable',
							'jquery.ui.droppable',
							'jquery.ui.resizable',
							'jquery.ui.selectable',
							'jquery.ui.sortable',
							'jquery.ui.slider'
						);

	$dependance_position = array(
							'jquery.ui.autocomplete',
							'jquery.ui.dialog',
							);

	$dependance_draggable = array(
							'jquery.ui.droppable'
							);

	$dependance_effects = array(
							'jquery.effects.blind',
							'jquery.effects.bounce',
							'jquery.effects.clip',
							'jquery.effects.drop',
							'jquery.effects.explode',
							'jquery.effects.fold',
							'jquery.effects.highlight',
							'jquery.effects.pulsate',
							'jquery.effects.scale',
							'jquery.effects.shake',
							'jquery.effects.slide',
							'jquery.effects.transfer'
						);

	/**
	 * Vérification des dépendances
	 * Ici on ajoute quand même le plugin en question et on supprime les doublons via array_unique
	 * Pour éviter le cas où un pipeline demanderait un plugin dans le mauvais sens de la dépendance par exemple
	 *
	 * On commence par le bas de l'échelle :
	 * - draggable
	 * - position
	 * - mouse
	 * - widget
	 * - core
	 * - effects
	 */
	if(count($intersect = array_intersect($plugins,$dependance_draggable)) > 0){
		$keys = array_keys($intersect);
		array_splice($plugins,$keys[0], 0, "jquery.ui.draggable");
	}
	if(count($intersect = array_intersect($plugins,$dependance_position)) > 0){
		$keys = array_keys($intersect);
		array_splice($plugins,$keys[0], 0, "jquery.ui.position");
	}
	if(count($intersect = array_intersect($plugins,$dependance_mouse)) > 0){
		$keys = array_keys($intersect);
		array_splice($plugins,$keys[0], 0, "jquery.ui.mouse");
	}
	if(count($intersect = array_intersect($plugins,$dependance_widget)) > 0){
		$keys = array_keys($intersect);
		array_splice($plugins,$keys[0], 0, "jquery.ui.widget");
	}
	if(count($intersect = array_intersect($plugins,$dependance_core)) > 0){
		$keys = array_keys($intersect);
		array_splice($plugins,$keys[0], 0, "jquery.ui.core");
	}
	if(count($intersect = array_intersect($plugins,$dependance_effects)) > 0){
		$keys = array_keys($intersect);
		array_splice($plugins,$keys[0], 0, "jquery.effects.core");
	}
	$plugins = array_unique($plugins);
	foreach ($plugins as $val) {
		$scripts[] = "prive/javascript/ui/".$val.".js";
	}

	return $scripts;
}

?>