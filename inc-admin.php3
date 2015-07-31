<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_ADMIN")) return;
define("_INC_ADMIN", "1");

// Afficher un bouton admin

function bouton_admin($titre, $script, $args) {
  $r = '';
  if ($args)
    foreach ($args as $n => $v)
      $r .= "<input type='hidden' name='$n' value='$v'/>";
  return "<form action='$script'>$r<input type='submit' value='" .
    attribut_html($titre) .
    "' class='spip_bouton' /></form>";
}

function split_query_string($query_string)
{
  $res = array();
  if ($query_string)
    foreach(split('&',$query_string) as $v)
      {
	ereg("^(.*)=(.*)$", $v, $m);
	if ($m[1] != 'recalcul')
	  $res[$m[1]] = $m[2];
      }
  return $res;
}

function afficher_boutons_admin($pop)  {
 global $id_article, $id_breve, $id_rubrique, $id_mot, $id_auteur;
	include_ecrire("inc_filtres.php3");
	include_ecrire("inc_lang.php3");

	// regler les boutons dans la langue de l'admin (sinon tant pis)
	if ($login = addslashes(ereg_replace('^@','',$GLOBALS['spip_admin']))) {
	  $row = spip_fetch_array(spip_query("SELECT lang FROM spip_auteurs WHERE login='$login'"));
	  
	  $lang = $row['lang'];
	}
	lang_select($lang);

	$ret =
	  (($id_article) ?
	   (bouton_admin(_T('admin_modifier_article') . " ($id_article)", 
			 './ecrire/articles.php3',
			 array('id_article' => $id_article))):
	   (($id_breve) ?
	    (bouton_admin(_T('admin_modifier_breve') . " ($id_breve)",
			  "./ecrire/breves_voir.php3",
			  array('id_breve' => $id_breve))) :
	    (($id_rubrique) ?
	     (bouton_admin(_T('admin_modifier_rubrique') . " ($id_rubrique)",
			   "./ecrire/naviguer.php3",
			   array( 'coll' => $id_rubrique))) :
	     (($id_mot) ?
	      (bouton_admin(_T('admin_modifier_mot') . " ($id_mot)", 
			    "./ecrire/mots_edit.php3",
			    array(  'id_mot' => $id_mot))) :
	      (($id_auteur) ?
	       (bouton_admin(_T('admin_modifier_auteur') . " ($id_auteur)",
			     "./ecrire/auteurs_edit.php3",
			     array(   'id_auteur' => $id_auteur))) :
	       '')))));

	$args = split_query_string($GLOBALS['QUERY_STRING']);
	$args['recalcul'] = 'oui';
	$ret .= bouton_admin(attribut_html(_T('admin_recalculer') . $pop),
			     $GLOBALS[PHP_SELF],
			     $args);
	if (lire_meta("activer_statistiques") != "non" AND $id_article AND ($GLOBALS['auteur_session']['statut'] == '0minirezo')) {
	  include_local ("inc-stats.php3");
	  $ret .= bouton_admin(_T('stats_visites_et_popularite',
				  afficher_raccourci_stats($id_article)),
			       "./ecrire/statistiques_visites.php3",
			       array('id_article' => $id_article));
	}

	lang_dselect();
	
	return "<div class='spip-admin' dir='" .
	  lang_dir($lang,'ltr','rtl') .
	  "'>$ret</div>";
}
?>
