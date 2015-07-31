<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
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

	global $use_cache;
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
	$env['debug'] = ((defined('_VAR_PREVIEW') AND _VAR_PREVIEW) ? "" : admin_debug());
	$env['analyser'] = (!$env['debug'] AND !$GLOBALS['xhtml']) ? '' : admin_valider();
	$env['inclure'] = ((defined('_VAR_INCLURE') AND _VAR_INCLURE)?'inclure':'');

	if (!$use_cache)
		$env['use_cache'] = ' *';
		
	if (isset($debug['validation'])) {
		$env['xhtml_error'] = $debug['validation'];
	}
	
	$env['_pipelines']['formulaire_admin']=array();

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

	$trouver_table = charger_fonction('trouver_table','base');
	$objets = urls_liste_objets(false);
	$objets = array_diff($objets, array('rubrique'));
	$objets = array_reverse($objets);
	array_unshift($objets, 'rubrique');
	foreach ($objets as $obj) {
		$type = $obj;
		if ($type==objet_type($type,false)
			AND $_id_type = id_table_objet($type)
			AND isset($GLOBALS['contexte'][$_id_type])
			AND $id = $GLOBALS['contexte'][$_id_type]
			AND !is_array($id)
			AND $id=intval($id)) {
			$id = sql_getfetsel($_id_type, table_objet_sql($type), "$_id_type=".intval($id));
			if ($id) {
				$env[$_id_type] = $id;
				$env['objet'] = $type;
				$env['id_objet'] = $id;
				$env['voir_'.$obj] =
				  str_replace('&amp;', '&', generer_url_entite($id,$obj,'','',false));
				if ($desc = $trouver_table(table_objet_sql($type))
					AND isset($desc['field']['id_rubrique'])
					AND $type != 'rubrique') {
					unset($env['id_rubrique']);
					unset($env['voir_rubrique']);
					if (admin_preview($type, $id, $desc))
						$env['preview']=parametre_url(self(),'var_mode','preview','&');
				}
			}
		}
	}
	return $env;
}


// http://doc.spip.org/@admin_preview
function admin_preview($type, $id, $desc=null)
{
	if (defined('_VAR_PREVIEW') AND _VAR_PREVIEW) return '';

	if (!$desc) {
		$trouver_table = charger_fonction('trouver_table','base');
		$desc = $trouver_table(table_objet_sql($type));
	}
	if (!$desc OR !isset($desc['field']['statut']))
		return '';

	include_spip('inc/autoriser');
	if (!autoriser('previsualiser')) return '';

	$notpub = sql_in("statut", array('prop', 'prive'));

	if  ($type == 'article' AND $GLOBALS['meta']['post_dates'] != 'oui')
		$notpub .= " OR (statut='publie' AND date>".sql_quote(date('Y-m-d H:i:s')).")";

	return sql_fetsel('1', table_objet_sql($type), id_table_objet($type)."=".$id." AND ($notpub)");
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
				defined('_VAR_MODE') AND _VAR_MODE == 'debug'
				AND $_COOKIE['spip_debug']
			)
		) AND autoriser('debug')
	  )
	  ? parametre_url(self(),'var_mode', 'debug', '&'): '';
}

?>
