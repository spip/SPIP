<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

//
// Fonctions d'appel aux serveurs SQL presentes dans le code compile
//

# NB : a l'exception des fonctions pour les balises dynamiques

include_spip('base/abstract_sql');

# retourne le chapeau d'un article, et seulement s'il est publie

// http://doc.spip.org/@quete_chapo
function quete_chapo($id_article, $connect) {
	return sql_getfetsel('chapo', 'spip_articles', array("id_article=".intval($id_article), "statut='publie'"), '','','','',$connect);
}

function quete_parent_lang($table,$id,$connect=''){
	static $cache_quete = array();
	
	if (!isset($cache_quete[$connect][$table][$id])
	AND in_array($table,array('spip_rubriques','spip_articles','spip_syndic','spip_breves'))){
		$select = ($table=='spip_rubriques'?'id_parent':'id_rubrique');
		$select .= in_array($table,array('spip_rubriques','spip_articles','spip_breves'))?", lang":"";
		$_id = id_table_objet(objet_type($table));
		$cache_quete[$connect][$table][$id] = sql_fetsel($select, $table,"$_id=".intval($id),'','','','',$connect);
	}
	return $cache_quete[$connect][$table][$id];
}


# retourne le parent d'une rubrique
// http://doc.spip.org/@quete_parent
function quete_parent($id_rubrique, $connect='') {
	if (!$id_rubrique = intval($id_rubrique))
		return 0;
	$id_parent = quete_parent_lang('spip_rubriques',$id_rubrique,$connect);
	return $id_parent['id_parent'];
}

# retourne la rubrique d'un article

// http://doc.spip.org/@quete_rubrique
function quete_rubrique($id_article, $serveur) {
	$id_parent = quete_parent_lang('spip_articles',$id_article,$serveur);
	return $id_parent['id_rubrique'];
}


# retourne la profondeur d'une rubrique

// http://doc.spip.org/@quete_profondeur
function quete_profondeur($id, $connect='') {
	$n = 0;
	while ($id) {
		$n++;
		$id = quete_parent($id, $connect);
	}
	return $n;
}


# retourne la date a laquelle comparer lorsqu'il y a des post-dates
// http://doc.spip.org/@quete_date_postdates
function quete_date_postdates() {
	return
		($GLOBALS['meta']['date_prochain_postdate'] > time())
			? date('Y-m-d H:i:s', $GLOBALS['meta']['date_prochain_postdate'])
			: '9999-12-31';
}


# retourne le fichier d'un document

// http://doc.spip.org/@quete_fichier
function quete_fichier($id_document, $serveur='') {
	return sql_getfetsel('fichier', 'spip_documents', ("id_document=" . intval($id_document)),	'',array(), '', '', $serveur);
}

# Toute les infos sur un document

function quete_document($id_document, $serveur='') {
	return sql_fetsel('*', 'spip_documents', ("id_document=" . intval($id_document)),	'',array(), '', '', $serveur);
}

// http://doc.spip.org/@quete_petitions
function quete_petitions($id_article, $table, $id_boucle, $serveur, &$cache) {
	$retour = sql_getfetsel('texte', 'spip_petitions',("id_article=".intval($id_article)),'',array(),'','', $serveur);

	if ($retour === NULL) return '';
	# cette page est invalidee par toute petition
	$cache['varia']['pet'.$id_article] = 1;
	# ne pas retourner '' car le texte sert aussi de presence
	return $retour ? $retour : ' ';
}

// recuperer une meta sur un site distant (en local il y a plus simple)
// http://doc.spip.org/@quete_meta
function quete_meta($nom, $serveur) {
	return sql_getfetsel("valeur", "spip_meta", "nom=" . sql_quote($nom),
			     '','','','',$serveur);
}

//
// Retourne le logo d'un objet, eventuellement par heritage
// Si flag <> false, retourne le chemin du fichier
// sinon retourne un tableau de 3 elements:
// le chemin du fichier, celui du logo de survol, l'attribut style=w/h

function quete_logo($type, $onoff, $id, $id_rubrique, $flag) {
	$chercher_logo = charger_fonction('chercher_logo', 'inc');
	$nom = strtolower($onoff);

	while (1) {
		$on = $chercher_logo($id, $type, $nom);
		if ($on) {
			if ($flag)
				return "$on[2].$on[3]";
			else {
				$taille = @getimagesize($on[0]);
				$off = ($onoff != 'ON') ? '' :
					$chercher_logo($id, $type, 'off');
				// on retourne une url du type IMG/artonXX?timestamp
				// qui permet de distinguer le changement de logo
				// et placer un expire sur le dossier IMG/
				return array ($on[0].($on[4]?"?$on[4]":""),
					($off ? $off[0] . ($off[4]?"?$off[4]":"") : ''),
					(!$taille ? '' : (" ".$taille[3])));
			}
		}
		else if ($id_rubrique) {
			$type = 'id_rubrique';
			$id = $id_rubrique;
			$id_rubrique = 0;
		} else if ($id AND $type == 'id_rubrique')
			$id = quete_parent($id);
		else return '';
	}
}

// fonction appelee par la balise #LOGO_DOCUMENT
// http://doc.spip.org/@calcule_logo_document
function quete_logo_file($row, $connect=NULL) {
	include_spip('inc/documents');
	$logo = vignette_logo_document($row, $connect);
	if (!$logo) $logo = image_du_document($row);
	if (!$logo) $logo = vignette_par_defaut($row['extension'], false);
	return get_spip_doc($logo);
}

function quete_logo_document($row, $lien, $align, $x, $y, $connect=NULL) {
	include_spip('inc/documents');
	$logo = vignette_logo_document($row, $connect);
	return vignette_automatique($logo, $row, $lien, $x, $y, $align);
}

// Retourne la vignette explicitement attachee a un document
// le resutat est un fichier local existant, ou une URL
function vignette_logo_document($row, $connect='')
{
	if (!$row['id_vignette']) return '';
	$fichier = quete_fichier($row['id_vignette'], $connect);
	if ($connect) {
		$site = quete_meta('adresse_site', $connect);
		$dir = quete_meta('dir_img', $connect);
		return "$site/$dir$fichier";
	}
	$f = get_spip_doc($fichier);
	if ($f AND @file_exists($f)) return $f;
	if ($row['mode'] !== 'vignette') return '';
	return generer_url_entite($row['id_document'], 'document','','', $connect);
}

// http://doc.spip.org/@calcul_exposer
function calcul_exposer ($id, $prim, $reference, $parent, $type, $connect='') {
	static $exposer = array();

	// Que faut-il exposer ? Tous les elements de $reference
	// ainsi que leur hierarchie ; on ne fait donc ce calcul
	// qu'une fois (par squelette) et on conserve le resultat
	// en static.
	if (!isset($exposer[$m=md5(serialize($reference))][$prim])) {
		$principal = isset($reference[$type])?$reference[$type]:
			// cas de la pagination indecte @xx qui positionne la page avec l'id xx
			// et donne la reference dynamique @type=xx dans le contexte
			(isset($reference["@$type"])?$reference["@$type"]:'');
		// le parent fournit en argument est le parent de $id, pas celui de $principal
		// il n'est donc pas utile
		$parent = 0;
		if (!$principal) { // regarder si un enfant est dans le contexte, auquel cas il expose peut etre le parent courant
			$enfants = array('id_rubrique'=>array('id_article'),'id_groupe'=>array('id_mot'));
			if (isset($enfants[$type]))
				foreach($enfants[$type] as $t)
					if (isset($reference[$t])
						// cas de la reference donnee dynamiquement par la pagination
						OR isset($reference["@$t"])) {
						$type = $t;
						$principal = isset($reference[$type])?$reference[$type]:$reference["@$type"];
						continue;
					}
		}
		$exposer[$m][$type] = array();
		if ($principal) {
			$principaux = is_array($principal)?$principal:array($principal);
			foreach($principaux as $principal){
				$exposer[$m][$type][$principal] = true;
				if ($type == 'id_mot'){
					if (!$parent) {
						$parent = sql_getfetsel('id_groupe','spip_mots',"id_mot=" . $principal, '','','','',$connect);
					}
					if ($parent)
						$exposer[$m]['id_groupe'][$parent] = true;
				}
				else if ($type != 'id_groupe') {
				  if (!$parent) {
				  	if ($type == 'id_rubrique')
				  		$parent = $principal;
				  	if ($type == 'id_article') {
						$parent = quete_rubrique($principal,$connect);
				  	}
				  }
				  do { $exposer[$m]['id_rubrique'][$parent] = true; }
				  while ($parent = quete_parent($parent, $connect));
				}
			}
		}
	}
	// And the winner is...
	return isset($exposer[$m][$prim]) ? isset($exposer[$m][$prim][$id]) : '';
}

function quete_debut_pagination($primary,$valeur,$pas,$res,$serveur=''){
	$pos = 0;
	while ($row = sql_fetch($res,$serveur) AND $row[$primary]!=$valeur){
		$pos++;
	}
	// remettre le pointeur au debut des resultats
	sql_seek($res,0,$serveur);
	// si on a pas trouve
	if ($row[$primary]!=$valeur)
		return 0;
	
	// sinon, calculer le bon numero de page
	return floor($pos/$pas)*$pas;
}

?>