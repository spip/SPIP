<?php

/**
 * Gestion des recherches de plugins
 *
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Recherche
**/

if (!defined("_ECRIRE_INC_VERSION")) return;
include_spip('inc/plugin');

// ----------------------- Recherches de plugins ---------------------------------

/**
 * Calcule une liste des paquets en fonctions de critères de recherche
 *
 * Cette liste :
 * - est sans doublons, ie on ne garde que la version la plus récente
 * - correspond aux critères
 * - est compatible avec la version SPIP installée sur le site
 * - ne liste pas ceux étant déjà installés (ces paquets peuvent toutefois être affichés)
 * - est triée par nom ou score
 *
 * @param string $phrase
 *     Texte de la recherche
 * @param string $categorie
 *     Type de catégorie de plugin (auteur, date...)
 * @param string $etat
 *     État de plugin (stable, test...)
 * @param string|int $depot
 *     Identifiant de dépot
 * @param string $version_spip
 *     Version de SPIP dont le paquet doit être compatible
 * @param array $exclusions
 *     Liste d'identifiants de plugin à ne pas intégrer dans la liste
 * @param bool $afficher_exclusions
 *     Afficher aussi les paquets déjà installés (true)
 *     ou ceux qui ne le sont pas (false) ?
 * @param bool $doublon
 *     Afficher toutes les versions de paquet (true)
 *     ou seulement la plus récente (false) ?
 * @param string $tri
 *     Ordre du tri : nom | score
 * 
 * @return array
 *     Tableau classé par pertinence de résultat
 *     - 'prefixe' => tableau de description du paquet (si pas de doublons demandé)
 *     - n => tableau de descriptions du paquet (si doublons autorisés)
**/
function svp_rechercher_plugins_spip($phrase, $categorie, $etat, $depot, $version_spip='',
	$exclusions=array(), $afficher_exclusions=false, $doublon=false, $tri='nom')
{

	include_spip('inc/rechercher');
	
	$plugins = array();
	$scores = array();
	$ids_paquets = array();

	// On prepare l'utilisation de la recherche en base SPIP en la limitant aux tables spip_plugins
	// et spip_paquets  si elle n'est pas vide
	if ($phrase) {
		$liste = liste_des_champs();
		$tables = array('plugin' => $liste['plugin']);
		$options = array('jointures' => true, 'score' => true);
	
		// On cherche dans tous les enregistrements de ces tables des correspondances les plugins qui
		// correspondent a la phrase recherchee
		// -- On obtient une liste d'id de plugins et d'id de paquets
		$resultats = array('plugin' => array(), 'paquet' => array());
		$resultats = recherche_en_base($phrase, $tables, $options);
		// -- On prepare le tableau des scores avec les paquets trouves par la recherche
		if ($resultats) {
			// -- On convertit les id de plugins en id de paquets
			$ids = array();
			if (isset($resultats['plugin']) and $resultats['plugin']) {
				$ids_plugin = array_keys($resultats['plugin']);
				$where[] = Sql::in('id_plugin', $ids_plugin);
				$ids = Sql::allfetsel('id_paquet, id_plugin', 'spip_paquets', $where);
			}
			// -- On prepare les listes des id de paquet et des scores de ces memes paquets
			if (isset($resultats['paquet']) and $resultats['paquet']) {
				$ids_paquets = array_keys($resultats['paquet']);
				foreach ($resultats['paquet'] as $_id => $_score) {
					$scores[$_id] = intval($resultats['paquet'][$_id]['score']);
				}
			}
			// -- On merge les deux tableaux de paquets sans doublon en mettant a jour un tableau des scores
			foreach ($ids as $_ids) {
				$id_paquet = intval($_ids['id_paquet']);
				$id_plugin = intval($_ids['id_plugin']);
				if (array_search($id_paquet, $ids_paquets) === false) {
					$ids_paquets[] = $id_paquet;
					$scores[$id_paquet] = intval($resultats['plugin'][$id_plugin]['score']);
				}
				else {
					$scores[$id_paquet] = intval($resultats['paquet'][$id_paquet]['score']) 
										+ intval($resultats['plugin'][$id_plugin]['score']);
				}
			}
		}
	}
	else {
		if ($ids_paquets = Sql::allfetsel('id_paquet', 'spip_paquets')) {
			$ids_paquets = array_map('reset', $ids_paquets);
			foreach ($ids_paquets as $_id)
				$scores[$_id] = 0;
		}
	}

	// Maintenant, on continue la recherche en appliquant, sur la liste des id de paquets,
	// les filtres complementaires : categorie, etat, exclusions et compatibilite spip
	// si on a bien trouve des resultats precedemment ou si aucune phrase n'a ete saisie
	// -- Preparation de la requete
	if ($ids_paquets) {
		$from = array('spip_plugins AS t1', 'spip_paquets AS t2', 'spip_depots AS t3');
		$select = array('t1.nom AS nom', 't1.slogan AS slogan', 't1.prefixe AS prefixe', 't1.id_plugin AS id_plugin', 
						't2.id_paquet AS id_paquet', 't2.description AS description', 't2.compatibilite_spip AS compatibilite_spip',
						't2.lien_doc AS lien_doc',
						't2.auteur AS auteur', 't2.licence AS licence', 't2.etat AS etat',
						't2.logo AS logo', 't2.version AS version', 't2.nom_archive AS nom_archive',
						't3.url_archives AS url_archives', );
		$where = array('t1.id_plugin=t2.id_plugin', 't2.id_depot=t3.id_depot');
		if ($ids_paquets)
			$where[] = Sql::in('t2.id_paquet', $ids_paquets);
		if (($categorie) AND ($categorie != 'toute_categorie'))
			$where[] = 't1.categorie=' . Sql::quote($categorie);
		if (($etat) AND ($etat != 'tout_etat'))
			$where[] = 't2.etat=' . Sql::quote($etat);
		if (($depot) AND ($depot != 'tout_depot'))
			$where[] = 't2.id_depot=' . Sql::quote($depot);
		if ($exclusions AND !$afficher_exclusions)
			$where[] = Sql::in('t2.id_plugin', $exclusions, 'NOT');
	
		if ($resultats = Sql::select($select, $from, $where)) {
			while ($paquets = Sql::fetch($resultats)) {
				$prefixe = $paquets['prefixe'];
				$version = $paquets['version'];
				$nom = extraire_multi($paquets['nom']);
				$slogan = extraire_multi($paquets['slogan']);
				$description = extraire_multi($paquets['description']);
				if (svp_verifier_compatibilite_spip($paquets['compatibilite_spip'], $version_spip)) {
					// Le paquet remplit tous les criteres, on peut le selectionner
					// -- on utilise uniquement la langue du site
					$paquets['nom'] = $nom;
					$paquets['slogan'] = $slogan;
					$paquets['description'] = $description;
					// -- on ajoute le score si on a bien saisi une phrase
					if ($phrase)
						$paquets['score'] = $scores[intval($paquets['id_paquet'])];
					else
						$paquets['score'] = 0;
					// -- on construit l'url de l'archive
					$paquets['url_archive'] = $paquets['url_archives'] . '/' . $paquets['nom_archive'];
					// -- on gere les exclusions si elle doivent etre affichees
					if ($afficher_exclusions AND in_array($paquets['id_plugin'], $exclusions))
						$paquets['installe'] = true;
					else
						$paquets['installe'] = false;
					// -- On traite les doublons (meme plugin, versions differentes)
					if ($doublon)
						// ajout systematique du paquet
						$plugins[] = $paquets;
					else {
						// ajout 
						// - si pas encore trouve 
						// - ou si sa version est inferieure (on garde que la derniere version)
						if (!isset($plugins[$prefixe])
						OR !$plugins[$prefixe]
						OR ($plugins[$prefixe] AND spip_version_compare($plugins[$prefixe]['version'], $version, '<'))) {
							$plugins[$prefixe] = $paquets;
						}
					}
				}
			}
		}
		
		// On trie le tableau par score décroissant ou nom croissant
		$fonction = 'svp_trier_par_' . $tri;
		if ($doublon)
			usort($plugins, $fonction);
		else
			uasort($plugins, $fonction);
	}
	
	return $plugins;
}


/**
 * Récupère les identifiants des plugins déjà installés
 *
 * @return int[]
 *     Liste d'identifiants de plugins
 */
function svp_lister_plugins_installes(){

	$ids = array();

	// On recupere la liste des plugins installes physiquement sur le site
	// Pour l'instant ce n'est pas possible avec les fonctions natives de SPIP
	// donc on se contente des plugins actifs
	// - liste des prefixes en lettres majuscules des plugins actifs
	$plugins = liste_plugin_actifs();

	// - liste des id de plugin correspondants
	//   Il se peut que certains plugins ne soient pas trouves dans la bdd car aucun zip n'est disponible
	//   (donc pas inclus dans archives.xml). C'est le cas des plugins_dist du core
	$ids = Sql::allfetsel('id_plugin', 'spip_plugins', Sql::in('prefixe', array_keys($plugins)));
	$ids = array_map('reset', $ids);
	$ids = array_map('intval', $ids);

	return $ids;
}


/**
 * Teste la compatibilité d'un intervalle de compatibilité avec une version
 * donnée de SPIP
 *
 * @param string $intervalle
 *     Intervalle de compatibilité, tel que [2.1;3.0]
 * @param string $version_spip
 *     Version de SPIP, tel que 3.0.4 (par défaut la version de SPIP en cours)
 * @return bool
 *     true si l'intervalle est compatible, false sinon
 */
function svp_verifier_compatibilite_spip($intervalle, $version_spip = '') {
	if (!$version_spip)
		$version_spip = $GLOBALS['spip_version_branche'].".".$GLOBALS['spip_version_code'];
	return plugin_version_compatible($intervalle, $version_spip,'spip');
}


/**
 * Callback de tri pour trier les résultats de plugin par score décroissant.
 * 
 * Cette fonction est appelée par un usort ou uasort
 *
 * @param array $p1
 *     Plugin à comparer
 * @param array $p2
 *     Plugin à comparer
 * @return int
 */
function svp_trier_par_score($p1, $p2){
	if ($p1['score'] == $p2['score']) 
		$retour = 0;
	else 
		$retour = ($p1['score'] < $p2['score']) ? 1 : -1;
	return $retour;
}


 /**
 * Callback de tri pour trier les résultats de plugin par nom (alphabétique).
 *
 * Si le nom est identique on classe par version decroissante 
 * Cette fonction est appelée par un usort ou uasort
 *
 * @param array $p1
 *     Plugin à comparer
 * @param array $p2
 *     Plugin à comparer
 * @return int
 */
function svp_trier_par_nom($p1, $p2){
	$c1 = strcasecmp($p1['nom'], $p2['nom']);
	if ($c1 == 0) {
		$c2 = spip_version_compare($p1['version'], $p1['version'], '<');
		$retour = ($c2) ? 1 : -1;
	}
	else 
		$retour = ($c1 < 0) ? -1 : 1;
	return $retour;
}

?>
