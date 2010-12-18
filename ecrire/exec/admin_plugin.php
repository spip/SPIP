<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
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

	if (!autoriser('configurer', 'plugins')) {
		include_spip('inc/minipres');
		echo minipres();
	} else {
	// on fait la verif du path avant tout,
	// et l'installation des qu'on est dans la colonne principale
	// si jamais la liste des plugins actifs change, il faut faire un refresh du hit
	// pour etre sur que les bons fichiers seront charges lors de l'install
	if (actualise_plugins_actifs()==-1 AND _request('actualise')<2){
		include_spip('inc/headers');
		redirige_par_entete(parametre_url(self(),'actualise',_request('actualise')+1,'&'));
	}

	if ($erreur_activation = isset($GLOBALS['meta']['plugin_erreur_activation'])){
		$erreur_activation = $GLOBALS['meta']['plugin_erreur_activation'];
		// l'effacement reel de la meta se fera au moment de l'affichage
		// mais on la vide pour ne pas l'afficher dans le bandeau haut
		unset($GLOBALS['meta']['plugin_erreur_activation']);
	}

	$format = '';
	if (_request('format')!==NULL)
		$format = _request('format'); // liste ou repertoires

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('icone_admin_plugin'), "configuration", "plugin");
	echo "<br />\n";
	echo "<br />\n";

	$quoi = _request('voir');
	$quoi = $quoi ? $quoi : 'actifs';

	echo gros_titre(_T('icone_admin_plugin'),'',false);
	echo barre_onglets("plugins", $quoi=='actifs'?"plugins_actifs":"admin_plugin");

	echo debut_gauche('plugin',true);
	echo debut_boite_info(true);
	$s = "";
	$s .= _T('info_gauche_admin_tech');
	echo $s;
	echo fin_boite_info(true);

	// la valeur de retour de la fonction ci-dessus n'est pas compatible
	// avec ce que fait actualise_plugins_actifs, il faut recalculer. A revoir.
	$lcpa = liste_chemin_plugin_actifs();

	echo pipeline('affiche_gauche',
		array(
		'args'=>array('exec'=>'admin_plugin'),
		'data'=>afficher_librairies()
		)
	);

	echo debut_droite('plugin', true);

	// message d'erreur au retour d'un operation
	if (_request('erreur')){
		echo "<div class='erreur_message-plugins'>" . _T(_request('erreur')) . "</div>";
	}
	if ($erreur_activation){
		echo "<div class='erreur_message-plugins'>" . $erreur_activation . "</div>";
		effacer_meta('plugin_erreur_activation');
	}


	// on fait l'installation ici,
	// cela permet aux scripts d'install de faire des affichages (moches...)
	installe_plugins();

	$lpf = liste_plugin_files();
	$plugins_interessants = @array_keys(unserialize($GLOBALS['meta']['plugins_interessants']));
	if (!is_array($plugins_interessants))
		$plugins_interessants = array();

	echo "<div class='liste-plugins formulaire_spip'>";

	echo debut_cadre_trait_couleur('plugin-24.gif',true,'',_T('plugins_liste'),
	'plugins');

	if ($quoi!=='actifs'){
		if ($lpf)
			echo "<p>"._T('texte_presente_plugin')."</p>";
		else {
			if (!@is_dir(_DIR_PLUGINS))
				echo  "<p>"._T('plugin_info_automatique_ftp',array('rep'=>joli_repertoire(_DIR_PLUGINS)))
							. " &mdash; "._T('plugin_info_automatique_creer')."</p>";
		}
	}

	if ($quoi=='actifs' OR $lpf)
		echo "<h3>".sinon(
						singulier_ou_pluriel(count($lcpa), 'plugins_actif_un', 'plugins_actifs', 'count'),
						_T('plugins_actif_aucun')
						)."</h3>";

	$sub = "\n<div class='boutons' style='display:none;'>"
	.  "<input type='submit' class='submit save' value='"._T('bouton_enregistrer')
	."' />"
	. "</div>";

	$no_button = false;

	// la liste
	if ($quoi=='actifs'){
		$aff = affiche_les_plugins($lcpa, $lcpa, $format);
		$no_button = !strlen($aff);
		$corps = $aff;
	}
	elseif ($quoi=='tous')
		$corps = affiche_les_plugins($lpf, $lcpa, $format);
	else {
		$dir_auto = substr(_DIR_PLUGINS_AUTO, strlen(_DIR_PLUGINS));
		$lcpaffiche = array();
		foreach ($lpf as $f)
			if (!strpos($f, '/')
			OR ($dir_auto AND substr($f, 0, strlen($dir_auto)) == $dir_auto)
			OR in_array($f, $lcpa)
			OR in_array($f, $plugins_interessants))
				$lcpaffiche[] = $f;

		$corps = affiche_les_plugins($lcpaffiche, $lcpa, $format);
	}

	if (!$no_button)
		$corps .= "\n<br />" . $sub;

	echo redirige_action_post('activer_plugins','activer','admin_plugin','', $corps);

	echo fin_cadre_trait_couleur(true);

	if ($quoi=='actifs')
		echo affiche_les_extensions(liste_chemin_plugin_actifs(_DIR_EXTENSIONS));
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
}

function affiche_les_extensions($liste_plugins_actifs){
	$res = "";
	if ($liste_extensions = liste_plugin_files(_DIR_EXTENSIONS)) {
		$res .= "<div id='extensions'>";
		$res .= debut_cadre_trait_couleur('',true,'',_T('plugins_liste_extensions'),
		'liste_extensions');
		$res .= "<p>"
			._T('plugin_info_extension_1', array('extensions' => joli_repertoire(_DIR_EXTENSIONS)))
			. '<br />'. _T('plugin_info_extension_2')
			."</p>";

		$format = 'liste';
		$afficher = charger_fonction("afficher_$format",'plugins');
		$res .= $afficher(self(), $liste_extensions,$liste_plugins_actifs, _DIR_EXTENSIONS);

		$res .= fin_cadre_trait_couleur(true);
		$res .= "</div>\n";
	}
	return $res;
}

// http://doc.spip.org/@affiche_les_plugins
function affiche_les_plugins($liste_plugins, $liste_plugins_actifs, $format='liste'){
	if (!$format)
		$format = 'liste';
	if (!in_array($format,array('liste','repertoires')))
		$format = 'repertoires';

	$afficher = charger_fonction("afficher_$format",'plugins');
	$res = $afficher(self(), $liste_plugins,$liste_plugins_actifs);

	if (!$res) return "";
#	var_dump(spip_timer('cachexml'));


	return	$res;
}

/**
 * Afficher la liste des librairies presentes
 *
 * @return <type>
 */
function afficher_librairies(){
	$res = "";
	// Lister les librairies disponibles
	if ($libs = plugins_liste_librairies()) {
		$res .= debut_cadre_enfonce('', true, '', _T('plugin_librairies_installees'));
		ksort($libs);
		$res .= '<dl>';
		foreach ($libs as $lib => $rep)
			$res .= "<dt>$lib</dt><dd>".joli_repertoire($rep)."</dd>";
		$res .= '</dl>';
		$res .= fin_cadre_enfonce(true);
	}
	return $res;
}

?>
