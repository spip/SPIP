<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_ADMIN")) return;
define("_INC_ADMIN", "1");

global $admin_array ;
$admin_array = array('id_article', 'id_breve', 'id_rubrique', 'id_mot', 'id_auteur');

# on ne peut rien dire au moment de l'exécution du squelette

function admin_stat($args, $filtres)
{
	return $args;
}

# les boutons admin sont mis d'autorite si absents
# donc une variable statique controle si FORMULAIRE_ADMIN a ete vu.
# Toutefois, si c'est le debuger qui appelle,
# il peut avoir recopie le code dans ses donnees et il faut le lui refounir.
# Pas question de recompiler: ca fait boucler !
# Le debuger transmet donc ses donnees, et cette balise y retrouve son petit.

function admin_dyn($id_article, $id_breve, $id_rubrique, $id_mot, $id_auteur, $debug='') {
	global $var_preview, $use_cache;
	static $dejafait = false;

	if ($GLOBALS['flag_preserver'] || !$GLOBALS['spip_admin'])
	  return '';

	if (!is_array($debug))
	  {
	    if ($dejafait) return '';
	  }
	else {
	  if ($dejafait) {
	    $res = '';
	    foreach($debug['sourcefile'] as $k => $v) {
	      if (strpos($v,'formulaire_admin') === 0)
		{return $debug['resultat'][$k . 'tout'];}
	    }
	    return '';
	  }
	}

	$dejafait = true;

	include(_FILE_CONNECT);
	// regler les boutons dans la langue de l'admin (sinon tant pis)
	$login = addslashes(ereg_replace('^@','',$GLOBALS['spip_admin']));
	if ($row = spip_fetch_array(spip_query("SELECT lang FROM spip_auteurs WHERE login='$login'"))) {
		$lang = $row['lang'];
		lang_select($lang);
	}

	// repartir de zero pour les boutons car clean_link a pu etre utilisee
	$link = new Link;
	$link->delVar('var_mode');
	$link->delVar('var_mode_objet');
	$link->delVar('var_mode_affiche');
	$action = $link->getUrl();
	$action = ($action . ((strpos($action, '?') === false) ? '?' : '&'));

  // en preview pas de stat ni de debug
	if (!$var_preview) {
		// Bouton statistiques
		if (lire_meta("activer_statistiques") != "non" 
		    AND $id_article
		    AND ($GLOBALS['auteur_session']['statut'] == '0minirezo')) {
			if (spip_fetch_array(spip_query("SELECT id_article
			FROM spip_articles WHERE statut='publie'
			AND id_article =".intval($id_article)))) {
				include_local ("inc-stats.php3");
				$r = afficher_raccourci_stats($id_article);
				$visites = $r['visites'];
				$popularite = $r['popularite'];
				$statistiques = 'statistiques_visites.php3?';
			}
		}

		// Bouton de debug
		$debug = (($forcer_debug
			  OR $GLOBALS['bouton_admin_debug']
			  OR ($GLOBALS['var_mode'] == 'debug'
			      AND $GLOBALS['HTTP_COOKIE_VARS']['spip_debug']))
		  	AND ($GLOBALS['code_activation_debug'] == 'oui' OR
			     ($GLOBALS['auteur_session']['statut'] == '0minirezo'))) ?
		  'debug' : '';
	}

	return array('formulaire_admin', 0,
		     array(
			   'action' => $action,
			   'id_article' => $id_article,
			   'id_auteur' => $id_auteur,
			   'id_breve' => $id_breve,
			   'debug' => $debug,
			   'id_mot' => $id_mot,
			   'popularite' => intval($popularite),
			   'rubrique' => $rubrique,
			   'statistiques' => $statistiques,
			   'visites' => intval($visites),
			   'use_cache' => ($use_cache ? ' *' : '')));
}


?>
