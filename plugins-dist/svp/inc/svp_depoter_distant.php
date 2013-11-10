<?php

/**
 * Traitement des dépots distants
 *
 * Un dépot distant est une liste de paquets que l'on peut télécharger.
 * Cette liste est donnée par un fichier XML que l'on peut relire
 * régulièrement pour actualiser nos informations. Effectivement, chaque
 * paquet (et plugin) décrit est inséré en base de données pour nous
 * faciliter les recherches.
 * 
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Depots
 */
 
if (!defined("_ECRIRE_INC_VERSION")) return;
include_spip('inc/plugin');
include_spip('inc/svp_phraser');


/**
 * Ajout d'un dépot et de son contenu (paquets, plugins) dans la base de données
 *
 * Si une erreur survient (syntaxe XML incorrecte, pas de plugin dans le dépot),
 * son texte est placé dans le paramètre $erreur
 * 
 * @param string $url
 *     URL du fichier XML de description du dépot
 * @param string $erreur
 *     Texte d'un éventuel message d'erreur
 * @return bool
 *     true si le dépot est ajouté correctement, false sinon
 */
function svp_ajouter_depot($url, &$erreur='') {
	include_spip('inc/distant');

	// On considere que l'url a deja ete validee (correcte et nouveau depot)
	$url = trim($url);
	// Ajout du depot dans la table spip_depots. Les compteurs de paquets et de plugins
	// sont mis a jour apres le traitement des paquets

	// on recupère le XML
	$fichier_xml = copie_locale($url, 'modif');
	if (!$fichier_xml) {
		$erreur = _T('svp:message_nok_xml_non_recupere', array('fichier' => $url));
		return false;
	}

	$fichier_xml = _DIR_RACINE . $fichier_xml;

	// Lire les donnees d'un depot de paquets
	$infos = svp_phraser_depot($fichier_xml);
	if (!$infos) {
		$erreur = _T('svp:message_nok_xml_non_conforme', array('fichier' => $url));
		return false;
	}
	
	$champs = array('titre' => filtrer_entites($infos['depot']['titre']),
					'descriptif' => filtrer_entites($infos['depot']['descriptif']),
					'type' => $infos['depot']['type'],
					'url_serveur' => $infos['depot']['url_serveur'],
					'url_brouteur' => $infos['depot']['url_brouteur'],
					'url_archives' => $infos['depot']['url_archives'],
					'url_commits' => $infos['depot']['url_commits'],
					'xml_paquets'=> $url,
					'sha_paquets'=> sha1_file($fichier_xml),
					'nbr_paquets' => 0,
					'nbr_plugins' => 0,
					'nbr_autres' => 0);
	if (!$id_depot = Sql::insertq('spip_depots', $champs)) {
		$erreur = _T('svp:message_nok_sql_insert_depot', array('objet' => $titre));
		return false;
	}
	
	// Ajout des paquets dans spip_paquets et actualisation des plugins dans spip_plugins
	$ok = svp_actualiser_paquets($id_depot, $infos['paquets'], $nb_paquets, $nb_plugins, $nb_autres);
	if (!$ok OR ($nb_paquets == 0)) {
		// Si une erreur s'est produite, on supprime le depot deja insere
		Sql::delete('spip_depots','id_depot='.Sql::quote($id_depot));
		if (!$ok)
			$erreur = _T('svp:message_nok_xml_non_conforme', array('fichier' => $url));
		else
			$erreur = _T('svp:message_nok_aucun_paquet_ajoute', array('url' => $url));
		return false;
	}

	// On met à jour le nombre de paquets et de plugins du depot maintenant !
	Sql::updateq('spip_depots',
				array('nbr_paquets'=> $nb_paquets, 'nbr_plugins'=> $nb_plugins, 'nbr_autres'=> $nb_autres),
				'id_depot=' . Sql::quote($id_depot));

	// On vide les paquets locaux pour mettre a jour leurs donnees relatives au depot
	// comme les mises a jour disponibles
	include_spip('inc/svp_depoter_local');
	svp_base_supprimer_paquets_locaux();
	
	return true;
}

/**
 * Suppression d'un dépot et de son contenu (paquets, plugins) dans la base de données
 *
 * Cette suppression entraîne des recalcul comme les versions maximales
 * des plugins téléchargeables qui peuvent changer.
 * 
 * @param int $id
 *     Identifiant du dépot
 * @return bool
 *     false si le dépot n'est pas trouvé, true sinon
 */
function svp_supprimer_depot($id){
	$id = intval($id);

	// Pas de depot a cet id ?
	if (!$id_depot = Sql::getfetsel('id_depot', 'spip_depots', 'id_depot='. Sql::quote($id)) ){
		return false;
	}

	// on calcule les versions max des plugins heberges par le depot
	$vmax =array();

	if ($resultats = Sql::allfetsel('id_plugin, version', 'spip_paquets', 'id_depot='. Sql::quote($id))) {
		foreach ($resultats as $paquet) {
			$id_plugin = $paquet['id_plugin'];
			if (!isset($vmax[$id_plugin])
			OR (spip_version_compare($vmax[$id_plugin], $paquet['version'], '<'))) 
				$vmax[$id_plugin] = $paquet['version'];
		}
	}

	// On supprime les paquets heberges par le depot
	Sql::delete('spip_paquets','id_depot='.Sql::quote($id_depot));

	// Si on est pas en mode runtime, on utilise surement l'espace public pour afficher les plugins.
	// Il faut donc verifier que les urls suivent bien la mise à jour
	// Donc avant de nettoyer la base des plugins du depot ayant disparus on supprime toutes les urls
	// associees a ce depot : on les recreera apres le nettoyage
	if (!_SVP_MODE_RUNTIME)
		svp_actualiser_url_plugins($id_depot);

	// Nettoyer les autres relations à ce dépot
	svp_nettoyer_apres_suppression($id_depot, $vmax);

	// Si on est pas en mode runtime, on utilise surement l'espace public pour afficher les plugins.
	// Il faut donc s'assurer que les urls suivent bien la mise à jour
	// - on supprime toutes les urls plugin
	// - on les regenere pour la liste des plugins mise a jour
	if (!_SVP_MODE_RUNTIME)
		svp_actualiser_url_plugins($id_depot);

	// On supprime le depot lui-meme
	Sql::delete('spip_depots','id_depot='.Sql::quote($id_depot));

	// on supprime les paquets locaux pour reactualisation
	include_spip('inc/svp_depoter_local');
	svp_base_supprimer_paquets_locaux();

	return true;
}


/**
 * Nettoyer la base de données après la suppression d'un dépot 
 *
 * Supprime
 * - les liens des plugins avec le dépot (table spip_depots_plugins)
 * - les plugins dont aucun paquet n'est encore hébergé par un dépot restant (table spip_plugins)
 * Remet à zéro la version maximale des plugins ayant vu leur paquet en version maximale supprimée
 * 
 * @param int $id_depot
 *     Identifiant du dépot
 * @param array $vmax
 *     Tableau de la version maximale des plugins du dépot supprimé
 *     Tableau (id_plugin => version maximale)
 * @return bool
 *     true toujours.
**/
function svp_nettoyer_apres_suppression($id_depot, $vmax) {

	// On rapatrie la liste des plugins du depot qui servira apres qu'on ait supprime les liens 
	// de la table spip_depots_plugins
	$liens = Sql::allfetsel('id_plugin', 'spip_depots_plugins', 'id_depot='.Sql::quote($id_depot));
	$plugins_depot = array_map('reset', $liens);

	// On peut donc supprimer tous ces liens *plugins-depots* du depot
	Sql::delete('spip_depots_plugins', 'id_depot='.Sql::quote($id_depot));

	// On verifie pour chaque plugin concerne par la disparition de paquets si c'est la version
	// la plus elevee qui a ete supprimee.
	// Si oui, on positionne le vmax a 0, ce qui permettra de remettre a jour le plugin systematiquement
	// a la prochaine actualisation. 
	// Cette operation est necessaire car on n'impose pas que les informations du plugin soient identiques
	// pour chaque paquet !!!
	
	// On insere, en encapsulant pour sqlite...
	if (Sql::preferer_transaction()) {
		Sql::demarrer_transaction();
	}
	
	if ($resultats = Sql::allfetsel('id_plugin, vmax', 'spip_plugins', Sql::in('id_plugin', $plugins_depot))) {
		foreach ($resultats as $plugin) {
			if (spip_version_compare($plugin['vmax'], $vmax[$plugin['id_plugin']], '='))
				Sql::updateq('spip_plugins',	array('vmax' => ''),	'id_plugin=' . Sql::quote($plugin['id_plugin']));
		}
	}

	if (Sql::preferer_transaction()) {
		Sql::terminer_transaction();
	}
	
	// Maintenant on calcule la liste des plugins du depot qui ne sont pas heberges 
	// par un autre depot => donc a supprimer
	// - Liste de tous les plugins encore lies a un autre depot
	// tous les plugins correspondants aux anciens paquets
	$plugins_restants = Sql::allfetsel('DISTINCT(id_plugin)', 'spip_paquets', Sql::in('id_plugin', $plugins_depot));
	$plugins_restants = array_map('array_shift', $plugins_restants);

	// - L'intersection des deux tableaux renvoie les plugins a supprimer	
	$plugins_a_supprimer = array_diff($plugins_depot, $plugins_restants);

	// On supprimer les plugins identifies
	Sql::delete('spip_plugins', Sql::in('id_plugin', $plugins_a_supprimer));	
	
	return true;
}


/**
 * Actualisation des plugins d'un dépot déjà crée
 *
 * Actualise les informations uniquement si la signature du fichier
 * XML de description du dépot a changé
 * 
 * @param int $id
 *     Identifiant du dépot
 * @return bool
 *     false si erreur, true sinon
 */
function svp_actualiser_depot($id){
	include_spip('inc/distant');

	$id = intval($id);
	
	// pas de depot a cet id ?
	if (!$depot = Sql::fetsel('*', 'spip_depots', 'id_depot='. Sql::quote($id)) ){
		return false;
	}
	
	$fichier_xml = _DIR_RACINE . copie_locale($depot['xml_paquets'], 'modif');

	$sha = sha1_file($fichier_xml);

	if ($depot['sha_paquets'] == $sha) {
		// Le fichier n'a pas change (meme sha1) alors on ne fait qu'actualiser la date 
		// de mise a jour du depot en mettant a jour *inutilement* le sha1
		spip_log('Aucune modification du fichier XML, actualisation non declenchee - id_depot = ' . $depot['id_depot'], 'svp_actions.' . _LOG_INFO);
		Sql::replace('spip_depots', array_diff_key($depot, array('maj' => '')));
	}
	else {

		// Le fichier a bien change il faut actualiser tout le depot
		$infos = svp_phraser_depot($fichier_xml);

		if (!$infos)
			return false;

		// On actualise les paquets dans spip_paquets en premier lieu.
		// Lors de la mise a jour des paquets, les plugins aussi sont actualises
		$ok = svp_actualiser_paquets($depot['id_depot'], $infos['paquets'],
									$nb_paquets, $nb_plugins, $nb_autres);

		// apres la mise a jour des paquets d'un depot, on actualise les informations des paquets locaux
		// principalement l'info "maj_version" indiquant s'il existe un paquet plus recent
		include_spip('inc/svp_depoter_local');
		svp_actualiser_maj_version();

		if ($ok) {
			// On met à jour :
			// -- les infos ne pouvant pas etre editees par le formulaire d'edition
			//    d'un depot et extraites du xml
			// -- le nombre de paquets et de plugins du depot ainsi que le nouveau sha1
			// ce qui aura pour effet d'actualiser la date de mise a jour
			$champs = array('url_serveur'  => $infos['depot']['url_serveur'],
							'url_brouteur' => $infos['depot']['url_brouteur'],
							'url_archives' => $infos['depot']['url_archives'],
							'url_commits'  => $infos['depot']['url_commits'],
							'nbr_paquets'  => $nb_paquets,
							'nbr_plugins'  => $nb_plugins,
							'nbr_autres'   => $nb_autres,
							'sha_paquets'  => $sha);
			Sql::updateq('spip_depots', $champs, 'id_depot=' . Sql::quote($depot['id_depot']));
		}
	}

	return true;
}


/**
 * Actualisation de la table des paquets pour le dépot choisi
 *
 * Enlève de la base les paquets du dépots qui ne sont plus présents
 * dans la description du XML. Ajoute ou met à jour les autres.
 * 
 * @param int $id_depot
 *     Identifiant du dépot
 * @param array $paquets
 *     Tableau des paquets extraits du fichier XML
 *     L'index est le nom de l'archive (xxxx.zip) et le contenu est
 *     un tableau à deux entrées :
 *     - Index 'plugin' : le tableau des infos du plugin
 *     - Index 'file' : le nom de l'archive .zip
 * @param int $nb_paquets
 *     Nombre de paquets réellement inserés dans la base
 * @param int $nb_plugins
 *     Nombre de plugins parmi les paquets inserés
 * @param int &$nb_autres
 *     Nombre de contributions non issues de plugin parmi les paquets inserés
 * @return bool
 *     false si aucun dépot ou paquets, true sinon
 */
function svp_actualiser_paquets($id_depot, $paquets, &$nb_paquets, &$nb_plugins, &$nb_autres) {

	// Initialisation des compteurs
	$nb_paquets = 0;
	$nb_plugins = 0;
	$nb_autres = 0;

	// Si aucun depot ou aucun paquet on renvoie une erreur
	if ((!$id_depot) OR (!is_array($paquets)))
		return false;

	// On initialise l'url de base des logos du depot et son type afin de
	// calculer l'url complete de chaque logo
	$select = array('url_archives', 'type');
	$depot = Sql::fetsel($select, 'spip_depots', 'id_depot=' . Sql::quote($id_depot));


	// On supprime tous les paquets du depot
	// qui ont ete evacues, c'est a dire ceux dont les signatures
	// ne correspondent pas aux nouveaux...
	// et on retablit les vmax des plugins restants...
	$signatures = array();
	foreach ($paquets as $_paquet) {
		$signatures[] = $_paquet['md5'];
	}

	// tous les paquets du depot qui ne font pas parti des signatures
	$anciens_paquets = Sql::allfetsel('id_paquet', 'spip_paquets', array('id_depot=' . Sql::quote($id_depot), Sql::in('signature', $signatures, 'NOT')));
	$anciens_paquets = array_map('array_shift', $anciens_paquets);

	// pour ces vieux paquets, on les nettoie de la base
	if ($anciens_paquets) {
		// tous les plugins correspondants aux anciens paquets
		$anciens_plugins = Sql::allfetsel('pl.id_plugin', array('spip_plugins AS pl', 'spip_paquets AS pa'), array('pl.id_plugin=pa.id_plugin', Sq::in('pa.id_paquet', $anciens_paquets)));
		$anciens_plugins = array_map('array_shift', $anciens_plugins);

		// suppression des anciens paquets
		Sql::delete('spip_paquets', Sql::in('id_paquet', $anciens_paquets));
		// suppressions des liaisons depots / anciens plugins
		// on enlève la liaison lorsqu'il n'y a plus aucun paquet lie a un des plugins qui ont vu un paquet enlevé

		// liste des plugins qui ont encore des paquets dans ce depot
		$plugins_restants = Sql::allfetsel('pl.id_plugin',
			array('spip_plugins AS pl', 'spip_paquets AS pa'),
			array(Sql::in('pl.id_plugin', $anciens_plugins), 'pl.id_plugin=pa.id_plugin', 'pa.id_depot=' . Sql::quote($id_depot)));
		$plugins_restants = array_map('array_shift', $plugins_restants);
		// par opposition, on retrouve ceux qui n'en ont plus...
		$plugins_supprimes = array_diff($anciens_plugins, $plugins_restants);
		Sql::delete('spip_depots_plugins', array('id_depot='. Sql::quote($id_depot), Sql::in('id_plugin', $plugins_supprimes)));
		unset($plugins_restants, $plugins_supprimes);

		// supprimer les plugins orphelins
		include_spip('inc/svp_depoter_local');
		svp_supprimer_plugins_orphelins($anciens_plugins);
		
		// corriger les vmax des plugins
		svp_corriger_vmax_plugins($anciens_plugins);

		// corriger les compats, branches aussi
		svp_completer_plugins($anciens_plugins);
	}

	// on ne garde que les paquets qui ne sont pas presents dans la base
	$signatures = Sql::allfetsel('signature', 'spip_paquets', 'id_depot='.Sql::quote($id_depot));
	$signatures = array_map('array_shift', $signatures);
	foreach ($paquets as $cle => $_infos) {
		if (in_array($_infos['md5'], $signatures)) {
			unset($paquets[$cle]);
		}
	}

	// tableaux d'actions
	$insert_paquets = array();
	$insert_plugins = array();
	$insert_contribs = array();
	$prefixes = array(); // prefixe => id_plugin
	
	// On met a jour ou on cree chaque paquet a partir du contenu du fichier xml
	// On ne fait pas cas de la compatibilite avec la version de SPIP installee
	// car l'operation doit permettre de collecter tous les paquets
	foreach ($paquets as $_archive => $_infos) {

		$insert_paquet = array();
		// On initialise les informations specifiques au paquet :
		// l'id du depot et les infos de l'archive
		$insert_paquet['id_depot'] = $id_depot;
		$insert_paquet['nom_archive'] = $_archive;
		$insert_paquet['nbo_archive'] = $_infos['size'];
		$insert_paquet['maj_archive'] = date('Y-m-d H:i:s', $_infos['date']);
		$insert_paquet['src_archive'] = $_infos['source'];
		$insert_paquet['date_modif'] = $_infos['last_commit'];
		// On serialise le tableau des traductions par module
		$insert_paquet['traductions'] = serialize($_infos['traductions']);
		// On ajoute la signature
		$insert_paquet['signature'] = $_infos['md5'];

		// On verifie si le paquet est celui d'un plugin ou pas
		// -- Les traitements du XML dependent de la DTD utilisee
		// Formatage des informations extraites du plugin pour insertion dans la base SVP
		$formater = charger_fonction('preparer_sql_' . $_infos['dtd'], 'plugins');
		if ($champs_aplat = $formater($_infos['plugin'])) {
			// Eclater les champs recuperes en deux sous tableaux, un par table (plugin, paquet)
			$champs = eclater_plugin_paquet($champs_aplat);

			$paquet_plugin = true;
			// On complete les informations du paquet et du plugin
			$insert_paquet = array_merge($insert_paquet, $champs['paquet']);
			$insert_plugin = $champs['plugin'];
			// On construit l'url complete du logo
			// Le logo est maintenant disponible a la meme adresse que le zip et porte le nom du zip.
			// Son extension originale est conservee
			if ($insert_paquet['logo'])
				$insert_paquet['logo'] = $depot['url_archives'] . '/'
									   . basename($insert_paquet['nom_archive'], '.zip') . '.'
									   . pathinfo($insert_paquet['logo'], PATHINFO_EXTENSION);

			// On loge l'absence de categorie ou une categorie erronee et on positionne la categorie
			// par defaut "aucune"
			// Provisoire tant que la DTD n'est pas en fonction
			if (!$insert_plugin['categorie']) {
				spip_log("Categorie absente dans le paquet issu de <". $insert_paquet['src_archive'] . 
						"> du depot <" . $insert_paquet['id_depot'] . ">\n", 'svp_paquets.' . _LOG_INFO_IMPORTANTE);
				$insert_plugin['categorie'] = 'aucune';
			}
			else {
				$svp_categories = $GLOBALS['categories_plugin'];
				if (!in_array($insert_plugin['categorie'], $svp_categories)) {
					spip_log("Categorie &#107;" . $insert_plugin['categorie'] . "&#108; incorrecte dans le paquet issu de <". $insert_paquet['src_archive'] . 
							"> du depot <" . $insert_paquet['id_depot'] . ">\n", 'svp_paquets.' . _LOG_INFO_IMPORTANTE);
					$insert_plugin['categorie'] = 'aucune';
				}
			}
		}
		else {
			$paquet_plugin = false;
		}
		// On teste l'existence du paquet dans la base avec les champs
		// id_depot, nom_archive et src_archive pour être sur de l'unicité.
		// - si le paquet n'existe pas, on le crée,
		// - sinon (et ça ne devrait pas arriver), on ne fait qu'un update
		if (!$paquet = Sql::fetsel('*', 'spip_paquets', array('id_depot='. Sql::quote($insert_paquet['id_depot']),
															'nom_archive='. Sql::quote($insert_paquet['nom_archive']),
															'src_archive='. Sql::quote($insert_paquet['src_archive'])))) {
			// Le paquet n'existe pas encore en base de donnees
			// ------------------------------------------------
			
			// On positionne la date de creation a celle du dernier commit ce qui est bien le cas
			$insert_paquet['date_crea'] = $insert_paquet['date_modif'];

			// Les collisions ne sont possibles que si on ajoute un nouveau paquet
			$collision = false;

			if ($paquet_plugin) {
				// On est en presence d'un PLUGIN
				// ------------------------------
				// On evite les doublons de paquet
				// Pour determiner un doublon on verifie actuellement :
				// - le prefixe
				// - la version du paquet et de la base
				// - l'etat
				$where = array('t1.id_plugin=t2.id_plugin',
						't1.version=' . Sql::quote($insert_paquet['version']),
						't1.version_base=' . Sql::quote($insert_paquet['version_base']),
						't1.etatnum=' . Sql::quote($insert_paquet['etatnum']),
						't1.id_depot>' . intval(0),
						't2.prefixe=' . Sql::quote($insert_plugin['prefixe']));
				if (!$id_paquet = Sql::getfetsel('t1.id_paquet', 'spip_paquets AS t1, spip_plugins AS t2', $where)) {
					// On traite d'abord le plugin du paquet pour recuperer l'id_plugin
					// On rajoute le plugin dans la table spip_plugins si celui-ci n'y est pas encore ou on recupere
					// l'id si il existe deja et on le met a jour si la version du paquet est plus elevee

					$plugin = Sql::fetsel('id_plugin, vmax', 'spip_plugins', array('prefixe=' . Sql::quote($insert_plugin['prefixe'])));
					if (!$plugin AND !array_key_exists($insert_plugin['prefixe'], $insert_plugins)) {
						$insert_plugins[ $insert_plugin['prefixe'] ] = array_merge($insert_plugin, array('vmax' => $insert_paquet['version']));
					}
					else {
						if ($plugin) {
							$id_plugin = $plugin['id_plugin'];
							$prefixes[$insert_plugin['prefixe']] = $id_plugin;
						}
						if (array_key_exists($insert_plugin['prefixe'], $insert_plugins)
						AND (spip_version_compare($insert_plugins[ $insert_plugin['prefixe'] ]['vmax'], $insert_paquet['version'], '<='))) {
							// attribuer au plugin le nom et le slogan du paquet le plus à jour
							$insert_plugins[ $insert_plugin['prefixe'] ]['nom'] = $insert_plugin['nom'];
							$insert_plugins[ $insert_plugin['prefixe'] ]['slogan'] = $insert_plugin['slogan'];
							$insert_plugins[ $insert_plugin['prefixe'] ]['vmax'] = $insert_paquet['version'];
						}
					}

					// On traite maintenant le paquet connaissant l'id du plugin
					// temporaire qui sera supprime lors de la connaissance de l'id_paquet
					$insert_paquet['prefixe'] = $insert_plugin['prefixe']; 
					$insert_paquets[] = $insert_paquet;
				}
				else
					$collision = true;
			}
			else {
				// On est en presence d'une CONTRIBUTION NON PLUGIN
				// ------------------------------------------------
				$where = array(
						'id_depot=' . Sql::quote($insert_paquet['id_depot']),
						'nom_archive=' . Sql::quote($insert_paquet['nom_archive']));
				if (!$id_paquet = Sql::getfetsel('id_paquet', 'spip_paquets', $where)) {
					// Ce n'est pas un plugin, donc id_plugin=0 et toutes les infos plugin sont nulles
					$insert_paquet['id_plugin'] = 0;
					$insert_contribs[] = $insert_paquet;
				} else
					$collision = true;
			}
			// On loge le paquet ayant ete refuse dans un fichier a part afin de les verifier
			// apres coup
			if ($collision) {
				spip_log("Collision avec le paquet <". $insert_paquet['nom_archive'] . 
						" / " . $insert_paquet['src_archive'] . "> du depot <" . $insert_paquet['id_depot'] . ">\n", 'svp_paquets.' . _LOG_INFO_IMPORTANTE);
			}
		}
		else {
			// Le paquet existe deja en base de donnees
			// ----------------------------------------

			// On ne devrait plus arriver ICI...
			// Code obsolete ?
			spip_log('!!!!!! Passage dans code obsolete (svp/svp_depoter_distant)', 'depoter');
			
			// on effectue les traitements en attente
			// pour que les updates soient corrects
			svp_inserer_multi($insert_plugins, $insert_paquets, $insert_contribs, $prefixes);
			
			
			// On met a jour le paquet en premier lieu qu'il soit un plugin ou une contribution
			Sql::updateq('spip_paquets', $insert_paquet,
						'id_paquet=' . Sql::quote($paquet['id_paquet']));

		}
	}

	// on effectue les traitements en attente
	// pour que les updates soient corrects
	svp_inserer_multi($insert_plugins, $insert_paquets, $insert_contribs, $prefixes);

	// On rajoute le plugin comme heberge par le depot si celui-ci n'est pas encore enregistre comme tel
	$ids = Sql::allfetsel('p.id_plugin',
		array('spip_plugins AS p', 'spip_depots_plugins AS dp'),
		array('p.id_plugin=dp.id_plugin', 'dp.id_depot='.Sql::quote($id_depot)));
	$ids = array_map('array_shift', $ids);

	// inserer les liens avec le depots
	$insert_dp = array();
	$news_id = array_diff(array_values($prefixes), $ids);
	foreach($news_id as $id) {
		$insert_dp[] = array('id_depot'=>$id_depot, 'id_plugin'=>$id);
	}
	if ($insert_dp) {
		Sql::insertq_multi('spip_depots_plugins', $insert_dp);
	}

	// on recalcul les vmax des plugins de ce depot.
	svp_corriger_vmax_plugins(array_values($prefixes));

	// On compile maintenant certaines informations des paquets mis a jour dans les plugins
	// (date de creation, date de modif, version spip...)
	svp_completer_plugins_depot($id_depot);

	// Si on est pas en mode runtime, on utilise surement l'espace public pour afficher les plugins.
	// Il faut donc s'assurer que les urls suivent bien la mise à jour
	// - on supprime toutes les urls plugin
	// - on les regenere pour la liste des plugins mise a jour
	if (!_SVP_MODE_RUNTIME)
		svp_actualiser_url_plugins($id_depot);
		
	// Calcul des compteurs de paquets, plugins et contributions
	$nb_paquets = Sql::countsel('spip_paquets', 'id_depot=' . Sql::quote($id_depot));
	$nb_plugins = Sql::countsel('spip_depots_plugins', 'id_depot=' . Sql::quote($id_depot));
	$nb_autres = Sql::countsel('spip_paquets', array('id_depot=' . Sql::quote($id_depot), 'id_plugin=0'));
	
	return true;
}


/**
 * Insertion en masse de plugins ou de paquets.
 *
 * Les paquets peuvent de pas avoir d'info "prefixe" (à transformer en id_plugin)
 * lorsqu'ils ne proviennent pas de plugin (squelettes...)
 * 
 * @param array $insert_plugins
 *     Tableau de description de plugins.
 *     Une description est un tableau de couples (colonne sql => valeur)
 *     pour l'insertion en base de données.
 * @param array $insert_paquets
 *     Tableau de description de paquets.
 *     Une description est un tableau de couples (colonne sql => valeur)
 *     pour l'insertion en base de données.
 * @param array $insert_contribs
 *     Tableau de description de paquets (contributions non plugins).
 *     Une description est un tableau de couples (colonne sql => valeur)
 *     pour l'insertion en base de données.
 * @param array $prefixes
 *     Couples de relation (préfixe de plugin => identifiant de plugin) connues,
 *     pour limiter les accès SQL.
 * @return void
**/
function svp_inserer_multi(&$insert_plugins, &$insert_paquets, &$insert_contribs, &$prefixes) {

	if (count($insert_plugins)) {
		Sql::insertq_multi('spip_plugins', $insert_plugins);
		$insert_plugins = array();
	}
	
	if (count($insert_paquets)) {

		// on cherche tous les id_plugin/prefixe que l'on a à récuperer
		// en une seule requete
		$prefixes_manquants = array();
		foreach ($insert_paquets as $p) {
			// on ne connait que le prefixe
			if (isset($p['prefixe']) and !isset($prefixes[ $p['prefixe'] ])) {
				$prefixes_manquants[] = $p['prefixe']; 
			} 
		}

		// recuperer les nouveaux prefixes :
		$new = Sql::allfetsel(array('prefixe', 'id_plugin'), 'spip_plugins', Sql::in('prefixe', $prefixes_manquants));
		foreach ($new as $p) {
			$prefixes[ $p['prefixe'] ] = $p['id_plugin'];
		}

		// inserer les id_plugin dans les paquets a inserer
		// inserer le prefixe dans le paquet (pour raccourcis de jointures)
		foreach ($insert_paquets as $c=>$p) {
			if (isset($p['prefixe'])) {
				$insert_paquets[$c]['id_plugin'] = $prefixes[ $insert_paquets[$c]['prefixe'] ];
			} else {
				$insert_paquets[$c]['prefixe'] = array_search($p['id_plugin'], $prefixes);
			}
		}

		// on insere tout !
		Sql::insertq_multi('spip_paquets', $insert_paquets);
		$insert_paquets = array();
	}

	// les contribs n'ont pas le même nombre de champs dans les insertions
	// et n'ont pas de plugin rattachés.
	if (count($insert_contribs)) {
		Sql::insertq_multi('spip_paquets', $insert_contribs);
		$insert_contribs = array();
	}
}

/**
 * Complète les informations des plugins contenus dans un depot
 * en compilant certaines informations (compatibilités, dates,  branches)
 *
 * @param int $id_depot
 *     Identifiant du depot à actualiser
**/
function svp_completer_plugins_depot($id_depot) {
	// On limite la revue des paquets a ceux des plugins heberges par le depot en cours d'actualisation
	$ids_plugins = Sql::allfetsel('id_plugin', 'spip_depots_plugins', array('id_depot=' . Sql::quote($id_depot)));
	$ids_plugins = array_map('reset', $ids_plugins);
	if ($ids_plugins) {
		svp_completer_plugins($ids_plugins);
	}
}

/**  
 * Complète les informations des plugins, d'une liste de plugins donnés,
 * en compilant certaines informations (compatibilités, dates,  branches)
 *
 * @param array $ids_plugin
 *     Liste d'identifiants de plugins
 * @return bool
 *     false si rien à faire, true sinon
**/
function svp_completer_plugins($ids_plugin) {

	if (!$ids_plugin) {
		return false;
	}

	include_spip('inc/svp_outiller');

	// -- on recupere tous les paquets associes aux plugins indiques et on compile les infos
	if ($resultats = Sql::allfetsel('id_plugin, compatibilite_spip, date_crea, date_modif', 'spip_paquets', 
				array(Sql::in('id_plugin', $ids_plugin), 'id_depot>'.intval(0)), '', 'id_plugin')) {

		$plugin_en_cours = 0;
		$inserts = array();
		
		foreach($resultats as $paquet) {
			// On finalise le plugin en cours et on passe au suivant 
			if ($plugin_en_cours != $paquet['id_plugin']) {
				// On met a jour le plugin en cours
				if ($plugin_en_cours) {
					// On deduit maintenant les branches de la compatibilite globale
					$complements['branches_spip'] = compiler_branches_spip($complements['compatibilite_spip']);
					$inserts[$plugin_en_cours] = $complements;
				}
				// On passe au plugin suivant
				$plugin_en_cours = $paquet['id_plugin'];
				$complements = array('compatibilite_spip' => '', 'branches_spip' => '', 'date_crea' => 0, 'date_modif' => 0);
			}
			
			// On compile les compléments du plugin avec le paquet courant sauf les branches
			// qui sont deduites en fin de compilation de la compatibilite
			if ($paquet['date_modif'] > $complements['date_modif'])
				$complements['date_modif'] = $paquet['date_modif'];
			if (($complements['date_crea'] === 0)
			OR ($paquet['date_crea'] < $complements['date_crea']))
				$complements['date_crea'] = $paquet['date_crea'];
			if ($paquet['compatibilite_spip'])
				if (!$complements['compatibilite_spip'])
					$complements['compatibilite_spip'] = $paquet['compatibilite_spip'];
				else
					$complements['compatibilite_spip'] = fusionner_intervalles($paquet['compatibilite_spip'], $complements['compatibilite_spip']);
		}
		// On finalise le dernier plugin en cours
		$complements['branches_spip'] = compiler_branches_spip($complements['compatibilite_spip']);
		$inserts[$plugin_en_cours] = $complements;

		// On insere, en encapsulant pour sqlite...
		if (Sql::preferer_transaction()) {
			Sql::demarrer_transaction();
		}
		
		foreach ($inserts as $id_plugin => $complements) {
			Sql::updateq('spip_plugins', $complements, 'id_plugin=' . intval($id_plugin));
		}
		
		if (Sql::preferer_transaction()) {
			Sql::terminer_transaction();
		}

	}

	return true;
}


/**
 * Recrée toutes les URLs propres de plugin 
 *
 * Supprime toutes les urls de plugin de la table spip_urls puis les régénère.
 * 
 * @return int
 *     Nombre d'URLs de plugin régénérées
**/
function svp_actualiser_url_plugins () {
	$nb_plugins = 0;

	// On supprime toutes les urls de plugin
	Sql::delete('spip_urls', array('type=\'plugin\''));

	// On recupere les ids des plugins et on regenere les urls
	if ($ids_plugin = Sql::allfetsel('id_plugin', 'spip_plugins')) {
		$ids_plugin = array_map('reset', $ids_plugin);
		$nb_plugins = count($ids_plugin);

		foreach ($ids_plugin as $_id) {
			generer_url_entite($_id, 'plugin', '', '', true);
		}
	}
	
	return $nb_plugins;
}

/**
 * Éclate une description de paquet issu du XML du dépot en deux parties,
 * une pour le plugin, l'autre pour le paquet 
 *
 * Sépare en deux une description de champs désignant un paquet, en extrayant :
 * - la partie plugin, soit ce qui peut être propre à plusieurs paquets.
 *   On trouve dedans le prefixe, nom, slogan, catégorie, tags
 * - la partie paquet, soit ce qui est propre à ce conteneur là. On trouve
 *   dedans entre autres la description, la version, la compatibilité
 *   à SPIP, les dépendances, etc...
 * 
 * @param array $champs_aplat
 *     Couples (clé => valeur) d'un paquet issu de l'analyse XML du dépot
 * @return array
 *     Tableau de 2 index :
 *     - Index 'plugin' : couples (clé=>valeur) relatives au plugin
 *     - Index 'paquet' : couples (clé=>valeur) spécifiques au paquet
**/
function eclater_plugin_paquet($champs_aplat) {
	return array(
		'plugin' => array(
			'prefixe' => $champs_aplat['prefixe'],
			'nom' => $champs_aplat['nom'],
			'slogan' => $champs_aplat['slogan'],
			'categorie' => $champs_aplat['categorie'],
			'tags' => $champs_aplat['tags']),
		'paquet' => array(
			'logo' => $champs_aplat['logo'],
			'description' => $champs_aplat['description'],
			'auteur' => $champs_aplat['auteur'],
			'credit' => $champs_aplat['credit'],
			'version' => $champs_aplat['version'],
			'version_base' => $champs_aplat['version_base'],
			'compatibilite_spip' => $champs_aplat['compatibilite_spip'],
			'branches_spip' => $champs_aplat['branches_spip'],
			'etat' => $champs_aplat['etat'],
			'etatnum' => $champs_aplat['etatnum'],
			'licence' => $champs_aplat['licence'],
			'copyright' => $champs_aplat['copyright'],
			'lien_doc' => $champs_aplat['lien_doc'],
			'lien_demo' => $champs_aplat['lien_demo'],
			'lien_dev' => $champs_aplat['lien_dev'],
			'dependances' => $champs_aplat['dependances'])
	);
}



/**
 * Détermine la version max de chaque plugin, c'est à dire
 * la version maxi d'un des paquets qui lui est lié.
 * 
 * @param array $plugins Liste d'identifiant de plugins
**/
function svp_corriger_vmax_plugins($plugins) {
	// tous les plugins encore lies a des depots (hors local)...
	// la vmax est a retablir...
	if ($plugins) {
		$p = Sql::allfetsel('DISTINCT(p.id_plugin)',
			array('spip_plugins AS p', 'spip_paquets AS pa'),
			array(Sql::in('p.id_plugin', $plugins), 'p.id_plugin=pa.id_plugin', 'pa.id_depot>'.intval(0)));
		$p = array_map('array_shift', $p);

		// pour les autres, on la fixe correctement
		
		// On insere, en encapsulant pour sqlite...
		if (Sql::preferer_transaction()) {
			Sql::demarrer_transaction();
		}
		
		foreach ($p as $id_plugin) {
			$vmax = '';
			if ($pa = Sql::allfetsel('version', 'spip_paquets', array('id_plugin='.$id_plugin, 'id_depot>'.intval(0)))) {
				foreach ($pa as $v) {
					if (spip_version_compare($v['version'], $vmax, '>')) {
						$vmax = $v['version'];
					}
				}
			}
			Sql::updateq('spip_plugins', array('vmax'=>$vmax), 'id_plugin=' . intval($id_plugin));
		}
		
		if (Sql::preferer_transaction()) {
			Sql::terminer_transaction();
		}
	}
}



?>
