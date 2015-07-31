<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2010                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


if (!defined("_ECRIRE_INC_VERSION")) return;

// Ce fichier doit imperativement definir la fonction ci-dessous:

// Actuellement tous les squelettes se terminent par .html
// pour des raisons historiques, ce qui est trompeur

// http://doc.spip.org/@public_styliser_dist
function public_styliser_dist($fond, $contexte, $lang='', $connect='', $ext='html') {

	// Choisir entre $fond-dist.html, $fond=7.html, etc?
	$id_rubrique = 0;
	// Chercher le fond qui va servir de squelette
	if ($r = quete_rubrique_fond($contexte))
		list($id_rubrique, $lang) = $r;

	// trouver un squelette du nom demande
	// ne rien dire si on ne trouve pas, 
	// c'est l'appelant qui sait comment gerer la situation
	$base = find_in_path("$fond.$ext");
	
	// supprimer le ".html" pour pouvoir affiner par id_rubrique ou par langue
	$squelette = substr($base, 0, - strlen(".$ext"));
	$flux = array(
		'args' => array(
			'id_rubrique' => $id_rubrique,
			'ext' => $ext,
			'fond' => $fond,
			'lang' => $lang,
			'contexte' => $contexte, // le style d'un objet peut dependre de lui meme
			'connect' => $connect
		),
		'data' => $squelette,
	);

	if (test_espace_prive() OR defined('_ZPIP')) {
		$styliser_par_z = charger_fonction('par_z','styliser');
		$flux = $styliser_par_z($flux);
	}

	// pipeline styliser
	$squelette = pipeline('styliser', $flux);

	return array($squelette, $ext, $ext, "$squelette.$ext");
}


/**
 * Fonction Page automatique a partir de contenu/page-xx
 *
 * @param array $flux
 * @return array
 */
function styliser_par_z_dist($flux){
	static $prefix_path=null;
	static $prefix_length;
	static $z_blocs;
	static $apl_constant;
	static $page;

	if (!isset($prefix_path)) {
		if (test_espace_prive ()){
			$prefix_path = "prive/squelettes/";
			$prefix_length = strlen($prefix_path);
			$z_blocs = isset($GLOBALS['z_blocs_ecrire'])?$GLOBALS['z_blocs_ecrire']:array('contenu','navigation','extra','head','hierarchie');
			$apl_constant = '_ECRIRE_AJAX_PARALLEL_LOAD';
			$page = 'exec';
			$echaffauder = ""; // pas d'echaffaudage dans ecrire/ pour le moment
		}
		else {
			$z_blocs = isset($GLOBALS['z_blocs'])?$GLOBALS['z_blocs']:array('contenu','navigation','extra','head');
			$prefix_path = "";
			$prefix_length = 0;
			$apl_constant = '_Z_AJAX_PARALLEL_LOAD';
			$page = _SPIP_PAGE;
			$echaffauder = charger_fonction('echaffauder','public',true);
		}
	}
	$z_contenu = reset($z_blocs); // contenu par defaut

	$fond = $flux['args']['fond'];
	if (strncmp($fond,$prefix_path,$prefix_length)==0) {
		$fond = substr($fond, $prefix_length);
		$squelette = $flux['data'];
		$ext = $flux['args']['ext'];

		// Ajax Parallel loading : ne pas calculer le bloc, mais renvoyer un js qui le loadera an ajax
		if (defined('_Z_AJAX_PARALLEL_LOAD_OK')
			AND $dir = explode('/',$fond)
			AND count($dir)==2 // pas un sous repertoire
			AND $dir = reset($dir)
			AND in_array($dir,$z_blocs) // verifier deja qu'on est dans un bloc Z
			AND in_array($dir,explode(',',constant($apl_constant))) // et dans un demande en APL
			AND $pipe = find_in_path("$prefix_path$dir/z_apl.$ext") // et qui contient le squelette APL
			){
			$flux['data'] = substr($pipe, 0, - strlen(".$ext"));
			return $flux;
		}

		// gerer les squelettes non trouves
		// -> router vers les /dist.html
		// ou scaffolding ou page automatique les contenus
		if (!$squelette){

			// si on est sur un ?page=XX non trouve
			if ($flux['args']['contexte'][$page] == $fond OR $flux['args']['contexte']['type'] == $fond) {
				// si c'est un objet spip, associe a une table, utiliser le fond homonyme
				if (echaffaudable($fond)){
					$flux['data'] = substr(find_in_path($prefix_path."objet.$ext"), 0, - strlen(".$ext"));
				}
				// sinon, brancher sur contenu/page-xx si elle existe
				// si on est sur un ?page=XX non trouve
				elseif ($flux['args']['contexte'][$page] == $fond) {
					$base = "$prefix_path$z_contenu/page-".$fond.".".$ext;
					if ($base = find_in_path($base)){
						$flux['data'] = substr(find_in_path($prefix_path."page.$ext"), 0, - strlen(".$ext"));
					}
				}
			}

			// echaffaudage :
			// si c'est un fond de contenu d'un objet en base
			// generer un fond automatique a la volee pour les webmestres
			elseif (strncmp($fond, "$z_contenu/", strlen($z_contenu)+1)==0
				AND include_spip('inc/autoriser')
				AND isset($GLOBALS['visiteur_session']['statut']) // performance
				AND autoriser('webmestre')){
				$type = substr($fond,strlen($z_contenu)+1);
				if ($echaffauder AND $is = echaffaudable($type))
					$flux['data'] = $echaffauder($type,$is[0],$is[1],$is[2],$ext);
			}

			// sinon, si on demande un fond non trouve dans un des autres blocs
			// et si il y a bien un contenu correspondant ou echaffaudable
			// se rabbatre sur le dist.html du bloc concerne
			else{
				if ( $dir = explode('/',$fond)
					AND $dir = reset($dir)
					AND $dir !== $z_contenu
					AND in_array($dir,$z_blocs)){
					$type = substr($fond,strlen("$dir/"));
					if ($type=='page' OR find_in_path($prefix_path."$z_contenu/$type.$ext") OR echaffaudable($type))
						$flux['data'] = substr(find_in_path($prefix_path."$dir/dist.$ext"), 0, - strlen(".$ext"));
				}
			}
			$squelette = $flux['data'];
		}
		// layout specifiques par type et compositions :
		// body-article.html
		// body-page-sommaire.html
		// pour des raisons de perfo, les declinaisons doivent etre dans le
		// meme dossier que body.html
		if ($fond=='body' AND substr($squelette,-strlen($fond))==$fond){
			if (isset($flux['args']['contexte']['type'])
				AND (
					(isset($flux['args']['contexte']['composition'])
					AND file_exists(($f=$squelette."-".$flux['args']['contexte']['type']."-".$flux['args']['contexte']['composition']).".$ext"))
					OR
					file_exists(($f=$squelette."-".$flux['args']['contexte']['type']).".$ext")
					))
				$flux['data'] = $f;
		}
		// chercher le fond correspondant a la composition
		elseif (isset($flux['args']['contexte']['composition'])
			AND (basename($fond)=='page' OR ($squelette AND substr($squelette,-strlen($fond))==$fond))
			AND $dir = explode('/',$fond)
			AND $dir = reset($dir)
			AND in_array($dir,$z_blocs)
			AND $f=find_in_path($prefix_path.$fond."-".$flux['args']['contexte']['composition'].".$ext")){
			$flux['data'] = substr($f,0,-strlen(".$ext"));
		}
	}
	return $flux;
}


/**
 * Tester si un type est echaffaudable
 * cad si il correspond bien a un objet en base
 *
 * @staticvar array $echaffaudable
 * @param string $type
 * @return bool
 */
function echaffaudable($type){
	static $echaffaudable = array();
	if (isset($echaffaudable[$type]))
		return $echaffaudable[$type];
	if (preg_match(',[^\w],',$type))
		return $echaffaudable[$type] = false;
	if ($table = table_objet($type)
	  AND $type == objet_type($table)
	  AND $trouver_table = charger_fonction('trouver_table','base')
	  AND
		($desc = $trouver_table($table)
		OR $desc = $trouver_table($table_sql = "spip_$table"))
		)
		return $echaffaudable[$type] = array($table,$desc['table'],$desc);
	else
		return $echaffaudable[$type] = false;
}

/*
 * Options de recherche de squelette par le styliseur, appele par le pipeline 'styliser' :
 * Squelette par rubrique squelette-XX.html ou squelette=XX.html
 */
function styliser_par_rubrique($flux) {

	// uniquement si un squelette a ete trouve
	if ($squelette = $flux['data']) {
		$ext = $flux['args']['ext'];

		// On selectionne, dans l'ordre :
		// fond=10
		if ($id_rubrique = $flux['args']['id_rubrique']) {
			$f = "$squelette=$id_rubrique";
			if (@file_exists("$f.$ext"))
				$squelette = $f;
			else {
				// fond-10 fond-<rubriques parentes>
				do {
					$f = "$squelette-$id_rubrique";
					if (@file_exists("$f.$ext")) {
						$squelette = $f;
						break;
					}
				} while ($id_rubrique = quete_parent($id_rubrique));
			}
			// sauver le squelette
			$flux['data'] = $squelette;
		}		
	}
	
	return $flux;
}

/*
 * Options de recherche de squelette par le styliseur, appele par le pipeline 'styliser' :
 * Squelette par langue squelette.en.html
 */
function styliser_par_langue($flux) {

	// uniquement si un squelette a ete trouve
	if ($squelette = $flux['data']) {
		$ext = $flux['args']['ext'];

		// Affiner par lang
		if ($lang = $flux['args']['lang']) {
			$l = lang_select($lang);
			$f = "$squelette.".$GLOBALS['spip_lang'];
			if ($l) lang_select();
			if (@file_exists("$f.$ext")) {
				// sauver le squelette
				$flux['data'] = $f;
			}
		}
	}
	
	return $flux;
}

// Calcul de la rubrique associee a la requete
// (selection de squelette specifique par id_rubrique & lang)

// http://doc.spip.org/@quete_rubrique_fond
function quete_rubrique_fond($contexte) {

	if (isset($contexte['id_rubrique'])
	AND $id = intval($contexte['id_rubrique'])
	AND $row = quete_parent_lang('spip_rubriques',$id)) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array ($id, $lang);
	}

	if (isset($contexte['id_breve'])
	AND $id = intval($contexte['id_breve'])
	AND $row = quete_parent_lang('spip_breves',$id)
	AND $id_rubrique_fond = $row['id_rubrique']) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array($id_rubrique_fond, $lang);
	}

	if (isset($contexte['id_syndic'])
	AND $id = intval($contexte['id_syndic'])
	AND $row = quete_parent_lang('spip_syndic',$id)
	AND $id_rubrique_fond = $row['id_rubrique']
	AND $row = quete_parent_lang('spip_rubriques',$id_rubrique_fond)) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array($id_rubrique_fond, $lang);
	}

	if (isset($contexte['id_article'])
	AND $id = intval($contexte['id_article'])
	AND $row = quete_parent_lang('spip_articles',$id)
	AND $id_rubrique_fond = $row['id_rubrique']) {
		$lang = isset($row['lang']) ? $row['lang'] : '';
		return array($id_rubrique_fond, $lang);
	}
}
?>
