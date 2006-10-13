<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2006                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;
include_spip('inc/presentation');
include_spip('inc/acces');

// http://doc.spip.org/@exec_auteur_infos_dist
function exec_auteur_infos_dist()
{
	global $id_auteur, $redirect, $echec, $initial,
	  $connect_statut, $connect_toutes_rubriques, $connect_id_auteur;

	$id_auteur = intval($id_auteur);

	pipeline('exec_init',
		array('args' => array(
			'exec'=>'auteur_infos',
			'id_auteur'=>$id_auteur),
			'data'=>'')	);


	// id_auteur nul ==> creation, et seuls les admins complets creent
	if (!$id_auteur AND $connect_toutes_rubriques) {
		$arg = "0/";
		redirige_par_entete(generer_action_auteur('legender_auteur', $arg, $redirect, true));
		exit;
	}

	$auteur = spip_fetch_array(spip_query("SELECT * FROM spip_auteurs WHERE id_auteur=$id_auteur"));

// on peut se changer soi-meme
	if  (!($auteur AND 
	       (($connect_id_auteur == $id_auteur) ||
  // sinon on doit etre admin
  // et si on est admin restreint on ne peut pas changer un autre admin
		(($connect_statut == "0minirezo") &&
		 ($connect_toutes_rubriques OR 
		  ($auteur['statut'] != "0minirezo")))))) {

		gros_titre(_T('info_acces_interdit'));
		exit;
	}

	affiche_auteur_info_dist($initial, $auteur,  $echec, $redirect, $ajouter_id_article);
}


// http://doc.spip.org/@exec_affiche_auteur_info_dist
function affiche_auteur_info_dist($initial, $auteur,  $echec, $redirect, $ajouter_id_article)
{
	global $connect_id_auteur;

	$id_auteur = $auteur['id_auteur'];

	if ($connect_id_auteur == $id_auteur)
		debut_page($auteur['nom'], "auteurs", "perso");
	else
		debut_page($auteur['nom'],"auteurs","redacteurs");

	echo "<br /><br /><br />";

	debut_gauche();

  // charger ça tout de suite pour diposer de la fonction ci-dessous
	$instituer_auteur = charger_fonction('instituer_auteur', 'inc');
	cadre_auteur_infos($id_auteur, $auteur);

	echo pipeline('affiche_gauche',
		array('args' => array(
			'exec'=>'auteur_infos',
			'id_auteur'=>$id_auteur),
		'data'=>'')
	);

	creer_colonne_droite();
	echo pipeline('affiche_droite',
		array('args' => array(
			'exec'=>'auteur_infos',
			'id_auteur'=>$id_auteur),
		'data'=>'')
	);
	debut_droite();

	if ($echec){
		$m = '';
		foreach (split('%%%',$echec) as $e)
			$m .= '<p>' . _T($e) . "</p>\n";
		debut_cadre_relief();
		echo http_img_pack("warning.gif", _T('info_avertissement'), "width='48' height='48' align='left'"),
		  "<div style='color: red; left-margin: 5px'>",$m,"<p>",_T('info_recommencer'),"</p></div>\n";
		fin_cadre_relief();
		echo "\n<p>";
	}

	$legender_auteur = charger_fonction('legender_auteur', 'inc');
	debut_cadre_formulaire();

	echo $legender_auteur($id_auteur, $auteur, $initial, $ajouter_id_article, $redirect);

	echo $instituer_auteur($id_auteur, $auteur['statut'], "auteurs_edit");

	fin_cadre_formulaire();

	echo fin_page();
}

?>
