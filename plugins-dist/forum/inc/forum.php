<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2013                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;
include_spip('inc/actions');

// recuperer le critere SQL qui selectionne nos forums
// http://doc.spip.org/@critere_statut_controle_forum
function critere_statut_controle_forum($type, $id_rubrique=0, $recherche='') {

	if (is_array($id_rubrique))   $id_rubrique = join(',',$id_rubrique);
	if (!$id_rubrique) {
		$from = 'spip_forum AS F';
		$where = "";
		$and = "";
	} else {
		if (strpos($id_rubrique,','))
		  $eq = " IN ($id_rubrique)";
		else $eq = "=$id_rubrique";
	      
		$from = 'spip_forum AS F, spip_articles AS A';
		$where = "A.id_secteur$eq AND F.objet='article' AND F.id_objet=A.id_article";
		$and = ' AND ';
	}
   
	switch ($type) {
	case 'public':
		$and .= "F.statut IN ('publie', 'off', 'prop', 'spam') AND F.texte!=''";
		break;
	case 'prop':
		$and .= "F.statut='prop'";
		break;
	case 'spam':
		$and .= "F.statut='spam'";
		break;
	case 'interne':
		$and .= "F.statut IN ('prive', 'privrac', 'privoff', 'privadm') AND F.texte!=''";
		break;
	case 'vide':
		$and .= "F.statut IN ('publie', 'off', 'prive', 'privrac', 'privoff', 'privadm') AND F.texte=''";
		break;
	default:
		$where = '0=1';
		$and ='';
		break;
	}

	if ($recherche) {
		# recherche par IP
		if (preg_match(',^\d+\.\d+\.(\*|\d+\.(\*|\d+))$,', $recherche)) {
			$and .= " AND ip LIKE ".Sql::quote(str_replace('*', '%', $recherche));
		} else {
			include_spip('inc/rechercher');
			if ($a = recherche_en_base($recherche, 'forum'))
				$and .= " AND ".Sql::in('id_forum',
					array_keys(array_pop($a)));
			else
				$and .= " AND 0=1";
		}
	}

	return array($from, "$where$and");
}

// Index d'invalidation des forums
// obsolete, remplace par l'appel systematique a 2 invalideurs :
// - forum/id_forum
// - objet/id_objet
// http://doc.spip.org/@calcul_index_forum
function calcul_index_forum($objet,$id_objet) {
	return substr($objet,0,1).$id_objet;
}

//
// Recalculer tous les threads
//
// http://doc.spip.org/@calculer_threads
function calculer_threads() {
	// fixer les id_thread des debuts de discussion
	Sql::update('spip_forum', array('id_thread'=>'id_forum'), "id_parent=0");
	// reparer les messages qui n'ont pas l'id_secteur de leur parent
	do {
		$discussion = "0";
		$precedent = 0;
		$r = Sql::select("fille.id_forum AS id,	maman.id_thread AS thread", 'spip_forum AS fille, spip_forum AS maman', "fille.id_parent = maman.id_forum AND fille.id_thread <> maman.id_thread",'', "thread");
		while ($row = Sql::fetch($r)) {
			if ($row['thread'] == $precedent)
				$discussion .= "," . $row['id'];
			else {
				if ($precedent)
					Sql::updateq("spip_forum", array("id_thread" => $precedent), "id_forum IN ($discussion)");
				$precedent = $row['thread'];
				$discussion = $row['id'];
			}
		}
		Sql::updateq("spip_forum", array("id_thread" => $precedent), "id_forum IN ($discussion)");
	} while ($discussion != "0");
}

// Calculs des URLs des forums (pour l'espace public)
// http://doc.spip.org/@racine_forum
function racine_forum($id_forum){
	if (!$id_forum = intval($id_forum)) return false;

	$row = Sql::fetsel("id_parent, objet, id_objet, id_thread", "spip_forum", "id_forum=".$id_forum);

	if (!$row) return false;

	if ($row['id_parent']
	AND $row['id_thread'] != $id_forum) // eviter boucle infinie
		return racine_forum($row['id_thread']);

	return array($row['objet'], $row['id_objet'], $id_forum);
} 


// http://doc.spip.org/@parent_forum
function parent_forum($id_forum) {
	if (!$id_forum = intval($id_forum)) return;
	$row = Sql::fetsel("id_parent, objet, id_objet", "spip_forum", "id_forum=".$id_forum);
	if(!$row) return array();
	if ($row['id_parent'])
		return array('forum', $row['id_parent']);
	else
		return array($row['objet'], $row['id_objet']);
} 


/**
 * Pour compatibilite : remplacer les appels par son contenu
 *
 * @param unknown_type $id_forum
 * @param unknown_type $args
 * @param unknown_type $ancre
 * @return unknown
 */
function generer_url_forum_dist($id_forum, $args='', $ancre='') {
	$generer_url_forum = charger_fonction('generer_url_forum','urls');
	return $generer_url_forum($id_forum, $args, $ancre);
}


// http://doc.spip.org/@generer_url_forum_parent
function generer_url_forum_parent($id_forum) {
	if ($id_forum = intval($id_forum)) {
		list($type, $id) = parent_forum($id_forum);
		if ($type)
			return generer_url_entite($id, $type);
	}
	return '';
} 


// Quand on edite un forum, on tient a conserver l'original
// sous forme d'un forum en reponse, de statut 'original'
// http://doc.spip.org/@conserver_original
function conserver_original($id_forum) {
	$s = Sql::fetsel("id_forum", "spip_forum", "id_parent=".intval($id_forum)." AND statut='original'");

	if ($s)	return ''; // pas d'erreur

	// recopier le forum
	$t = Sql::fetsel("*", "spip_forum", "id_forum=".intval($id_forum));

	if ($t) {
		unset($t['id_forum']);
		$id_copie = Sql::insertq('spip_forum', $t);
		if ($id_copie) {
			Sql::updateq('spip_forum', array('id_parent'=> $id_forum, 'statut'=>'original'), "id_forum=$id_copie");
			return ''; // pas d'erreur
		}
	}

	return '&erreur';
}

// appelle conserver_original(), puis modifie le contenu via l'API inc/modifier
// http://doc.spip.org/@enregistre_et_modifie_forum
function enregistre_et_modifie_forum($id_forum, $c=false) {
	if ($err = conserver_original($id_forum)) {
		spip_log("erreur de sauvegarde de l'original, $err");
		return;
	}

	include_spip('action/editer_forum');
	return revision_forum($id_forum, $c);
}


/**
 * Trouver le titre d'un objet publie
 * @param string $objet
 * @param int $id_objet
 * @param int $id_forum
 * @param bool $publie
 * @return bool|string
 */
function forum_recuperer_titre_dist($objet, $id_objet, $id_forum=0, $publie = true) {
	include_spip('inc/filtres');
	$titre = "";

	if ($f = charger_fonction($objet.'_forum_extraire_titre', 'inc', true)){
		$titre = $f($id_objet);
	}
	else {
		include_spip('base/objets');
		if ($publie AND !objet_test_si_publie($objet, $id_objet))
			return false;

		$titre = generer_info_entite($id_objet, $objet,'titre','*');
	}

	if ($titre AND $id_forum){
		$titre_m = Sql::getfetsel('titre', 'spip_forum', "id_forum = " . intval($id_forum));
		if (!$titre_m) {
			return false; // URL fabriquee
		}
		$titre = $titre_m;
	}

	$titre = supprimer_numero($titre);
	$titre = str_replace('~', ' ', extraire_multi($titre));

	return $titre;
}


/**
 * Retourne pour un couple objet/id_objet donne
 * de quelle maniere les forums sont acceptes dessus
 * non: pas de forum
 * pos: a posteriori, acceptes et eventuellements moderes ensuite
 * pri: a priori, doivent etre valides par un admin
 * abo: les personnes doivent au prealable etre identifiees
 *
 * http://doc.spip.org/@controler_forum
 * 
 * @param string $objet
 *   objet a tester
 * @param int $id_objet
 *   identifiant de l'objet
 * @return string
 *   chaine de 3 caractere parmi 'non','pos','pri','abo'
 */
function controler_forum($objet, $id_objet) {
	// Valeur par defaut
	$accepter_forum = $GLOBALS['meta']["forums_publics"];

	// il y a un cas particulier pour l'acceptation de forum d'article...
	if ($f = charger_fonction($objet . '_accepter_forums_publics', 'inc', true)){
		$accepter_forum = $f($id_objet);
	}

	return substr($accepter_forum, 0, 3);
}


/**
 * Verifier la presence du jeton de secu post previsu
 * http://doc.spip.org/@forum_insert_noprevisu
 * @return bool
 */
function forum_insert_noprevisu(){
	// simuler une action venant de l'espace public
	// pour se conformer au cas general.
	set_request('action', 'ajout_forum');
	// Creer une session s'il n'y en a pas (cas du postage sans cookie)
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arg = $securiser_action();

	$file = _DIR_TMP ."forum_" . preg_replace('/[^0-9]/', '', $arg) .".lck";
	if (!file_exists($file)) {
		# ne pas tracer cette erreur, peut etre due a un double POST
		# tracer_erreur_forum('session absente');
		return true;
	}
	unlink($file);

	// antispam : si le champ au nom aleatoire verif_$hash n'est pas 'ok'
	// on meurt
	if (_request('verif_'._request('hash')) != 'ok') {
			tracer_erreur_forum('champ verif manquant');
			return true;
	}
	return false;
}


/**
 * recuperer tous les objets dont on veut pouvoir obtenir l'identifiant
 * directement dans l'environnement
 *
 * @return array
 */
function forum_get_objets_depuis_env() {
	static $objets = null;
	if ($objets === null) {
		// on met une cle (le type d'objet) pour qu'un appel du pipeline
		// puisse facilement soustraire un objet qu'il ne veut pas avec
		// unset($objets['rubrique']) par exemple.
		$objets = pipeline('forum_objets_depuis_env', array(
			'article' => id_table_objet('article'),
			'rubrique' => id_table_objet('rubrique'),
			'site' => id_table_objet('site'),
			'breve' => id_table_objet('breve')
		));
		asort($objets);
	}

	return $objets;
}


// http://doc.spip.org/@reduce_strlen
function reduce_strlen($n, $c)
{
  return $n - (is_string($c) ? strlen($c) : 0);
}
?>