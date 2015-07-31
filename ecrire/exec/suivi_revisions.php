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
include_spip('inc/suivi_versions');

// http://doc.spip.org/@exec_suivi_revisions_dist
function exec_suivi_revisions_dist()
{
	$debut = intval(_request('debut'));
	$lang_choisie = _request('lang_choisie');
	$id_auteur = intval(_request('id_auteur'));
	$id_secteur = intval(_request('id_secteur'));

	$nom_auteur = $GLOBALS['auteur_session']['nom'];
	$connecte = $GLOBALS['auteur_session']['id_auteur'];
	if ($id_auteur == $connecte) $id_auteur = false;

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T("icone_suivi_revisions"));

	echo debut_gauche('', true);

	if (autoriser('voir', 'article'))
	  $req_where = "('prepa','prop','publie')"; 
	else $req_where = "('prop','publie')"; 

	echo debut_cadre_relief('', true);

	echo "<div class='arial11'><ul>";

	if (!$id_auteur AND $id_secteur < 1) echo "\n<li><b>"._T('info_tout_site')."</b></li>";
	else echo "\n<li><a href='" . generer_url_ecrire("suivi_revisions") . "'>"._T('info_tout_site')."</a></li>";


	if ($id_auteur) echo "\n<li><b>$nom_auteur</b></li>";
	else echo "\n<li><a href='" . generer_url_ecrire("suivi_revisions","id_auteur=$connecte") . "'>$nom_auteur</a></li>";

	if (($GLOBALS['meta']['multi_rubriques'] == 'oui') OR ($GLOBALS['meta']['multi_articles'] == 'oui'))

		$langues = explode(',', $GLOBALS['meta']['langues_multilingue']);
	else $langues = array();

	$result = sql_select("id_rubrique, titre", "spip_rubriques", 'id_parent=0','', '0+titre,titre');

	while ($row = sql_fetch($result)) {
		$id_rubrique = $row['id_rubrique'];
		$titre = typo($row['titre']);

		if ($id_rubrique == $id_secteur)  echo "\n<li><b>$titre</b>";
		else {
		  if (sql_countsel('spip_versions AS versions, spip_articles AS articles', "versions.id_article = articles.id_article AND versions.id_version > 1 AND articles.id_secteur=$id_rubrique AND articles.statut IN $req_where"))
		    echo "\n<li><a href='" . generer_url_ecrire("suivi_revisions","id_secteur=$id_rubrique") . "'>$titre</a></li>";
		}
		foreach ($langues as $lang) {
			$titre = traduire_nom_langue($lang);
	
			if ($lang == $lang_choisie)  echo "\n<li><b>$titre</b></li>";
			else {
				$n = sql_countsel('spip_versions AS versions, spip_articles AS articles', "versions.id_article = articles.id_article AND versions.id_version > 1 AND articles.lang='$lang' AND articles.statut IN $req_where");
				if ($n) echo "\n<li><a href='" . generer_url_ecrire("suivi_revisions","lang_choisie=$lang") . "'>$titre</a></li>";
			}
		}
	}
	echo "</ul></div>\n";

// lien vers le rss


	$args = array(
	'id_secteur' => $id_secteur,
	'id_auteur' => $id_auteur,
	'lang_choisie' => $lang_choisie
	);
	$op =  bouton_spip_rss('revisions', $args);

	echo "<div style='text-align: ", $GLOBALS['spip_lang_right'], ";'>", $op, "</div>";

	echo fin_cadre_relief(true);

	echo debut_droite("", true);
	echo afficher_suivi_versions($debut, $id_secteur, $id_auteur, $lang_choisie);
	echo fin_gauche(), fin_page();
}
?>
