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

include_spip('inc/presentation');
include_spip('base/abstract_sql');
include_spip('inc/mots');

// http://doc.spip.org/@exec_message_dist
function exec_message_dist()
{
global 
$ajout_forum,
$annee,
$annee_fin,
$change_statut,
$changer_rv,
$cherche_auteur,
$connect_id_auteur,
$forcer_dest,
$heures,
$heures_fin,
$id_message,
$jour,
$jour_fin,
$minutes,
$minutes_fin,
$modifier_message,
$mois,
$mois_fin,
$nouv_auteur,
$rv,
$texte,
$titre;

$id_message = intval($id_message);
$nouv_auteur = intval($nouv_auteur);
charger_generer_url();

$row = spip_fetch_array(spip_query("SELECT type FROM spip_messages WHERE id_message=$id_message"));

if ($row['type'] != "affich"){
	$n = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM spip_auteurs_messages WHERE id_auteur=$connect_id_auteur AND id_message=$id_message"));
	if (!$n['n']) {
		$commencer_page = charger_fonction('commencer_page', 'inc');
		echo $commencer_page(_T('info_acces_refuse'));
		debut_gauche();
		debut_droite();
		echo "<b>"._T('avis_non_acces_message')."</b><p>";
		echo fin_gauche(), fin_page();
		exit;
	}
}

if ($ajout_forum AND strlen($texte) > 10 AND strlen($titre) > 2) {
	spip_query("UPDATE spip_auteurs_messages SET vu='non' WHERE id_message='$id_message'");
}

if ($modifier_message == "oui") {
	spip_query("UPDATE spip_messages SET titre=" . _q($titre) . ", texte=" . _q($texte) . " WHERE id_message='$id_message'");
}

if ($changer_rv) {
  spip_query("UPDATE spip_messages SET rv=" . _q($rv) . " WHERE id_message='$id_message'");
}

if ($jour)
  change_date_message($id_message, $heures,$minutes,$mois, $jour, $annee, $heures_fin,$minutes_fin,$mois_fin, $jour_fin, $annee_fin);

if ($change_statut) {
	spip_query("UPDATE spip_messages SET statut=" . _q($change_statut) . " WHERE id_message='$id_message'");
	spip_query("UPDATE spip_messages SET date_heure=NOW() WHERE id_message='$id_message' AND rv<>'oui'");
}


 exec_affiche_message_dist($id_message,  $cherche_auteur, $nouv_auteur, $forcer_dest);
}



// http://doc.spip.org/@http_afficher_rendez_vous
function http_afficher_rendez_vous($date_heure, $date_fin)
{
  global $spip_lang_rtl;

	$dirpuce = _DIR_RACINE . 'dist';
	if (jour($date_heure) == jour($date_fin) AND mois($date_heure) == mois($date_fin) AND annee($date_heure) == annee($date_fin)) {		
	  echo "<p class='verdana2' style='text-align: center'>"._T('titre_rendez_vous')." ".majuscules(nom_jour($date_heure))." <b>".majuscules(affdate($date_heure))."</b><br />\n<b>".heures($date_heure)." "._T('date_mot_heures')." ".minutes($date_heure)."</b>";
	  echo " &nbsp; <img src='$dirpuce/puce$spip_lang_rtl.gif' alt=' ' style='border: 0px;' /> &nbsp;  ".heures($date_fin)." "._T('date_mot_heures')." ".minutes($date_fin)."</p>";
	} else {
	  echo "<p class='verdana2' style='text-align: center'>"._T('titre_rendez_vous')."<br />\n".majuscules(nom_jour($date_heure))." <b>".majuscules(affdate($date_heure))."</b>, <b>".heures($date_heure)." "._T('date_mot_heures')." ".minutes($date_heure)."</b>";
	  echo "<br />\n<img src='$dirpuce/puce$spip_lang_rtl.gif' alt=' ' style='border: 0px;' /> ".majuscules(nom_jour($date_fin))." ".majuscules(affdate($date_fin)).", <b>".heures($date_fin)." "._T('date_mot_heures')." ".minutes($date_fin)."</b></p>";
	}
}

// http://doc.spip.org/@sql_nouveau_participant
function sql_nouveau_participant($nouv_auteur, $id_message)
{
	spip_query("DELETE FROM spip_auteurs_messages WHERE id_auteur='$nouv_auteur' AND id_message='$id_message'");
	spip_abstract_insert('spip_auteurs_messages',
		"(id_auteur,id_message,vu)",
		"('$nouv_auteur','$id_message','non')");
}

// http://doc.spip.org/@http_auteurs_ressemblants
function http_auteurs_ressemblants($cherche_auteur, $id_message)
{
  global $connect_id_auteur;
  $query = spip_query("SELECT id_auteur, nom FROM spip_auteurs WHERE messagerie<>'non' AND id_auteur<>'$connect_id_auteur' AND pass<>'' AND login<>''");
  $table_auteurs = array();
  $table_ids = array();
  while ($row = spip_fetch_array($query)) {
    $table_auteurs[] = $row['nom'];
    $table_ids[] = $row['id_auteur'];
  }
  $resultat =  mots_ressemblants($cherche_auteur, $table_auteurs, $table_ids);
  if (!$resultat) {
    return '<b>' . _T('info_recherche_auteur_zero', array('cherche_auteur' => $cherche_auteur))."</b><br />";
  }
  else if (count($resultat) == 1) {
    list(, $nouv_auteur) = each($resultat);
    sql_nouveau_participant($nouv_auteur, $id_message);
    $row = spip_fetch_array(spip_query("SELECT nom FROM spip_auteurs WHERE id_auteur=$nouv_auteur"));
    $nom_auteur = $row['nom'];
    return "<b>"._T('info_ajout_participant')."</b><br />" .
      "<ul><li><span class='verdana1 spip_small'><b><span class='spip_medium'>$nom_auteur</span></b></span>\n</ul>";
  }
  else if (count($resultat) < 16) {
    $res = '';
    $query = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur IN (" . join(',', $resultat) . ") ORDER BY nom");

    while ($row = spip_fetch_array($query)) {
      $id_auteur = $row['id_auteur'];
      $nom_auteur = $row['nom'];
      $email_auteur = $row['email'];
      $bio_auteur = $row['bio'];
      $res .= "<li><span class='spip_medium' style='class='verdana1'><b>$nom_auteur</b></span>" .
	($email_auteur ? " ($email_auteur)" : '') .
	" | <a href='" . redirige_action_auteur("editer_message","$id_message/@$id_auteur", 'message', "id_message=$id_message")
	. "'>" 
	. _T('lien_ajout_destinataire').
	"</a>" .
	(!trim($bio_auteur) ? '' :
	 ("<br /><span class='spip_x-small'>".propre(couper($bio_auteur, 100))."</span>\n")) .
	"</font></li>\n";
    }
    return  "<b>"._T('info_recherche_auteur_ok', array('cherche_auteur' => $cherche_auteur))."</b><br /><ul>$res</ul>";
  }
  else {
    return "<b>"._T('info_recherche_auteur_a_affiner', array('cherche_auteur' => $cherche_auteur))."</b><br />";
  }
}

// http://doc.spip.org/@http_visualiser_participants
function http_visualiser_participants($auteurs_tmp)
{
  return "\n<table border='0' cellspacing='0' cellpadding='3' width='100%'><tr><td style='background-color: #EEEECC'>" .
    bouton_block_invisible("auteurs,ajouter_auteur") .
    "<span class='serif2'><b>" .
    _T('info_nombre_partcipants') .
    "</b></span>" .
    ((count($auteurs_tmp) == 0) ? '' :
     (" <span class='arial2'>".join($auteurs_tmp,", ")."</span>")) .
    "</td></tr></table>\n";
}

// http://doc.spip.org/@http_ajouter_participants
function http_ajouter_participants($ze_auteurs, $id_message)
{	
    $result_ajout_auteurs = spip_query("SELECT * FROM spip_auteurs WHERE " . (!$ze_auteurs ? '' : "id_auteur NOT IN ($ze_auteurs) AND ") . " messagerie<>'non' AND statut IN ('0minirezo', '1comite') ORDER BY statut, nom");

    if (spip_num_rows($result_ajout_auteurs) > 0) {

      echo "<div align='left'>";
      echo generer_url_post_ecrire('message');
      echo "<span class='verdana1 spip_small'><b>", _T('bouton_ajouter_participant')," &nbsp; </b></span>\n",
	"<input type='hidden' name='id_message' value=\"$id_message\" />";

      if (spip_num_rows($result_ajout_auteurs) > 50) {
	echo "\n<input type='text' name='cherche_auteur' class='fondl' value='' size='20' />";
	echo "\n<input type='submit' name='Chercher' value='"._T('bouton_chercher')."' class='fondo' />";
      }
      else {
	echo "<select name='nouv_auteur' size='1' style='width: 150px' class='fondl'>";
	$group = false;
	$group2 = false;
	
	while($row=spip_fetch_array($result_ajout_auteurs)) {
	  $id_auteur = $row['id_auteur'];
	  $nom = $row['nom'];
	  $email = $row['email'];
	  $statut_auteur = $row['statut'];
	  
	  $statut_auteur=ereg_replace("0minirezo", _T('info_statut_administrateur'), $statut_auteur);
	  $statut_auteur=ereg_replace("1comite", _T('info_statut_redacteur'), $statut_auteur);
	  $statut_auteur=ereg_replace("2redac", _T('info_statut_redacteur'), $statut_auteur);
	  $statut_auteur=ereg_replace("5poubelle", _T('info_statut_efface'), $statut_auteur);
	  
	  $premiere = strtoupper(substr(trim($nom), 0, 1));

	  if ($GLOBALS['connect_statut'] != '0minirezo') {
	    if ($p = strpos($email, '@')) $email = substr($email, 0, $p).'@...';
	  }

	  if ($statut_auteur != $statut_old) {
	    echo "\n<option value=\"x\"></option>";
	    echo "\n<option value=\"x\"> $statut_auteur".'s</option>';
	  }
						
	  if ($premiere != $premiere_old AND ($statut_auteur != _T('info_administrateur') OR !$premiere_old)) {
	    echo "\n<option value=\"x\"></option>";
	  }
	  
	  $texte_option = supprimer_tags(couper("$nom ($email) ", 40));
	  echo "\n<option value=\"$id_auteur\">&nbsp;&nbsp;&nbsp;&nbsp;$texte_option</option>";
	  $statut_old = $statut_auteur;
	  $premiere_old = $premiere;
	}
	
	echo "</select>";
	echo "<input type='submit' name='Ajouter' value='"._T('bouton_ajouter')."' class='fondo' />";
      }
      echo "</form></div>";
    }
}

// http://doc.spip.org/@http_afficher_forum_perso
function http_afficher_forum_perso($id_message)
{

	echo "<br /><br />\n<div align='center'>";
	icone(_T('icone_poster_message'), generer_url_ecrire("forum_envoi", "statut=perso&id=$id_message&script=message"). '#formulaire', "forum-interne-24.gif", "creer.gif");
	echo  "</div>\n<p align='left'>";

	$query_forum = spip_query("SELECT * FROM spip_forum WHERE statut='perso' AND id_message='$id_message' AND id_parent=0 ORDER BY date_heure DESC LIMIT 20");
	echo afficher_forum($query_forum, "message","id_message=$id_message");
	echo "\n</p>";
}


// http://doc.spip.org/@http_message_avec_participants
function http_message_avec_participants($id_message, $statut, $forcer_dest, $nouv_auteur, $cherche_auteur, $expediteur='')
{
	global $connect_id_auteur, $couleur_claire ;
	echo debut_cadre_enfonce("redacteurs-24.gif", true);

	if ($cherche_auteur) {
			echo "\n<p align='left'><div class='cadre-info'>" .
			  http_auteurs_ressemblants($cherche_auteur , $id_message) .
			  "\n</div></p>";
	  }

	if ($nouv_auteur > 0) sql_nouveau_participant($nouv_auteur, $id_message);

		//
		// Liste des participants
		//

	$result_auteurs = spip_query("SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE lien.id_message=$id_message AND lien.id_auteur=auteurs.id_auteur");

	$total_dest = spip_num_rows($result_auteurs);

	if ($total_dest > 0) {
			$couleurs = array("#FFFFFF",$couleur_claire);
			$auteurs_tmp = array();
			$ze_auteurs = array();
			$ifond = 0;
			$res = '';
			while($row = spip_fetch_array($result_auteurs)) {
				$id_auteur = $row["id_auteur"];
				$nom_auteur = typo($row["nom"]);
				$statut_auteur = $row["statut"];
				$ze_auteurs[] = $id_auteur;

				$couleur = $couleurs[$ifond];
				$ifond = 1 - $ifond;

				$auteurs_tmp[] = "<a href='" .
				  generer_url_ecrire('auteurs_edit',"id_auteur=" . $id_auteur) ."'>". $nom_auteur . "</a>";

				$aut =  (($id_auteur != $expediteur) ? '' :
					 ("<span class='arial0'>".  _T('info_auteur_message') ."</span> "));

				$res .= "<tr><td style='background-color: $couleur'><span class='verdana1 spip_small'>&nbsp;". bonhomme_statut($row)."&nbsp;" .  $aut .	  $nom_auteur .  "</span></td>" .
				  "<td style='background-color: $couleur' align='right'><span class='verdana1 spip_x-small'>" . (($id_auteur == $connect_id_auteur) ?  "&nbsp;" : ("[<a href='" . redirige_action_auteur("editer_message","$id_message/-$id_auteur", 'message', "id_message=$id_message") . "'>"._T('lien_retrait_particpant')."</a>]")) .  "</span></td></tr>\n";
			}
			echo
			  http_visualiser_participants($auteurs_tmp),
			  debut_block_invisible("auteurs"),
			  "\n<table border='0' cellspacing='0' cellpadding='3' width='100%'>",
			  $res,
			    "</table>\n",
			  fin_block();
	  }

	  if ($statut == 'redac' OR $forcer_dest)
		  http_ajouter_participants(join(',', $ze_auteurs),
					    $id_message);
	  else {
		  echo
		    debut_block_invisible("ajouter_auteur"),
		    "<br /><div align='right'><span class='verdana1 spip_small'><a href='" . generer_url_ecrire("message","id_message=$id_message&forcer_dest=oui") . "'>"._T('lien_ajouter_participant')."</a></span></div>",
		    fin_block();
		}
	  fin_cadre_enfonce();
	  return $total_dest;
}

// http://doc.spip.org/@http_affiche_message
function http_affiche_message($id_message, $expediteur, $statut, $type, $texte, $titre, $rv, $date_heure, $date_fin, $cherche_auteur, $nouv_auteur, $forcer_dest)
{
  global $connect_id_auteur,$connect_statut, $les_notes;

	if ($type == 'normal') {
		$le_type = _T('info_message_2').aide ("messut");
		$la_couleur = "#02531b";
		$couleur_fond = "#cffede";
	}
	else if ($type == 'pb') {
		$le_type = _T('info_pense_bete').aide ("messpense");
		$la_couleur = "#3874b0";
		$couleur_fond = "#edf3fe";
	}
	else if ($type == 'affich') {
		$le_type = _T('info_annonce');
		$la_couleur = "#ccaa00";
		$couleur_fond = "#ffffee";
	}
	
	// affichage des caracteristiques du message

	echo "<div style='border: 1px solid $la_couleur; background-color: $couleur_fond; padding: 5px;'>"; // debut cadre de couleur
	//debut_cadre_relief("messagerie-24.gif");
	echo "\n<table width='100%' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr><td>"; # uniques

	echo "<span style='color: $la_couleur' class='verdana1 spip_small'><b>$le_type</b></span><br />";
	echo "<span class='verdana1 spip_large'><b>$titre</b></span>";
	if ($statut == 'redac') {
		echo "<br /><span style='color: red;' class='verdana1 spip_small'><b>"._T('info_redaction_en_cours')."</b></span>";
	}
	else if ($rv == 'non') {
		echo "<br /><span style='color: #666666;' class='verdana1 spip_small'><b>".nom_jour($date_heure).' '.affdate_heure($date_heure)."</b></span>";
	}


	//////////////////////////////////////////////////////
	// Message avec participants
	//
	
	if ($type == 'normal') {
	  $total_dest = http_message_avec_participants($id_message, $statut, $forcer_dest, $nouv_auteur, $cherche_auteur, $expediteur);
	}

	if ($rv != "non") http_afficher_rendez_vous($date_heure, $date_fin);


	//////////////////////////////////////////////////////
	// Le message lui-meme
	//

	echo "<div align='left'>",
	  "\n<table width='100%' cellpadding='0' cellspacing='0' border='0'>",
	  "<tr><td>",
	  "<div class='serif'><p>$texte</p></div>";

	if ($les_notes) {
		echo debut_cadre_relief();
		echo "<div $dir_lang class='arial11'>";
		echo justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes);
		echo "</div>";
		echo fin_cadre_relief();
	}

	if ($expediteur == $connect_id_auteur AND $statut == 'redac') {
	  if ($type == 'normal' AND $total_dest < 2) {
	    echo "<p style='color: #666666; text-align: right;' class='verdana1 spip_small'><b>"._T('avis_destinataire_obligatoire')."</b></p>";
	  } else {
	    echo "\n<div align='center'><table><tr><td>";
	    icone (_T('icone_envoyer_message'), (generer_url_ecrire("message","id_message=$id_message&change_statut=publie")), "messagerie-24.gif", "creer.gif");
	    echo "</td></tr></table></div>";
	  }
	}
	echo "</td></tr></table>\n</div>";	

	echo "</td></tr></table>\n"; //fin_cadre_relief();
	echo "</div>";			// fin du cadre de couleur
	
	// Les boutons

	$aut = ($expediteur == $connect_id_auteur);
	$aff = ($type == 'affich' AND $connect_statut == '0minirezo');

	echo "\n<table width='100%'><tr><td>";

	// bouton de suppression

	if ($aut AND ($statut == 'redac' OR $type == 'pb') OR $aff) {
	  echo "\n<table align='left'><tr><td>";
	  icone (_T('icone_supprimer_message'), redirige_action_auteur("editer_message","-$id_message", 'messagerie'), "messagerie-24.gif", "supprimer.gif");
	  echo "</td></tr></table>";
	}

	// bouton retrait de la discussion

	if ($statut == 'publie' AND $type == 'normal') {
	  echo "\n<table align='left'><tr><td>";
	  icone (_T('icone_arret_discussion'), redirige_action_auteur("editer_message","$id_message/-$connect_id_auteur", 'messagerie', "id_message=$id_message"), "messagerie-24.gif", "supprimer.gif");
	  echo "</td></tr></table>";
	}

	// bouton modifier ce message

	if ($aut OR $aff) {
	  echo "\n<table align='right'><tr><td>";
	  icone (_T('icone_modifier_message'), (generer_url_ecrire("message_edit","id_message=$id_message")), "messagerie-24.gif", "edit.gif");
	  echo "</td></tr></table>";
	}
	echo "</td></tr></table>";
}

// Convertir dates a calendrier correct (exemple: 31 fevrier devient debut mars, 24h12 devient 00h12 du lendemain)

// http://doc.spip.org/@change_date_message
function change_date_message($id_message, $heures,$minutes,$mois, $jour, $annee, $heures_fin,$minutes_fin,$mois_fin, $jour_fin, $annee_fin)
{
			$date = date("Y-m-d H:i:s", mktime($heures,$minutes,0,$mois, $jour, $annee));
			
			$jour = journum($date);
			$mois = mois($date);
			$annee = annee($date);
			$heures = heures($date);
			$minutes = minutes($date);
			
			// Verifier que la date de fin est bien posterieure au debut
			$unix_debut = date("U", mktime($heures,$minutes,0,$mois, $jour, $annee));
			$unix_fin = date("U", mktime($heures_fin,$minutes_fin,0,$mois_fin, $jour_fin, $annee_fin));
			if ($unix_fin <= $unix_debut) {
				$jour_fin = $jour;
				$mois_fin = $mois;
				$annee_fin = $annee;
				$heures_fin = $heures + 1;
				$minutes_fin = $minutes;
			}		

			$date_fin = date("Y-m-d H:i:s", mktime($heures_fin,$minutes_fin,0,$mois_fin, $jour_fin, $annee_fin));
			
			$jour_fin = journum($date_fin);
			$mois_fin = mois($date_fin);
			$annee_fin = annee($date_fin);
			$heures_fin = heures($date_fin);
			$minutes_fin = minutes($date_fin);
			

	spip_query("UPDATE spip_messages SET date_heure='$annee-$mois-$jour $heures:$minutes:00',  date_fin='$annee_fin-$mois_fin-$jour_fin $heures_fin:$minutes_fin:00' WHERE id_message='$id_message'");
}


// http://doc.spip.org/@exec_affiche_message_dist
function exec_affiche_message_dist($id_message, $cherche_auteur, $nouv_auteur, $forcer_dest)
{
  global $connect_id_auteur, $echelle, $partie_cal;
  $row = spip_fetch_array(spip_query("SELECT * FROM spip_messages WHERE id_message=$id_message"));
  if ($row) {
	$id_message = $row['id_message'];
	$date_heure = $row["date_heure"];
	$date_fin = $row["date_fin"];
	$titre = typo($row["titre"]);
	$texte = propre($row["texte"]);
	$type = $row["type"];
	$statut = $row["statut"];
	$rv = $row["rv"];
	$expediteur = $row['id_auteur'];

	$lejour=journum($row['date_heure']);
	$lemois = mois($row['date_heure']);		
	$lannee = annee($row['date_heure']);		

	
	// Marquer le message vu pour le visiteur
	if ($type != "affich")
		spip_query("UPDATE spip_auteurs_messages SET vu='oui' WHERE id_message='$id_message' AND id_auteur='$connect_id_auteur'");

	$commencer_page = charger_fonction('commencer_page', 'inc');
	echo $commencer_page($titre, "accueil", "messagerie");

	debut_gauche();
	
	if ($rv != 'non')
	  echo http_calendrier_agenda ($lannee, $lemois, $lejour, $lemois, $lannee,false, generer_url_ecrire('calendrier'));
	
	echo "<br />";
	
	echo  http_calendrier_rv(sql_calendrier_taches_annonces(),"annonces");
	echo  http_calendrier_rv(sql_calendrier_taches_pb(),"pb");
	echo  http_calendrier_rv(sql_calendrier_taches_rv(), "rv");

	if ($rv != "non") {
		list ($sh, $ah) = sql_calendrier_interval(sql_calendrier_jour($lannee,$lemois, $lejour));
		foreach ($ah as $k => $v)
		  {
		    foreach ($v as $l => $e)
		      {
			if (ereg("=$id_message$", $e['URL']))
			  {
			    $ah[$k][$l]['CATEGORIES'] = "calendrier-nb";
			    break;
			  }
		      }
		  }
		creer_colonne_droite();	

		echo http_calendrier_ics_titre($lannee,$lemois,$lejour,generer_url_ecrire('calendrier'));
		echo http_calendrier_ics($lannee,$lemois, $lejour, $echelle, $partie_cal, 90, array($sh, $ah));
	}

	debut_droite();

	http_affiche_message($id_message, $expediteur, $statut, $type, $texte, $titre, $rv, $date_heure, $date_fin, $cherche_auteur, $nouv_auteur, $forcer_dest);

	// reponses et bouton poster message

	http_afficher_forum_perso($id_message);
 }

 echo fin_gauche(), fin_page();
}

?>
