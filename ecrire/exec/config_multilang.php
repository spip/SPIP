<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');
include_spip('inc/config');

// http://doc.spip.org/@exec_config_multilang_dist
function exec_config_multilang_dist()
{
	global $connect_statut, $connect_toutes_rubriques, $spip_lang_right, $changer_config;

	lire_metas();

	pipeline('exec_init',array('args'=>array('exec'=>'config_multilang'),'data'=>''));
	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_config_contenu'), "configuration", "langues");

	echo "<br /><br /><br />";
	gros_titre(_T('info_langues'));

	if ($connect_statut != '0minirezo' OR !$connect_toutes_rubriques) {
		echo _T('avis_non_acces_page');
		echo fin_gauche(), fin_page();
		exit;
	}

	init_config();

	echo barre_onglets("config_lang", "multi");

	debut_gauche();
	
	echo pipeline('affiche_gauche',array('args'=>array('exec'=>'config_multilang'),'data'=>''));
	creer_colonne_droite();
	echo pipeline('affiche_droite',array('args'=>array('exec'=>'config_multilang'),'data'=>''));
debut_droite();

	$action = generer_action_auteur('configurer_langue', '', generer_url_ecrire('config_multilang'));

	$res = "<form action='$action' method='post'><div>"
	.  form_hidden($action)
	. "<input type='hidden' name='changer_config' value='oui' />"
	. debut_cadre_couleur("traductions-24.gif", true, "", _T('info_multilinguisme'))
	. "<p>"._T('texte_multilinguisme')."</p>"
	. "<div>"
	. _T('info_multi_articles')
	. "<div style='text-align: $spip_lang_right;'>"
	. afficher_choix('multi_articles', $GLOBALS['meta']['multi_articles'],
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</div>"
	. "</div>"
	. "<div>"
	. _T('info_multi_rubriques')
	. "<div style='text-align: $spip_lang_right;'>"
	. afficher_choix('multi_rubriques', $GLOBALS['meta']['multi_rubriques'],
		array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
	. "</div>"
	. "</div>";

	if  ($GLOBALS['meta']['multi_rubriques'] == 'oui') {
		$res .= "\n<div>"
		. _T('info_multi_secteurs')
		. "<div style='text-align: $spip_lang_right;'>"
		. afficher_choix('multi_secteurs', $GLOBALS['meta']['multi_secteurs'],
			array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
		. "</div>"
		. "</div>";
	} else
		$res .= "<input type='hidden' name='multi_secteurs' value='".$GLOBALS['meta']['multi_secteurs']."' />";

	if (($GLOBALS['meta']['multi_rubriques'] == 'oui') OR ($GLOBALS['meta']['multi_articles'] == 'oui')) {
		$res .= "<hr />"
		. "<p>"._T('texte_multilinguisme_trad')."</p>";

		$res .= _T('info_gerer_trad')
		. "<div style='text-align: $spip_lang_right;'>"
		. afficher_choix('gerer_trad', $GLOBALS['meta']['gerer_trad'],
			array('oui' => _T('item_oui'), 'non' => _T('item_non')), " &nbsp; ")
		. "</div>";
	} else
		$res .= "<input type='hidden' name='gerer_trad' value='".$GLOBALS['meta']['gerer_trad']."' />";


	$res .= "\n<div style='text-align: $spip_lang_right;'><input type='submit' value='"._T('bouton_valider')."' class='fondo' /></div>";

	$res .= fin_cadre_couleur(true);


	calculer_langues_utilisees();

	if ($GLOBALS['meta']['multi_articles'] == "oui"
	OR $GLOBALS['meta']['multi_rubriques'] == "oui"
	OR count(explode(',',$GLOBALS['meta']['langues_utilisees'])) > 1) {

		$res .= debut_cadre_relief("langues-24.gif", true)
		. "<p class='verdana2'>"
		. _T('info_multi_langues_choisies')
		. '</p>';

		include_spip('inc/lang_liste');
		$langues = $GLOBALS['codes_langues'];
		$cesure = floor((count($langues) + 1) / 2);

		$langues_installees = explode(',', $GLOBALS['all_langs']);
		$langues_autorisees = explode(',', $GLOBALS['meta']['langues_multilingue']);

		while (list(,$l) = each ($langues_installees)) {
			$langues_trad[$l] = true;
		}

		while (list(,$l) = each ($langues_autorisees)) {
			$langues_auth[$l] = true;
		}

		$l_bloquees_tmp = explode(',',$GLOBALS['meta']['langues_utilisees']);
		while (list(,$l) = each($l_bloquees_tmp)) {
			$langues_bloquees[$l] = true;
		}

		$res .= "\n<table width='100%' cellspacing='10'><tr><td style='width: 50%'  class='verdana1'>";

		while (list($code_langue) = each($langues_bloquees)) {
			$i++;
			$nom_langue = $langues[$code_langue];
			if ($langues_trad[$code_langue]) $nom_langue = "<span style='text-decoration: underline'>$nom_langue</span>";

			$res .= "\n<div class='ligne_foncee' style='font-weight: bold'>";
			$res .= "\n<input type='hidden' name='langues_auth[]' value='$code_langue' id='langue_auth_$code_langue' />";
			$res .= "\n<input type='checkbox' checked='checked' disabled='disabled' />";
			$res .=  $nom_langue ."\n&nbsp; &nbsp;<span style='color: #777777'>[$code_langue]</span>";
			$res .= "</div>";

			if ($i == $cesure) $res .= "\n</td><td style='width: 50%' class='verdana1'>";
		}

		$res .= "\n<div>&nbsp;</div>";

		while (list($code_langue, $nom_langue) = each($langues)) {
			if ($langues_bloquees[$code_langue]) continue;
			$i++;
			$res .= "\n<div>";
			if ($langues_trad[$code_langue]) $nom_langue = "<span style='text-decoration: underline'>$nom_langue</span>";
	
			if ($langues_auth[$code_langue]) {
				$res .= "<input type='checkbox' name='langues_auth[]' value='$code_langue' id='langue_auth_$code_langue' checked='checked' />";
				$nom_langue = "<b>$nom_langue</b>";
			}
			else {
				$res .= "<input type='checkbox' name='langues_auth[]' value='$code_langue' id='langue_auth_$code_langue' />";
			}
			$res .=  "\n<label for='langue_auth_$code_langue'>$nom_langue</label> &nbsp; &nbsp;<span style='color: #777777'>[$code_langue]</span>";

			$res .= "</div>";

			if ($i == $cesure) $res .= "</td><td style='width: 50%' class='verdana1'>";
		}

		$res .= "</td></tr>"
		. "<tr><td style='text-align:$spip_lang_right;' colspan='2'>"
		. "<input type='submit' value='"._T('bouton_valider')."' class='fondo' />"
		. "</td></tr></table>"
		. "<div class='verdana1'>"._T("info_multi_langues_soulignees")."</div>"
		. fin_cadre_relief(true);
	}

	$res .= "</div></form>";

	echo $res, fin_gauche(), fin_page();
}
?>
