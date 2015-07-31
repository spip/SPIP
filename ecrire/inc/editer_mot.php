<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;
include_spip('inc/actions');
include_spip('inc/mots');

// http://doc.spip.org/@inc_editer_mot_dist
function inc_editer_mot_dist($objet, $id_objet, $cherche_mot, $select_groupe, $flag) {
	global $connect_statut;

	if ($GLOBALS['meta']["articles_mots"] == 'non')	return '';

	$visible = ($cherche_mot OR ($flag === 'ajax'));

	if ($objet == 'article') {
		$table_id = 'id_article';
		$table = 'articles';
		$url_base = "articles";
	}
	else if ($objet == 'breve') {
		$table_id = 'id_breve';
		$table = 'breves';
		$url_base = "breves_voir";
	}
	else if ($objet == 'rubrique') {
		$table_id = 'id_rubrique';
		$table = 'rubriques';
		$url_base = "naviguer";
	}

	else if ($objet == 'syndic') {
		$table_id = 'id_syndic';
		$table = 'syndic';
		$url_base = "sites";
	}
	else {
		spip_log("erreur dans formulaire_mots($objet, $id_objet, $cherche_mot, $select_groupe, $flag)");
		return '';
	}

	$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_mots AS mots, spip_mots_$table AS lien WHERE lien.$table_id=$id_objet AND mots.id_mot=lien.id_mot"));

	if (!($nombre_mots = $cpt['n'])) {
		if (!$flag) return;
		$cpt = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_groupes_mots WHERE $table = 'oui'	AND ".substr($connect_statut,1)." = 'oui'"));

		if (!$cpt['n']) return;
	}

	//
	// Preparer l'affichage
	//

	// La reponse
	$reponse = '';
	if ($flag AND $cherche_mot) {
		$reindexer = false;
		list($reponse, $nouveaux_mots) = recherche_mot_cle($cherche_mot, $select_groupe, $objet, $id_objet, $table, $table_id, $url_base);
		foreach($nouveaux_mots as $nouv_mot) {
			if ($nouv_mot!='x') {
				$reindexer |= inserer_mot("spip_mots_$table", $table_id, $id_objet, $nouv_mot);
			}
		}
		if ($reindexer AND ($GLOBALS['meta']['activer_moteur'] == 'oui')) {
			include_spip("inc/indexation");
			marquer_indexer("spip_$table", $id_objet);
		}
	}

	$form = afficher_mots_cles($flag, $objet, $id_objet, $table, $table_id, $url_base, $visible);

	// Envoyer titre + div-id + formulaire + fin
	if ($flag){
		if ($visible)
			$bouton = bouton_block_visible("lesmots");
		else
			$bouton =  bouton_block_invisible("lesmots");
	} else $bouton = '';

	$bouton .= _T('titre_mots_cles').aide ("artmots");

	$res = debut_cadre_enfonce("mot-cle-24.gif", true, "", $bouton)
	  . $reponse
	  . $form
	  . fin_cadre_enfonce(true);

	return ajax_action_greffe("editer_mot-$id_objet", $res);
}

// http://doc.spip.org/@inserer_mot
function inserer_mot($table, $table_id, $id_objet, $id_mot)
{
	$result = spip_num_rows(spip_query("SELECT id_mot FROM $table WHERE id_mot=$id_mot AND $table_id=$id_objet"));

	if (!$result) {
		spip_query("INSERT INTO $table (id_mot,$table_id) VALUES ($id_mot, $id_objet)");
	}
	return $result;
}


// http://doc.spip.org/@recherche_mot_cle
function recherche_mot_cle($cherche_mots, $id_groupe, $objet, $id_objet, $table, $table_id, $url_base)
{
	if ($table == 'articles') $ou = _T('info_l_article');
	else if ($table == 'breves') $ou = _T('info_la_breve');
	else if ($table == 'rubriques') $ou = _T('info_la_rubrique');

	$result = spip_query("SELECT id_mot, titre FROM spip_mots WHERE id_groupe=" . _q($id_groupe));

	$table_mots = array();
	$table_ids = array();
	while ($row = spip_fetch_array($result)) {
			$table_ids[] = $row['id_mot'];
			$table_mots[] = $row['titre'];
	}

	$nouveaux_mots = array();
	$res = '';

	foreach (split(" *[,;] *", $cherche_mots) as $cherche_mot) {
	  if  ($cherche_mot) {
		$resultat = mots_ressemblants($cherche_mot, $table_mots, $table_ids);
		$res .= "<br />" . debut_boite_info(true);
		if (!$resultat) {
			$res .= "<b>"._T('info_non_resultat', array('cherche_mot' => htmlspecialchars($cherche_mot)))."</b><br />";
		}
		else if (count($resultat) == 1) {
			$nouveaux_mots[] = $resultat[0];
			$row = spip_fetch_array(spip_query("SELECT titre FROM spip_mots WHERE id_mot=$resultat[0]"));
			$res .= "<b>"._T('info_mot_cle_ajoute')." $ou : </b><br />\n<ul>";
			$res .= "\n<li><span class='verdana1 spip_small'><b><span class='spip_medium'>".typo($row['titre'])."</span></b></span></li>";
			$res .= "\n</ul>";
		}
		else $res .= affiche_mots_ressemblant($cherche_mot, $objet, $id_objet, $resultat, $table, $table_id, $url_base);

/*		if (autoriser('modifier','groupemots',$id_groupe)) {
			$titre = rawurlencode($cherche_mot);
			$res .= "<div style='width: 200px;'>";
			$res .= icone_horizontale(_T('icone_creer_mot_cle'), generer_url_ecrire("mots_edit","new=oui&id_groupe=$id_groupe&ajouter_id_article=$id_objet&table=$table&table_id=$table_id&titre=$titre&redirect=" . generer_url_retour($url_base, "$table_id=$id_objet")), "mot-cle-24.gif", "creer.gif", false);
			$res .= "</div> ";
		}
*/

		$res .= fin_boite_info(true) . "<br />";
	  }
	}
	return array($res, $nouveaux_mots);
}

// http://doc.spip.org/@afficher_mots_cles
function afficher_mots_cles($flag_editable, $objet, $id_objet, $table, $table_id, $url_base, $visible)
{
	global $connect_statut, $connect_toutes_rubriques;

	$les_mots = array();
	$id_groupes_vus = array();
	$groupes_vus = array();
	$result = spip_query("SELECT mots.id_mot, mots.titre, mots.descriptif, mots.id_groupe FROM spip_mots AS mots, spip_mots_$table AS lien WHERE lien.$table_id=$id_objet AND mots.id_mot=lien.id_mot ORDER BY mots.type, mots.titre");
	if (spip_num_rows($result) > 0) {
	
		$tableau= array();
		$cle = http_img_pack('petite-cle.gif', "", "width='23' height='12'");
		$ret = generer_url_retour($url_base, "$table_id=$id_objet#mots");
		while ($row = spip_fetch_array($result)) {

			$id_mot = $row['id_mot'];
			$titre_mot = $row['titre'];
			$descriptif_mot = $row['descriptif'];
			$id_groupe = $row['id_groupe'];

			$id_groupes_vus[] = $id_groupe;
			$url = generer_url_ecrire('mots_edit', "id_mot=$id_mot&redirect=$ret");
			$vals= array("<a href='$url'>$cle</a>");
			

			$row_groupe = spip_fetch_array(spip_query("SELECT titre, unseul, obligatoire, minirezo, comite FROM spip_groupes_mots WHERE id_groupe = $id_groupe"));
	// On recupere le typo_mot ici, et non dans le mot-cle lui-meme; sinon bug avec arabe

			$type_mot = typo($row_groupe['titre']);
			$flag_groupe = ($flag_editable AND
					((($connect_statut === '1comite') AND $row_groupe['comite'] === 'oui') OR (($connect_statut === '0minirezo') AND $row_groupe['minirezo'] === 'oui')));

			// Changer
			if (($row_groupe['unseul'] == "oui") AND $flag_groupe) {
				$vals[]= formulaire_mot_remplace($id_groupe, $id_mot, $url_base, $table, $table_id, $objet, $id_objet);
			} else {
				$vals[]= "<a href='$url'>".typo($titre_mot)."</a>";
			}

			if ($connect_toutes_rubriques)
				$vals[]= "<a href='" . generer_url_ecrire("mots_type","id_groupe=$id_groupe") . "'>$type_mot</a>";

			else	$vals[] = $type_mot;
	
			if ($flag_editable){
				if ($flag_groupe) {
					$s =  _T('info_retirer_mot')
					. "&nbsp;"
					. http_img_pack('croix-rouge.gif', "X", " class='puce' style='vertical-align: bottom;'");
					$s = ajax_action_auteur('editer_mot', "$id_objet,$id_mot,$table,$table_id,$objet", $url_base, "$table_id=$id_objet", array($s,''),"&id_objet=$id_objet&objet=$objet");
				} else $s = "&nbsp;";
				$vals[] = $s;
			} else $vals[]= "";

			$tableau[] = $vals;
	
			$les_mots[] = $id_mot;
		}
	
		$largeurs = array('25', '', '', '');
		$styles = array('arial11', 'arial2', 'arial2', 'arial1');

		$res = "\n<div class='liste'>"
		. "\n<table width='100%' cellpadding='3' cellspacing='0' border='0'>"
		. afficher_liste($largeurs, $tableau, $styles)
		. "</table></div>";
	} else $res ='';

	if ($flag_editable)
	  $res .= formulaire_mots_cles($id_groupes_vus, $id_objet, $les_mots, $table, $table_id, $url_base, $visible, $objet);

	return $res;
}

// http://doc.spip.org/@formulaire_mot_remplace
function formulaire_mot_remplace($id_groupe, $id_mot, $url_base, $table, $table_id, $objet, $id_objet)
{
	$result = spip_query("SELECT id_mot, titre FROM spip_mots WHERE id_groupe = $id_groupe ORDER by titre");

	$s = '';

	while ($row_autres = spip_fetch_array($result)) {
		$id = $row_autres['id_mot'];
		$le_titre_mot = supprimer_tags(typo($row_autres['titre']));
		$selected = ($id == $id_mot) ? " selected='selected'" : "";
		$s .= "\n<option value='$id'$selected> $le_titre_mot</option>";
	}

	$ancre = "valider_groupe_$id_groupe"; 
	// forcer le recalcul du noeud car on est en Ajax
	$jscript1 = "findObj_forcer('$ancre').style.visibility='visible';";

	$corps = "\n<select name='nouv_mot' onchange=\"$jscript1\""
	. " class='fondl spip_xx-small' style='width:90px;'>"
	. $s
	. "</select>\n&nbsp;" ;

	$t =  _T('bouton_changer');

	return ajax_action_post('editer_mot', "$id_objet,$id_mot,$table,$table_id,$objet", $url_base, "$table_id=$id_objet",$corps, $t, " class='fondo spip_xx-small visible_au_chargement' id='$ancre'", "", "&id_objet=$id_objet&objet=$objet");
}


// http://doc.spip.org/@formulaire_mots_cles
function formulaire_mots_cles($id_groupes_vus, $id_objet, $les_mots, $table, $table_id, $url_base, $visible, $objet) {
	global $connect_statut, $spip_lang, $spip_lang_right, $spip_lang_rtl;

	if ($les_mots) {
		$nombre_mots_associes = count($les_mots);
		$les_mots = join($les_mots, ",");
	} else {
		$les_mots = "0";
		$nombre_mots_associes = 0;
	}
	$cond_id_groupes_vus = "0";
	if ($id_groupes_vus) $cond_id_groupes_vus = join(",",$id_groupes_vus);
	
	$nb_groupes = spip_num_rows(spip_query("SELECT * FROM spip_groupes_mots WHERE $table = 'oui' AND ".substr($connect_statut,1)." = 'oui' AND obligatoire = 'oui' AND id_groupe NOT IN ($cond_id_groupes_vus)"));

	if ($visible)
		$res = debut_block_visible("lesmots");
	else if ($nb_groupes > 0) {
		$res = debut_block_visible("lesmots");
			// vilain hack pour redresser un triangle
		$couche_a_redresser = $GLOBALS['numero_block']['lesmots'];
		if ($GLOBALS['browser_layer'])
			$res .= http_script("
				triangle = findObj('triangle' + $couche_a_redresser);
				if (triangle) triangle.src = '" . _DIR_IMG_PACK . "deplierbas$spip_lang_rtl.gif';");
	} else $res = debut_block_invisible("lesmots");

	if ($nombre_mots_associes > 3) {
		$res .= "<div style='text-align: right' class='arial1'>"
		  . ajax_action_auteur('editer_mot', "$id_objet,-1,$table,$table_id,$objet", $url_base, "$table_id=$id_objet", array(_T('info_retirer_mots'),''),"&id_objet=$id_objet&objet=$objet")
		. "</div><br />\n";
	}

	$result_groupes = spip_query("SELECT id_groupe,unseul,obligatoire,titre, ".creer_objet_multi ("titre", $spip_lang)." FROM spip_groupes_mots WHERE $table = 'oui' AND ".substr($connect_statut,1)." = 'oui' AND (unseul != 'oui'  OR (unseul = 'oui' AND id_groupe NOT IN ($cond_id_groupes_vus))) ORDER BY multi");

	// Afficher un menu par groupe de mots
	$ajouter ='';
	while ($row = spip_fetch_array($result_groupes)) {
		if ($menu = menu_mots($row, $id_groupes_vus, $les_mots)) {
			$id_groupe = $row['id_groupe'];
			list($corps, $clic) = $menu;

			$ajouter .= ajax_action_post('editer_mot',
				"$id_objet,,$table,$table_id,$objet",
				$url_base,
				"$table_id=$id_objet",
				$corps,
				$clic,
				" class='visible_au_chargement fondo spip_xx-small' id='valider_groupe_$id_groupe'", "",
				"&id_objet=$id_objet&objet=$objet&select_groupe=$id_groupe");
		}
	}
	if ($ajouter) {
		$message = "<span class='verdana1'><b>"._T('titre_ajouter_mot_cle')."</b></span>\n";
		$res .= "<div style='float:$spip_lang_right; width:280px;position:relative;display:inline;'>"
			. $ajouter
			."</div>\n" ;
	}

	if (autoriser('modifier','groupemots')) {
		$titre = _request('cherche_mot')
			? "&titre=".rawurlencode(_request('cherche_mot')) : '';
		$bouton_ajouter = icone_horizontale(_T('icone_creer_mot_cle'), generer_url_ecrire("mots_edit","new=oui&ajouter_id_article=$id_objet&table=$table&table_id=$table_id$titre&redirect=" . generer_url_retour($url_base, "$table_id=$id_objet")), "mot-cle-24.gif", "creer.gif", false)
		. "\n";
	}

	if ($message OR $bouton_ajouter) {
		$res .= "<div style='width:170px;'>$message
			<br />$bouton_ajouter</div>\n";
	}

	return $res . fin_block();
}


// http://doc.spip.org/@menu_mots
function menu_mots($row, $id_groupes_vus, $les_mots)
{
	$rand = rand(0,10000); # pour antifocus & ajax

	$id_groupe = $row['id_groupe'];

	$result = spip_query("SELECT COUNT(id_mot) FROM spip_mots WHERE id_groupe =$id_groupe " . ($les_mots ? "AND id_mot NOT IN ($les_mots) " : ''));

	list($n) = spip_fetch_array($result, SPIP_NUM);
	if (!$n) return '';

	$titre = textebrut(typo($row['titre']));
	$titre_groupe = entites_html($titre);
	$unseul = $row['unseul'] == 'oui';
	$obligatoire = $row['obligatoire']=='oui' AND !in_array($id_groupe, $id_groupes_vus);

	$res = '';
	$ancre = "valider_groupe_$id_groupe"; 

	// forcer le recalcul du noeud car on est en Ajax
	$jscript1 = "findObj_forcer('$ancre').style.visibility='visible';";
	$jscript2 = "if(!antifocus_mots['$rand-$id_groupe']){this.value='';antifocus_mots['$rand-$id_groupe']=true;}";

	if ($n > 50) {
		$jscript = "onfocus=\"$jscript1 $jscript2\"";

		if ($obligatoire)
			$res .= "<input type='text' name='cherche_mot' class='fondl' style='width: 180px; background-color:#E86519;' value=\"$titre_groupe\" size='20' $jscript />";
		else if ($unseul)
			$res .= "<input type='text' name='cherche_mot' class='fondl' style='width: 180px; background-color:#cccccc;' value=\"$titre_groupe\" size='20' $jscript />";
		else
			$res .= "<input type='text' name='cherche_mot'  class='fondl' style='width: 180px; ' value=\"$titre_groupe\" size='20' $jscript />";

		$res .= "<input type='hidden' name='select_groupe'  value='$id_groupe' />&nbsp;";
		return array($res, _T('bouton_chercher')); 
	} else {

		$jscript = "onchange=\"$jscript1\"";
	  
		if ($obligatoire)
			$res .= "<select name='nouv_mot' size='1' style='width: 180px; background-color:#E86519;' class='fondl' $jscript>";
		else if ($unseul)
			$res .= "<select name='nouv_mot' size='1' style='width: 180px; background-color:#cccccc;' class='fondl' $jscript>";
		else
			$res .= "<select name='nouv_mot' size='1' style='width: 180px; ' class='fondl' $jscript>";

		$res .= "\n<option value='x' style='font-variant: small-caps;'>$titre</option>";

		$result = spip_query("SELECT id_mot, type, titre FROM spip_mots WHERE id_groupe =$id_groupe " . ($les_mots ? "AND id_mot NOT IN ($les_mots) " : '') .  "ORDER BY titre");


		while($row = spip_fetch_array($result)) {
			$res .= "\n<option value='" .$row['id_mot'] .
				"'>&nbsp;&nbsp;&nbsp;" .
				textebrut(typo($row['titre'])) .
				"</option>";
		}
		$res .= "</select>&nbsp;";
		return array($res, _T('bouton_choisir'));
	}

}
?>
