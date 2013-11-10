<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Ajout des scripts de jQuery UI pour les pages publiques et privées
 * 
 * @param: $plugins 
 * @return: $plugins
 */
function jqueryui_jquery_plugins($plugins){

	// Modules demandés par le pipeline jqueryui_plugins
	is_array($jqueryui_plugins = pipeline('jqueryui_plugins', array())) || $jqueryui_plugins = array();
	// gestion des dépendances des modules demandés
	is_array($jqueryui_plugins = jqueryui_dependances($jqueryui_plugins)) || $jqueryui_plugins = array();

	// insérer les scripts nécessaires
	foreach ($jqueryui_plugins as $val) {
		$plugins[] = "javascript/ui/".$val.".js";
	}

	return $plugins;
}

/**
 * Ajout des css de jQuery UI pour les pages publiques
 * 
 * @param: $flux 
 * @return: $flux
 */
function jqueryui_insert_head_css_dist($flux) {

	// Modules demandés par le pipeline jqueryui_plugins
	is_array($jqueryui_plugins = pipeline('jqueryui_plugins', array())) || $jqueryui_plugins = array();
	// gestion des dépendances des modules demandés
	is_array($jqueryui_plugins = jqueryui_dependances($jqueryui_plugins)) || $jqueryui_plugins = array();

	// ajouter le thème si nécessaire
	if ($jqueryui_plugins AND !in_array('jquery.ui.theme', $jqueryui_plugins))
		$jqueryui_plugins[] = 'jquery.ui.theme';

	// les css correspondantes aux plugins
	$styles = array(
						'jquery.ui.accordion',
						'jquery.ui.autocomplete',
						'jquery.ui.button',
						'jquery.ui.core',
						'jquery.ui.datepicker',
						'jquery.ui.dialog',
						'jquery.ui.progressbar',
						'jquery.ui.resizable',
						'jquery.ui.selectable',
						'jquery.ui.slider',
						'jquery.ui.tabs',
						'jquery.ui.theme'
						);

	// insérer les css nécessaires
	foreach ($jqueryui_plugins as $plugin) {
		if (in_array($plugin, $styles)) {
			$flux .= "<link rel='stylesheet' type='text/css' media='all' href='".find_in_path('css/'.$plugin.'.css')."' />\n";
		}
	}

	return $flux;
}

/**
 * Ajout de la css de jQuery UI pour les pages privées
 * 
 * @param: $flux 
 * @return: $flux
 */
function jqueryui_header_prive_css($flux) {
	
	$flux .= "<link rel='stylesheet' type='text/css' media='all' href='".find_in_path('css/jquery-ui.css')."' />\n";
	
	return $flux;
}

/**
 * Gérer les dépendances de la lib jQuery UI
 *
 * @param array $plugins tableau des plugins demandés
 * @return array $plugins tableau des plugins nécessaires ou false
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

	return $plugins;
}

?>
