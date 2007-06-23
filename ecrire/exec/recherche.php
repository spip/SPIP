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

// http://doc.spip.org/@exec_recherche_dist
function exec_recherche_dist() {
	global $spip_lang_right;
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

/*		$modifier = false;
		foreach ($results as $table => $r) {
			foreach ($r as $id => $x) {
				$modifier |= autoriser('modifier', $table, $id);
			}
		}
*/

		// Ajouter la recherche par identifiant
		if (preg_match(',^[0-9]+$,', $recherche)
		AND $id = intval($recherche))
		foreach ($tables as $table => $x) {
			$s = spip_query($q = "SELECT ".id_table_objet($table)." FROM spip_".table_objet($table)." WHERE ".id_table_objet($table)."="._q($id));
			if ($t = spip_fetch_array($s)
			AND autoriser('voir', $table, $id)
			AND !isset($results[$table][$id]))
				$results[$table][$id] = array();
		}

	}
	
	echo debut_grand_cadre();

	if (!strlen($recherche)) {
		$recherche_aff = _T('info_rechercher');
		$onfocus = " onfocus=\"this.value='';\"";
	} else $onfocus = '';

	$onfocus = '<input type="text" size="10" value="'.$recherche_aff.'" name="recherche" class="spip_recherche" accesskey="r"' . $onfocus . ' />';
	echo "<div style='width:200px;float:$spip_lang_right;'>".generer_form_ecrire("recherche", $onfocus, " method='get'")."</div>";

/*
	// Si on est autorise a modifier, proposer le choix de REMPLACER
	// Il faudra aussi pouvoir indiquer sur quels elements on veut effectuer le remplacement...
	if ($modifier) {
	echo '<br /><input type="text" size="10" value="'.entites_html(_request('remplacer')).'" name="remplacer" class="spip_recherche" />';
	}
*/

	if ($results) {
		echo "<span class='verdana1'><b>"._T('info_resultat_recherche')."</b></span><br />";
		echo "<h1>$recherche_aff</h1>";
		include_spip('inc/afficher_objets');

		foreach($results as $table => $r) {
			switch ($table) {
			case 'article':
				$titre = _T('info_articles_trouves');
				$order = 'date DESC';
				break;
			case 'breve':
				$titre = _T('info_breves_touvees');
				$order = 'date_heure DESC';
				break;
			case 'rubrique':
				$titre = _T('info_rubriques_trouvees');
				$order = 'date DESC';
				break;
			case 'site':
				$titre = _T('info_sites_trouves');
				$order = 'date DESC';
				break;
			case 'auteur':
				$titre = _T('info_auteurs_trouves');
				$order = 'nom';
				break;
			case 'mot':
				$titre = _T('titre_page_mots_tous');
				$order = 'titre';
				break;
			default:
				$titre = _T("info_trouves");
				$order = "id_$table";
				break;
			}

			echo afficher_objets($table,$titre,
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

	echo fin_grand_cadre(), fin_page();
}

?>