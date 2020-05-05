<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2019                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Des fonctions diverses utilisees lors du calcul d'une page ; ces fonctions
 * bien pratiques n'ont guere de logique organisationnelle ; elles sont
 * appelees par certaines balises ou criteres au moment du calcul des pages. (Peut-on
 * trouver un modele de donnees qui les associe physiquement au fichier
 * definissant leur balise ???)
 *
 * Ce ne sont pas des filtres � part enti�re, il n'est donc pas logique de les retrouver dans inc/filtres
 *
 * @package SPIP\Core\Compilateur\Composer
 **/

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Calcul d'une introduction
 *
 * L'introduction est prise dans le descriptif s'il est renseign�,
 * sinon elle est calcul�e depuis le texte : � ce moment l�,
 * l'introduction est prise dans le contenu entre les balises
 * `<intro>` et `</intro>` si pr�sentes, sinon en coupant le
 * texte � la taille indiqu�e.
 *
 * Cette fonction est utilis�e par la balise #INTRODUCTION
 *
 * @param string $descriptif
 *     Descriptif de l'introduction
 * @param string $texte
 *     Texte � utiliser en absence de descriptif
 * @param string $longueur
 *     Longueur de l'introduction
 * @param string $connect
 *     Nom du connecteur � la base de donn�es
 * @param string $suite
 *     points de suite si on coupe (par defaut _INTRODUCTION_SUITE et sinon &nbsp;(...)
 * @return string
 *     Introduction calcul�e
 **/
function filtre_introduction_dist($descriptif, $texte, $longueur, $connect, $suite = null) {
	// Si un descriptif est envoye, on l'utilise directement
	if (strlen($descriptif)) {
		return appliquer_traitement_champ($descriptif, 'introduction', '', array(), $connect);
	}

	// De preference ce qui est marque <intro>...</intro>
	$intro = '';
	$texte = preg_replace(",(</?)intro>,i", "\\1intro>", $texte); // minuscules
	while ($fin = strpos($texte, "</intro>")) {
		$zone = substr($texte, 0, $fin);
		$texte = substr($texte, $fin + strlen("</intro>"));
		if ($deb = strpos($zone, "<intro>") or substr($zone, 0, 7) == "<intro>") {
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
		if (strpos("\n" . $texte, "\n|") === false
			and strlen($texte) > 2.5 * $longueur
		) {
			if (strpos($texte, "<multi") !== false) {
				$texte = extraire_multi($texte);
			}
			$texte = couper($texte, 2 * $longueur);
		}
	}

	// ne pas tenir compte des notes
	if ($notes = charger_fonction('notes', 'inc', true)) {
		$notes('', 'empiler');
	}
	// Supprimer les mod�les avant le propre afin d'�viter qu'ils n'ajoutent du texte ind�sirable
	// dans l'introduction.
	$texte = supprime_img($texte, '');
	$texte = appliquer_traitement_champ($texte, 'introduction', '', array(), $connect);

	if ($notes) {
		$notes('', 'depiler');
	}

	if (is_null($suite) and defined('_INTRODUCTION_SUITE')) {
		$suite = _INTRODUCTION_SUITE;
	}
	$texte = couper($texte, $longueur, $suite);
	// comme on a coupe il faut repasser la typo (on a perdu les insecables)
	$texte = typo($texte, true, $connect, array());

	// et reparagrapher si necessaire (coherence avec le cas descriptif)
	// une introduction a tojours un <p>
	if ($GLOBALS['toujours_paragrapher']) // Fermer les paragraphes
	{
		$texte = paragrapher($texte, $GLOBALS['toujours_paragrapher']);
	}

	return $texte;
}


/**
 * Retourne pour une cl� primaire d'objet donn�e les identifiants ayant un logo
 *
 * @param string $type
 *     Nom de la cl� primaire de l'objet
 * @return string
 *     Liste des identifiants ayant un logo (s�par�s par une virgule)
 **/
function lister_objets_avec_logos($type) {

	$objet = objet_type($type);
	$ids = sql_allfetsel("L.id_objet", "spip_documents AS D JOIN spip_documents_liens AS L ON L.id_document=D.id_document", "D.mode=".sql_quote('logoon')." AND L.objet=".sql_quote($objet));
	if ($ids) {
		$ids = array_column($ids, 'id_objet');
		return implode(',', $ids);
	}
	else {
		return "0";
	}
}


/**
 * Renvoie l'�tat courant des notes, le purge et en pr�pare un nouveau
 *
 * Fonction appel�e par la balise `#NOTES`
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
		$r = $notes(array());
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
function retrouver_rang_lien($objet_source, $ids, $objet_lie, $idl, $objet_lien){
	$res = lister_objets_liens($objet_source, $objet_lie, $idl, $objet_lien);
	$res = array_column($res, 'rang_lien', $objet_source);

	return (isset($res[$ids]) ? $res[$ids] : '');
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
	static $liens = array();
	if (!isset($liens["$objet_source-$objet-$id_objet-$objet_lien"])) {
		include_spip('action/editer_liens');
		// quand $objet == $objet_lien == $objet_source on reste sur le cas par defaut de $objet_lien == $objet_source
		if ($objet_lien == $objet and $objet_lien !== $objet_source) {
			$res = objet_trouver_liens(array($objet => $id_objet), array($objet_source => '*'));
		} else {
			$res = objet_trouver_liens(array($objet_source => '*'), array($objet => $id_objet));
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
	// Cas du #RANG utilis� dans #FORMULAIRE_EDITER_LIENS -> attraper le rang du lien
	// permet de voir le rang du lien si il y en a un en base, meme avant un squelette xxxx-lies.html ne gerant pas les liens
	if (isset($env['form']) and $env['form']
		and isset($env['_objet_lien']) and $env['_objet_lien']
		and (function_exists('lien_triables') or include_spip('action/editer_liens'))
		and $r = objet_associable($env['_objet_lien'])
		and list($p, $table_lien) = $r
	  and lien_triables($table_lien)
	  and isset($env['objet']) and $env['objet']
		and isset($env['id_objet']) and $env['id_objet']
		and $objet_source
		and $id = intval($id)
	) {
		$rang = retrouver_rang_lien($objet_source, $id, $env['objet'], $env['id_objet'], $env['_objet_lien']);
		return ($rang ? $rang : '');
	}
	return recuperer_numero($titre);
}
