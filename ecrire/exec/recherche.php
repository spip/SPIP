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
include_spip('inc/sites_voir');


// http://doc.spip.org/@exec_recherche_dist
function exec_recherche_dist() {
	$recherche = _request('recherche');
	$recherche_aff = entites_html($recherche);

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('titre_page_recherche', array('recherche' => $recherche_aff)));

	if (strlen($recherche)) {
		include_spip('inc/rechercher');
		include_spip('base/abstract_sql');

		$tables = liste_des_champs();
		unset($tables['document']);
		unset($tables['forum']);

		$results = recherche_en_base($recherche, $tables);
		$modifier = false;
		foreach ($results as $table => $r) {
			foreach ($r as $id => $x) {
				$modifier |= autoriser('modifier', $table, $id);
			}
		}
	}

	debut_gauche();

	if (!strlen($recherche)) {
		$recherche_aff = _T('info_rechercher');
		$onfocus = " onfocus=\"this.value='';\"";
	}

	echo generer_form_ecrire("recherche", 
				 ('<input type="text" size="10" value="'.$recherche_aff.'" name="recherche" class="spip_recherche" accesskey="r"' . $onfocus . ' />'),
				 " method='get'");

/*
	// Si on est autorise a modifier, proposer le choix de REMPLACER
	// Il faudra aussi pouvoir indiquer sur quels elements on veut effectuer le remplacement...
	if ($modifier) {
	echo '<br /><input type="text" size="10" value="'.entites_html(_request('remplacer')).'" name="remplacer" class="spip_recherche" />';
	}
*/
	echo "</div></form>";

	debut_droite();

	if ($results) {
		echo "<span class='verdana1'><b>"._T('info_resultat_recherche')."</b></span><br />";
		echo "<span class='ligne_foncee verdana1 spip_large'><b>$recherche_aff</b></span>";

		foreach($results as $table => $r) {
			switch ($table) {
			case 'article':
				$fn = 'afficher_articles';
				$titre = _T('info_articles_trouves');
				$order = 'date DESC';
				break;
			case 'breve':
				$fn = 'afficher_breves';
				$titre = _T('info_breves_touvees');
				$order = 'date_heure DESC';
				break;
			case 'rubrique':
				$fn = 'afficher_rubriques';
				$titre = _T('info_rubriques_trouvees');
				$order = 'date DESC';
				break;
			case 'site':
				$fn = 'afficher_sites';
				$titre = _T('info_sites_trouves');
				$order = 'date DESC';
				break;
			case 'auteur':
				$fn = 'afficher_auteurs';
				$titre = _T('info_auteurs_trouves');
				$order = 'nom';
				break;
			}

			echo $a = $fn($titre,
				array(
					// gasp: la requete spip_articles exige AS articles...
					'FROM' => 'spip_'.table_objet($table).' AS '.$table.'s',
					'WHERE' => calcul_mysql_in(
						$table.'s.'.id_table_objet($table),
						join(',',array_keys($r))
					),
					'ORDER BY' => $order
				)
			);
		}

	}
	else
		if (strlen($recherche))
			echo "<p class='verdana1'>"._T('avis_aucun_resultat')."</p>";

	echo fin_gauche(), fin_page();
}


// old style, devrait etre dans inc/presentation
// http://doc.spip.org/@afficher_auteurs
function afficher_auteurs ($titre_table, $requete) {

	if (!$requete['SELECT']) $requete['SELECT'] = '*' ;

	$tous_id = array();
	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '')));
	if (! ($cpt = $cpt['n'])) return 0 ;
	if ($requete['LIMIT']) $cpt = min($requete['LIMIT'], $cpt);

	$tmp_var = 't_' . substr(md5(join('', $requete)), 0, 4);
	$nb_aff = floor(1.5 * _TRANCHES);
	$deb_aff = intval(_request($tmp_var));
	$tranches = '';
	if ($cpt > $nb_aff) {
		$nb_aff = (_TRANCHES); 
		$tranches = afficher_tranches_requete($cpt, $tmp_var, '', $nb_aff);
	}

	debut_cadre_relief("auteur-24.gif");

	if ($titre_table) {
			echo "<p><table width='100%' cellpadding='0' cellspacing='0' border='0'>";
			echo "<tr><td style='width: 100%'>";
			echo "<table width='100%' cellpadding='3' cellspacing='0' border='0'>";
			echo "<tr style='background-color: #333333'><td style='width: 100%' colspan='5'><span style='color: #FFFFFF;' class='verdana1 spip_medium'><b>$titre_table</b></span></td></tr>";
		}
	else {
			echo "<p><table width='100%' cellpadding='3' cellspacing='0' border='0'>";
		}

	echo $tranches;

	$result = spip_query("SELECT " . $requete['SELECT'] . " FROM " . $requete['FROM'] . ($requete['WHERE'] ? (' WHERE ' . $requete['WHERE']) : '') . ($requete['GROUP BY'] ? (' GROUP BY ' . $requete['GROUP BY']) : '') . ($requete['ORDER BY'] ? (' ORDER BY ' . $requete['ORDER BY']) : '') . " LIMIT " . ($deb_aff >= 0 ? "$deb_aff, $nb_aff" : ($requete['LIMIT'] ? $requete['LIMIT'] : "99999")));

	$table = array();
	while ($row = spip_fetch_array($result)) {
		$tous_id[] = $row['id_auteur'];
		$formater_auteur = charger_fonction('formater_auteur', 'inc');
		$table[]= $formater_auteur($row['id_auteur']);
	}
	spip_free_result($result);
	$largeurs = array(20, 20, 200, 20, 50);
	$styles = array('','','arial2','arial1','arial1');
	echo afficher_liste($largeurs, $table, $styles);

	if ($titre_table) echo "</table></td></tr>";
	echo "</table>";
	fin_cadre_relief();

}

?>
