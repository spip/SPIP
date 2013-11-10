<?php
/**
 * Déclarations d'autorisations et utilisations de pipelines
 * 
 * @plugin Porte Plume pour SPIP
 * @license GPL
 * @package SPIP\PortePlume\Pipelines
**/

if (!defined("_ECRIRE_INC_VERSION")) return;

#define('PORTE_PLUME_PUBLIC', true);

/**
 * Fonction du pipeline autoriser. N'a rien à faire
 * @pipeline autoriser
 */
function porte_plume_autoriser(){}

/**
 * Autoriser l'action de previsu
 *
 * La fermer aux non identifiés si pas de porte plume dans le public
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_porteplume_previsualiser_dist($faire, $type, $id, $qui, $opt){
	return
		(test_espace_prive() AND autoriser('ecrire'))
	  OR (!test_espace_prive() AND autoriser('afficher_public','porteplume'));
}

/**
 * Autoriser le porte plume dans l'espace public ?
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_porteplume_afficher_public_dist($faire, $type, $id, $qui, $opt) {
	// compatibilite d'avant le formulaire de configuration
	if (defined('PORTE_PLUME_PUBLIC')) {
		return PORTE_PLUME_PUBLIC;
	}
	return ($GLOBALS['meta']['barre_outils_public'] !== 'non');
	
	// n'autoriser qu'aux identifies :
	# return $qui['id_auteur'] ? PORTE_PLUME_PUBLIC : false;
}

/**
 * Ajout des scripts du porte-plume dans le head des pages publiques
 *
 * Uniquement si l'on est autorisé à l'afficher le porte plume dans
 * l'espace public !
 *
 * @pipeline insert_head
 * @param  string $flux Contenu du head
 * @return string Contenu du head
 */
function porte_plume_insert_head_public($flux){
	include_spip('inc/autoriser');
	if (autoriser('afficher_public', 'porteplume')) {
		$flux = porte_plume_inserer_head($flux, $GLOBALS['spip_lang']);
	}
	return $flux;
}

/**
 * Ajout des scripts du porte-plume dans le head des pages privées
 *
 * @pipeline header_prive
 * @param  string $flux Contenu du head
 * @return string Contenu du head
 */
function porte_plume_insert_head_prive($flux){
	$js = find_in_path('javascript/porte_plume_forcer_hauteur.js');
	$flux = porte_plume_inserer_head($flux, $GLOBALS['spip_lang'], $prive=true)
		. "<script type='text/javascript' src='$js'></script>\n";
	
	return $flux;
}

/**
 * Ajout des scripts du porte-plume au texte (un head) transmis
 *
 * @param  string $flux  Contenu du head
 * @param  string $lang  Langue en cours d'utilisation
 * @param  bool   $prive Est-ce pour l'espace privé ?
 * @return string Contenu du head complété
 */
function porte_plume_inserer_head($flux, $lang, $prive = false){
	$markitup = find_in_path('javascript/jquery.markitup_pour_spip.js');
	$js_previsu = find_in_path('javascript/jquery.previsu_spip.js');
	$js_start = parametre_url(generer_url_public('porte_plume_start.js'), 'lang', $lang);
	if (defined('_VAR_MODE') AND _VAR_MODE=="recalcul")
		$js_start = parametre_url($js_start, 'var_mode', 'recalcul');

	$flux .= 
		   "<script type='text/javascript' src='$markitup'></script>\n"
		.  "<script type='text/javascript' src='$js_previsu'></script>\n"
		.  "<script type='text/javascript' src='$js_start'></script>\n";

	return $flux;
}

/**
 * Ajout des CSS du porte-plume au head privé
 *
 * @pipeline header_prive_css
 * @param string $flux  Contenu du head
 * @return string Contenu du head complété
 */
function porte_plume_insert_head_prive_css($flux){
	return porte_plume_insert_head_css($flux, true);
}

/**
 * Ajout des CSS du porte-plume au head public
 *
 * Appelé aussi depuis le privé avec $prive à true.
 * 
 * @pipeline insert_head_css
 * @param string $flux  Contenu du head
 * @param  bool  $prive Est-ce pour l'espace privé ?
 * @return string Contenu du head complété
 */
function porte_plume_insert_head_css($flux='', $prive = false){
	include_spip('inc/autoriser');
	// toujours autoriser pour le prive.
	if ($prive or autoriser('afficher_public', 'porteplume')) {
		if ($prive) {
			$cssprive = find_in_path('css/barre_outils_prive.css');
			$flux .= "<link rel='stylesheet' type='text/css' media='all' href='$cssprive' />\n";
		}
		$css = direction_css(find_in_path('css/barre_outils.css'), lang_dir());
		$css_icones = generer_url_public('barre_outils_icones.css');
		if (defined('_VAR_MODE') AND _VAR_MODE=="recalcul")
			$css_icones = parametre_url($css_icones, 'var_mode', 'recalcul');
		$flux
			.= "<link rel='stylesheet' type='text/css' media='all' href='$css' />\n"
			.  "<link rel='stylesheet' type='text/css' media='all' href='$css_icones' />\n";
	}
	return $flux;
}

/**
 * Valeur par défaut des configurations
 *
 * @pipeline configurer_liste_metas
 * @param array $metas
 *     Tableaux des metas et valeurs par défaut
 * @return array
 *     Tableaux des metas et valeurs par défaut
 */
function porte_plume_configurer_liste_metas($metas){
	$metas['barre_outils_public'] = 'oui';
	return $metas;
}

/**
 * Ajoute le formulaire de configuration du porte-plume sur la page
 * des configurations avancées.
 *
 * @pipeline affiche_milieu
 * @param array $flux Données du pipeline
 * @return array      Données du pipeline
 */
function porte_plume_affiche_milieu($flux){
	if ($flux['args']['exec']=='configurer_avancees')
		$flux['data'] .= recuperer_fond('prive/squelettes/inclure/configurer',array('configurer'=>'configurer_porte_plume'));

	return $flux;
}
?>
