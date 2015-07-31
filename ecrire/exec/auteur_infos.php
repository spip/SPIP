<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2011                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;
include_spip('inc/presentation');
include_spip('inc/acces');
include_spip('inc/autoriser');

// http://doc.spip.org/@exec_auteur_infos_dist
function exec_auteur_infos_dist() {

	exec_auteur_infos_args(intval(_request('id_auteur')),
		_request('nom'),
		_request('new'),
		_request('echec'),
		_request('redirect'));
}

// http://doc.spip.org/@exec_auteur_infos_args
function exec_auteur_infos_args($id_auteur, $nom, $new, $echec='', $redirect='')
{
	global $connect_id_auteur;
	pipeline('exec_init',
		array('args' => array(
			'exec'=> 'auteur_infos',
			'id_auteur'=>$id_auteur),
			'data'=>''
		)
	);

	if ($id_auteur) {
		$auteur = sql_fetsel("*", "spip_auteurs", "id_auteur=$id_auteur");
		unset($auteur['maj']);
		unset($auteur['en_ligne']);
	} else {
		$auteur = array();
		if (strlen(_request('nom')))
			$auteur['nom'] = $nom;
	}

	if (!$auteur AND !$new AND !$echec) {
		include_spip('inc/minipres');
		echo minipres(_T('public:aucun_auteur'));
	} else {
		$commencer_page = charger_fonction('commencer_page', 'inc');
		if ($connect_id_auteur == $id_auteur) {
			echo $commencer_page($auteur['nom'], "auteurs", "perso");
		} else {
			echo $commencer_page($auteur['nom'],"auteurs","redacteurs");
		}
		echo "<br /><br /><br />";
		echo debut_gauche('', true);
		auteur_infos_ok($auteur, $id_auteur, $echec, $new, $redirect);
		if($id_auteur > 0)
			echo auteurs_interventions($auteur);
		echo fin_gauche(), fin_page();
	}
}

// http://doc.spip.org/@auteur_infos_ok
function auteur_infos_ok($auteur, $id_auteur, $echec, $new, $redirect)
{
	$associer_objet = _request('associer_objet');
	if (!preg_match(',^\w+\|[0-9]+$,',$associer_objet))
		$associer_objet = '';
	$auteur_infos = charger_fonction('auteur_infos', 'inc');
	$fiche = $auteur_infos($auteur, $new, $echec, _request('edit'), $associer_objet, $redirect, 'infos');
	if ($fiche) 
		$form_auteur = $auteur_infos($auteur, $new, $echec, _request('edit'), $associer_objet, $redirect, 'edit');
	else $form_auteur = '';

	echo cadre_auteur_infos($id_auteur, $auteur);

	echo pipeline('affiche_gauche',
			array('args' => array (
				'exec'=>'auteur_infos',
				'id_auteur'=>$id_auteur),
			'data'=>'')
		      );

	// Interface de logo
	$iconifier = charger_fonction('iconifier', 'inc');

	if ($id_auteur > 0)
		echo $iconifier('auteur', $id_auteur, 'auteur_infos', false, autoriser('modifier', 'auteur', $id_auteur));
		// nouvel auteur : le hack classique
	else if ($fiche)
		echo $iconifier('id_auteur',
			0 - $GLOBALS['visiteur_session']['id_auteur'],
			'auteur_infos');

	echo creer_colonne_droite('', true);
	echo pipeline('affiche_droite',
			      array('args' => array(
						    'exec'=>'auteur_infos',
						    'id_auteur'=>$id_auteur),
				    'data'=>'')
			      );
	echo debut_droite('', true);

	echo debut_cadre_relief("auteur-24.png", true,'','','auteur-voir');

	// $fiche est vide si on demande par exemple
	// a creer un auteur alors que c'est interdit
	if ($fiche) {
		echo $fiche;
	} else {
		echo gros_titre(_T('info_acces_interdit'),'', false);
	}
	echo pipeline('affiche_milieu',
			      array('args' => array(
						    'exec'=>'auteur_infos',
						    'id_auteur'=>$id_auteur),
				    'data'=>''));
		
	echo fin_cadre_relief(true);

	// afficher le formulaire d'edition apres le cadre d'info
	// pour pouvoir afficher soit les infos, 
	//  soit ce formulaire (qui a deja son cadre)
	echo $form_auteur;
}

// http://doc.spip.org/@cadre_auteur_infos
function cadre_auteur_infos($id_auteur, $auteur)
{
	$boite = pipeline ('boite_infos', array('data' => '',
		'args' => array(
			'type'=>'auteur',
			'id' => $id_auteur,
			'row' => $auteur
		)
	));

	if ($boite)
		return debut_boite_info(true) . $boite . fin_boite_info(true);
}


// http://doc.spip.org/@auteurs_interventions
function auteurs_interventions($auteur) {
	if (!$id_auteur = intval($auteur['id_auteur']))
		return;
	$statut = $auteur['statut'];
	
	global $connect_id_auteur;

	if (autoriser('voir', 'article')) $aff_art = array('prepa','prop','publie','refuse'); 
	else if ($connect_id_auteur == $id_auteur) $aff_art = array('prepa','prop','publie');
	else $aff_art = array('prop','publie'); 

	$lister_objets = charger_fonction('lister_objets','inc');

  echo pipeline('affiche_auteurs_interventions',
	  array(
		  'args' => array('id_auteur'=>$id_auteur, 'auteur'=>$auteur),
		  'data' => $lister_objets('articles',array('titre'=>_T('info_articles_auteur'),'statut'=>$aff_art, 'par'=>'date','id_auteur'=>$id_auteur))
	  )
  );
}
?>
