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

global $logo_libelles;
// utilise pour le logo du site, donc doit rester ici
$logo_libelles['site'] = _T('logo_site');
$logo_libelles['racine'] = _T('logo_standard_rubrique');

/**
 * Formulaire #EDITER_LOGO
 *
 * Ce formulaire ajoute, modifie ou supprime des logos sur les objets de SPIP.
 * - En dehors d'une boucle, ce formulaire modifie le logo du site.
 * - Dans une boucle, il modifie le logo de la table selectionnee.
 * Pensez juste que l'appel de #LOGO_{TYPE} s'appuie sur le nom de la cle primaire et non sur le
 * nom de l'objet reel. Par exemple on ecrira #LOGO_GROUPE (et non #LOGO_GROUPEMOTS) pour afficher
 * un logo issu du formulaire mis dans une boucle GROUPES_MOTS
 * - il est possible de lui passer les parametres objet et id : #FORMULAIRE_EDITER_LOGO{article,1}
 * - il est possible de spécifier une url de redirection apres traitement :
 * ex. #FORMULAIRE_EDITER_LOGO{article,1,#URL_ARTICLE}
 */

/**
 * Chargement du formulaire
 *
 * @param string $objet        Objet SPIP auquel sera lie le document (ex. article)
 * @param integer $id_objet    Identifiant de l'objet
 * @param string $retour       Url de redirection apres traitement
 * @param Array $options       Tableau d'option (exemple : image_reduire => 50)
 * @return Array               Variables d'environnement pour le fond
 */
function formulaires_editer_logo_charger_dist($objet, $id_objet, $retour='', $options=array()){
	// pas dans une boucle ? formulaire pour le logo du site
	// dans ce cas, il faut chercher un 'siteon0.ext'
	if (!$objet) $objet = 'site';

	$objet = objet_type($objet);
	$_id_objet = id_table_objet($objet);

	if (!is_array($options))
		$options = unserialize($options);

	if (!isset ($options['titre'])) {
		$balise_img = chercher_filtre('balise_img');
		$img = $balise_img(chemin_image('image-24.png'), "", 'cadre-icone');
		$libelles = pipeline('libeller_logo', $GLOBALS['logo_libelles']);
		$libelle = (($id_objet OR $objet != 'rubrique') ? $objet : 'racine');
		if (isset($libelles[$libelle])) {
			$libelle = $libelles[$libelle];
		} elseif ($libelle = objet_info($objet, 'texte_logo_objet')) {
			$libelle = _T($libelle);
		} else {
			$libelle = _L('Logo');
		}
		switch($objet){
			case 'article':
				$libelle .= " " . aide ("logoart");
				break;
			case 'breve':
				$libelle .= " " . aide ("breveslogo");
				break;
			case 'rubrique':
				$libelle .= " " . aide ("rublogo");
				break;
			default:
				break;
		}

		$options['titre'] = $img . $libelle;
	}
	if (!isset ($options['editable'])){
		include_spip('inc/autoriser');
		$options['editable'] = autoriser('iconifier',$objet,$id_objet);
	}

	$res = array(
		'editable'=>($GLOBALS['meta']['activer_logos'] == 'oui' ? ' ' : '')&&(!isset($options['editable']) OR $options['editable']),
		'logo_survol'=>($GLOBALS['meta']['activer_logos_survol'] == 'oui' ? ' ' : ''),
		'objet'=>$objet,
		'id_objet'=>$id_objet,
		'_options'=>$options,
		'_show_upload_off'=>'',
	);
	
	// rechercher le logo de l'objet
	// la fonction prend un parametre '_id_objet' etrange : 
	// le nom de la cle primaire (et non le nom de la table)
	// ou directement le nom du raccourcis a chercher
	$chercher_logo = charger_fonction('chercher_logo', 'inc');
	$etats = $res['logo_survol'] ? array('on','off') : array('on');
	foreach($etats as $etat) {
		$logo = $chercher_logo($id_objet, $_id_objet, $etat);
		if ($logo){
			$res['logo_'.$etat] = $logo[0];
		}
	}
	// pas de logo_on -> pas de formulaire pour le survol
	if (!isset($res['logo_on']))
		$res['logo_survol']='';
	elseif (!isset($res['logo_off']) AND _request('logo_up'))
		$res['_show_upload_off'] = ' ';

	// si le logo n'est pas editable et qu'il n'y en a pas, on affiche pas du tout le formulaire
	if (!$res['editable']
	  AND !isset($res['logo_off'])
	  AND !isset($res['logo_on']))
		return false;

	return $res;
}

/**
 * Identifier le formulaire en faisant abstraction des parametres qui
 * ne representent pas l'objet edite
 */
function formulaires_editer_logo_identifier_dist($objet, $id_objet, $retour='', $options=array()){
	return serialize(array($objet, $id_objet));
}

/**
 * Verification avant traitement
 *
 * On verifie que l'upload s'est bien passe et
 * que le document recu est une image (d'apres son extension)
 *
 * @param string $objet
 * @param integer $id_objet
 * @param string $retour
 * @return Array Tableau des erreurs
 */
function formulaires_editer_logo_verifier_dist($objet, $id_objet, $retour=''){
	$erreurs = array();
	// verifier les extensions
	$sources = formulaire_editer_logo_get_sources();
	foreach($sources as $etat=>$file) {
		// seulement si une reception correcte a eu lieu
		if ($file AND $file['error'] == 0) {
			if (!in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)),array('jpg','png','gif','jpeg')))
				$erreurs['logo_'.$etat] = _L('Extension non reconnue');
		}
	}
	return $erreurs;
}

/**
 * Traitement de l'upload d'un logo
 *
 * Il est affecte au site si la balise n'est pas dans une boucle,
 * sinon a l'objet concerne par la boucle ou indiquee par les parametres d'appel
 *
 * @param string $objet
 * @param integer $id_objet
 * @param string $retour
 * @return Array
 */
function formulaires_editer_logo_traiter_dist($objet, $id_objet, $retour=''){
	$res = array('editable'=>' ');
	
	// pas dans une boucle ? formulaire pour le logo du site
	// dans ce cas, il faut chercher un 'siteon0.ext'	
	if (!$objet) $objet = 'site';

	$objet = objet_type($objet);
	$_id_objet = id_table_objet($objet);

	// supprimer l'ancien logo puis copier le nouveau
	include_spip('inc/chercher_logo');
	include_spip('inc/flock');
	$type = type_du_logo($_id_objet);
	$chercher_logo = charger_fonction('chercher_logo','inc');
	
	// effectuer la suppression si demandee d'un logo
	$on = _request('supprimer_logo_on');
	if ($on OR _request('supprimer_logo_off')){
		$logo = $chercher_logo($id_objet, $_id_objet, $on ? 'on' : 'off');
		if ($logo)
			spip_unlink($logo[0]);
		$res['message_ok'] = ''; // pas besoin de message : la validation est visuelle
		set_request('logo_up',' ');
	}
	
	// sinon supprimer ancien logo puis copier le nouveau
	else {
		include_spip('action/iconifier');
		$ajouter_image = charger_fonction('spip_image_ajouter','action');
		$sources = formulaire_editer_logo_get_sources();
		foreach($sources as $etat=>$file) {
			if ($file and $file['error']==0) {
				$logo = $chercher_logo($id_objet, $_id_objet, $etat);
				if ($logo)
					spip_unlink($logo[0]);
				if ($err = $ajouter_image($type.$etat.$id_objet," ",$file,true))
					$res['message_erreur'] = $err;
				else
					$res['message_ok'] = ''; // pas besoin de message : la validation est visuelle
				set_request('logo_up',' ');
			}
		}
	}
	
	if ($retour){
		include_spip('inc/headers');
		$res['redirect'] = parametre_url($retour,'var_mode','calcul');
	}

	return $res;
}


/**
 * Extraction des sources des fichiers uploades correspondant aux 2 logos (normal + survol)
 * si leur upload s'est bien passé
 *
 * @return Array
 */
function formulaire_editer_logo_get_sources(){
	if (!$_FILES) $_FILES = $GLOBALS['HTTP_POST_FILES'];
	if (!is_array($_FILES)) return array();
	
	$sources = array();
	foreach(array('on','off') as $etat) {
		if ($_FILES['logo_'.$etat]['error'] == 0) {
			$sources[$etat] = $_FILES['logo_'.$etat];
		}
	}
	return $sources;
}
?>
