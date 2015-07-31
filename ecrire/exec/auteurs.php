<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/presentation');

// Constante pour le nombre d'auteurs par page.
@define('MAX_AUTEURS_PAR_PAGE', 30);
@define('AUTEURS_MIN_REDAC', "0minirezo,1comite,5poubelle");
@define('AUTEURS_DEFAUT', '');
// decommenter cette ligne et commenter la precedente
// pour que l'affichage par defaut soit les visiteurs
#@define('AUTEURS_DEFAUT', '!');

// http://doc.spip.org/@exec_auteurs_dist
function exec_auteurs_dist($vue = 'auteurs'){

	$statut = AUTEURS_DEFAUT . AUTEURS_MIN_REDAC;

	pipeline('exec_init',array('args'=>array('exec'=>$vue),'data'=>''));

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page(_T('info_'.$vue),"auteurs","$vue");

	$ret = debut_gauche("$vue",true) . debut_boite_info(true);

	$ret .= "\n<p class='arial1'>"._T('info_gauche_auteurs'). '</p>';

	if ($GLOBALS['visiteur_session']['statut'] == '0minirezo')
		$ret .= "\n<p class='arial1'>". _T('info_gauche_auteurs_exterieurs') . '</p>';

	$ret .= fin_boite_info(true);

	$ret .= pipeline('affiche_gauche',array('args'=>array('exec'=>$vue),'data'=>''));

	$res = '';
	if (autoriser('creer','auteur'))
		$res = icone_horizontale(_T('icone_creer_nouvel_auteur'), generer_url_ecrire("auteur_infos", 'new=oui'), "auteur-24.gif", "creer.gif", false);

	$res .= icone_horizontale(_T('icone_informations_personnelles'), generer_url_ecrire("auteur_infos","id_auteur=".$GLOBALS['visiteur_session']['id_auteur']), "fiche-perso-24.gif","rien.gif", false);

	if ($vue=='auteurs' AND avoir_visiteurs(true))
		$res .= icone_horizontale (_T('icone_afficher_visiteurs'), generer_url_ecrire("visiteurs"), "auteur-24.gif", "", false);
	if ($vue=='visiteurs')
		$res .= icone_horizontale (_T('icone_afficher_auteurs'), generer_url_ecrire("auteurs"), "auteur-24.gif", "", false);

	$ret .= bloc_des_raccourcis($res);
	$ret .= creer_colonne_droite($vue,true);
	$ret .= pipeline('affiche_droite',array('args'=>array('exec'=>$vue),'data'=>''));
	$ret .= debut_droite('',true);

	$ret .= gros_titre(_T('info_'.$vue),'',false);

	echo $ret;
	echo formulaire_recherche($vue,(($s=_request('statut'))?"<input type='hidden' name='statut' value='$s' />":""));

	echo "<div class='nettoyeur'></div>";

	$contexte = $_GET;
	$contexte['nb'] = MAX_AUTEURS_PAR_PAGE;
	if (substr($statut,0,1)!=='!')
		$contexte['statut'] = explode(',',$statut);
	else {
		$statut = substr($statut,1);
		$statut = explode(',',$statut);
		$statut = sql_allfetsel('DISTINCT statut','spip_auteurs',sql_in('statut',$statut,'NOT'));
		$contexte['statut'] = array_map('reset',$statut);
	}

	// une barre de navigation entre statuts
	if (count($contexte['statut'])>1) {
		$nav = array(lien_ou_expose(generer_url_ecrire($vue), _T('info_tout_afficher'), !_request('statut')));
		$statuts = $contexte['statut'];
		$res = sql_allfetsel('*', 'spip_auteurs', $cond, '', "statut, nom");
		foreach ($statuts as $statut) {
			$texte = array_search($statut, $GLOBALS['liste_des_statuts']);
			$texte = ($texte?_T($texte):$statut);

			$nav[] = lien_ou_expose(generer_url_ecrire($vue, 'statut='.$statut), $texte, _request('statut')==$statut);
			// verification du get
			if ($statut == _request('statut'))
				$contexte['statut'] = $statut;
		}
		echo "<p class='pagination'>".implode(' | ',$nav)."</p>";
	}

	if ($GLOBALS['visiteur_session']['statut']=='0minirezo'){
		// n'exclure que les articles a la poubelle des compteurs
		$contexte['filtre_statut_articles'] = array('poubelle');
	}
	else {
		// exclure les articles a la poubelle, en redac ou refuse des compteurs
		$contexte['filtre_statut_articles'] = array('prepa','poubelle','refuse');
	}

	$lister_objets = charger_fonction('lister_objets','inc');
	echo $lister_objets($vue,$contexte);

	echo pipeline('affiche_milieu',array('args'=>array('exec'=>$vue),'data'=>''));
	echo fin_gauche(), fin_page();
}

?>