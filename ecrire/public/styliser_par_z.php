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

	if (!isset($prefix_path)) {
		$z_blocs = z_blocs(test_espace_prive());
		if (test_espace_prive ()){
			$prefix_path = "prive/squelettes/";
			$prefix_length = strlen($prefix_path);
			$apl_constant = '_ECRIRE_AJAX_PARALLEL_LOAD';
			$page = 'exec';
			$echaffauder = ""; // pas d'echaffaudage dans ecrire/ pour le moment
		}
		else {
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

		// Ajax Parallel loading : ne pas calculer le bloc, mais renvoyer un js qui le loadera en ajax
		if (defined('_Z_AJAX_PARALLEL_LOAD_OK')
			AND $dir = explode('/',$fond)
			AND count($dir)==2 // pas un sous repertoire
			AND $dir = reset($dir)
			AND in_array($dir,$z_blocs) // verifier deja qu'on est dans un bloc Z
			AND in_array($dir,explode(',',constant($apl_constant))) // et dans un demande en APL
			AND $pipe = z_trouver_bloc($prefix_path,$dir,'z_apl',$ext) // et qui contient le squelette APL
			){
			$flux['data'] = $pipe;
			return $flux;
		}

		// surcharger aussi les squelettes a la racine venant de squelettes-dist/
		if (preg_match(',squelettes-dist/[^/]+$,',$squelette))
			$squelette = "";

		// gerer les squelettes non trouves
		// -> router vers les /dist.html
		// ou scaffolding ou page automatique les contenus
		if (!$squelette){

			// si on est sur un ?page=XX non trouve
			if ($flux['args']['contexte'][$page] == $fond OR $flux['args']['contexte']['type'] == $fond) {

				// si on est sur un ?page=XX non trouve
				// se brancher sur contenu/xx si il existe
				// ou si c'est un objet spip, associe a une table, utiliser le fond homonyme
				if (!isset($disponible[$fond]))
					$disponible[$fond] = z_contenu_disponible($prefix_path,$z_contenu,$fond,$ext);

				if ($disponible[$fond]) {
					$flux['data'] = trouver_fond("page",$prefix_path,true);
			    $flux['data'] = $flux['data']['fond'];
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
				if (!isset($disponible[$type]))
					$disponible[$type] = z_contenu_disponible($prefix_path,$z_contenu,$type,$ext);
				if ($echaffauder
					AND $is = $disponible[$type]
					AND is_array($is))
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
					if ($type!=='page' AND !isset($disponible[$type]))
						$disponible[$type] = z_contenu_disponible($prefix_path,$z_contenu,$type,$ext);
					if ($type=='page' OR $disponible[$type])
						$flux['data'] = z_trouver_bloc($prefix_path,$dir,'dist',$ext);
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
			AND $dir = substr($fond,$prefix_length)
			AND $dir = explode('/',$dir)
			AND $dir = reset($dir)
			AND in_array($dir,$z_blocs)
			AND $f=trouver_fond($fond."-".$flux['args']['contexte']['composition'],$prefix_path,true)
			AND $f['fond']){
			$flux['data'] = $f['fond'];
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
	return (isset($GLOBALS['z_blocs'])?$GLOBALS['z_blocs']:array('contenu','navigation','extra','head','head_js'));
}

/**
 * Verifier qu'un type a un contenu disponible,
 * soit parcequ'il a un fond, soit parce qu'il est echaffaudable
 *
 * @param string $prefix_path
 * @param string $z_contenu
 * @param string $type
 * @param string $ext
 * @return mixed
 */
function z_contenu_disponible($prefix_path,$z_contenu,$type,$ext){
	if ($d = z_trouver_bloc($prefix_path,$z_contenu,$type,$ext))
		return $d;
	return z_echaffaudable($type);
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
 * @param <type> $ext
 *	extension du squelette
 * @return string
 */
function z_trouver_bloc($prefix_path,$bloc,$fond,$ext){
	$f = trouver_fond("$bloc.$fond","$prefix_path$bloc/",true);
	if ($f['fond']) return $f['fond'];

	$f = trouver_fond($fond,"$prefix_path$bloc/",true);
	// vide si rien trouve
	return $f['fond'];
}
/**
 * Tester si un type est echaffaudable
 * cad si il correspond bien a un objet en base
 *
 * @staticvar array $echaffaudable
 * @param string $type
 * @return bool
 */
function z_echaffaudable($type){
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

?>
