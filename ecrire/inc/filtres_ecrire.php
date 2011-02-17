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

include_spip('inc/filtres_boites');
include_spip('inc/boutons');

/**
 * Fonctions utilises au calcul des squelette du prive.
 */

/**
 * Bloquer l'acces a une page en renvoyant vers 403
 * @param bool $ok
 */
function sinon_interdire_acces($ok=false) {
	if ($ok) return '';
	ob_end_clean(); // vider tous les tampons
	$echec = charger_fonction('403','exec');
	$echec();

	#include_spip('inc/headers');
	#redirige_formulaire(generer_url_ecrire('403','acces='._request('exec')));
	exit;
}


/**
 * Retourne les parametres de personnalisation css de l'espace prive
 * (ltr et couleurs) ce qui permet une ecriture comme :
 * generer_url_public('style_prive', parametres_css_prive())
 * qu'il est alors possible de recuperer dans le squelette style_prive.html avec
 * 
 * #SET{claire,##ENV{couleur_claire,edf3fe}}
 * #SET{foncee,##ENV{couleur_foncee,3874b0}}
 * #SET{left,#ENV{ltr}|choixsiegal{left,left,right}}
 * #SET{right,#ENV{ltr}|choixsiegal{left,right,left}}
 *
 * http://doc.spip.org/@parametres_css_prive
 *
 * @return string
 */
function parametres_css_prive(){
	global $visiteur_session;
	global $browser_name, $browser_version;

	$ie = "";
	include_spip('inc/layer');
	if ($browser_name=='MSIE')
		$ie = "&ie=$browser_version";

	$v = "&v=".$GLOBALS['spip_version_code'];

	$p = "&p=".substr(md5($GLOBALS['meta']['plugin']),0,4);

	$theme = "&themes=".implode(',',lister_themes_prives());

	$c = (is_array($visiteur_session)
	AND is_array($visiteur_session['prefs']))
		? $visiteur_session['prefs']['couleur']
		: 1;

	$couleurs = charger_fonction('couleurs', 'inc');
	$recalcul = _request('var_mode')=='recalcul' ? '&var_mode=recalcul':'';
	return 'ltr=' . $GLOBALS['spip_lang_left'] . '&'. $couleurs($c) . $theme . $v . $p . $ie . $recalcul ;
}


// http://doc.spip.org/@chercher_rubrique
function chercher_rubrique($msg,$id, $id_parent, $type, $id_secteur, $restreint,$actionable = false, $retour_sans_cadre=false){
	global $spip_lang_right;
	include_spip('inc/autoriser');
	if (intval($id) && !autoriser('modifier', $type, $id))
		return "";
	if (!sql_countsel('spip_rubriques'))
		return "";
	$chercher_rubrique = charger_fonction('chercher_rubrique', 'inc');
	$form = $chercher_rubrique($id_parent, $type, $restreint, ($type=='rubrique')?$id:0);

	if ($id_parent == 0) $logo = "racine-24.png";
	elseif ($id_secteur == $id_parent) $logo = "secteur-24.png";
	else $logo = "rubrique-24.png";

	$confirm = "";
	if ($type=='rubrique') {
		// si c'est une rubrique-secteur contenant des breves, demander la
		// confirmation du deplacement
		$contient_breves = sql_countsel('spip_breves', "id_rubrique=$id");

		if ($contient_breves > 0) {
			$scb = ($contient_breves>1? 's':'');
			$scb = _T('avis_deplacement_rubrique',
				array('contient_breves' => $contient_breves,
				      'scb' => $scb));
			$confirm .= "\n<div class='confirmer_deplacement verdana2'><div class='choix'><input type='checkbox' name='confirme_deplace' value='oui' id='confirme-deplace' /><label for='confirme-deplace'>" . $scb . "</label></div></div>\n";
		} else
			$confirm .= "<input type='hidden' name='confirme_deplace' value='oui' />\n";
	}
	$form .= $confirm;
	if ($actionable){
		if (strpos($form,'<select')!==false) {
			$form .= "<div style='text-align: $spip_lang_right;'>"
				. '<input class="fondo" type="submit" value="'._T('bouton_choisir').'"/>'
				. "</div>";
		}
		$form = "<input type='hidden' name='editer_$type' value='oui' />\n" . $form;
		$form = generer_action_auteur("editer_$type", $id, self(), $form, " method='post' class='submit_plongeur'");
	}

	if ($retour_sans_cadre)
		return $form;

	include_spip('inc/presentation');
	return debut_cadre_couleur($logo, true, "", $msg) . $form .fin_cadre_couleur(true);

}


// http://doc.spip.org/@avoir_visiteurs
function avoir_visiteurs($past=false, $accepter=true) {
	if ($GLOBALS['meta']["forums_publics"] == 'abo') return true;
	if ($accepter AND $GLOBALS['meta']["accepter_visiteurs"] <> 'non') return true;
	if (sql_countsel('spip_articles', "accepter_forum='abo'"))return true;
	if (!$past) return false;
	return sql_countsel('spip_auteurs',  "statut NOT IN ('0minirezo','1comite', 'nouveau', '5poubelle')");
}

/**
 * lister les status d'article visibles dans l'espace prive
 * en fonction du statut de l'auteur
 * pour l'extensibilie de SPIP, on se repose sur autoriser('voir','article')
 * en testant un a un les status presents en base
 *
 * on memorise en static pour eviter de refaire plusieurs fois
 * 
 * @param string $statut_auteur
 * @return array
 */
function statuts_articles_visibles($statut_auteur){
	static $auth = array();
	if (!isset($auth[$statut_auteur])){
		$auth[$statut_auteur] = array();
		$statuts = array_map('reset',sql_allfetsel('distinct statut','spip_articles'));
		foreach($statuts as $s){
			if (autoriser('voir','article',0,array('statut'=>$statut_auteur),array('statut'=>$s)))
				$auth[$statut_auteur][] = $s;
		}
	}

	return $auth[$statut_auteur];
}

/**
 * Afficher le nom de la table
 * @param  $table
 * @return mixed|string
 */
function affiche_nom_table($table){
	static $libelles = null;
	if (!$libelles){
		$libelles = array('articles'=>'info_articles_2','breves'=>'info_breves_02','rubriques'=>'info_rubriques','syndic'=>'icone_sites_references');
		$libelles = pipeline('libelle_association_mots',$libelles);
	}
	if (!strlen($table))
		return '';

	return _T(isset($libelles[$table])?$libelles[$table]:"$table:info_$table");
}


//
/**
 * Traduire le statut technique de l'auteur en langage comprehensible
 * si $statut=='nouveau' et que le statut en attente est fourni,
 * le prendre en compte en affichant que l'auteur est en attente
 *
 * http://doc.spip.org/@traduire_statut_auteur
 * 
 * @param string $statut
 * @param string $attente
 * @return string
 */
function traduire_statut_auteur($statut,$attente=""){
	$plus = "";
	if ($statut=='nouveau') {
		if ($attente) {
			$statut = $attente;
			$plus = " ("._T('info_statut_auteur_a_confirmer').")";
		}
		else return _T('info_statut_auteur_a_confirmer');
	}

	$recom = array("info_administrateurs" => _T('item_administrateur_2'),
		       "info_redacteurs" =>  _T('intem_redacteur'),
		       "info_visiteurs" => _T('item_visiteur'),
		       '5poubelle' => _T('texte_statut_poubelle'), // bouh
		       );
	if (isset($recom[$statut]))
		return $recom[$statut].$plus;

	// retrouver directement par le statut sinon
	if ($t = array_search($statut, $GLOBALS['liste_des_statuts'])){
	  if (isset($recom[$t]))
			return $recom[$t].$plus;
		return _T($t).$plus;
	}

	// si on a pas reussi a le traduire, retournons la chaine telle quelle
	// c'est toujours plus informatif que rien du tout
	return $statut;
}

/**
 * Afficher la mention des autres auteurs ayant modifie un objet
 *
 * @param int $id_objet
 * @param string $objet
 * @return string
 */
function afficher_qui_edite($id_objet,$objet){
	static $qui = array();
	if (isset($qui[$objet][$id_objet]))
		return $qui[$objet][$id_objet];

	if ($GLOBALS['meta']['articles_modif'] == 'non')
		return $qui[$objet][$id_objet] = '';
	
	include_spip('inc/drapeau_edition');
	$modif = mention_qui_edite($id_objet, $objet);
	if (!$modif) return $qui[$objet][$id_objet] = '';

	include_spip('base/objets');
	$infos = lister_tables_objets_sql(table_objet_sql($objet));
	if (isset($infos['texte_signale_edition']))
		return $qui[$objet][$id_objet] = _T($infos['texte_signale_edition'], $modif);
	
	// TODO -- _L("Fil a travaille sur cet objet il y a x minutes")
	return $qui[$objet][$id_objet] = _L('@nom_auteur_modif@ a travaill&eacute; sur ce contenu il y a @date_diff@ minutes', $modif);
}

/**
 * Lister les statuts des auteurs
 *
 * @param string $redacteurs
 *   redacteurs : retourne les statuts des auteurs au moins redacteur, tels que defini par AUTEURS_MIN_REDAC
 *   visiteurs : retourne les statuts des autres auteurs, cad les visiteurs et autres statuts perso
 *   tous : retourne tous les statuts connus
 * @param bool $en_base
 *   si true, ne retourne strictement que les status existants en base
 *   dans tous les cas, les statuts existants en base sont inclus
 * @return array
 */
function auteurs_lister_statuts($quoi='tous',$en_base=true) {
	if (!defined('AUTEURS_MIN_REDAC')) define('AUTEURS_MIN_REDAC', "0minirezo,1comite,5poubelle");

	switch($quoi){
		case "redacteurs":
			$statut = AUTEURS_MIN_REDAC;
			$statut = explode(',',$statut);
			if ($en_base) {
				$check = array_map('reset',sql_allfetsel('DISTINCT statut','spip_auteurs',sql_in('statut',$statut)));
				$retire = array_diff($statut,$check);
				$statut = array_diff($statut,$retire);
			}
			return $statut;
			break;
		case "visiteurs":
			$statut = array();
			$exclus = AUTEURS_MIN_REDAC;
			$exclus = explode(',',$exclus);
			if (!$en_base){
				// prendre aussi les statuts de la table des status qui ne sont pas dans le define
				$statut = array_diff(array_values($GLOBALS['liste_des_statuts']),$exclus);
			}
			$s_complement = array_map('reset',sql_allfetsel('DISTINCT statut','spip_auteurs',sql_in('statut',$exclus,'NOT')));
			return array_merge($statut,$s_complement);
			break;
		default:
		case "tous":
			$statut = array_values($GLOBALS['liste_des_statuts']);
			$s_complement = array_map('reset',sql_allfetsel('DISTINCT statut','spip_auteurs',sql_in('statut',$statut,'NOT')));
			$statut = array_merge($statut,$s_complement);
			if ($en_base) {
				$check = array_map('reset',sql_allfetsel('DISTINCT statut','spip_auteurs',sql_in('statut',$statut)));
				$retire = array_diff($statut,$check);
				$statut = array_diff($statut,$retire);
			}
			return $statut;
			break;
	}

	// on arrive jamais ici
	return array_values($GLOBALS['liste_des_statuts']);
}


function trouver_rubrique_creer_objet($id_rubrique,$objet){
	global $connect_id_rubrique;
	if (!$id_rubrique){
		$in = !count($connect_id_rubrique)
			? ''
			: (" AND ".sql_in('id_rubrique', $connect_id_rubrique));

		$id_rubrique = sql_getfetsel('id_rubrique', 'spip_rubriques', "id_parent=0$in", '', "id_rubrique DESC", 1);

		if (!autoriser("creer{$objet}dans", 'rubrique', $id_rubrique)){
			// manque de chance, la rubrique n'est pas autorisee, on cherche un des secteurs autorises
			$res = sql_select("id_rubrique", "spip_rubriques", "id_parent=0");
			while (!autoriser("creer{$objet}dans", 'rubrique', $id_rubrique) && $row_rub = sql_fetch($res)){
				$id_rubrique = $row_rub['id_rubrique'];
			}
		}
	}
  return $id_rubrique;
}

function lien_article_virtuel($chapo){
	include_spip('inc/lien');
  if (!chapo_redirigetil($chapo))
	  return '';
  return propre("[->".chapo_redirige(substr($chapo, 1))."]");
}


// http://doc.spip.org/@bouton_spip_rss
function bouton_spip_rss($op, $args=array(), $lang='') {

	global $spip_lang_right;
	include_spip('inc/acces');
	$clic = http_img_pack('rss-24.png', 'RSS', '', 'RSS');
	$args = param_low_sec($op, $args, $lang, 'rss');
	$url = generer_url_public('rss', $args);
	return "<a style='float: $spip_lang_right;' href='$url'>$clic</a>";
}
?>