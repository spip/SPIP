<?php

/**
 * Déclarations de fonctions
 * 
 * @plugin SVP pour SPIP
 * @license GPL
 * @package SPIP\SVP\Fonctions
**/

/**
 * Retourne un texte expliquant l'intervalle de compatibilité avec un plugin ou SPIP
 *
 * Retourne par exemple "2.0 <= SPIP < 3.1"
 * 
 * @param string $intervalle
 *     L'intervalle tel que déclaré dans paquet.xml. Par exemple "[2.1;3.0.*]"
 * @param string $logiciel
 *     Nom du plugin pour qui est cette intervalle
 * @return string
 *     Texte expliquant l'intervalle
**/
function svp_afficher_intervalle($intervalle, $logiciel){
	if (!strlen($intervalle)) return '';
	if (!preg_match(',^[\[\(\]]([0-9.a-zRC\s\-]*)[;]([0-9.a-zRC\s\-\*]*)[\]\)\[]$,Uis',$intervalle,$regs)) return false;
	$mineure = $regs[1];
	$majeure = preg_replace(',\.99$,', '.*', $regs[2]);
	$mineure_inc = $intervalle{0}=="[";
	$majeure_inc = substr($intervalle,-1)=="]";
	if (strlen($mineure)){
		if (!strlen($majeure))
			$version = $logiciel . ($mineure_inc ? ' &ge; ' : ' &gt; ') . $mineure;
		else
			$version = $mineure . ($mineure_inc ? ' &le; ' : ' &lt; ') .  $logiciel . ($majeure_inc ? ' &le; ' : ' &lt; ') . $majeure;
	}
	else {
		if (!strlen($majeure))
			$version = $logiciel;
		else
			$version =  $logiciel . ($majeure_inc ? ' &le; ' : ' &lt; ') . $majeure;
	}

	return $version;
}

/**
 * Traduit un type d'état de plugin
 *
 * Si l'état n'existe pas, prendra par défaut 'developpement'
 *
 * @see plugin_etat_en_clair()
 * @param string $etat
 *     Le type d'état (stable, test, ...)
 * @return string
 *     Traduction de l'état dans la langue en cours
**/
function svp_afficher_etat($etat) {
	include_spip('plugins/afficher_plugin');
	return plugin_etat_en_clair($etat);
}

/**
 * Retourne un texte HTML présentant la liste des dépendances d'un plugin 
 *
 * Des liens vers les plugins dépendants sont présents lorsque les plugins
 * en dépendance sont connus dans notre base.
 * 
 * @param string $balise_serialisee
 *     Informations des dépendances (tableau sérialisé) tel que stocké
 *     en base dans la table spip_paquets
 * @param string $dependance
 *     Type de dépendances à afficher (necessite ou utilise).
 *     Une autre valeur indique qu'on demande la liste des librairies dépendantes.
 * @param string $sep
 *     Séparateur entre les noms de dépendances
 * @return string
 *     Texte informant des dépendances
**/
function svp_afficher_dependances($balise_serialisee, $dependance='necessite', $sep='<br />') {
	$texte = '';
	
	$t = unserialize($balise_serialisee);
	$dependances = $t[$dependance];
	if (is_array($dependances)) {
		ksort($dependances);
	
		foreach($dependances as $_compatibilite => $_dependance) {
			$compatibilite = ($_compatibilite !== 0)
					? _T('svp:info_compatibilite_dependance', array('compatibilite' => svp_afficher_intervalle($_compatibilite, 'SPIP')))
					: '';
			if ($compatibilite)
				$texte .= ($texte ? str_repeat($sep, 2) : '') . $compatibilite;
			foreach ($_dependance as $_plugin) {
				if ($texte)
					$texte .= $sep;
				if (($dependance == 'necessite' ) OR ($dependance == 'utilise')) {
					if ($plugin = Sql::fetsel('id_plugin, nom', 'spip_plugins', 'prefixe=' . Sql::quote($_plugin['nom']))) {
						$logiciel = '<a href="' . generer_url_entite($plugin['id_plugin'], 'plugin') . '" title="' . _T('svp:bulle_aller_plugin') . '">' .
									extraire_multi($plugin['nom']) . '</a>';
					} else {
						// Cas ou le plugin n'est pas encore dans la base SVP.
						// On affiche son préfixe, cependant ce n'est pas un affichage devant perdurer
						$logiciel = $_plugin['nom'];
					}
					$intervalle = "";
					if (isset($_plugin['compatibilite'])) {
						$intervalle = svp_afficher_intervalle($_plugin['compatibilite'], $logiciel);
					}
					$texte .= ($intervalle) ? $intervalle : $logiciel;
				}
				else
					// On demande l'affichage des librairies
					$texte .= '<a href="' . $_plugin['lien'] . '" title="' . _T('svp:bulle_telecharger_librairie') . '">' .	$_plugin['nom'] . '</a>';
			}
		}
	}

	return $texte;
}

/**
 * Teste si un plugin possède des dépendances
 *
 * @param string $balise_serialisee
 *     Informations des dépendances (tableau sérialisé) tel que stocké
 *     en base dans la table spip_paquets
 * @return bool
 *     Le plugin possède t'il des dépendances ?
**/
function svp_dependances_existe($balise_serialisee) {
	$dependances = unserialize($balise_serialisee);
	foreach($dependances as $_dependance) {
		if ($_dependance)
			return true;
	}

	return false;
}


/**
 * Retourne un texte HTML présentant les crédits d'un plugin 
 *
 * Des liens vers les crédits sont présents lorsqu'ils sont déclarés
 * dans le paquet.xml.
 * 
 * @param string $balise_serialisee
 *     Informations des crédits (tableau sérialisé) tel que stocké
 *     en base dans la table spip_paquets
 * @param string $sep
 *     Séparateur entre les différents crédits
 * @return string
 *     Texte informant des crédits
**/
function svp_afficher_credits($balise_serialisee, $sep=', ') {
	$texte = '';
	
	$credits = unserialize($balise_serialisee);
	if (is_array($credits)) {
		foreach ($credits as $_credit) {
			if ($texte) 
				$texte .= $sep;
			// Si le credit en cours n'est pas un array c'est donc un copyright
			$texte .= 
				(!is_array($_credit)) 
				? PtoBR(propre($_credit)) // propre pour les [lien->url] des auteurs de plugin.xml ...
				: ($_credit['url'] ? '<a href="' . $_credit['url'] . '">' : '') . 
				  $_credit['nom'] .
				  ($_credit['url'] ? '</a>' : '');
		}
	}

	return $texte;
}


/**
 * Retourne un texte HTML présentant la liste des langues et traducteurs d'un plugin 
 *
 * Des liens vers les traducteurs sont présents lorsqu'ils sont connus.
 * 
 * @param array $langues
 *     Tableau code de langue => traducteurs
 * @param string $sep
 *     Séparateur entre les différentes langues
 * @return string
 *     Texte informant des langues et traducteurs
**/
function svp_afficher_langues($langues, $sep=', '){
	$texte = '';
	
	if ($langues) {
		foreach ($langues as $_code => $_traducteurs) {
			if ($texte) 
				$texte .= $sep;
			$traducteurs_langue = array();
			foreach ($_traducteurs as $_traducteur) {
				if (is_array($_traducteur))
					$traducteurs_langue[] =
						($_traducteur['lien'] ? '<a href="' . $_traducteur['lien'] . '">' : '') .
						$_traducteur['nom'] .
						($_traducteur['lien'] ? '</a>' : '');
			}
			$texte .= $_code . (count($traducteurs_langue) > 0 ? ' (' . implode(', ', $traducteurs_langue) . ')' : '');
		}
	}

	return $texte;
}


/**
 * Retourne un texte HTML présentant des statistiques d'un dépot
 *
 * Liste le nombre de plugins et de paquets d'un dépot
 * Indique aussi le nombre de dépots si l'on ne demande pas de dépot particulier.
 * 
 * @param int $id_depot
 *     Identifiant du dépot
 * @return string
 *     Code HTML présentant les statistiques du dépot
**/
function svp_afficher_statistiques_globales($id_depot=0){
	$info = '';

	$total = svp_compter('depot', $id_depot);
	if (!$id_depot) {
		// Si on filtre pas sur un depot alors on affiche le nombre de depots
		$info = '<li id="stats-depot" class="item">
					<div class="unit size4of5">' . ucfirst(trim(_T('svp:info_depots_disponibles', array('total_depots'=>'')))) . '</div>
					<div class="unit size1of5 lastUnit">' . $total['depot'] . '</div>
				</li>';
	}
	// Compteur des plugins filtre ou pas par depot
	$info .= '<li id="stats-plugin" class="item">
				<div class="unit size4of5">' . ucfirst(trim(_T('svp:info_plugins_heberges',  array('total_plugins'=>'')))) . '</div>
				<div class="unit size1of5 lastUnit">' . $total['plugin'] . '</div>
			</li>';
	// Compteur des paquets filtre ou pas par depot
	$info .= '<li id="stats-paquet" class="item">
				<div class="unit size4of5">' . ucfirst(trim(_T('svp:info_paquets_disponibles', array('total_paquets'=>'')))) . '</div>
				<div class="unit size1of5 lastUnit">' . $total['paquet'] . '</div>
			</li>';

	return $info;
}


/**
 * Retourne un texte indiquant un nombre total de paquets
 *
 * Calcule le nombre de paquets correspondant à certaines contraintes,
 * tel que l'appartenance à un certain dépot, une certaine catégorie
 * ou une certaine branche de SPIP et retourne une phrase traduite
 * tel que «78 paquets disponibles»
 * 
 * @param int $id_depot
 *     Identifiant du dépot
 *     Zéro (par défaut) signifie ici : «dans tous les dépots distants»
 *     (id_dépot>0) et non «dans le dépot local»
 * @param string $categorie
 *     Type de catégorie (auteur, communication, date...)
 * @param string $compatible_spip
 *     Numéro de branche de SPIP. (3.0, 2.1, ...)
 * @return string
 *     Texte indiquant un nombre total de paquets
**/
function svp_compter_telechargements($id_depot=0, $categorie='', $compatible_spip=''){
	$total = svp_compter('paquet', $id_depot, $categorie, $compatible_spip);
	$info = _T('svp:info_paquets_disponibles', array('total_paquets'=>$total['paquet']));
	return $info;
}

/**
 * Retourne un texte indiquant un nombre total de contributions pour un dépot
 *
 * Calcule différents totaux pour un dépot donné et retourne un texte
 * de ces différents totaux. Les totaux correspondent par défaut aux
 * plugins et paquets, mais l'on peut demander le total des autres contributions
 * avec le second paramètre.
 * 
 * @param int $id_depot
 *     Identifiant du dépot
 *     Zéro (par défaut) signifie ici : «dans tous les dépots distants»
 *     (id_dépot>0) et non «dans le dépot local»
 * @param string $contrib
 *     Type de total demandé ('plugin' ou autre)
 *     Si 'plugin' : indique le nombre de plugins et de paquets du dépot
 *     Si autre chose : indique le nombre des autres contributions, c'est
 *     à dire des zips qui ne sont pas des plugins, comme certaines libraires ou
 *     certains jeux de squelettes.
 * @return string
 *     Texte indiquant certains totaux tel que nombre de plugins, nombre de paquets...
**/
function svp_compter_depots($id_depot, $contrib='plugin'){
	$info = '';

	$total = svp_compter('depot', $id_depot);
	if (!$id_depot) {
		$info = _T('svp:info_depots_disponibles', array('total_depots'=>$total['depot'])) . ', ' .
				_T('svp:info_plugins_heberges', array('total_plugins'=>$total['plugin'])) . ', ' .
				_T('svp:info_paquets_disponibles', array('total_paquets'=>$total['paquet']));
	}
	else {
		if ($contrib == 'plugin') {
			$info = _T('svp:info_plugins_heberges', array('total_plugins'=>$total['plugin'])) . ', ' .
					_T('svp:info_paquets_disponibles', array('total_paquets'=>$total['paquet']-$total['autre']));
		}
		else {
			$info = _T('svp:info_contributions_hebergees', array('total_autres'=>$total['autre']));
		}
	}
	return $info;
}


/**
 * Retourne un texte indiquant un nombre total de plugins
 *
 * Calcule le nombre de plugins correspondant à certaines contraintes,
 * tel que l'appartenance à un certain dépot, une certaine catégorie
 * ou une certaine branche de SPIP et retourne une phrase traduite
 * tel que «64 plugins disponibles»
 * 
 * @param int $id_depot
 *     Identifiant du dépot
 *     Zéro (par défaut) signifie ici : «dans tous les dépots distants»
 *     (id_dépot>0) et non «dans le dépot local»
 * @param string $categorie
 *     Type de catégorie (auteur, communication, date...)
 * @param string $compatible_spip
 *     Numéro de branche de SPIP. (3.0, 2.1, ...)
 * @return string
 *     Texte indiquant un nombre total de paquets
**/
function svp_compter_plugins($id_depot=0, $categorie='', $compatible_spip='') {
	$total = svp_compter('plugin', $id_depot, $categorie, $compatible_spip);
	$info = _T('svp:info_plugins_disponibles', array('total_plugins'=>$total['plugin']));
	return $info;
}


/**
 * Compte le nombre de plugins, paquets ou autres contributions
 * en fonction de l'entité demandée et de contraintes
 *
 * Calcule, pour un type d'entité demandé (depot, plugin, paquet, catégorie)
 * leur nombre en fonction de certaines contraintes, tel que l'appartenance
 * à un certain dépot, une certaine catégorie ou une certaine branche de SPIP.
 *
 * Lorsque l'entité demandée est un dépot, le tableau des totaux possède,
 * en plus du nombre de dépots, le nombre de plugins et paquets.
 * 
 * @note
 *     Attention le critère de compatibilite SPIP pris en compte est uniquement
 *     celui d'une branche SPIP
 * 
 * @param string $entite
 *     De quoi veut-on obtenir des comptes. Peut être 'depot', 'plugin',
 *    'paquet' ou 'categorie'
 * @param int $id_depot
 *     Identifiant du dépot
 *     Zéro (par défaut) signifie ici : «dans tous les dépots distants»
 *     (id_dépot>0) et non «dans le dépot local»
 * @param string $categorie
 *     Type de catégorie (auteur, communication, date...)
 * @param string $compatible_spip
 *     Numéro de branche de SPIP. (3.0, 2.1, ...)
 * @return array
 *     Couples (entite => nombre).
**/
function svp_compter($entite, $id_depot=0, $categorie='', $compatible_spip=''){
	$compteurs = array();

	$group_by = array();
	$where = array();
	if ($id_depot)
		$where[] = "t1.id_depot=" . Sql::quote($id_depot);
	else
		$where[] = "t1.id_depot>0";

	if ($entite == 'plugin') {
		$from = 'spip_plugins AS t2, spip_depots_plugins AS t1';
		$where[] = "t1.id_plugin=t2.id_plugin";
		if ($categorie)
			$where[] = "t2.categorie=" . Sql::quote($categorie);
		if ($compatible_spip) {
			$creer_where = charger_fonction('where_compatible_spip', 'inc');
			$where[] =  $creer_where($compatible_spip, 't2', '>');
		}
		$compteurs['plugin'] = Sql::count(Sql::select('t2.id_plugin', $from, $where));
	}
	elseif ($entite == 'paquet') {
		if ($categorie) {
			$ids = Sql::allfetsel('id_plugin', 'spip_plugins', 'categorie='.Sql::quote($categorie));
			$ids = array_map('reset', $ids);
			$where[] = Sql::in('t1.id_plugin', $ids);
		}
		if ($compatible_spip) {
			$creer_where = charger_fonction('where_compatible_spip', 'inc');
			$where[] =  $creer_where($compatible_spip, 't1', '>');
		}
		$compteurs['paquet'] = Sql::countsel('spip_paquets AS t1', $where);
	}
	elseif ($entite == 'depot') {
		$champs = array('COUNT(t1.id_depot) AS depot', 
						'SUM(t1.nbr_plugins) AS plugin', 
						'SUM(t1.nbr_paquets) AS paquet',
						'SUM(t1.nbr_autres) AS autre');
		$compteurs = Sql::fetsel($champs, 'spip_depots AS t1', $where);
	}
	elseif ($entite == 'categorie') {
		$from = array('spip_plugins AS t2');
		$where_depot = $where[0];
		$where = array();
		if ($id_depot) {
			$ids = Sql::allfetsel('id_plugin', 'spip_depots_plugins AS t1', $where_depot);
			$ids = array_map('reset', $ids);
			$where[] = Sql::in('t2.id_plugin', $ids);
		}
		if ($compatible_spip) {
			$creer_where = charger_fonction('where_compatible_spip', 'inc');
			$where[] =  $creer_where($compatible_spip, 't2', '>');
		}
		if ($categorie)
			$where[] = "t2.categorie=" . Sql::quote($categorie);
		else
			$group_by = array('t2.categorie');
		$compteurs['categorie'] = Sql::countsel($from, $where, $group_by); 
	}

	return $compteurs;
}


/**
 * Compile la balise #SVP_CATEGORIES
 *
 * Cette balise retourne un tableau listant chaque type de catégorie
 * en index, associé à sa traduction en valeur.
 *
 * Accepte 2 paramètres :
 * 1) le type du tri (ordre_cle ou ordre_alpha)
 * 2) une catégorie (dans ce cas, limite le tableau à cette seule catégorie si elle existe)
 *
 * @example
 *     #SVP_CATEGORIES
 *     #SVP_CATEGORIES{ordre_alpha}
 *     #SVP_CATEGORIES{ordre_cle,auteur}
 *
 * @balise svp_categories
 * @see calcul_svp_categories()
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_SVP_CATEGORIES($p) {
	// tri, peut être 'ordre_cle' ou 'ordre_alpha'
	if (!$tri = interprete_argument_balise(1,$p)) {
		$tri = "'ordre_cle'";
	}
	// catégorie (pour n'en prendre qu'une au lieu de toutes)
	if (!$categorie = interprete_argument_balise(2,$p)) {
		$categorie = "''";
	}
	$p->code = 'calcul_svp_categories(' . $tri .  ',' . $categorie . ')';
	return $p;
}

/**
 * Retourne un tableau listant chaque type de catégorie
 * en index, associé à sa traduction en valeur.
 *
 * @param string $tri
 *     Type de tri (ordre_cle ou ordre_alpha)
 * @param string $categorie
 *     Restreindre le tableau de retour à cette catégorie si elle existe
 * @return array
 *     Couples (type de catégorie => Texte de la catégorie)
**/
function calcul_svp_categories($tri='ordre_cle', $categorie='') {

	$retour = array();
	include_spip('inc/svp_phraser'); // pour $GLOBALS['categories_plugin']
	$svp_categories = $GLOBALS['categories_plugin'];

	if (is_array($svp_categories)) {
		if (($categorie) AND in_array($categorie, $svp_categories))
			$retour[$categorie] = _T('svp:categorie_' . strtolower($categorie));
		else {
			if ($tri == 'ordre_alpha') {
				sort($svp_categories);
				// On positionne l'absence de categorie en fin du tableau
				$svp_categories[] = array_shift($svp_categories);
			}
			foreach ($svp_categories as $_alias)
				$retour[$_alias] = svp_traduire_categorie($_alias);
		}
	}
	
	return $retour;
}


/**
 * Compile la balise #SVP_BRANCHES_SPIP
 *
 * Cette balise retourne une liste des branches de SPIP
 *
 * Avec un paramètre indiquant une branche, la balise retourne
 * une liste des bornes mini et maxi de cette branche.
 * 
 * @example
 *     #SVP_BRANCHES_SPIP       : array('1.9', '2.0', '2.1', ....)
 *     #SVP_BRANCHES_SPIP{3.0}  : array('3.0.0', '3.0.99')
 *
 * @balise svp_branches_spip
 * @see calcul_svp_branches_spip()
 * 
 * @param Champ $p
 *     Pile au niveau de la balise
 * @return Champ
 *     Pile complétée par le code à générer
**/
function balise_SVP_BRANCHES_SPIP($p) {
	// nom d'une branche en premier argument
	if (!$branche = interprete_argument_balise(1,$p)) {
		$branche = "''";
	}
	$p->code = 'calcul_svp_branches_spip('.$branche.')';
	return $p;
}

/**
 * Retourne une liste des branches de SPIP, ou les bornes mini et maxi
 * d'une branche donnée
 *
 * @param string $branche
 *     Branche dont on veut récupérer les bornes mini et maxi
 * @return array
 *     Liste des branches array('1.9', '2.0', '2.1', ....)
 *     ou liste mini et maxi d'une branche array('3.0.0', '3.0.99')
**/
function calcul_svp_branches_spip($branche) {

	$retour = array();
	include_spip('inc/svp_outiller'); // pour $GLOBALS['infos_branches_spip']
	$svp_branches = $GLOBALS['infos_branches_spip'];

	if (is_array($svp_branches)) {
		if (($branche) AND in_array($branche, $svp_branches))
			// On renvoie les bornes inf et sup de la branche specifiee
			$retour = $svp_branches[$branche];
		else {
			// On renvoie uniquement les numeros de branches
			$retour = array_keys($svp_branches);
		}
	}
	
	return $retour;
}

/**
 * Traduit un type de catégorie de plugin 
 *
 * @param string $alias
 *     Type de catégorie (auteur, communication, date...)
 * @return string
 *     Titre complet et traduit de la catégorie
**/
function svp_traduire_categorie($alias) {
	$traduction = '';
	if ($alias) {
		$traduction = _T('svp:categorie_' . strtolower($alias));
	}
	return $traduction;
}

/**
 * Traduit un type de dépot de plugin 
 *
 * @param string $type
 *     Type de dépot (svn, git, manuel)
 * @return string
 *     Titre complet et traduit du type de dépot
**/
function svp_traduire_type_depot($type) {

	$traduction = '';
	if ($type) {
		$traduction = _T('svp:info_type_depot_' . $type);
	}
	return $traduction;
}

/**
 * Critère de compatibilité avec une version précise ou une branche de SPIP.
 * 
 * Fonctionne sur les tables spip_paquets et spip_plugins
 *
 * Si aucune valeur n'est explicité dans le critère, tous les enregistrements
 * sont retournés.
 *
 * Le ! (NOT) fonctionne sur le critère BRANCHE
 * 
 * @critere compatible_spip
 * @example
 *   {compatible_spip}
 *   {compatible_spip 2.0.8} ou {compatible_spip 1.9}
 *   {compatible_spip #ENV{vers}} ou {compatible_spip #ENV{vers, 1.9.2}}
 *   {compatible_spip #GET{vers}} ou {compatible_spip #GET{vers, 2.1}}
 * 
 * @param string $idb     Identifiant de la boucle
 * @param array $boucles  AST du squelette
 * @param Critere $crit   Paramètres du critère dans cette boucle
 * @return void
 */
function critere_compatible_spip_dist($idb, &$boucles, $crit) {

	$boucle = &$boucles[$idb];
	$table = $boucle->id_table;

	// Si on utilise ! la fonction LOCATE doit retourner 0.
	// -> utilise uniquement avec le critere BRANCHE
	$op = ($crit->not == '!') ? '=' : '>';

	$boucle->hash .= '
	// COMPATIBILITE SPIP
	$creer_where = charger_fonction(\'where_compatible_spip\', \'inc\');';

	// version/branche explicite dans l'appel du critere
	if (isset($crit->param[0][0])) {
		$version = calculer_liste(array($crit->param[0][0]), array(), $boucles, $boucle->id_parent);
		$boucle->hash .= '
		$where = $creer_where('.$version.', \''.$table.'\', \''.$op.'\');
		';
	}
	// pas de version/branche explicite dans l'appel du critere
	// on regarde si elle est dans le contexte
	else {
		$boucle->hash .= '
		$version = isset($Pile[0][\'compatible_spip\']) ? $Pile[0][\'compatible_spip\'] : \'\';
		$where = $creer_where($version, \''.$table.'\', \''.$op.'\');
		';
	}

	$boucle->where[] = '$where';
}

/**
 * Retourne la liste des plugins trouvés par une recherche
 *
 * @filtre construire_recherche_plugins
 * @param string $phrase
 *     Texte de la recherche
 * @param string $categorie
 *     Type de catégorie de plugin (auteur, date...)
 * @param string $etat
 *     État de plugin (stable, test...)
 * @param string|int $depot
 *     Identifiant de dépot
 * @param bool|string $afficher_exclusions
 *     Afficher aussi les paquets déjà installés (true ou 'oui')
 *     ou ceux qui ne le sont pas (false) ?
 * @param bool|string $afficher_doublons
 *     Afficher toutes les versions de paquet (true ou 'oui')
 *     ou seulement la plus récente (false) ?
 * @return array
 *     Tableau classé par pertinence de résultat
 *     - 'prefixe' => tableau de description du paquet (si pas de doublons demandé)
 *     - n => tableau de descriptions du paquet (si doublons autorisés)
**/
function filtre_construire_recherche_plugins($phrase='', $categorie='', $etat='', $depot='', $afficher_exclusions=true, $afficher_doublons=false) {

	// On traite les paramètres d'affichage
	$afficher_exclusions = ($afficher_exclusions == 'oui') ? true : false;
	$afficher_doublons = ($afficher_doublons == 'oui') ? true : false;

	$tri = ($phrase) ? 'score' : 'nom';
	$version_spip = $GLOBALS['spip_version_branche'].".".$GLOBALS['spip_version_code'];

	// On recupere la liste des paquets:
	// - sans doublons, ie on ne garde que la version la plus recente
	// - correspondant a ces criteres
	// - compatible avec la version SPIP installee sur le site
	// - et n'etant pas deja installes (ces paquets peuvent toutefois etre affiches)
	// tries par nom ou score
	include_spip('inc/svp_rechercher');
	$plugins = svp_rechercher_plugins_spip(
		$phrase, $categorie, $etat, $depot, $version_spip,
		svp_lister_plugins_installes(), $afficher_exclusions, $afficher_doublons, $tri);

	return $plugins;

}

/**
 * Retourne le nombre d'heures entre chaque actualisation
 * si le cron est activé.
 *
 * @return int
 *     Nombre d'heures (sinon 0)
**/
function filtre_svp_periode_actualisation_depots() {
	include_spip('genie/svp_taches_generales_cron');
	return _SVP_CRON_ACTUALISATION_DEPOTS ? _SVP_PERIODE_ACTUALISATION_DEPOTS : 0;
}


/**
 * Retourne 'x.y.z' à partir de '00x.00y.00z'
 * 
 * Retourne la chaine de la version x.y.z sous sa forme initiale,
 * sans remplissage à gauche avec des 0.
 *
 * @see normaliser_version()
 * @param string $version_normalisee
 *     Numéro de version normalisée
 * @return string
 *     Numéro de version dénormalisée
**/
function denormaliser_version($version_normalisee='') {

	$version = '';
	if ($version_normalisee) {
		$v = explode('.', $version_normalisee);
		foreach($v as $_nombre) {
			$n = ltrim($_nombre, '0');
			// On traite les cas du type 001.002.000-dev qui doivent etre transformes en 1.2.0-dev.
			// Etant donne que la denormalisation est toujours effectuee sur une version normalisee on sait
			// que le suffixe commence toujours pas '-'
			$vn[] = ((strlen($n)>0) AND substr($n, 0, 1)!='-' ) ? $n : "0$n";
		}
		$version = implode('.', $vn);
	}
	return $version;
}

/**
 * Teste l'utilisation du répertoire auto des plugins.
 *
 * Ce répertoire permet de télécharger dedans des plugins
 * lorsqu'il est présent.
 * 
 * @return bool
 *     Le répertoire de chargement des plugins auto est-il présent
 *     et utilisable ?
 */
function test_plugins_auto() {
	static $test = null;
	if (is_null($test)) {
		include_spip('inc/plugin'); // pour _DIR_PLUGINS_AUTO
		$test = (defined('_DIR_PLUGINS_AUTO') and _DIR_PLUGINS_AUTO and is_writable(_DIR_PLUGINS_AUTO));
	}
	return $test;
}
?>
