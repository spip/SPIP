<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;	#securite

include_spip('inc/autoriser');
include_spip('base/abstract_sql');

// http://doc.spip.org/@balise_FORMULAIRE_ADMIN
function balise_FORMULAIRE_ADMIN ($p) {
	return calculer_balise_dynamique($p,'FORMULAIRE_ADMIN', array());
}

# on ne peut rien dire au moment de l'execution du squelette

// http://doc.spip.org/@balise_FORMULAIRE_ADMIN_stat
function balise_FORMULAIRE_ADMIN_stat($args, $filtres) {
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
			$res = '';
			foreach($debug['sourcefile'] as $k => $v) {
				if (strpos($v,'administration.') !== false)
					return $debug['resultat'][$k . 'tout'];
			}
			return '';
		}
	}

	$dejafait = true;

	// Preparer le #ENV des boutons

	$env = admin_objet();

	// Pas de "modifier ce..." ? -> donner "acces a l'espace prive"
	if (!$env)
		$env['ecrire'] = _DIR_RESTREINT_ABS;

	$env['action'] = self('&');
	$env['divclass'] = $float;
	$env['lang'] = admin_lang();
	$env['calcul'] = (_request('var_mode') ? 'recalcul' : 'calcul');

	if (!$var_preview AND admin_debug())
		$env['debug'] = $debug;

	if (!$use_cache)
		$env['use_cache'] = ' *';
	if ($analyser = (!$env['debug'] ? '' : admin_valider()))
		$env['analyser'] = $analyser;
	if (isset($GLOBALS['xhtml_error']) AND $GLOBALS['xhtml_error']) {
		$env['xhtml_error'] = count($GLOBALS['xhtml_error']);
	}

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
		if ($id_type = $GLOBALS[$_id_type]) {
			$id_type = sql_getfetsel($_id_type, table_objet_sql($id), "$_id_type=".intval($id_type));
			if ($id_type) {
				$env[$_id_type] = $id_type;
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

	$notpub = "(statut IN ('prop', 'prive'))";

	if  ($id == 'article' AND $GLOBALS['meta']['post_dates'] != 'oui')
		$notpub = "($notpub OR (statut='publie' AND date>NOW()))";

	return sql_fetsel('1', table_objet_sql($id), id_table_objet($id)."=".$id_type." AND $notpub");
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
	return (($GLOBALS['forcer_debug']
			OR $GLOBALS['bouton_admin_debug']
			OR (
				$GLOBALS['var_mode'] == 'debug'
				AND $_COOKIE['spip_debug']
			)
		) AND autoriser('debug')
	  )
	  ? parametre_url(self(),'var_mode', 'debug', '&'): '';
}

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
