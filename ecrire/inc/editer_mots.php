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
include_spip('inc/actions');
include_spip('inc/mots');

// $flag indique si on est autorise a modifier l'article
// http://doc.spip.org/@inc_editer_mots_dist
function inc_editer_mots_dist($objet, $id_objet, $cherche_mot, $select_groupe, $flag, $visible = false, $url_base='') {
	if ($GLOBALS['meta']["articles_mots"] == 'non')	return '';
	if (!preg_match('/^[0-9, ]*$/', $select_groupe)) return '';
	$trouver_table = charger_fonction('trouver_table', 'base');
	$nom = table_objet($objet);
	$desc = $trouver_table($nom);
	$table_id =  @$desc['key']["PRIMARY KEY"];

	$reponse = ($flag AND $cherche_mot)
		? chercher_inserer_mot($cherche_mot, $select_groupe, $objet, $id_objet, $nom, $table_id, $url_base)
		: '';

	list($liste, $mots) = afficher_mots_cles($flag, $objet, $id_objet, $nom, $table_id, $url_base);

	$aff =false;
	$bouton = _T('titre_mots_cles').aide ("artmots");

	if ($liste)
		$aff = true;

	if ($flag) { 	// si droit de modif donner le formulaire
		$visible = ($visible OR $cherche_mot OR ($flag === 'ajax'));
		list($visible, $res) = formulaire_mots_cles($id_objet, $mots, $nom, $table_id, $url_base, $visible, $objet);

		if ($res) {
			$liste .= debut_block_depliable($visible,"lesmots")
				. $res
				. creer_mot($nom, $id_objet, $table_id, $url_base, $cherche_mot, $select_groupe)
				. fin_block();
			$bouton = bouton_block_depliable($bouton, $visible,"lesmots");
			$aff = true;
		}
	}

	if (!$aff)
		return '';

	$res = debut_cadre_enfonce("mot-cle-24.gif", true, "", $bouton)
	  . $reponse
	  . $liste
	  . fin_cadre_enfonce(true);

	return ajax_action_greffe("editer_mots", $id_objet, $res);
}

function chercher_inserer_mot($cherche_mot, $select_groupe, $objet, $id_objet, $nom, $table_id, $url_base)
{
	$modifier = false;

	list($reponse, $nouveaux_mots) = recherche_mot_cle($cherche_mot, $select_groupe, $objet, $id_objet, $nom, $table_id, $url_base);
	foreach($nouveaux_mots as $nouv_mot) {
		if ($nouv_mot!='x') {
			$modifier |= inserer_mot("spip_mots_$nom", $table_id, $id_objet, $nouv_mot);
		}
	}
	if ($modifier) {
		pipeline('post_edition',
				array(
					'args' => array(
					'operation' => 'editer_mots',
					'table' => table_objet_sql($objet),
					'id_objet' => $id_objet
					),
				'data' => null
				)
			);
	}
	return $reponse;
}
// http://doc.spip.org/@inserer_mot
function inserer_mot($table, $table_id, $id_objet, $id_mot)
{
	$r = sql_countsel($table, "id_mot=$id_mot AND $table_id=$id_objet");
	if (!$r) {
		sql_insertq($table, array('id_mot' =>$id_mot,  $table_id => $id_objet));
		return true;
	}
}


// http://doc.spip.org/@recherche_mot_cle
function recherche_mot_cle($cherche_mots, $id_groupe, $objet, $id_objet, $table, $table_id, $url_base)
{
	$ou = _T('info_mot_cle_ajoute') . ' ';
	if ($table == 'articles') $ou .= _T('info_l_article');
	else if ($table == 'breves') $ou .= _T('info_la_breve');
	else if ($table == 'rubriques') $ou .= _T('info_la_rubrique');

	$result = sql_select("id_mot, titre", "spip_mots", (!$id_groupe ? '' : sql_in('id_groupe', $id_groupe)));

	$table_mots = array();
	$table_ids = array();
	while ($row = sql_fetch($result)) {
		$table_ids[] = $row['id_mot'];
		$table_mots[] = $row['titre'];
	}

	$nouveaux_mots = array();
	$res = '';

	foreach (preg_split("/ *[,;] */", $cherche_mots) as $cherche_mot) {
	  if  ($cherche_mot) {
		$resultat = mots_ressemblants($cherche_mot, $table_mots, $table_ids);
		$res .= "<br />" . debut_boite_info(true);
		if (!$resultat) {
			$res .= "<b>"._T('info_non_resultat', array('cherche_mot' => htmlspecialchars($cherche_mot)))."</b><br />";
		}
		else if (count($resultat) == 1) {
			$n = $resultat[0];
			$nouveaux_mots[] = $n;
			$t = sql_getfetsel("titre", "spip_mots", "id_mot=$n");
			$res .= "<b>"
			. $ou
			. ": </b><br />\n<ul><li><span class='verdana1 spip_small'><b><span class='spip_medium'>"
			. typo($t)
			. "</span></b></span></li></ul>";
		}
		else $res .= affiche_mots_ressemblant($cherche_mot, $objet, $id_objet, $resultat, $table, $table_id, $url_base);

		$res .= fin_boite_info(true) . "<br />";
	  }
	}
	return array($res, $nouveaux_mots);
}

// http://doc.spip.org/@afficher_mots_cles
function afficher_mots_cles($flag, $objet, $id_objet, $table, $table_id, $url)
{
	$q = array('SELECT' => "M.id_mot, M.titre, M.id_groupe", 'FROM' => "spip_mots AS M LEFT JOIN spip_mots_$table AS L ON M.id_mot=L.id_mot", 'WHERE' => "L.$table_id=$id_objet", 'ORDER BY' => "M.type, M.titre");

	$ret = generer_url_retour($url, "$table_id=$id_objet#editer_mots-$id_objet");
	$styles = array(array('arial11',25), array('arial2'), array('arial2'), array('arial1'));

	$presenter_liste = charger_fonction('presenter_liste', 'inc');

	// cette variable est passee par reference
	// pour recevoir les valeurs du champ indique 
	$mots = 'id_mot'; 
	$a = array($flag,$id_objet, $objet, $ret, $table, $table_id, $url);
	$res = $presenter_liste($q, 'editer_mots_un', $mots, $a, false, $styles);

	return array($res, $mots);
}

// http://doc.spip.org/@editer_mots_un
function editer_mots_un($row, $own)
{
	$puce_statut = charger_fonction('puce_statut', 'inc');

	list ($flag_editable, $id_objet, $objet, $ret, $table, $table_id, $url_base) = $own;

	$id_mot = $row['id_mot'];
	$titre_mot = $row['titre'];
	$id_groupe = $row['id_groupe'];

	$url = generer_url_ecrire('mots_edit', "id_mot=$id_mot&redirect=$ret");

	$groupe_champs = sql_fetsel("*", "spip_groupes_mots", "id_groupe = $id_groupe");
	$groupe = typo($groupe_champs['titre']);

	if (autoriser('modifier', 'groupemots', $id_groupe))
		$groupe = "<a href='" . generer_url_ecrire("mots_type","id_groupe=$id_groupe") . "'>$groupe</a>";

	$mot = "<a href='$url'>".typo($titre_mot)."</a>";

	$retire = '';
	if ($flag_editable
	AND autoriser('editermots', $objet, $id_objet, null, array('id_groupe'=>$id_groupe,'groupe_champs'=>$groupe_champs))
	) {
		$r =  _T('info_retirer_mot')
		  . "&nbsp;"
		  . http_img_pack('croix-rouge.gif', "X", " class='puce' style='vertical-align: bottom;'");

		$retire = ajax_action_auteur('editer_mots', "$id_objet,$id_mot,$table,$table_id,$objet", $url_base, "$table_id=$id_objet", array($r,''),"&id_objet=$id_objet&objet=$objet");

		// Changer ; si unseul, poser un petit menu
		if (sql_getfetsel('unseul', 'spip_groupes_mots', 'id_groupe='.$id_groupe)
		== 'oui')
			$mot = formulaire_mot_remplace($id_groupe, $id_mot, $url_base, $table, $table_id, $objet, $id_objet);
	}

	$cle = $puce_statut($id_mot, 'publie', $id_groupe, 'mot');

	return array("<a href='$url'>$cle</a>", $mot, $groupe, $retire);
}

// http://doc.spip.org/@formulaire_mot_remplace
function formulaire_mot_remplace($id_groupe, $id_mot, $url_base, $table, $table_id, $objet, $id_objet)
{
	$res = sql_allfetsel("id_mot, titre", "spip_mots", "id_groupe = $id_groupe", "", "titre");

	foreach($res as $k => $row) {
		$id = $row['id_mot'];
		$titre = supprimer_tags(typo($row['titre']));
		$selected = ($id == $id_mot) ? " selected='selected'" : "";
		$res[$k]= "<option value='$id'$selected> $titre</option>";
	}

	$ancre = "valider_groupe_$id_groupe"; 
	// forcer le recalcul du noeud car on est en Ajax
	$jscript1 = "findObj_forcer('$ancre').style.visibility='visible';";

	$corps = "\n<select name='nouv_mot' id='nouv_mot$id_groupe' onchange=\"$jscript1\""
	. " class='spip_xx-small' style='width:90px;'>"
	. join("\n", $res)
	. "</select>\n&nbsp;" ;

	$t =  _T('bouton_changer');

	return ajax_action_post('editer_mots', "$id_objet,$id_mot,$table,$table_id,$objet", $url_base, "$table_id=$id_objet",$corps, $t, " class='visible_au_chargement' id='$ancre'", "", "&id_objet=$id_objet&objet=$objet");
}

// int $id_objet : id_article
// array $les_mots : les mots deja apposes
// string $table : 'articles'
// string $table_id : 'id_article'
// string $url_base : 'articles' (?)
// boolean $visible : determiner si le formulaire est deplie
// string $objet : 'article'
// http://doc.spip.org/@formulaire_mots_cles
function formulaire_mots_cles($id_objet, $les_mots, $table, $table_id, $url_base, $visible, $objet) {
	global  $spip_lang, $spip_lang_right;

	$res = '';

	// liste des groupes de mots contenant au moins un mot deja appose a l'objet
	$id_groupes_vus = array_map('array_pop',
		sql_allfetsel('DISTINCT(id_groupe)', 'spip_mots',
			sql_in('id_mot', $les_mots)));

	// supprimer tous les mots ?
	// a partir de 3 mots on regarde si l'ensemble des mots sont supprimables
	// si oui on propose ce lien
	if (count($les_mots)>= 3) {
		$ok = true;
		foreach ($id_groupes_vus as $id_groupe)
			$ok &= autoriser('editermots', $objet, $id_objet, null,
					array('id_groupe'=>$id_groupe,'groupe_champs'=>$row));
		if ($ok)
			$res .= "<div style='text-align: right' class='arial1'>"
				. ajax_action_auteur('editer_mots', "$id_objet,-1,$table,$table_id,$objet", $url_base, "$table_id=$id_objet", array(_T('info_retirer_mots'),''),"&id_objet=$id_objet&objet=$objet")
				. "</div><br />\n";
	}

	// formulaire groupe par groupe
	$ajouter ='';
	$cond_mots_vus = $les_mots
		? " AND " . sql_in('id_mot', $les_mots, 'NOT')
		: '';

	define('_TRI_GROUPES_MOTS', 'titre');  
	foreach(sql_allfetsel('*,' . sql_multi ("titre", $spip_lang), 'spip_groupes_mots', '', '', _TRI_GROUPES_MOTS) as $row) {
		$id_groupe = $row['id_groupe'];
		if (autoriser('editermots', $objet, $id_objet, null,
		array('id_groupe'=>$id_groupe,'groupe_champs'=>$row))
		AND $menu = menu_mots($row, $id_groupes_vus, $cond_mots_vus)) {
			list($corps, $clic) = $menu;
			$ajouter .= ajax_action_post('editer_mots',
				"$id_objet,,$table,$table_id,$objet",
				$url_base,
				"$table_id=$id_objet",
				$corps,
				$clic,
				" class='visible_au_chargement spip_xx-small' id='valider_groupe_$id_groupe'", "",
				"&id_objet=$id_objet&objet=$objet&select_groupe=$id_groupe");

			// forcer la visibilite si au moins un mot obligatoire absent
			// attention true <> 1 pour bouton_block_depliable
			if ($row['obligatoire'] == 'oui'
			AND !in_array($id_groupe, $id_groupes_vus))
				$visible = true;
		}
	}

	if ($ajouter) {
		$res .= "<div style='float:$spip_lang_right; width:280px;position:relative;display:inline;'>"
		  . $ajouter
		  ."</div>\n" 
		  . "<span class='verdana1'><b>"
		  ._T('titre_ajouter_mot_cle')
		  ."</b></span><br />\n";
	}

	return array($visible, $res);
}

function creer_mot($table, $id_objet, $table_id, $url_base, $mot='', $id_groupe=0)
{
	static $titres = array(
			'articles'=>'icone_creer_mot_cle',
			'breves'=>'icone_creer_mot_cle_breve',
			'rubriques'=>'icone_creer_mot_cle_rubrique',
			'sites'=>'icone_creer_mot_cle_site'
			);

	if (!($id_groupe ? 
		autoriser('modifier','groupemots', $id_groupe) :
		autoriser('modifier','groupemots'))
	    )
		return '';

	$legende = isset($titres[$table])
	  ? _T($titres[$table])
	  : _T('icone_creer_mot_cle');

	$args = "new=oui&ajouter_id_article=$id_objet&table=$table&table_id=$table_id"
	. (!$mot ? '' : ("&titre=".rawurlencode($mot)))
	. (!$id_groupe ? '' : ("&id_groupe=".intval($id_groupe)))
	. "&redirect=" . generer_url_retour($url_base, "$table_id=$id_objet");

	return icone_horizontale_display($legende, generer_url_ecrire("mots_edit", $args), "mot-cle-24.gif", "creer.gif", false);
}

// http://doc.spip.org/@menu_mots
function menu_mots($row, $id_groupes_vus, $cond_mots_vus)
{
	$id_groupe = $row['id_groupe'];

	// nombre de mots dans le groupe ?
	$n = sql_countsel("spip_mots", "id_groupe=$id_groupe" . $cond_mots_vus);
	if (!$n) return '';

	// mot seul, si deja present on se casse
	$unseul = ($row['unseul'] == 'oui');
	if ($unseul
	AND in_array($id_groupe, $id_groupes_vus))
		return '';

	$titre = textebrut(typo($row['titre']));
	$obligatoire = ($row['obligatoire']=='oui'
		AND !in_array($id_groupe, $id_groupes_vus));

	// forcer le recalcul du noeud car on est en Ajax
	$rand = rand(0,10000); # pour antifocus & ajax
	$ancre = "valider_groupe_$id_groupe"; 
	$jscript1 = "findObj_forcer('$ancre').style.visibility='visible';";
	$jscript2 = "if(!antifocus_mots['$rand-$id_groupe']){this.value='';antifocus_mots['$rand-$id_groupe']=true;}";

	if (!defined('_MAX_MOTS_LISTE')) define('_MAX_MOTS_LISTE', '50');
	if ($n > _MAX_MOTS_LISTE) {
		$jscript = "onfocus=\"$jscript1 $jscript2\"";

		if ($obligatoire)
			$res = "<input type='text' name='cherche_mot' id='cherche_mot$id_groupe' style='width: 180px; background-color:#E86519;' value=\"".entites_html($titre)."\" size='20' $jscript />";
		else if ($unseul) {
			$res = "<input type='text' name='cherche_mot' id='cherche_mot$id_groupe' style='width: 180px; background-color:#cccccc;' value=\"".entites_html($titre)."\" size='20' $jscript />";
		} else
			$res = "<input type='text' name='cherche_mot' id='cherche_mot$id_groupe'  style='width: 180px; ' value=\"".entites_html($titre)."\" size='20' $jscript />";

		$res .= "<input type='hidden' name='select_groupe'  value='$id_groupe' />&nbsp;";
		return array($res, _T('bouton_chercher')); 
	} else {
		if ($obligatoire)
			$style = 'width: 180px; background-color:#E86519;';
		else if ($unseul)
			$style = 'width: 180px; background-color:#cccccc;';
		else
			$style = 'width: 180px;';

		$q = sql_allfetsel("id_mot, type, titre", "spip_mots", "id_groupe =$id_groupe " . $cond_mots_vus, "", "titre");

		foreach($q as $k => $r) {
			$q[$k] = "<option value='" .$r['id_mot'] .
				"'>&nbsp;&nbsp;&nbsp;" .
				textebrut(typo($r['titre'])) .
				"</option>";
		}
		$res = "<select name='nouv_mot' id='nouv_mot$id_groupe' size='1' style='$style' onchange=\"$jscript1\">"
		. "\n<option value='x' style='font-variant: small-caps;'>"
		. $titre
		. "</option>\n"
		.  join("\n", $q)
		.  "</select>&nbsp;";

		return array($res, _T('bouton_choisir'));
	}
}

?>
