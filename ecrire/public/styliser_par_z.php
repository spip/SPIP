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


if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Fonction Page automatique a partir de contenu/xx
 *
 * @param array $flux
 * @return array
 */
function public_styliser_par_z_dist($flux){
	static $prefix_path=null;
	static $prefix_length;
	static $z_blocs;
	static $apl_constant;
	static $page;
	static $disponible = array();
	static $echafauder;
	static $prepend = "";

	if (!isset($prefix_path)) {
		$z_blocs = z_blocs(test_espace_prive());
		if (test_espace_prive ()){
			$prefix_path = "prive/squelettes/";
			$prefix_length = strlen($prefix_path);
			$apl_constant = '_ECRIRE_AJAX_PARALLEL_LOAD';
			$page = 'exec';
			$echafauder = charger_fonction('echafauder','prive',true);
			define('_ZCORE_EXCLURE_PATH','');
		}
		else {
			$prefix_path = "";
			$prefix_length = 0;
			$apl_constant = '_Z_AJAX_PARALLEL_LOAD';
			$page = _SPIP_PAGE;
			$echafauder = charger_fonction('echafauder','public',true);
			define('_ZCORE_EXCLURE_PATH','\bprive|\bsquelettes-dist'.(defined('_DIR_PLUGIN_DIST')?'|\b'.rtrim(_DIR_PLUGIN_DIST,'/'):''));
		}
	  $prepend = (defined('_Z_PREPEND_PATH')?_Z_PREPEND_PATH:"");
	}
	$z_contenu = reset($z_blocs); // contenu par defaut

	$fond = $flux['args']['fond'];

	if ($prepend OR strncmp($fond,$prefix_path,$prefix_length)==0) {
		$fond = substr($fond, $prefix_length);
		$squelette = $flux['data'];
		$ext = $flux['args']['ext'];
		// Ajax Parallel loading : ne pas calculer le bloc, mais renvoyer un js qui le loadera en ajax
		if (defined('_Z_AJAX_PARALLEL_LOAD_OK')
			AND $dir = explode('/',$fond)
			AND count($dir)==2 // pas un sous repertoire
			AND $dir = reset($dir)
			AND in_array($dir,$z_blocs) // verifier deja qu'on est dans un bloc Z
			AND defined($apl_constant)
			AND in_array($dir,explode(',',constant($apl_constant))) // et dans un demande en APL
			AND $pipe = z_trouver_bloc($prefix_path.$prepend,$dir,'z_apl',$ext) // et qui contient le squelette APL
			){
			$flux['data'] = $pipe;
			return $flux;
		}

		// surcharger aussi les squelettes venant de squelettes-dist/
		if ($squelette AND !z_fond_valide($squelette)){
			$squelette = "";
		  $echafauder = "";
		}
	  if ($prepend){
		  $squelette = substr(find_in_path($prefix_path.$prepend."$fond.$ext"), 0, - strlen(".$ext"));
	    if ($squelette)
		    $flux['data'] = $squelette;
	  }

		// gerer les squelettes non trouves
		// -> router vers les /dist.html
		// ou scaffolding ou page automatique les contenus
		if (!$squelette){

			// si on est sur un ?page=XX non trouve
			if ($flux['args']['contexte'][$page] == $fond 
				OR $flux['args']['contexte']['type-page'] == $fond
				OR ($fond=='sommaire' AND !$flux['args']['contexte'][$page])) {

				// si on est sur un ?page=XX non trouve
				// se brancher sur contenu/xx si il existe
				// ou si c'est un objet spip, associe a une table, utiliser le fond homonyme
				if (!isset($disponible[$fond]))
					$disponible[$fond] = z_contenu_disponible($prefix_path.$prepend,$z_contenu,$fond,$ext,$echafauder);

				if ($disponible[$fond])
					$flux['data'] = substr(find_in_path($prefix_path."page.$ext"), 0, - strlen(".$ext"));
			}

			// echafaudage :
			// si c'est un fond de contenu d'un objet en base
			// generer un fond automatique a la volee pour les webmestres
			elseif (strncmp($fond, "$z_contenu/", strlen($z_contenu)+1)==0){
				$type = substr($fond,strlen($z_contenu)+1);
				if (($type=='page') AND isset($flux['args']['contexte'][$page]))
					$type = $flux['args']['contexte'][$page];
				if (!isset($disponible[$type]))
					$disponible[$type] = z_contenu_disponible($prefix_path.$prepend,$z_contenu,$type,$ext,$echafauder);
				if (is_string($disponible[$type])) {
					$flux['data'] = $disponible[$type];
				}
				elseif ($echafauder
					AND include_spip('inc/autoriser')
					AND isset($GLOBALS['visiteur_session']['statut']) // performance
					AND autoriser('echafauder',$type)
					AND $is = $disponible[$type]
					AND is_array($is)) {
					$flux['data'] = $echafauder($type,$is[0],$is[1],$is[2],$ext);
				}
				else{
					$flux['data'] = ($disponible['404'] = z_contenu_disponible($prefix_path.$prepend,$z_contenu,'404',$ext,$echafauder));
				}
			}

			// sinon, si on demande un fond non trouve dans un des autres blocs
			// et si il y a bien un contenu correspondant ou echafaudable
			// se rabbatre sur le dist.html du bloc concerne
			else{
				if ( $dir = explode('/',$fond)
					AND $dir = reset($dir)
					AND $dir !== $z_contenu
					AND in_array($dir,$z_blocs)){
					$type = substr($fond,strlen("$dir/"));
					if (($type=='page') AND isset($flux['args']['contexte'][$page]))
						$type = $flux['args']['contexte'][$page];
					if ($type!=='page' AND !isset($disponible[$type]))
						$disponible[$type] = z_contenu_disponible($prefix_path.$prepend,$z_contenu,$type,$ext,$echafauder);
					if ($type=='page' OR $disponible[$type])
						$flux['data'] = z_trouver_bloc($prefix_path.$prepend,$dir,'dist',$ext);
				}
			}
			$squelette = $flux['data'];
		}
		// layout specifiques par type et compositions :
		// body-article.html
		// body-sommaire.html
		// pour des raisons de perfo, les declinaisons doivent etre dans le
		// meme dossier que body.html
		if ($fond=='body' AND substr($squelette,-strlen($fond))==$fond){
			if (isset($flux['args']['contexte']['type-page'])
				AND (
					(isset($flux['args']['contexte']['composition'])
					AND file_exists(($f=$squelette."-".$flux['args']['contexte']['type-page']."-".$flux['args']['contexte']['composition']).".$ext"))
					OR
					file_exists(($f=$squelette."-".$flux['args']['contexte']['type-page']).".$ext")
					))
				$flux['data'] = $f;
		}
		elseif ($fond=='structure' 
			AND z_sanitize_var_zajax()
			AND $f = find_in_path($prefix_path.$prepend.'ajax'.".$ext")) {
			$flux['data'] = substr($f,0,-strlen(".$ext"));
		}
		// chercher le fond correspondant a la composition
		elseif (isset($flux['args']['contexte']['composition'])
			AND (basename($fond)=='page' OR ($squelette AND substr($squelette,-strlen($fond))==$fond))
			AND $dir = substr($fond,$prefix_length)
			AND $dir = explode('/',$dir)
			AND $dir = reset($dir)
			AND in_array($dir,$z_blocs)
			AND $f=find_in_path($prefix_path.$prepend.$fond."-".$flux['args']['contexte']['composition'].".$ext")){
			$flux['data'] = substr($f,0,-strlen(".$ext"));
		}
	}
	return $flux;
}

/**
 * Lister les blocs de la page selon le contexte prive/public
 *
 * @param bool $espace_prive
 * @return array
 */
function z_blocs($espace_prive=false) {
	if ($espace_prive)
		return (isset($GLOBALS['z_blocs_ecrire'])?$GLOBALS['z_blocs_ecrire']:array('contenu','navigation','extra','head','hierarchie','top'));
	return (isset($GLOBALS['z_blocs'])?$GLOBALS['z_blocs']:array('contenu'));
}

/**
 * Verifier qu'un type a un contenu disponible,
 * soit parcequ'il a un fond, soit parce qu'il est echafaudable
 *
 * @param string $prefix_path
 * @param string $z_contenu
 * @param string $type
 * @param string $ext
 * @param bool $echafauder
 * @return mixed
 */
function z_contenu_disponible($prefix_path,$z_contenu,$type,$ext,$echafauder=true){
	if ($d = z_trouver_bloc($prefix_path,$z_contenu,$type,$ext))
		return $d;
	return $echafauder?z_echafaudable($type):false;
}

function z_fond_valide($squelette){
	if (!_ZCORE_EXCLURE_PATH
		OR !preg_match(',('._ZCORE_EXCLURE_PATH.')/,',$squelette))
		return true;
  return false;
}

/**
 * Trouver un bloc qui peut etre sous le nom
 * contenu/article.html
 * ou
 * contenu/contenu.article.html
 *
 * @param string $prefix_path
 *	chemin de base qui prefixe la recherche
 * @param string $bloc
 *	nom du bloc cherche
 * @param string $fond
 *	nom de la page (ou 'dist' pour le bloc par defaut)
 * @param string $ext
 *	extension du squelette
 * @return string
 */
function z_trouver_bloc($prefix_path,$bloc,$fond,$ext){
	if (
		(defined('_ZCORE_BLOC_PREFIX_SKEL') AND $f = find_in_path("$prefix_path$bloc/$bloc.$fond.$ext") AND z_fond_valide($f))
		OR ($f = find_in_path("$prefix_path$bloc/$fond.$ext") AND z_fond_valide($f))
		){
		return substr($f, 0, - strlen(".$ext"));
	}
	return "";
}
/**
 * Tester si un type est echafaudable
 * cad si il correspond bien a un objet en base
 *
 * @staticvar array $echafaudable
 * @param string $type
 * @return bool
 */
function z_echafaudable($type){
	static $pages = null;
	static $echafaudable = array();
	if (isset($echafaudable[$type]))
		return $echafaudable[$type];
	if (preg_match(',[^\w],',$type))
		return $echafaudable[$type] = false;

	if (test_espace_prive()){
		if (!function_exists('trouver_objet_exec'))
			include_spip('inc/pipelines_ecrire');
		if ($e=trouver_objet_exec($type)){
			return $echafaudable[$type] = array($e['table'],$e['table_objet_sql'],$e);
		}
		else {
			// peut etre c'est un exec=types qui liste tous les objets "type"
			if (($t=objet_type($type,false))!==$type
			  AND $e=trouver_objet_exec($t)){
				return $echafaudable[$type] = array($e['table'],$e['table_objet_sql'],$t);
			}
		}
	}
	else {
		if (is_null($pages)) {
			$pages = array();
			$liste = lister_tables_objets_sql();
			foreach($liste as $t=>$d)
				if ($d['page']) $pages[$d['page']] = array($d['table_objet'],$t);
		}
		if (!isset($pages[$type]))
			return $echafaudable[$type] = false;
		if (count($pages[$type])==2){
			$trouver_table = charger_fonction('trouver_table','base');
			$pages[$type][] = $trouver_table(reset($pages[$type]));
		}
		return $echafaudable[$type] = $pages[$type];
	}
	return $echafaudable[$type] = false;
}


/**
 * Generer a la volee un fond a partir d'un contenu connu
 * tous les squelettes d'echafaudage du prive sont en fait explicites dans prive/echafaudage
 * on ne fait qu'un mini squelette d'inclusion pour reecrire les variables d'env
 *
 * @param string $type
 * @param string $table
 * @param string $table_sql
 * @param array $desc
 * @param string $ext
 * @return string
 */
function prive_echafauder_dist($exec,$table,$table_sql,$desc_exec,$ext){
	$scaffold = "";

	// page objet ou objet_edit
	if (is_array($desc_exec)) {
		$type = $desc_exec['type'];
		$primary = $desc_exec['id_table_objet'];

		if ($desc_exec['edition']===false)
			$fond = "objet";
		else {
			$trouver_table = charger_fonction('trouver_table','base');
			$desc = $trouver_table($table_sql);
			if (isset($desc['field']['id_rubrique']))
				$fond = 'objet_edit';
			else
				$fond = 'objet_edit.sans_rubrique';
		}
		$dir = z_blocs(test_espace_prive());
		$dir = reset($dir);
		$scaffold = "<INCLURE{fond=prive/echafaudage/$dir/".$fond.",objet=".$type.",id_objet=#".strtoupper($primary).",env}>";
	}
	// page objets
	elseif($type = $desc_exec AND strpos($type,"/")===false){
		$dir = z_blocs(test_espace_prive());
		$dir = reset($dir);
		$scaffold = "<INCLURE{fond=prive/echafaudage/$dir/objets,objet=".$type.",env} />";
	}
	// morceau d'objet : on fournit le fond de sibstitution dans $desc_exec
	// et objet et tire de $table
	elseif($fond = $desc_exec){
		$dir = md5(dirname($fond));
		$scaffold = "<INCLURE{fond=$fond,objet=".objet_type($table).",env} />";
	}

	$base_dir = sous_repertoire(_DIR_CACHE,"scaffold",false);
	$base_dir = sous_repertoire($base_dir,$dir,false);
	$f = $base_dir."$exec";
	ecrire_fichier("$f.$ext",$scaffold);
	return $f;
}

/**
 * Recuperer et verifier var_zajax si demande dans l'url
 * @return bool|string
 */
function z_sanitize_var_zajax(){
	$z_ajax = _request('var_zajax');
	if (!$z_ajax) return false;
	if (!$z_blocs = z_blocs(test_espace_prive())
	  OR !in_array($z_ajax,$z_blocs)) {
		set_request('var_zajax'); // enlever cette demande incongrue
		$z_ajax = false;
	}
	return $z_ajax;
}
?>
