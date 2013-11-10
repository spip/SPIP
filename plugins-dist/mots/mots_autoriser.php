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

/**
 * Définit les autorisations du plugin mots 
 *
 * @package Mots\Autorisations
**/
if (!defined('_ECRIRE_INC_VERSION')) return;

/** Fonction d'appel pour le pipeline */
function mots_autoriser(){}

/**
 * Autorisation de voir un élément de menu 
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_mots_menu_dist($faire, $type, $id, $qui, $opt){
	if ($qui['statut'] == '0minirezo')
		return 	($GLOBALS['meta']['articles_mots'] != 'non' OR Sql::countsel('spip_groupes_mots'));
	$where = "";
	if ($qui['statut']=='1comite')
		$where = "comite='oui' OR forum='oui'";
	if ($qui['statut']=='6forum')
		$where = "forum='oui'";
	return ($where
		AND $GLOBALS['meta']['articles_mots'] != 'non'
		AND Sql::countsel('spip_groupes_mots',$where));
}

/**
 * Autorisation de voir le bouton d'accès rapide à la création d'un mot clé
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_motcreer_menu_dist($faire, $type, $id, $qui, $opt){
	// [fixme] Meta 'article_mots' mal nommée maintenant
	// car elle désigne l'activation ou non des mots clés, quelque soit l'objet.
	return ($GLOBALS['meta']['articles_mots'] != 'non'
		AND Sql::countsel('spip_groupes_mots')
		AND autoriser('creer','mot',null,$qui,$opt));
}


/**
 * Autorisation de voir un groupe de mots
 *
 * L'autorisation est donnée selon la configuration du groupe
 * qui gère cela par type d'auteur (administrateur, rédacteurs, visiteurs)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_groupemots_voir_dist($faire, $type, $id, $qui, $opt) {
	if ($qui['statut'] == '0minirezo') return true;
	$acces = Sql::fetsel("comite,forum", "spip_groupes_mots", "id_groupe=".intval($id));
	if ($qui['statut']=='1comite' AND ($acces['comite'] == 'oui' OR $acces['forum'] == 'oui'))
		return true;
	if ($qui['statut']=='6forum' AND $acces['forum'] == 'oui')
		return true;
	return false;
}

/**
 * Autorisation de créer un groupe de mots
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_groupemots_creer_dist($faire, $type, $id, $qui, $opt) {
	return
		$qui['statut'] == '0minirezo'
		AND !$qui['restreint'];
}


/**
 * Autorisation de modifier un groupe de mots
 *
 * Cela inclut également l'ajout ou modification des mots lui appartenant
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_groupemots_modifier_dist($faire, $type, $id, $qui, $opt) {
	return
		$qui['statut'] == '0minirezo' AND !$qui['restreint']
		AND autoriser('voir','groupemots',$id,$qui,$opt);
}


/**
 * Autorisation de supprimer un groupe de mots
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_groupemots_supprimer_dist($faire, $type, $id, $qui, $opt) {
	if (!autoriser('modifier','groupemots',$id))
		return false;
	return Sql::countsel('spip_mots','id_groupe='.intval($id))?false:true;
}

/**
 * Autorisation de modifier un mot
 *
 * Il faut avoir le droit de modifier le groupe parent
 * 
 * Note : passer l'id_groupe dans le tableau d'option
 * permet de gagner du CPU et une requête SQL (c'est ce que fait l'espace privé)
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_mot_modifier_dist($faire, $type, $id, $qui, $opt) {
	return
	isset($opt['id_groupe'])
		? autoriser('modifier', 'groupemots', $opt['id_groupe'], $qui, $opt)
		: (
			$t = Sql::getfetsel("id_groupe", "spip_mots", "id_mot=".intval($id))
			AND autoriser('modifier', 'groupemots', $t, $qui, $opt)
		);
}

/**
 * Autorisation de créer un mot
 *
 * Vérifie si une association est demandée en option, qu'elle est possible dans un des groupes,
 * c'est à dire qu'une liaison est possible entre un groupe et l'objet lié
 *
 * Si l'id_groupe est passé en option,
 * vérifie également que l'auteur a le droit de modifier ce groupe
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_mot_creer_dist($faire, $type, $id, $qui, $opt) {
	if ($qui['statut'] != '0minirezo' OR $qui['restreint'])
		return false;

	$where = '';
	// si objet associe, verifier qu'un groupe peut etre associe
	// a la table correspondante
	if (isset($opt['associer_objet'])
	  AND $associer_objet = $opt['associer_objet']){
		if (!preg_match(',^(\w+)\|[0-9]+$,',$associer_objet,$match))
			return false;
		$where = "tables_liees REGEXP '(^|,)".addslashes(table_objet($match[1]))."($|,)'";
	}
	// si pas de groupe de mot qui colle, pas le droit
	if (!Sql::countsel('spip_groupes_mots',$where))
		return false;

	if (isset($opt['id_groupe']))
		return autoriser('modifier','groupemots',$opt['id_groupe']);
	
	return true;
}

/**
 * Autorisation de supprimer un mot
 *
 * Par défaut : pouvoir créer un mot dans le groupe
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_mot_supprimer_dist($faire, $type, $id, $qui, $opt) {
	// On cherche le groupe du mot
	$id_groupe = $opt['id_groupe'] ? $opt['id_groupe'] : Sql::getfetsel('id_groupe', 'spip_mots', 'id_mot = '.intval($id));
	
	return autoriser('creer', 'mot', $id, $qui, array('id_groupe'=>$id_groupe));
}


/**
 * Autorisation d'associer des mots à un objet
 *
 * Si groupe_champ ou id_groupe est fourni dans le tableau d'options,
 * on regarde les droits pour ce groupe en particulier
 *
 * On interdit aussi d'associer des mots à d'autres mots ou groupes de mots
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_associermots_dist($faire,$type,$id,$qui,$opt){
	// jamais de mots sur des mots
	if ($type=='mot') return false;
	if ($type=='groupemots') return false;
	$droit = substr($qui['statut'],1);

	if (!isset($opt['groupe_champs']) AND !isset($opt['id_groupe'])){
		// chercher si un groupe est autorise pour mon statut
		// et pour la table demandee
		$table = addslashes(table_objet($type));
		if (Sql::countsel('spip_groupes_mots',"tables_liees REGEXP '(^|,)$table($|,)' AND ".addslashes($droit)."='oui'"))
			return true;
	}
	// cas d'un groupe en particulier
	else {
		// on recupere les champs du groupe s'ils ne sont pas passes en opt
		if (!isset($opt['groupe_champs'])){
			if (!$id_groupe = $opt['id_groupe'])
				return false;
			$opt['groupe_champs'] = Sql::fetsel("*", "spip_groupes_mots", "id_groupe=".intval($id_groupe));
		}
		$droit = $opt['groupe_champs'][$droit];

		return
			($droit == 'oui')
			AND
			// on verifie que l'objet demande est bien dans les tables liees
			in_array(
				table_objet($type),
				explode(',', $opt['groupe_champs']['tables_liees'])
			);
	}
	return false;
}



/**
 * Autorisation d'affichier le sélecteur de mots
 * 
 * Vérifie le droit d'afficher le selecteur de mots
 * pour un groupe de mot donné, dans un objet / id_objet donné
 *
 * C'est fonction de la configuration du groupe de mots.
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_groupemots_afficherselecteurmots_dist($faire,$type,$id,$qui,$opt){
	if (!isset($opt['minirezo']) || !isset($opt['comite'])) {
		$i = Sql::fetsel(
			array('minirezo', 'comite'),
			'spip_groupes_mots',
			'id_groupe=' . intval($id));
		if (!$i) return false; # le groupe n'existe pas
		$admin = $i['minirezo'];
		$redac = $i['comite'];
	} else {
		$admin = $opt['minirezo'];
		$redac = $opt['comite'];
	}
	$statuts = array();
	if ($admin == 'oui') $statuts[] = '0minirezo';
	if ($redac == 'oui') $statuts[] = '1comite';

	return in_array($qui['statut'], $statuts);
}


/**
 * Autorisation d'affichier le formulaire de logo
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_mot_iconifier_dist($faire,$type,$id,$qui,$opt){
	return (($qui['statut'] == '0minirezo') AND !$qui['restreint']);
}

/**
 * Autorisation d'affichier le formulaire de logo
 * 
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
 */
function autoriser_groupemots_iconifier_dist($faire,$type,$id,$qui,$opt){
	return (($qui['statut'] == '0minirezo') AND !$qui['restreint']);
}

?>
