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
include_spip('inc/traduire');

// http://doc.spip.org/@exec_lang_raccourcis_dist
function exec_lang_raccourcis_dist() {
	global  $spip_lang_left;


	##### a revoir : des repertoires lang, il peut y en avoir plusieurs
	$modules = array();
	$fichiers = preg_files(repertoire_lang().'[a-z_]+\.php[3]?$');
	foreach ($fichiers as $fichier) {
		if (preg_match(',/([a-z]+)_([a-z_]+)\.php[3]?$,', $fichier, $r))
			isset($modules[$r[1]])?($modules[$r[1]] ++):($modules[$r[1]]=1);
	}

	$modules = array_keys($modules);

	if (!in_array($module = _request('module'), $modules))
		$module = 'public';

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('module_fichier_langue').": $module",
		"configuration", "langues");

	echo "<br /><br /><br />";
	gros_titre(_T('module_fichier_langue').": $module");

	echo barre_onglets("config_lang", "fichiers");


	debut_gauche();

	if (count($modules) > 1) {
		echo debut_cadre_relief('',true,'',_T('module_fichiers_langues'));

		foreach ($modules as $nom_module) {
			if ($nom_module == $module) echo "<div style='padding-$spip_lang_left: 10px;' class='verdana3'><b>$nom_module</b></div>";
			else echo "<div style='padding-$spip_lang_left: 10px;' class='verdana3'><a href='" . generer_url_ecrire("lang_raccourcis","module=$nom_module") . "'>$nom_module</a></div>";
		}
		echo fin_cadre_relief();
	}


	debut_droite();

	afficher_raccourcis($module);


	echo fin_gauche(), fin_page();

}

// http://doc.spip.org/@afficher_raccourcis
function afficher_raccourcis($module = "public") {
	global $spip_lang;
	
	charger_langue($spip_lang, $module);

	$tableau = $GLOBALS['i18n_' . $module . '_' . $spip_lang];
	ksort($tableau);

	$aff_nom_module= "";
	if ($module != "public" AND $module != "local")
		$aff_nom_module = "$module:";

	echo "<div class='arial2'>"._T('module_texte_explicatif')."</div>";
	echo "<div>&nbsp;</div>";

	foreach (preg_files(repertoire_lang().$module.'_[a-z_]+\.php[3]?$') as $f)
		if (preg_match(",^".$module."\_([a-z_]+)\.php[3]?$,", $f, $regs))
				$langue_module[$regs[1]] = traduire_nom_langue($regs[1]);

	if (isset($langue_module) && ($langue_module)) {
		ksort($langue_module);
		echo "<div class='arial2'>"._T('module_texte_traduction',
			array('module' => $module));
		echo " ".join(", ", $langue_module).".";
		echo "</div><div>&nbsp;</div>";
	}

	echo debut_cadre_relief('',true,'','','raccourcis');
	echo "\n<table class='spip' style='border:0;'>";
	echo "\n<tr class='titrem'><th class='verdana1'>"._T('module_raccourci')."</th>\n<th class='verdana2'>"._T('module_texte_affiche')."</th></tr>\n";

	$i = 0;
	foreach ($tableau as $raccourci => $val) {
		$bgcolor = alterner(++$i, 'row_even','row_odd');
		echo "\n<tr class='$bgcolor'><td class='verdana2'><b>&lt;:$aff_nom_module$raccourci:&gt;</b></td>\n<td class='arial2'>".$val."</td></tr>";
	}

	echo "</table>",fin_cadre_relief(true);
}

?>
