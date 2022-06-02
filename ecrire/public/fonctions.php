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
 * Des fonctions diverses utilisees lors du calcul d'une page ; ces fonctions
 * bien pratiques n'ont guere de logique organisationnelle ; elles sont
 * appelees par certaines balises ou criteres au moment du calcul des pages. (Peut-on
 * trouver un modele de donnees qui les associe physiquement au fichier
 * definissant leur balise ???)
 *
 * Ce ne sont pas des filtres à part entière, il n'est donc pas logique de les retrouver dans inc/filtres
 *
 * @package SPIP\Core\Compilateur\Composer
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Calcul d'une introduction
 *
 * L'introduction est prise dans le descriptif s'il est renseigné,
 * sinon elle est calculée depuis le texte : à ce moment là,
 * l'introduction est prise dans le contenu entre les balises
 * `<intro>` et `</intro>` si présentes, sinon en coupant le
 * texte à la taille indiquée.
 *
 * Cette fonction est utilisée par la balise #INTRODUCTION
 *
 * @param string $descriptif
 *     Descriptif de l'introduction
 * @param string $texte
 *     Texte à utiliser en absence de descriptif
 * @param string $longueur
 *     Longueur de l'introduction
 * @param string $connect
 *     Nom du connecteur à la base de données
 * @param string $suite
 *     points de suite si on coupe (par defaut _INTRODUCTION_SUITE et sinon &nbsp;(...)
 * @return string
 *     Introduction calculée
 **/
function filtre_introduction_dist($descriptif, $texte, $longueur, $connect, $suite = null) {
	// Si un descriptif est envoye, on l'utilise directement
	if (strlen($descriptif)) {
		return appliquer_traitement_champ($descriptif, 'introduction', '', [], $connect);
	}

	// De preference ce qui est marque <intro>...</intro>
	$intro = '';
	$texte = preg_replace(',(</?)intro>,i', "\\1intro>", $texte); // minuscules
	while ($fin = strpos($texte, '</intro>')) {
		$zone = substr($texte, 0, $fin);
		$texte = substr($texte, $fin + strlen('</intro>'));
		if ($deb = strpos($zone, '<intro>') or substr($zone, 0, 7) == '<intro>') {
			$zone = substr($zone, $deb + 7);
		}
		$intro .= $zone;
	}

	// [12025] On ne *PEUT* pas couper simplement ici car c'est du texte brut,
	// qui inclus raccourcis et modeles
	// un simple <articlexx> peut etre ensuite transforme en 1000 lignes ...
	// par ailleurs le nettoyage des raccourcis ne tient pas compte
	// des surcharges et enrichissement de propre
	// couper doit se faire apres propre
	//$texte = nettoyer_raccourcis_typo($intro ? $intro : $texte, $connect);

	// Cependant pour des questions de perfs on coupe quand meme, en prenant
	// large et en se mefiant des tableaux #1323

	if (strlen($intro)) {
		$texte = $intro;
	} else {
		if (
			strpos("\n" . $texte, "\n|") === false
			and strlen($texte) > 2.5 * $longueur
		) {
			if (strpos($texte, '<multi') !== false) {
				$texte = extraire_multi($texte);
			}
			$texte = couper($texte, 2 * $longueur);
		}
	}

	// ne pas tenir compte des notes
	if ($notes = charger_fonction('notes', 'inc', true)) {
		$notes('', 'empiler');
	}
	// Supprimer les modèles avant le propre afin d'éviter qu'ils n'ajoutent du texte indésirable
	// dans l'introduction.
	$texte = supprime_img($texte, '');
	$texte = appliquer_traitement_champ($texte, 'introduction', '', [], $connect);

	if ($notes) {
		$notes('', 'depiler');
	}

	if (is_null($suite) and defined('_INTRODUCTION_SUITE')) {
		$suite = _INTRODUCTION_SUITE;
	}
	$texte = couper($texte, $longueur, $suite);
	// comme on a coupe il faut repasser la typo (on a perdu les insecables)
	$texte = typo($texte, true, $connect, []);

	// et reparagrapher si necessaire (coherence avec le cas descriptif)
	// une introduction a tojours un <p>
	if ($GLOBALS['toujours_paragrapher']) { // Fermer les paragraphes
	$texte = paragrapher($texte, $GLOBALS['toujours_paragrapher']);
	}

	return $texte;
}


/**
 * Filtre calculant une pagination, utilisé par la balise `#PAGINATION`
 *
 * Le filtre cherche le modèle `pagination.html` par défaut, mais peut
 * chercher un modèle de pagination particulier avec l'argument `$modele`.
 * S'il `$modele='prive'`, le filtre cherchera le modèle `pagination_prive.html`.
 *
 * @filtre
 * @see balise_PAGINATION_dist()
 *
 * @param int $total
 *     Nombre total d'éléments
 * @param string $nom
 *     Nom identifiant la pagination
 * @param int $position
 *     Page à afficher (tel que la 3è page)
 * @param int $pas
 *     Nombre d'éléments par page
 * @param bool $liste
 *     - True pour afficher toute la liste des éléments,
 *     - False pour n'afficher que l'ancre
 * @param string $modele
 *     Nom spécifique du modèle de pagination
 * @param string $connect
 *     Nom du connecteur à la base de données
 * @param array $env
 *     Environnement à transmettre au modèle
 * @return string
 *     Code HTML de la pagination
 **/
function filtre_pagination_dist(
	$total,
	$nom,
	$position,
	$pas,
	$liste = true,
	$modele = '',
	string $connect = '',
	$env = []
) {
	static $ancres = [];
	if ($pas < 1) {
		return '';
	}
	$ancre = 'pagination' . $nom; // #pagination_articles
	$debut = 'debut' . $nom; // 'debut_articles'

	// n'afficher l'ancre qu'une fois
	if (!isset($ancres[$ancre])) {
		$bloc_ancre = $ancres[$ancre] = "<a id='" . $ancre . "' class='pagination_ancre'></a>";
	} else {
		$bloc_ancre = '';
	}
	// liste = false : on ne veut que l'ancre
	if (!$liste) {
		return $ancres[$ancre];
	}

	$self = (empty($env['self']) ? self() : $env['self']);
	$pagination = [
		'debut' => $debut,
		'url' => parametre_url($self, 'fragment', ''), // nettoyer l'id ahah eventuel
		'total' => $total,
		'position' => intval($position),
		'pas' => $pas,
		'nombre_pages' => floor(($total - 1) / $pas) + 1,
		'page_courante' => floor(intval($position) / $pas) + 1,
		'ancre' => $ancre,
		'bloc_ancre' => $bloc_ancre
	];
	if (is_array($env)) {
		$pagination = array_merge($env, $pagination);
	}

	// Pas de pagination
	if ($pagination['nombre_pages'] <= 1) {
		return '';
	}

	if ($modele) {
		$pagination['type_pagination'] = $modele;
		if (trouver_fond('pagination_' . $modele, 'modeles')) {
			$modele = '_' . $modele;
		}
		else {
			$modele = '';
		}
	}

	if (!defined('_PAGINATION_NOMBRE_LIENS_MAX')) {
		define('_PAGINATION_NOMBRE_LIENS_MAX', 10);
	}
	if (!defined('_PAGINATION_NOMBRE_LIENS_MAX_ECRIRE')) {
		define('_PAGINATION_NOMBRE_LIENS_MAX_ECRIRE', 5);
	}


	return recuperer_fond("modeles/pagination$modele", $pagination, ['trim' => true], $connect);
}


/**
 * Calcule les bornes d'une pagination
 *
 * @filtre
 *
 * @param int $courante
 *     Page courante
 * @param int $nombre
 *     Nombre de pages
 * @param int $max
 *     Nombre d'éléments par page
 * @return int[]
 *     Liste (première page, dernière page).
 **/
function filtre_bornes_pagination_dist($courante, $nombre, $max = 10) {
	if ($max <= 0 or $max >= $nombre) {
		return [1, $nombre];
	}
	if ($max <= 1) {
		return [$courante, $courante];
	}

	$premiere = max(1, $courante - floor(($max - 1) / 2));
	$derniere = min($nombre, $premiere + $max - 2);
	$premiere = $derniere == $nombre ? $derniere - $max + 1 : $premiere;

	return [$premiere, $derniere];
}

function filtre_pagination_affiche_texte_lien_page_dist($type_pagination, $numero_page, $rang_item) {
	if ($numero_page === 'tous') {
		return '&#8734;';
	}
	if ($numero_page === 'prev') {
		return '&lt;';
	}
	if ($numero_page === 'next') {
		return '&gt;';
	}

	switch ($type_pagination) {
		case 'resultats':
			return $rang_item + 1; // 1 11 21 31...
		case 'naturel':
			return $rang_item ?: 1; // 1 10 20 30...
		case 'rang':
			return $rang_item; // 0 10 20 30...

		case 'page':
		case 'prive':
		default:
			return $numero_page; // 1 2 3 4 5...
	}
}

/**
 * Retourne pour une clé primaire d'objet donnée les identifiants ayant un logo
 *
 * @param string $type
 *     Nom de la clé primaire de l'objet
 * @return string
 *     Liste des identifiants ayant un logo (séparés par une virgule)
 **/
function lister_objets_avec_logos($type) {

	$objet = objet_type($type);
	$ids = sql_allfetsel('L.id_objet', 'spip_documents AS D JOIN spip_documents_liens AS L ON L.id_document=D.id_document', 'D.mode=' . sql_quote('logoon') . ' AND L.objet=' . sql_quote($objet));
	if ($ids) {
		$ids = array_column($ids, 'id_objet');
		return implode(',', $ids);
	}
	else {
		return '0';
	}
}


/**
 * Renvoie l'état courant des notes, le purge et en prépare un nouveau
 *
 * Fonction appelée par la balise `#NOTES`
 *
 * @see  balise_NOTES_dist()
 * @uses inc_notes_dist()
 *
 * @return string
 *     Code HTML des notes
 **/
function calculer_notes() {
	$r = '';
	if ($notes = charger_fonction('notes', 'inc', true)) {
		$r = $notes([]);
		$notes('', 'depiler');
		$notes('', 'empiler');
	}

	return $r;
}


/**
 * Retrouver le rang du lien entre un objet source et un obet lie
 * utilisable en direct dans un formulaire d'edition des liens, mais #RANG doit faire le travail automatiquement
 * [(#ENV{objet_source}|rang_lien{#ID_AUTEUR,#ENV{objet},#ENV{id_objet},#ENV{_objet_lien}})]
 *
 * @param $objet_source
 * @param $ids
 * @param $objet_lie
 * @param $idl
 * @param $objet_lien
 * @return string
 */
function retrouver_rang_lien($objet_source, $ids, $objet_lie, $idl, $objet_lien) {
	$res = lister_objets_liens($objet_source, $objet_lie, $idl, $objet_lien);
	$res = array_column($res, 'rang_lien', $objet_source);

	return ($res[$ids] ?? '');
}


/**
 * Lister les liens en le memoizant dans une static
 * pour utilisation commune par lister_objets_lies et retrouver_rang_lien dans un formuluaire d'edition de liens
 * (evite de multiplier les requetes)
 *
 * @param $objet_source
 * @param $objet
 * @param $id_objet
 * @param $objet_lien
 * @return mixed
 * @private
 */
function lister_objets_liens($objet_source, $objet, $id_objet, $objet_lien) {
	static $liens = [];
	if (!isset($liens["$objet_source-$objet-$id_objet-$objet_lien"])) {
		include_spip('action/editer_liens');
		// quand $objet == $objet_lien == $objet_source on reste sur le cas par defaut de $objet_lien == $objet_source
		if ($objet_lien == $objet and $objet_lien !== $objet_source) {
			$res = objet_trouver_liens([$objet => $id_objet], [$objet_source => '*']);
		} else {
			$res = objet_trouver_liens([$objet_source => '*'], [$objet => $id_objet]);
		}

		$liens["$objet_source-$objet-$id_objet-$objet_lien"] = $res;
	}
	return $liens["$objet_source-$objet-$id_objet-$objet_lien"];
}

/**
 * Calculer la balise #RANG
 * quand ce n'est pas un champ rang :
 * peut etre le num titre, le champ rang_lien ou le rang du lien en edition des liens, a retrouver avec les infos du formulaire
 * @param $titre
 * @param $objet_source
 * @param $id
 * @param $env
 * @return int|string
 */
function calculer_rang_smart($titre, $objet_source, $id, $env) {
	// Cas du #RANG utilisé dans #FORMULAIRE_EDITER_LIENS -> attraper le rang du lien
	// permet de voir le rang du lien si il y en a un en base, meme avant un squelette xxxx-lies.html ne gerant pas les liens
	if (
		isset($env['form']) and $env['form']
		and isset($env['_objet_lien']) and $env['_objet_lien']
		and (function_exists('lien_triables') or include_spip('action/editer_liens'))
		and $r = objet_associable($env['_objet_lien'])
		and [$p, $table_lien] = $r
		and lien_triables($table_lien)
		and isset($env['objet']) and $env['objet']
		and isset($env['id_objet']) and $env['id_objet']
		and $objet_source
		and $id = intval($id)
	) {
		$rang = retrouver_rang_lien($objet_source, $id, $env['objet'], $env['id_objet'], $env['_objet_lien']);
		return ($rang ?: '');
	}
	return recuperer_numero($titre);
}

/**
 * Calcul de la balise #TRI
 *
 * @param string $champ_ou_sens
 *     - soit le nom de champ sur lequel effectuer le nouveau tri
 *     - soit `<` et `>` pour définir le sens du tri sur le champ actuel
 * @param string $libelle
 *     Texte du lien
 * @param string $classe
 *     Classe ajoutée au lien, telle que `ajax`
 * @param string $tri_nom
 *     Nom du paramètre définissant le tri
 * @param string $tri_champ
 *     Nom du champ actuel utilisé pour le tri
 * @param string $tri_sens
 *     Sens de tri actuel, 1 ou -1
 * @param array|string|int $liste_tri_sens_defaut
 *     Soit la liste des sens de tri par défaut pour chaque champ
 *     Soit une valeur par défaut pour tous les champs (1, -1, inverse)
 * @return string
 *     HTML avec un lien cliquable
 */
function calculer_balise_tri(string $champ_ou_sens, string $libelle, string $classe, string $tri_nom, string $tri_champ, string $tri_sens, $liste_tri_sens_defaut): string {

	$url = self('&');
	$tri_sens = (int) $tri_sens;
	$tri_sens_fallback = 1;
	$alias_sens = [
		'<' => -1,
		'>' => 1,
		'inverse' => -1,
	];

	// Normaliser la liste des sens de tri par défaut
	// On ajoute un jocker pour les champs non présents dans la liste
	if (is_array($liste_tri_sens_defaut)) {
		$liste_tri_sens_defaut['*'] = $tri_sens_fallback;
	} else {
		$liste_tri_sens_defaut = [
			'*' => (int) ($alias_sens[$liste_tri_sens_defaut] ?? $liste_tri_sens_defaut),
		];
	}

	// Le nouveau sens de tri :
	// Soit c'est un sens fixe donné en paramètre (< ou >)
	$is_sens_fixe = array_key_exists($champ_ou_sens, $alias_sens);
	if ($is_sens_fixe) {
		$tri_sens_nouveau = $alias_sens[$champ_ou_sens];
	// Soit c'est le champ utilisé actuellement pour le tri → on inverse le sens
	} elseif ($champ_ou_sens === $tri_champ) {
		$tri_sens_nouveau = $tri_sens * -1;
	// Sinon c'est un nouveau champ, et on prend son tri par défaut
	} else {
		$tri_sens_nouveau = (int) ($liste_tri_sens_defaut[$champ_ou_sens] ?? $liste_tri_sens_defaut['*']);
	}

	// URL : ajouter le champ sur lequel porte le tri
	if (!$is_sens_fixe) {
		$param_tri = "tri$tri_nom";
		$url = parametre_url($url, $param_tri, $champ_ou_sens);
	}

	// URL : n'ajouter le sens de tri que si nécessaire,
	// c.à.d différent du sens par défaut pour le champ
	$param_sens = "sens$tri_nom";
	$tri_sens_defaut_champ = (int) ($liste_tri_sens_defaut[$champ_ou_sens] ?? $liste_tri_sens_defaut['*']);
	if ($tri_sens_nouveau !== $tri_sens_defaut_champ) {
		$url = parametre_url($url, $param_sens, $tri_sens_nouveau);
	} else {
		$url = parametre_url($url, $param_sens, '');
	}

	// Drapeau pour garder en session ?
	$param_memo = (!$is_sens_fixe ? $param_tri : $param_sens);
	$url = parametre_url($url, 'var_memotri', strncmp($tri_nom, 'session', 7) == 0 ? $param_memo : '');

	// Classe icône sur le champ de tri actuel
	$classe .= ' item-tri';
	if ($champ_ou_sens === $tri_champ) {
		$classe .= ' item-tri_' . ($tri_sens === 1 ? 'asc' : 'desc');
	}

	// Lien
	$balise = lien_ou_expose($url, $libelle, false, $classe);

	// Accessibilité
	$balise = inserer_attribut($balise, 'aria-sort', ($tri_sens === 1 ? 'ascending' : 'descending'));

	return $balise;
}


/**
 * Proteger les champs passes dans l'url et utiliser dans {tri ...}
 * preserver l'espace pour interpreter ensuite num xxx et multi xxx
 * on permet d'utiliser les noms de champ prefixes
 * articles.titre
 * et les propriete json
 * properties.gis[0].ville
 *
 * @param string $t
 * @return string
 */
function tri_protege_champ($t) {
	return preg_replace(',[^\s\w.+\[\]],', '', $t);
}

/**
 * Interpreter les multi xxx et num xxx utilise comme tri
 * pour la clause order
 * 'multi xxx' devient simplement 'multi' qui est calcule dans le select
 *
 * @param string $t
 * @param array $from
 * @return string
 */
function tri_champ_order($t, $from = null, $senstri = '') {
	if (strncmp($t, 'multi ', 6) == 0) {
		return 'multi';
	}

	$champ = $t;

	$prefixe = '';
	foreach (['num ', 'sinum '] as $p) {
		if (strpos($t, $p) === 0) {
			$champ = substr($t, strlen($p));
			$prefixe = $p;
		}
	}

	// enlever les autres espaces non evacues par tri_protege_champ
	$champ = preg_replace(',\s,', '', $champ);

	if (is_array($from)) {
		$trouver_table = charger_fonction('trouver_table', 'base');
		foreach ($from as $idt => $table_sql) {
			if (
				$desc = $trouver_table($table_sql)
				and isset($desc['field'][$champ])
			) {
				$champ = "$idt.$champ";
				break;
			}
		}
	}
	switch ($prefixe) {
		case 'num ':
			return "CASE( 0+$champ ) WHEN 0 THEN 1 ELSE 0 END{$senstri}, 0+$champ{$senstri}";
		case 'sinum ':
			return "CASE( 0+$champ ) WHEN 0 THEN 1 ELSE 0 END{$senstri}";
		default:
			return $champ . $senstri;
	}
}

/**
 * Interpreter les multi xxx et num xxx utilise comme tri
 * pour la clause select
 * 'multi xxx' devient select "...." as multi
 * les autres cas ne produisent qu'une chaine vide '' en select
 * 'hasard' devient 'rand() AS hasard' dans le select
 *
 * @param string $t
 * @return string
 */
function tri_champ_select($t) {
	if (strncmp($t, 'multi ', 6) == 0) {
		$t = substr($t, 6);
		$t = preg_replace(',\s,', '', $t);
		$t = sql_multi($t, $GLOBALS['spip_lang']);

		return $t;
	}
	if (trim($t) == 'hasard') {
		return 'rand() AS hasard';
	}

	return "''";
}

/**
 * Fonction de mise en forme utilisee par le critere {par_ordre_liste..}
 * @see critere_par_ordre_liste_dist()
 *
 * @param array $valeurs
 * @param string $serveur
 * @return string
 */
function formate_liste_critere_par_ordre_liste($valeurs, $serveur = '') {
	if (!is_array($valeurs)) {
		return '';
	}
	$f = sql_serveur('quote', $serveur, true);
	if (!is_string($f) or !$f) {
		return '';
	}
	$valeurs = implode(',', array_map($f, array_unique($valeurs)));

	return $valeurs;
}

/**
 * Applique un filtre s'il existe, sinon retourne la valeur par défaut indiquée
 *
 * @internal
 * @uses trouver_filtre_matrice()
 * @uses chercher_filtre()
 *
 * @param mixed $arg
 *     Texte (le plus souvent) sur lequel appliquer le filtre
 * @param string $filtre
 *     Nom du filtre à appliquer
 * @param array $args
 *     Arguments reçus par la fonction parente (appliquer_filtre ou appliquer_si_filtre).
 * @param mixed $defaut
 *     Valeur par défaut à retourner en cas d'absence du filtre.
 * @return string
 *     Texte traité par le filtre si le filtre existe,
 *     Valeur $defaut sinon.
 **/
function appliquer_filtre_sinon($arg, $filtre, $args, $defaut = '') {
	// Si c'est un filtre d'image, on utilise image_filtrer()
	// Attention : les 2 premiers arguments sont inversés dans ce cas
	if (trouver_filtre_matrice($filtre) and substr($filtre, 0, 6) == 'image_') {
		include_spip('inc/filtres_images_lib_mini');
		$args[1] = $args[0];
		$args[0] = $filtre;
		return image_graver(image_filtrer($args));
	}

	$f = chercher_filtre($filtre);
	if (!$f) {
		return $defaut;
	}
	array_shift($args); // enlever $arg
	array_shift($args); // enlever $filtre
	return $f($arg, ...$args);
}
