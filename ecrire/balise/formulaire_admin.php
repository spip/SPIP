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

// http://doc.spip.org/@balise_FORMULAIRE_ADMIN
function balise_FORMULAIRE_ADMIN ($p) {
	return calculer_balise_dynamique($p,'FORMULAIRE_ADMIN', array());
}

# on ne peut rien dire au moment de l'execution du squelette

// http://doc.spip.org/@balise_FORMULAIRE_ADMIN_stat
function balise_FORMULAIRE_ADMIN_stat($args, $context_compil) {
	return $args;
}

# les boutons admin sont mis d'autorite si absents
# donc une variable statique controle si FORMULAIRE_ADMIN a ete vu.
# Toutefois, si c'est le debuger qui appelle,
# il peut avoir recopie le code dans ses donnees et il faut le lui refounir.
# Pas question de recompiler: ca fait boucler !
# Le debuger transmet donc ses donnees, et cette balise y retrouve son petit.

// http://doc.spip.org/@balise_FORMULAIRE_ADMIN_dyn
function balise_FORMULAIRE_ADMIN_dyn($float='', $debug='') {

	global $var_preview, $use_cache;
	static $dejafait = false;

	if (!@$_COOKIE['spip_admin'])
		return '';

	if (!is_array($debug)) {
		if ($dejafait)
			return '';
	} else {
		if ($dejafait) {
			if (empty($debug['sourcefile'])) return '';
			foreach($debug['sourcefile'] as $k => $v) {
				if (strpos($v,'administration.') !== false)
					return $debug['resultat'][$k . 'tout'];
			}
			return '';
		}
	}

	include_spip('inc/autoriser');
	include_spip('base/abstract_sql');


	$dejafait = true;

	// Preparer le #ENV des boutons

	$env = admin_objet();

	// Pas de "modifier ce..." ? -> donner "acces a l'espace prive"
	if (!$env)
		$env['ecrire'] = _DIR_RESTREINT_ABS;

	$env['divclass'] = $float;
	$env['lang'] = admin_lang();
	$env['calcul'] = (_request('var_mode') ? 'recalcul' : 'calcul');
	$env['debug'] = $var_preview ? "" : admin_debug();		
	$env['analyser'] = (!$env['debug'] AND !$GLOBALS['xhtml']) ? '' : admin_valider();
	$env['inclure'] = ($GLOBALS['var_inclure']?'inclure':'');

	if (!$use_cache)
		$env['use_cache'] = ' *';
		
	if (isset($debug['validation'])) {
		$env['xhtml_error'] = $debug['validation'];
	}
	
	$env['_pipeline'] = 'formulaire_admin';

	return array('formulaires/administration', 0, $env);
}

// Afficher le bouton 'Modifier ce...' 
// s'il y a un $id_XXX defini globalement par spip_register_globals
// Attention a l'ordre dans la boucle:
//	on ne veut pas la rubrique si un autre bouton est possible

// http://doc.spip.org/@admin_objet
function admin_objet()
{
	include_spip('inc/urls');
	$env = array();

	foreach (array('mot','auteur','rubrique','breve','article','syndic'=>'site')
	as $id => $obj) {
		if (is_int($id)) $id = $obj;
		$_id_type = id_table_objet($id);
		if (isset($GLOBALS['contexte'][$_id_type]) AND $id_type = $GLOBALS['contexte'][$_id_type]) {
			$id_type = sql_getfetsel($_id_type, table_objet_sql($id), "$_id_type=".intval($id_type));
			if ($id_type) {
				$env[$_id_type] = $id_type;
				$env['objet'] = $id;
				$env['id_objet'] = $id_type;
				$g = 'generer_url_ecrire_'.$obj;
				$env['voir_'.$obj] = 
				  str_replace('&amp;', '&', $g($id_type, '','', 'prop'));
				if ($id == 'article' OR $id == 'breve') {
					unset($env['id_rubrique']);
					unset($env['voir_rubrique']);
					if ($l = admin_stats($id, $id_type, $var_preview)) {
						$env['visites'] = $l[0];
						$env['popularite'] = $l[1];
						$env['statistiques'] = $l[2];
					}
					if (admin_preview($id, $id_type))
						$env['preview']=parametre_url(self(),'var_mode','preview','&');
				}
			}
		}
	}
	return $env;
}


// http://doc.spip.org/@admin_preview
function admin_preview($id, $id_type)
{
	if ($GLOBALS['var_preview']) return '';

	if (!($id == 'article'
	OR $id == 'breve'
	OR $id == 'rubrique'
	OR $id == 'syndic'))

		return '';

	include_spip('inc/autoriser');
	if (!autoriser('previsualiser')) return '';

	$notpub = sql_in("statut", array('prop', 'prive'));

	if  ($id == 'article' AND $GLOBALS['meta']['post_dates'] != 'oui')
		$notpub .= " OR (statut='publie' AND date>".sql_quote(date('Y-m-d H:i:s')).")";

	return sql_fetsel('1', table_objet_sql($id), id_table_objet($id)."=".$id_type." AND ($notpub)");
}

//
// Regler les boutons dans la langue de l'admin (sinon tant pis)
//

// http://doc.spip.org/@admin_lang
function admin_lang()
{
	$alang = sql_getfetsel('lang', 'spip_auteurs', "login=" . sql_quote(preg_replace(',^@,','',@$_COOKIE['spip_admin'])));
	if (!$alang) return '';

	$l = lang_select($alang);
	$alang = $GLOBALS['spip_lang'];
	if ($l) lang_select();
	return $alang;
}

// http://doc.spip.org/@admin_valider
function admin_valider()
{
	global $xhtml;

	return ((@$xhtml !== 'true') ?
		(parametre_url(self(), 'var_mode', 'debug', '&')
			.'&var_mode_affiche=validation') :
		('http://validator.w3.org/check?uri='
		 . rawurlencode("http://" . $_SERVER['HTTP_HOST'] . nettoyer_uri())));
}

// http://doc.spip.org/@admin_debug
function admin_debug()
{
	return ((
			(isset($GLOBALS['forcer_debug']) AND $GLOBALS['forcer_debug'])
			OR (isset($GLOBALS['bouton_admin_debug']) AND $GLOBALS['bouton_admin_debug'])
			OR (
				isset($GLOBALS['var_mode'])
				AND $GLOBALS['var_mode'] == 'debug'
				AND $_COOKIE['spip_debug']
			)
		) AND autoriser('debug')
	  )
	  ? parametre_url(self(),'var_mode', 'debug', '&'): '';
}


// Tant que les stats ne sont pas passees dans une extension, il faut les traiter ici
// http://doc.spip.org/@admin_stats
function admin_stats($id, $id_type, $var_preview)
{
	if ($GLOBALS['meta']["activer_statistiques"] != "non" 
	AND $id = 'article'
	AND !$var_preview
	AND autoriser('voirstats')
	) {
		$row = sql_fetsel("visites, popularite", "spip_articles", "id_article=$id_type AND statut='publie'");

		if ($row) {
			return array(intval($row['visites']),
			       ceil($row['popularite']),
			       str_replace('&amp;', '&', generer_url_ecrire_statistiques($id_type)));
		}
	}
	return false;
}


?>
