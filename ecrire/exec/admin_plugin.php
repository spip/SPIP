<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

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

	$format = '';
	if (_request('format')!==NULL)
		$format = _request('format');

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('icone_admin_plugin'), "configuration", "plugin");
	echo "<br />\n";
	echo "<br />\n";

	echo gros_titre(_T('icone_admin_plugin'),'',false);

	echo debut_gauche('plugin',true);
	echo debut_boite_info(true);
	$s = "";
	$s .= _T('info_gauche_admin_tech');
	echo $s;
	echo fin_boite_info(true);

	// on fait l'installation ici,
	// cela permet aux scripts d'install de faire des affichages (moches...)
	verif_plugin();
	installe_plugins();
	// la valeur de retour de la fonction ci-dessus n'est pas compatible
	// avec ce que fait verif_plugin, il faut recalculer. A revoir.
	$lcpa = liste_chemin_plugin_actifs();

	// Si on a CFG, ajoute un lien (oui c'est mal)
	if (defined('_DIR_PLUGIN_CFG')) {
		echo debut_cadre_enfonce('',true);
		echo icone_horizontale('CFG &ndash; '._T('configuration'), generer_url_ecrire('cfg'), _DIR_PLUGIN_CFG.'cfg-22.png', '', false);
		echo fin_cadre_enfonce(true);
	}

	echo afficher_librairies();

	echo debut_droite('plugin', true);

	$lpf = liste_plugin_files();
	$plugins_interessants = @array_keys(unserialize($GLOBALS['meta']['plugins_interessants']));
	if (!is_array($plugins_interessants))
		$plugins_interessants = array();

	if ($lpf) {
		echo debut_cadre_trait_couleur('plugin-24.gif',true,'',_T('plugins_liste'),
		'liste_plugins');
		echo "<p>"._T('texte_presente_plugin')."</p>";


		$sub = "\n<div style='text-align:".$GLOBALS['spip_lang_right']."'>"
		.  "<input type='submit' value='"._T('bouton_valider')
		."' class='fondo' />" . "</div>";


		// S'il y a plus de 10 plugins pas installes, les signaler a part ;
		// mais on affiche tous les plugins mis a la racine ou dans auto/
		if (count($lpf) - count($lcpa) > 9
		AND _request('afficher_tous_plugins') != 'oui') {

			$dir_auto = substr(_DIR_PLUGINS_AUTO, strlen(_DIR_PLUGINS));
			$lcpaffiche = array();
			foreach ($lpf as $f)
				if (!strpos($f, '/')
				OR ($dir_auto AND substr($f, 0, strlen($dir_auto)) == $dir_auto)
				OR in_array($f, $lcpa)
				OR in_array($f, $plugins_interessants))
					$lcpaffiche[] = $f;
			if (count($lcpaffiche)<10 AND !$format) $format = 'liste';
			$lien_format = $format!='liste' ?
			  ("<a href='".parametre_url(self(),'format','liste')."'>"._T('plugins_vue_liste')."</a>")
			  :("<a href='".parametre_url(self(),'format','arbre')."'>"._T('plugins_vue_hierarchie')."</a>");
			$corps = "<p>$lien_format | "._T('plugins_actifs',array('count'=>count($lcpa)))."\n"
			  . " | <a href='". parametre_url(self(),'afficher_tous_plugins', 'oui') ."'>"
			  ._T('plugins_disponibles',array('count'=>count($lpf)))."</a></p>\n"
				. affiche_les_plugins($lcpaffiche, $lcpa, $format);

		} else {
			if (count($lpf)<10 AND !$format) $format = 'liste';
			$lien_format = $format!='liste' ?
			  ("<a href='".parametre_url(self(),'format','liste')."'>"._T('plugins_vue_liste')."</a>")
			  :("<a href='".parametre_url(self(),'format','arbre')."'>"._T('plugins_vue_hierarchie')."</a>");
			$corps =
				"<p>$lien_format | "
			  ."<a href='". parametre_url(self(),'afficher_tous_plugins', '') ."'>" . _T('plugins_actifs',array('count'=>count($lcpa)))."</a> | \n"
				. ""._T('plugins_disponibles',array('count'=>count($lpf)))
				. "</p>\n"
				. (count($lpf)>20 ? $sub : '')
				. affiche_les_plugins($lpf, $lcpa, $format);
		}

		$corps .= "\n<br />" . $sub;

		echo redirige_action_post('activer_plugins','activer','admin_plugin','', $corps);

		echo fin_cadre_trait_couleur(true);

	}

	if (include_spip('inc/charger_plugin')) {
		echo formulaire_charger_plugin($retour);
	}

	echo affiche_les_extensions($liste_plugins_actifs);

	echo fin_gauche(), fin_page();
	}
}

function affiche_les_extensions($liste_plugins_actifs){
	$res = "";
	if ($liste_extensions = liste_plugin_files(_DIR_EXTENSIONS)) {
		$res .= "<div id='extensions'>";
		$res .= debut_cadre_trait_couleur('plugin-24.png',true,'',_L('Extensions'),
		'liste_extensions');
		$res .= "<p>"._L('Les extensions ci-dessous sont charg&#233;es et activ&#233;es dans le r&#233;pertoire @extensions@. Elles ne sont pas d&#233;sactivables.', array('extensions' => joli_repertoire(_DIR_EXTENSIONS)))."</p>";

		$afficher = charger_fonction("afficher_$format",'plugins');
		$res .= $afficher($liste_extensions,$liste_plugins_actifs);

		$res .= fin_cadre_trait_couleur(true);
		$res .= "</div>\n";
	}
	return $res;
}

// http://doc.spip.org/@affiche_les_plugins
function affiche_les_plugins($liste_plugins, $liste_plugins_actifs, $format='liste'){
	if (!in_array($format,array('liste','repertoires')))
		$format = 'repertoires';

	$afficher = charger_fonction("afficher_$format",'plugins');
	$res = $afficher($liste_plugins,$liste_plugins_actifs);

#	var_dump(spip_timer('cachexml'));


	return http_script("
	jQuery(function(){
		jQuery('input.check').click(function(){
			jQuery(this).parent().toggleClass('nomplugin_on');
		});
		jQuery('div.nomplugin a[rel=info]').click(function(){
			var prefix = jQuery(this).parent().prev().attr('name');
			if (!jQuery(this).siblings('div.info').html()) {
				jQuery(this).siblings('div.info').prepend(ajax_image_searching).load(
					jQuery(this).attr('href').replace(/admin_plugin|plugins/, 'info_plugin'), {},
					function() {
						document.location = '#' + prefix;
					}
				);
			} else {
				if (jQuery(this).siblings('div.info').toggle().attr('display') != 'none') {
					document.location = '#' + prefix;
				}
			}
			return false;
		});
	});
	") . $res;
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