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

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/config');
include_spip('inc/plugin');
include_spip('inc/presentation');
include_spip('inc/layer');
include_spip('inc/actions');
include_spip('inc/securiser_action');

// http://doc.spip.org/@exec_admin_plugin_dist
function exec_admin_plugin_dist($retour='') {

	if (!autoriser('configurer', '_plugins')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
	// on fait la verif du path avant tout,
	// et l'installation des qu'on est dans la colonne principale
	// si jamais la liste des plugins actifs change, il faut faire un refresh du hit
	// pour etre sur que les bons fichiers seront charges lors de l'install
		$new = actualise_plugins_actifs();
		if ($new AND _request('actualise')<2) {
			include_spip('inc/headers');
			redirige_par_entete(parametre_url(self(),'actualise',_request('actualise')+1,'&'));
		}
		else {
			admin_plug_args(_request('voir'), _request('erreur'), _request('format'));
		}
	}
}

function admin_plug_args($quoi, $erreur, $format)
{
	if (!$quoi) $quoi = 'actifs';
	// empecher l'affichage des erreurs dans le bandeau, on le donne ensuite
	// format brut par plugin
	$GLOBALS['erreurs_activation_raw'] = plugin_donne_erreurs(true, false);
	// format resume mis en forme
	$erreur_activation = plugin_donne_erreurs();
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('icone_admin_plugin'), "configuration", "plugin");

	echo debut_gauche('plugin',true);
	echo recuperer_fond('prive/squelettes/navigation/configurer',array('exec'=>'admin_plugin'));

	echo pipeline('affiche_gauche',
		array(
		'args'=>array('exec'=>'admin_plugin'),
		'data'=>afficher_librairies()
		)
	);

	echo debut_droite('plugin', true);
	echo gros_titre(_T('icone_admin_plugin'),'',false);

	// Barre d'onglets de premier niveau
	echo barre_onglets("plugins", "plugins_actifs");
	// Barre d'onglets de second niveau
	$onglet2 = $quoi=='actifs' ? 'plugins_actifs' : 'admin_plugin';
	echo debut_onglet('onglets_simple second');
	echo onglet(_T('plugins_tous_liste'), generer_url_ecrire("admin_plugin", "voir=tous"), 'admin_plugin', $onglet2);
	echo onglet(_T('plugins_actifs_liste'), generer_url_ecrire("admin_plugin"), 'plugins_actifs', $onglet2);
	echo fin_onglet();

	// message d'erreur au retour d'une operation
	if ($erreur)
		echo "<div class='error'>$erreur</div>";
	if ($erreur_activation){
		echo "<div class='error'>$erreur_activation</div>";
	}

	// la mise a jour de cette meta a ete faite par ecrire_plugin_actifs
	$actifs = unserialize($GLOBALS['meta']['plugin']);
	$lcpa = $actifs + unserialize($GLOBALS['meta']['plugin_attente']);

	// Les affichages se basent sur le repertoire, pas sur le nom
	$actifs = liste_chemin_plugin($actifs, '');
	if (defined('_DIR_PLUGINS_SUPPL'))
		$lcpas = liste_chemin_plugin($lcpa,_DIR_PLUGINS_SUPPL);
	$lcpa = liste_chemin_plugin($lcpa);
	
	// on installe les plugins maintenant,
	// cela permet aux scripts d'install de faire des affichages (moches...)
	plugin_installes_meta();

	echo "<div class='liste-plugins formulaire_spip'>";
	echo debut_cadre_trait_couleur('plugin-24.png',true,'',_T('plugins_liste'), 'plugins');

	if ($quoi!=='actifs'){
		$lpf = liste_plugin_files();
		if ($lpf)
			echo "<p>"._T('texte_presente_plugin')."</p>";
		else {
			if (!@is_dir(_DIR_PLUGINS))
				echo  "<p>"._T('plugin_info_automatique_ftp',array('rep'=>joli_repertoire(_DIR_PLUGINS)))
							. " &mdash; "._T('plugin_info_automatique_creer')."</p>";
		}
		$lcpaffiche = $lpf;
		if (defined('_DIR_PLUGINS_SUPPL'))
			$lcpaffichesup = liste_plugin_files(_DIR_PLUGINS_SUPPL);
	}
	else {
		// la liste
		// $quoi=='actifs'
		$lcpaffiche = $lcpa;
		if (defined('_DIR_PLUGINS_SUPPL'))
			$lcpaffichesup = $lcpas;
	}

	if ($quoi=='actifs' OR $lpf){
		$nb = count($lcpa);
		if (defined('_DIR_PLUGINS_SUPPL'))
			$nb += count($lcpas);
		echo "<h3>".sinon(singulier_ou_pluriel($nb, 'plugins_actif_un', 'plugins_actifs', 'count'), _T('plugins_actif_aucun'))."</h3>";
	}

	if (empty($format))
	  $format = 'liste';
	elseif (!in_array($format,array('liste','repertoires')))
		$format = 'repertoires';

	$afficher = charger_fonction("afficher_$format",'plugins');
	$corps = $afficher(self(),$lcpaffiche, $lcpa, $actifs);
	if (defined('_DIR_PLUGINS_SUPPL'))
		$corps .= $afficher(self(),$lcpaffichesup, $lcpas, $actifs, _DIR_PLUGINS_SUPPL);

	if ($corps)
	  $corps .= "\n<div class='boutons' style='display:none;'>"
	    .  "<input type='submit' class='submit save' value='"._T('bouton_enregistrer')
	    ."' />"
	    . "</div>";

	echo redirige_action_post('activer_plugins','activer','admin_plugin','', $corps);

	echo fin_cadre_trait_couleur(true);

	if ($quoi=='actifs')
		echo affiche_les_plugins_verrouilles($actifs);
	echo "</div>";
	
	echo 	http_script("
	jQuery(function(){
		jQuery('.plugins li.item a[rel=info]').click(function(){
			var li = jQuery(this).parents('li').eq(0);
			var prefix = li.find('input.checkbox').attr('name');
			if (!jQuery('div.details',li).html()) {
				jQuery('div.details',li).prepend(ajax_image_searching).load(
					jQuery(this).attr('href').replace(/admin_plugin|plugins/, 'info_plugin'), function(){
						li.addClass('on');
					}
				);
			}
			else {
				if (jQuery('div.details',li).toggle().is(':visible'))
					li.addClass('on');
				else
					li.removeClass('on');
			}
			return false;
		});
		jQuery('.plugins li.item input.checkbox').change(function(){
			jQuery(this).parents('form').eq(0).find('.boutons').slideDown();
		});
	});
	");

	echo pipeline('affiche_milieu',
		array(
		'args'=>array('exec'=>'admin_plugin'),
		'data'=>''
		)
	);

	echo fin_gauche(), fin_page();
}

function affiche_les_plugins_verrouilles($actifs)
{
	if ((!$liste = liste_plugin_files(_DIR_PLUGINS_DIST))) return '';

	$afficher = charger_fonction("afficher_liste",'plugins');
	$liste = $afficher(self(), $liste, array(), $actifs, _DIR_PLUGINS_DIST);

	return 
		"<div id='plugins_dist'>"
		. debut_cadre_trait_couleur('',true,'',_T('plugins_liste_dist'), 'liste_plugins_dist')
		. "<p>"
		. _T('plugin_info_plugins_dist_1', array('plugins_dist' => joli_repertoire(_DIR_PLUGINS_DIST)))
		. '<br />'. _T('plugin_info_plugins_dist_2')
		. "</p>"
		. $liste
		. fin_cadre_trait_couleur(true)
		. "</div>\n";
}

/**
 * Afficher la liste des librairies presentes
 *
 * @return <type>
 */
function afficher_librairies(){

	if (!$libs = liste_librairies()) return '';
	ksort($libs);
	$res = debut_cadre_enfonce('', true, '', _T('plugin_librairies_installees'));
	$res .= '<dl>';
	foreach ($libs as $lib => $rep)
		$res .= "<dt>$lib</dt><dd>".joli_repertoire($rep)."</dd>\n";
	$res .= '</dl>';
	$res .= fin_cadre_enfonce(true);
	return $res;
}


/**
 * Faire la liste des librairies disponibles
 * retourne un array ( nom de la lib => repertoire , ... )
 *
 * @return array
 */
// http://doc.spip.org/@liste_librairies
function liste_librairies() {
	$libs = array();
	foreach (array_reverse(creer_chemin()) as $d) {
		if (is_dir($dir = $d.'lib/')
		AND $t = @opendir($dir)) {
			while (($f = readdir($t)) !== false) {
				if ($f[0] != '.'
				AND is_dir("$dir/$f"))
					$libs[$f] = $dir;
			}
		}
	}
	return $libs;
}
?>
