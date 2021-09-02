<?php

/***************************************************************************\
 *  SPIP, Système de publication pour l'internet                           *
 *                                                                         *
 *  Copyright © avec tendresse depuis 2001                                 *
 *  Arnaud Martin, Antoine Pitrou, Philippe Rivière, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribué sous licence GNU/GPL.     *
 *  Pour plus de détails voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Gestion des rôles
 *
 * Les rôles sont une qualification précise sur une liaison entre
 * deux objets. Ils doivent être définis dans la déclaration d'un objet
 * pour être utilisés. Ils s'appliquent sur une colonne particulière
 * de la table de liaison, par défaut 'role'.
 *
 * Cette table de liaison, lorsqu'elle a des rôles n'a plus sa clé primaire
 * sur le couple (id_x, objet, id_objet) mais sur (id_x, objet, id_objet, colonne_role)
 * de sorte qu'il peut exister plusieurs liens entre 2 objets, mais avec
 * des rôles différents. Chaque ligne de la table lien correspond alors à
 * un des rôles.
 *
 * @package SPIP\Core\Roles
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Vérifie qu'un objet dispose de rôles fonctionnels
 *
 * Retourne une description des rôles si c'est le cas
 *
 * @param string $objet
 *     Objet source qui possède la table de liaison
 * @param string $objet_destination
 *     Objet sur quoi on veut lier
 *     Si défini, le retour ne contient que les roles possibles pour cet objet
 *     Sinon retourne tous les roles possibles quelque soit l'objet
 * @return bool|array
 *     false si rôles indisponibles on non déclarés
 *     array : description des roles applicables dans 3 index : colonne, titres, roles
 **/
function roles_presents($objet, $objet_destination = '') {
	$desc = lister_tables_objets_sql(table_objet_sql($objet));

	// pas de liste de roles, on sort
	if (!isset($desc['roles_titres']) or !($titres = $desc['roles_titres'])) {
		return false;
	}

	// on vérifie que la table de liaison existe
	include_spip('action/editer_liens');
	if (!$lien = objet_associable($objet)) {
		return false;
	}

	// on cherche ensuite si la colonne existe bien dans la table de liaison (par défaut 'role')
	$colonne = isset($desc['roles_colonne']) ? $desc['roles_colonne'] : 'role';
	$trouver_table = charger_fonction('trouver_table', 'base');
	list(, $table_lien) = $lien;
	$desc_lien = $trouver_table($table_lien);
	if (!isset($desc_lien['field'][$colonne])) {
		return false;
	}

	// sur quoi peuvent s'appliquer nos rôles
	if (!$application = $desc['roles_objets']) {
		return false;
	}

	// destination presente, on restreint si possible
	if ($objet_destination) {
		$objet_destination = table_objet($objet_destination);

		// pour l'objet
		if (isset($application[$objet_destination])) {
			$application = $application[$objet_destination];
			// sinon pour tous les objets
		} elseif (isset($application['*'])) {
			$application = $application['*'];
		} // sinon tant pis
		else {
			return false;
		}
	}

	// tout est ok
	return [
		'titres' => $titres,
		'roles' => $application,
		'colonne' => $colonne
	];
}

/**
 * Retrouve la colonne de liaison d'un rôle si définie entre 2 objets
 *
 * @param string $objet
 *     Objet source qui possède la table de liaison
 * @param string $objet_destination
 *     Objet sur quoi on veut lier
 * @return string
 *     Nom de la colonne, sinon vide
 **/
function roles_colonne($objet, $objet_destination) {
	if ($roles = roles_presents($objet, $objet_destination)) {
		return $roles['colonne'];
	}

	return '';
}


/**
 * Extrait le rôle et la colonne de role d'un tableau de qualification
 *
 * Calcule également une condition where pour ce rôle.
 *
 * Pour un objet pouvant recevoir des roles sur sa liaison avec un autre objet,
 * on retrouve le rôle en question dans le tableau de qualification.
 * Si le rôle n'est pas défini dedans, on prend le rôle par défaut
 * déclaré.
 *
 * @param string $objet Objet source de la liaison
 * @param string $objet_destination Objet de destination de la liaison
 * @param array $qualif tableau de qualifications array(champ => valeur)
 * @return array
 *     Liste (role, colonne, (array)condition) si role possible
 *     Liste ('', '', array()) sinon.
 **/
function roles_trouver_dans_qualif($objet, $objet_destination, $qualif = []) {
	// si des rôles sont possibles, on les utilise
	$role = $colonne_role = ''; # role défini
	// condition du where par defaut
	$cond = [];
	if ($roles = roles_presents($objet, $objet_destination)) {
		$colonne_role = $roles['colonne'];
		// qu'il n'est pas défini
		if (
			!isset($qualif[$colonne_role])
			or !($role = $qualif[$colonne_role])
		) {
			$role = $roles['roles']['defaut'];
		}
		// where
		$cond = ["$colonne_role=" . sql_quote($role)];
	}

	return [$role, $colonne_role, $cond];
}

/**
 * Gérer l'ajout dans la condition where du rôle
 *
 * On ajoute la condition uniquement si la liaison entre les 2 objets a une colonne de rôle !
 *
 * @param string $objet_source Objet source (qui possède la table de liens)
 * @param string $objet Objet de destination
 * @param array $cond
 *     Tableau de conditions where
 *     qui peut avoir un index spécial 'role' définissant le role à appliquer
 *     ou valant '*' pour tous les roles.
 * @param bool $tous_si_absent
 *     true pour ne pas appliquer une condition sur le rôle s'il n'est pas indiqué
 *     dans la liste des conditions entrantes. Autrement dit, on n'applique
 *     pas de rôle par défaut si aucun n'est défini.
 * @return array
 *     Liste (Tableau de conditions where complété du role, Colonne du role, role utilisé)
 **/
function roles_creer_condition_role($objet_source, $objet, $cond, $tous_si_absent = false) {
	// role par défaut, colonne
	list($role_defaut, $colonne_role) = roles_trouver_dans_qualif($objet_source, $objet);

	// chercher d'eventuels rôles transmis
	$role = (isset($cond['role']) ? $cond['role'] : ($tous_si_absent ? '*' : $role_defaut));
	unset($cond['role']); // cette condition est particuliere...

	if ($colonne_role) {
		// on ajoute la condition du role aux autres conditions.
		if ($role != '*') {
			$cond[] = "$colonne_role=" . sql_quote($role);
		}
	}

	return [$cond, $colonne_role, $role];
}

/**
 * Liste des identifiants dont on ne peut ajouter de rôle
 *
 * Lister les id objet_source associés à l'objet id_objet
 * via la table de lien objet_lien, et détermine dans cette liste
 * lesquels ont les rôles complets, c'est à dire qu'on ne peut leur
 * affecteur d'autres rôles parmi ceux qui existe pour cette liaison.
 *
 * @see lister_objets_lies()
 *
 * @param string $objet_source Objet dont on veut récupérer la liste des identifiants
 * @param string $objet Objet sur lequel est liée la source
 * @param int $id_objet Identifiant d'objet sur lequel est liée la source
 * @param string $objet_lien Objet dont on utilise la table de liaison
 * 		(c'est forcément soit $objet_source, soit $objet)
 * @return array               Liste des identifiants
 */
function roles_complets($objet_source, $objet, $id_objet, $objet_lien) {

	$presents = roles_presents_liaisons($objet_source, $objet, $id_objet, $objet_lien);
	// pas de roles sur ces objets => la liste par defaut, comme sans role
	if ($presents === false) {
		return lister_objets_lies($objet_source, $objet, $id_objet, $objet_lien);
	}

	// types de roles possibles
	$roles_possibles = $presents['roles']['roles']['choix'];
	// couples id / roles
	$ids = $presents['ids'];

	// pour chaque groupe, on fait le diff entre tous les roles possibles
	// et les roles attribués à l'élément : s'il en reste, c'est que l'élément
	// n'est pas complet
	$complets = [];
	foreach ($ids as $id => $roles_presents) {
		if (!array_diff($roles_possibles, $roles_presents)) {
			$complets[] = $id;
		}
	}

	return $complets;
}


/**
 * Liste les roles attribués entre 2 objets/id_objet sur une table de liaison donnée
 *
 * @param string $id_objet_source Identifiant de l'objet qu'on lie
 * @param string $objet_source Objet qu'on lie
 * @param string $objet Objet sur lequel est liée la source
 * @param int $id_objet Identifiant d'objet sur lequel est liée la source
 * @param string $objet_lien Objet dont on utilise la table de liaison
 * 		(c'est forcément soit $objet_source, soit $objet)
 * @return array                  Liste des roles
 */
function roles_presents_sur_id($id_objet_source, $objet_source, $objet, $id_objet, $objet_lien) {

	$presents = roles_presents_liaisons($objet_source, $objet, $id_objet, $objet_lien);
	// pas de roles sur ces objets => la liste par defaut, comme sans role
	if ($presents === false) {
		return [];
	}

	if (!isset($presents['ids'][$id_objet_source])) {
		return [];
	}

	return $presents['ids'][$id_objet_source];
}


/**
 * Lister des rôles présents sur une liaion, pour un objet sur un autre,
 * classés par identifiant de l'objet
 *
 * Lister les id objet_source associés à l'objet id_objet
 * via la table de lien objet_lien, et groupe cette liste
 * par identifiant (la clé) et ses roles attribués (tableau de valeur)
 *
 * On retourne cette liste dans l'index 'ids' et la description des roles
 * pour la liaison dans l'index 'roles' pour éviter le le faire recalculer
 * aux fonctions utilisant celle ci.
 *
 * @param string $objet_source Objet dont on veut récupérer la liste des identifiants
 * @param string $objet Objet sur lequel est liée la source
 * @param int $id_objet Identifiant d'objet sur lequel est liée la source
 * @param string $objet_lien Objet dont on utilise la table de liaison
 * 		(c'est forcément soit $objet_source, soit $objet)
 * @return array|bool
 *     - Tableau d'index
 *       - roles : tableau de description des roles,
 *       - ids   : tableau des identifiants / roles.
 *     - False si pas de role déclarés
 */
function roles_presents_liaisons($objet_source, $objet, $id_objet, $objet_lien) {
	static $done = [];

	// stocker le résultat
	$hash = "$objet_source-$objet-$id_objet-$objet_lien";
	if (isset($done[$hash])) {
		return $done[$hash];
	}

	// pas de roles sur ces objets, on sort
	$roles = roles_presents($objet_lien, ($objet_lien == $objet) ? $objet_source : $objet);
	if (!$roles) {
		return $done[$hash] = false;
	}

	// inspiré de lister_objets_lies()
	if ($objet_lien == $objet) {
		$res = objet_trouver_liens([$objet => $id_objet], [$objet_source => '*']);
	} else {
		$res = objet_trouver_liens([$objet_source => '*'], [$objet => $id_objet]);
	}

	// types de roles possibles
	$roles_possibles = $roles['roles']['choix'];
	// colonne du role
	$colonne = $roles['colonne'];

	// on recupere par id, et role existant
	$ids = [];
	while ($row = array_shift($res)) {
		$id = $row[$objet_source];
		if (!isset($ids[$id])) {
			$ids[$id] = [];
		}
		// tableau des roles présents
		$ids[$id][] = $row[$colonne];
	}

	return $done[$hash] = [
		'roles' => $roles,
		'ids' => $ids
	];
}


/**
 * Lister des rôles connus en base pour une liaion, pour un objet source
 *
 * On retourne cette liste dans le datalist de saisie libre role.
 *
 * @param string $objet_source Objet dont on veut récupérer la liste des identifiants
 * @param string $objet Objet sur lequel est liée la source
 * @param string $objet_lien Objet dont on utilise la table de liaison
 * 		(c'est forcément soit $objet_source, soit $objet)
 * @return array|bool
 *     - Tableau de roles : tableau de description des roles,
 *     - false si pas de role déclarés
 */
function roles_connus_en_base($objet_source, $objet, $objet_lien) {
	static $done = [];

	// stocker le résultat
	$hash = "$objet_source-$objet-$objet_lien";
	if (isset($done[$hash])) {
		return $done[$hash];
	}

	if (!$lien = objet_associable($objet_lien)) {
		return $done[$hash] = false;
	}

	// pas de roles sur ces objets, on sort
	$roles = roles_presents($objet_lien, ($objet_lien == $objet) ? $objet_source : $objet);
	if (!$roles) {
		return $done[$hash] = false;
	}

	list($primary, $l) = $lien;
	$colone_role = $roles['colonne'];

	$all = sql_allfetsel(
		"DISTINCT $colone_role",
		$l,
		'objet=' . sql_quote(($objet_source == $objet_lien) ? $objet : $objet_source)
	);
	$done[$hash] = array_map('reset', $all);

	return $done[$hash];
}
