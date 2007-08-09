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
	global  $connect_id_auteur;

	$id_message = intval(_request('id_message'));
	$forcer_dest = _request('forcer_dest');
	$cherche_auteur = _request('cherche_auteur');

	$row = spip_abstract_fetch(spip_query("SELECT type FROM spip_messages WHERE id_message=$id_message"));

	if ($row['type'] != "affich"){
		$res = spip_abstract_fetch(spip_query("SELECT vu FROM spip_auteurs_messages WHERE id_auteur=$connect_id_auteur AND id_message=$id_message"));
		if (!$res) {
			include_spip('inc/minipres');
			echo minipres();
			exit;
		}
	// Marquer le message vu pour le visiteur
		if ($res['vu'] != 'oui') {
			include_spip('inc/headers');
			redirige_par_entete(redirige_action_auteur("editer_message","$id_message/:$connect_id_auteur", 'message', "id_message=$id_message", true));
		}
	}
	charger_generer_url();
	exec_affiche_message_dist($id_message, $cherche_auteur, $forcer_dest);
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

// http://doc.spip.org/@http_auteurs_ressemblants
function http_auteurs_ressemblants($cherche_auteur, $id_message)
{
  global $connect_id_auteur;
  $query = spip_query("SELECT id_auteur, nom FROM spip_auteurs WHERE messagerie<>'non' AND id_auteur<>'$connect_id_auteur' AND pass<>'' AND login<>''");
  $table_auteurs = array();
  $table_ids = array();
  while ($row = spip_abstract_fetch($query)) {
    $table_auteurs[] = $row['nom'];
    $table_ids[] = $row['id_auteur'];
  }
  $resultat =  mots_ressemblants($cherche_auteur, $table_auteurs, $table_ids);
  if (!$resultat) {
    return '<b>' . _T('info_recherche_auteur_zero', array('cherche_auteur' => $cherche_auteur))."</b><br />";
  }
  else if (count($resultat) == 1) {
    // action/editer_message a du prendre en compte ce cas
    list(, $nouv_auteur) = each($resultat);
    $row = spip_abstract_fetch(spip_query("SELECT nom FROM spip_auteurs WHERE id_auteur=$nouv_auteur"));
    $nom_auteur = $row['nom'];
    return "<b>"._T('info_ajout_participant')."</b><br />" .
      "<ul><li><span class='verdana1 spip_small'><b><span class='spip_medium'>$nom_auteur</span></b></span></li>\n</ul>";
  }
  else if (count($resultat) < 16) {
    $res = '';
    $query = spip_query("SELECT * FROM spip_auteurs WHERE id_auteur IN (" . join(',', $resultat) . ") ORDER BY nom");

    while ($row = spip_abstract_fetch($query)) {
      $id_auteur = $row['id_auteur'];
      $nom_auteur = $row['nom'];
      $email_auteur = $row['email'];
      $bio_auteur = $row['bio'];
      $res .= "\n<li><span class='spip_medium verdana1'><b>$nom_auteur</b></span>" .
	($email_auteur ? " ($email_auteur)" : '') .
	"\n <a href='" . redirige_action_auteur("editer_message","$id_message/@$id_auteur", 'message', "id_message=$id_message")
	. "'>" 
	. _T('lien_ajout_destinataire').
	"</a>" .
	(!trim($bio_auteur) ? '' :
	 ("<br />\n<span class='spip_x-small'>".propre(couper($bio_auteur, 100))."</span>\n")) .
	"</li>\n";
    }
    return  "<b>"._T('info_recherche_auteur_ok', array('cherche_auteur' => $cherche_auteur))."</b><br />" .($res ? "<ul>$res</ul>" : '');
  }
  else {
    return "<b>"._T('info_recherche_auteur_a_affiner', array('cherche_auteur' => $cherche_auteur))."</b><br />";
  }
}

// http://doc.spip.org/@http_ajouter_participants
function http_ajouter_participants($ze_auteurs, $id_message)
{	
	$result = auteurs_autorises((!$ze_auteurs ? '' : "id_auteur NOT IN ($ze_auteurs) AND  ") . "messagerie<>'non'",  "statut, nom");

	if (!spip_num_rows($result) > 0) return '';

	$res = "<span class='verdana1 spip_small'><b>" .
	  _T('bouton_ajouter_participant') ." &nbsp; </b></span>\n" .
	  "<input type='hidden' name='id_message' value=\"$id_message\" />";

	if (spip_num_rows($result) > 50) {
		$res .=  "\n<input type='text' name='cherche_auteur' class='fondl' value='' size='20' />";
		$res .=  "\n<input type='submit' value='"._T('bouton_chercher')."' class='fondo' />";
	} else {
		include_spip('inc/editer_auteurs');
		$res .=  "<select name='nouv_auteur' size='1' style='width: 150px' class='fondl'>"
		. objet_auteur_select($result)	
		.  "</select>"
		.  "<input type='submit' value='"._T('bouton_ajouter')."' class='fondo' />";
	}
	return redirige_action_auteur('editer_message', "$id_message,", 'message', "id_message=$id_message", "<div style='text-align: left'>\n$res</div>\n", " method='post'");
}

// http://doc.spip.org/@http_afficher_forum_perso
function http_afficher_forum_perso($id_message)
{

	echo "<br /><br />\n<div class='centered'>";
	echo icone_inline(_T('icone_poster_message'), generer_url_ecrire("forum_envoi", "statut=perso&id=$id_message&script=message"). '#formulaire', "forum-interne-24.gif", "creer.gif");
	echo  "</div>\n<div style='text-align: left'>";

	$query_forum = spip_query("SELECT * FROM spip_forum WHERE statut='perso' AND id_message=$id_message AND id_parent=0 ORDER BY date_heure DESC LIMIT 20");
	echo afficher_forum($query_forum, "message","id_message=$id_message");
	echo "\n</div>";
}


// http://doc.spip.org/@http_message_avec_participants
function http_message_avec_participants($id_message, $statut, $forcer_dest, $cherche_auteur, $expediteur='')
{
	global $connect_id_auteur ;

	if ($cherche_auteur) {
		echo "\n<div style='text-align: left' class='cadre-info'>"
		. http_auteurs_ressemblants($cherche_auteur , $id_message)
		. "\n</div>";
	  }
	$bouton = bouton_block_depliable(_T('info_nombre_partcipants'),true,"auteurs,ajouter_auteur");
	echo debut_cadre_enfonce("redacteurs-24.gif", true, '', $bouton, 'participants');

	//
	// Liste des participants
	//

	$result_auteurs = spip_query("SELECT auteurs.id_auteur,auteurs.nom,auteurs.bio,auteurs.email,auteurs.nom_site,auteurs.url_site,auteurs.login,auteurs.pass,auteurs.low_sec,auteurs.statut,auteurs.maj,auteurs.pgp,auteurs.htpass,auteurs.en_ligne,auteurs.imessage,auteurs.messagerie,auteurs.alea_actuel,auteurs.alea_futur,auteurs.prefs,auteurs.cookie_oubli,auteurs.source,auteurs.lang,auteurs.url_propre,auteurs.extra FROM spip_auteurs AS auteurs, spip_auteurs_messages AS lien WHERE lien.id_message=$id_message AND lien.id_auteur=auteurs.id_auteur");

	$total_dest = spip_num_rows($result_auteurs);

	if ($total_dest > 0) {
		$auteurs_tmp = array();
		$ze_auteurs = array();
		$ifond = 0;
		$res = $exp = '';
		$formater_auteur = charger_fonction('formater_auteur', 'inc');
		$t = _T('lien_retrait_particpant');
		while($row = spip_abstract_fetch($result_auteurs)) {
			$id_auteur = $row["id_auteur"];
			$nom_auteur = typo($row["nom"]);
			$ze_auteurs[] = $id_auteur;
			$class = alterner (++$ifond,'row_even','row_odd');

			$aut = "<a href='" .
				  generer_url_ecrire('auteur_infos',"id_auteur=" . $id_auteur) ."'>". $nom_auteur . "</a>";
				
			if ($id_auteur != $expediteur)
					$auteurs_tmp[] = $aut;
			else $exp = "<div><span class='arial0' style='margin-left: 10px'>".  _T('info_auteur_message') ."</span> $aut</div>";

			list($status, $mail, $nom, $site,) = $formater_auteur($id_auteur, $row);
			$res .= "<tr class='$class'>\n<td class='nom'>$status $amil $nom $site</td>" .
			  "\n<td align='right' class='lien'>" . (($id_auteur == $connect_id_auteur) ?  "&nbsp;" : ("[<a href='" . redirige_action_auteur("editer_message","$id_message/-$id_auteur", 'message', "id_message=$id_message") . "'>$t</a>]")) .  "</td></tr>\n";
		}
		echo
			debut_block_depliable(true,"auteurs"),
			"\n<table class='spip' width='100%'>",
			$res,
			  "</table>\n",
			fin_block();
	}

	if ($statut == 'redac' OR $forcer_dest)
		echo http_ajouter_participants(join(',', $ze_auteurs), $id_message);
	else {
		echo
		  debut_block_depliable(true,"ajouter_auteur"),
		  "<br />\n<div style='text-align: right' class='verdana1 spip_small'><a href='" . generer_url_ecrire("message","id_message=$id_message&forcer_dest=oui") . "'>"._T('lien_ajouter_participant')."</a></div>",
		  fin_block();
	}
	echo fin_cadre_enfonce(true);
	return $total_dest;
}

// http://doc.spip.org/@http_affiche_message
function http_affiche_message($id_message, $expediteur, $statut, $type, $texte, $titre, $rv, $date_heure, $date_fin, $cherche_auteur, $forcer_dest)
{
  global $connect_id_auteur,$connect_statut, $les_notes; 

	if ($type == 'normal') {
		$le_type = _T('info_message_2').aide ("messut");
		$la_couleur = "#02531b";
		$fond = "#cffede";
	}
	else if ($type == 'pb') {
		$le_type = _T('info_pense_bete').aide ("messpense");
		$la_couleur = "#3874b0";
		$fond = "#edf3fe";
	}
	else if ($type == 'affich') {
		$le_type = _T('info_annonce');
		$la_couleur = "#ccaa00";
		$fond = "#ffffee";
	}
	
	// affichage des caracteristiques du message

	echo "<div style='border: 1px solid $la_couleur; background-color: $fond; padding: 5px;'>"; // debut cadre de couleur
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
	
	if ($type == 'normal')
	  $total_dest = http_message_avec_participants($id_message, $statut, $forcer_dest, $cherche_auteur, $expediteur);

	if ($rv != "non") http_afficher_rendez_vous($date_heure, $date_fin);


	//////////////////////////////////////////////////////
	// Le message lui-meme
	//

	echo "\n<br />"
	  . "<div class='serif'>$texte</div>";

	if ($les_notes) {
		echo debut_cadre_relief();
		echo "<div dir=" . lang_dir() ."' class='arial11'>";
		echo justifier("<b>"._T('info_notes')."&nbsp;:</b> ".$les_notes);
		echo "</div>";
		echo fin_cadre_relief();
	}

	if ($expediteur == $connect_id_auteur AND $statut == 'redac') {
	  if ($type == 'normal' AND $total_dest < 2) {
	    echo "<p style='color: #666666; text-align: right;' class='verdana1 spip_small'><b>"._T('avis_destinataire_obligatoire')."</b></p>";
	  } else {
	    echo "\n<div class='centered'>";
	    echo icone_inline(_T('icone_envoyer_message'), redirige_action_auteur('editer_message', "$id_message/publie", "message","id_message=$id_message"), "messagerie-24.gif", "creer.gif");
	    echo "</div>";
	  }
	}
	echo "</td></tr></table>\n";

	//	echo "</td></tr></table>\n"; //fin_cadre_relief();
	echo "</div>";			// fin du cadre de couleur
	
	// Les boutons

	$aut = ($expediteur == $connect_id_auteur);
	$aff = ($type == 'affich' AND $connect_statut == '0minirezo');

	echo "\n<table width='100%'><tr><td>";

	// bouton de suppression

	if ($aut AND ($statut == 'redac' OR $type == 'pb') OR $aff) {
	  echo icone_inline(_T('icone_supprimer_message'), redirige_action_auteur("editer_message","-$id_message", 'messagerie'), "messagerie-24.gif", "supprimer.gif", 'left');
	}

	// bouton retrait de la discussion

	if ($statut == 'publie' AND $type == 'normal') {
	  echo icone_inline(_T('icone_arret_discussion'), redirige_action_auteur("editer_message","$id_message/-$connect_id_auteur", 'messagerie', "id_message=$id_message"), "messagerie-24.gif", "supprimer.gif", 'left');
	}

	// bouton modifier ce message

	if ($aut OR $aff) {
	  echo icone_inline(_T('icone_modifier_message'), (generer_url_ecrire("message_edit","id_message=$id_message")), "messagerie-24.gif", "edit.gif", 'right');
	}
	echo "</td></tr></table>";
}

// http://doc.spip.org/@exec_affiche_message_dist
function exec_affiche_message_dist($id_message, $cherche_auteur, $forcer_dest)
{
  global $echelle, $partie_cal;
  $row = spip_abstract_fetch(spip_query("SELECT * FROM spip_messages WHERE id_message=$id_message"));
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
			if (preg_match(",=$id_message$,", $e['URL']))
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

	http_affiche_message($id_message, $expediteur, $statut, $type, $texte, $titre, $rv, $date_heure, $date_fin, $cherche_auteur, $forcer_dest);

	// reponses et bouton poster message

	http_afficher_forum_perso($id_message);
 }

 echo fin_gauche(), fin_page();
}

?>
