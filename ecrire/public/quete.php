<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

//
// Fonctions d'appel aux serveurs SQL presentes dans le code compile
//

# NB : a l'exception des fonctions pour les balises dynamiques

include_spip('base/abstract_sql');

/**
 * retourne l'url de redirection d'un article virtuel, seulement si il est publié
 * http://doc.spip.org/@quete_chapo
 *
 * @param $id_article
 * @param $connect
 * @return array|bool|null
 */
function quete_virtuel($id_article, $connect) {
	return sql_getfetsel('virtuel', 'spip_articles', array("id_article=".intval($id_article), "statut='publie'"), '','','','',$connect);
}

/**
 * Retourne le couple parent,lang pour toute table
 * en pratique id_rubrique si present (ou id_parent pour table rubriques)
 * et champ lang si present
 *
 * @param string $table
 * @param int $id
 * @param string $connect
 * @return array
 */
function quete_parent_lang($table,$id,$connect=''){
	static $cache_quete = array();

	if (!isset($cache_quete[$connect][$table][$id])) {
		if (!isset($cache_quete[$connect][$table]['_select'])){
			$trouver_table = charger_fonction('trouver_table','base');
			if (!$desc=$trouver_table($table,$connect) OR !isset($desc['field']['id_rubrique']))
				// pas de parent rubrique, on passe
				$cache_quete[$connect][$table]['_select'] = false; 
			else {
				$select = ($table=='spip_rubriques'?'id_parent':'id_rubrique');
				$select .= isset($desc['field']['lang'])?", lang":"";
				$cache_quete[$connect][$table]['_select'] = $select;
				$cache_quete[$connect][$table]['_id'] = id_table_objet(objet_type($table));
			}
		}
		if ($cache_quete[$connect][$table]['_select'])
			$cache_quete[$connect][$table][$id] = sql_fetsel($cache_quete[$connect][$table]['_select'], $table,$cache_quete[$connect][$table]['_id']."=".intval($id),'','','','',$connect);
	}
	return isset($cache_quete[$connect][$table][$id]) ? $cache_quete[$connect][$table][$id] : null;
}


/**
 * retourne le parent d'une rubrique
 * repose sur la fonction quete_parent_lang pour la mutualisation
 * +mise en cache sql des requetes
 *
 * http://doc.spip.org/@quete_parent
 *
 * @param int $id_rubrique
 * @param string $connect
 * @return int
 */
function quete_parent($id_rubrique, $connect='') {
	if (!$id_rubrique = intval($id_rubrique))
		return 0;
	$id_parent = quete_parent_lang('spip_rubriques',$id_rubrique,$connect);
	return $id_parent['id_parent'];
}

/**
 * retourne la rubrique d'un article
 * repose sur la fonction quete_parent_lang pour la mutualisation
 * +mise en cache sql des requetes
 *
 * http://doc.spip.org/@quete_rubrique
 *
 * @param int $id_article
 * @param $serveur
 * @return
 */
function quete_rubrique($id_article, $serveur) {
	$id_parent = quete_parent_lang('spip_articles',$id_article,$serveur);
	return $id_parent['id_rubrique'];
}


/**
 * retourne la profondeur d'une rubrique
 *
 * http://doc.spip.org/@quete_profondeur
 *
 * @param int $id
 * @param string $connect
 * @return int
 */
function quete_profondeur($id, $connect='') {
	$n = 0;
	while ($id) {
		$n++;
		$id = quete_parent($id, $connect);
	}
	return $n;
}


/**
 * retourne la condition sur la date lorsqu'il y a des post-dates
 * @param string $champ_date
 * @param string $serveur
 * @return string
 */
function quete_condition_postdates($champ_date, $serveur='', $ignore_previsu=false) {
	if (defined('_VAR_PREVIEW') AND _VAR_PREVIEW AND !$ignore_previsu)
		return "1=1";
	return
	  (isset($GLOBALS['meta']['date_prochain_postdate'])
	     AND $GLOBALS['meta']['date_prochain_postdate'] > time())
			? "$champ_date<".sql_quote(date('Y-m-d H:i:s',$GLOBALS['meta']['date_prochain_postdate']),$serveur)
	    : "1=1" ;
}


/**
 * Calculer la condition pour filtrer les status,
 *
 * @param string $mstatut
 *  le champ de la table sur lequel porte la condition
 * @param string $previsu
 *  mode previsu : statut ou liste des statuts separes par une virgule
 * @param string $publie
 *  mode publie : statut ou liste des statuts separes par une virgule
 * @param string $serveur
 *  serveur de BDD
 * @return array
 */
function quete_condition_statut($mstatut,$previsu,$publie, $serveur='', $ignore_previsu=false){
	static $cond = array();
	$key = func_get_args();
	$key = implode("-",$key);
	if (isset($cond[$key])) return $cond[$key];

	$liste = $publie;
	if (defined('_VAR_PREVIEW') AND _VAR_PREVIEW AND !$ignore_previsu)
		$liste = $previsu;
	$not = false;
	if (strncmp($liste,'!',1)==0){
		$not = true;
	  $liste = substr($liste,1);
	}
	// '' => ne rien afficher, '!'=> ne rien filtrer
	if (!strlen($liste))
		return $cond[$key]=($not?"1=1":"'0=1'");

	$liste = explode(',',$liste);
	$where = array();
	foreach($liste as $k=>$v) {
		// filtrage /auteur pour limiter les objets d'un statut (prepa en general)
		// a ceux de l'auteur identifie
		if (strpos($v,"/")!==false){
			$v = explode("/",$v);
			$filtre = end($v);
			$v = reset($v);
			$v = preg_replace(",\W,","",$v);
			if ($filtre=="auteur"
				AND isset($GLOBALS['visiteur_session']['id_auteur'])
				AND intval($GLOBALS['visiteur_session']['id_auteur'])
				AND (strpos($mstatut,".")!==false)
			  AND $objet = explode(".",$mstatut)
				AND $id_table = reset($objet)
			  AND $objet = objet_type($id_table)){
				$primary = id_table_objet($objet);
				$where[] = "($mstatut<>".sql_quote($v)." OR $id_table.$primary IN (".sql_get_select("ssss.id_objet","spip_auteurs_liens AS ssss","ssss.objet=".sql_quote($objet)." AND ssss.id_auteur=".intval($GLOBALS['visiteur_session']['id_auteur']),'','','','',$serveur)."))";
			}
			// ignorer ce statut si on ne sait pas comment le filtrer
			else
				$v = "";
		}
		// securite
		$liste[$k] = preg_replace(",\W,","",$v);
	}
	$liste = array_filter($liste);
  if (count($liste)==1){
		$where[] = array('=', $mstatut, sql_quote(reset($liste),$serveur));
  }
  else {
	  $where[] = sql_in($mstatut,$liste,$not,$serveur);
  }

	while (count($where)>1){
		$and = array('AND',array_pop($where),array_pop($where));
		$where[] = $and;
	}
	$cond[$key] = reset($where);
	if ($not)
		$cond[$key] = array('NOT',$cond[$key]);

	return $cond[$key];
}

/**
 * retourne le fichier d'un document
 *
 * http://doc.spip.org/@quete_fichier
 *
 * @param int $id_document
 * @param string $serveur
 * @return array|bool|null
 */
function quete_fichier($id_document, $serveur='') {
	return sql_getfetsel('fichier', 'spip_documents', ("id_document=" . intval($id_document)),	'',array(), '', '', $serveur);
}

/**
 * Toute les infos sur un document
 *
 * @param int $id_document
 * @param string $serveur
 * @return array|bool
 */
function quete_document($id_document, $serveur='') {
	return sql_fetsel('*', 'spip_documents', ("id_document=" . intval($id_document)),	'',array(), '', '', $serveur);
}

/**
 * recuperer une meta sur un site distant (en local il y a plus simple)
 *
 * http://doc.spip.org/@quete_meta
 *
 * @param $nom
 * @param $serveur
 * @return array|bool|null
 */
function quete_meta($nom, $serveur) {
	return sql_getfetsel("valeur", "spip_meta", "nom=" . sql_quote($nom),
			     '','','','',$serveur);
}

/**
 * Retourne le logo d'un objet, eventuellement par heritage
 * Si flag <> false, retourne le chemin du fichier
 * sinon retourne un tableau de 3 elements:
 * le chemin du fichier, celui du logo de survol, l'attribut style=w/h
 *
 * @param string $type
 * @param string $onoff
 * @param int $id
 * @param int $id_rubrique
 * @param bool $flag
 * @return array|string
 */
function quete_logo($type, $onoff, $id, $id_rubrique, $flag) {
	static $chercher_logo;
	if (is_null($chercher_logo))
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
        else if (defined('_LOGO_RUBRIQUE_DESACTIVER_HERITAGE'))
            return '';
		else if ($id_rubrique) {
			$type = 'id_rubrique';
			$id = $id_rubrique;
			$id_rubrique = 0;
		} else if ($id AND $type == 'id_rubrique')
			$id = quete_parent($id);
		else return '';
	}
}

/**
 * fonction appelee par la balise #LOGO_DOCUMENT
 *
 * http://doc.spip.org/@calcule_logo_document
 *
 * @param array $row
 * @param string $connect
 * @return bool|string
 */
function quete_logo_file($row, $connect=NULL) {
	include_spip('inc/documents');
	$logo = vignette_logo_document($row, $connect);
	if (!$logo) $logo = image_du_document($row);
	if (!$logo){
		$f = charger_fonction('vignette','inc');
		$logo = $f($row['extension'], false);
	}
	// si c'est une vignette type doc, la renvoyer direct
	if (strcmp($logo,_DIR_PLUGINS)==0
		OR strcmp($logo,_DIR_PLUGINS_DIST)==0
		OR strcmp($logo,_DIR_RACINE.'prive/')==0)
		return $logo;
	return get_spip_doc($logo);
}

/**
 * Trouver l'image logo d'un document
 *
 * @param  $row
 *   description du document, issue de la base
 * @param  $lien
 *   url de lien
 * @param  $align
 *   alignement left/right
 * @param  $mode_logo
 *   mode du logo :
 *     '' => automatique (vignette sinon apercu sinon icone)
 *     icone => icone du type du fichier
 *     apercu => apercu de l'image exclusivement, meme si une vignette existe
 *     vignette => vignette exclusivement, ou rien si elle n'existe pas
 * @param  $x
 *   largeur maxi
 * @param  $y
 *   hauteur maxi
 * @param string $connect
 *   serveur
 * @return string
 */
function quete_logo_document($row, $lien, $align, $mode_logo, $x, $y, $connect=NULL) {
	include_spip('inc/documents');
	$logo = '';
	if (!in_array($mode_logo,array('icone','apercu')))
		$logo = vignette_logo_document($row, $connect);
	// si on veut explicitement la vignette, ne rien renvoyer si il n'y en a pas
	if ($mode_logo == 'vignette' AND !$logo)
		return '';
	if ($mode_logo == 'icone')
		$row['fichier'] = '';
	return vignette_automatique($logo, $row, $lien, $x, $y, $align);
}

/**
 * Retourne la vignette explicitement attachee a un document
 * le resutat est un fichier local existant, ou une URL
 * ou vide si pas de vignette
 *
 * @param array $row
 * @param string $connect
 * @return string
 */
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

/**
 * Calcul pour savoir si un objet est expose dans le contexte
 * fournit par $reference
 * 
 * http://doc.spip.org/@calcul_exposer
 *
 * @param int $id
 * @param string $prim
 * @param array $reference
 * @param int $parent
 * @param string $type
 * @param string $connect
 * @return bool|string
 */
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
						$parent = sql_getfetsel('id_groupe','spip_mots',"id_mot=" . intval($principal), '','','','',$connect);
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

/**
 * Trouver le numero de page d'une pagination indirecte
 * lorsque debut_xxx=@123
 * on cherche la page qui contient l'item dont la cle primaire vaut 123
 *
 * @param string $primary
 * @param int|string $valeur
 * @param int $pas
 * @param objetc $iter
 * @return int
 */
function quete_debut_pagination($primary,$valeur,$pas,$iter){
	// on ne devrait pas arriver ici si la cle primaire est inexistante
	// ou composee, mais verifions
	if (!$primary OR preg_match('/[,\s]/',$primary))
		return 0;

	$pos = 0;
	while ($row = $iter->fetch() AND $row[$primary]!=$valeur){
		$pos++;
	}
	// si on a pas trouve
	if ($row[$primary]!=$valeur)
		return 0;

	// sinon, calculer le bon numero de page
	return floor($pos/$pas)*$pas;
}
?>
