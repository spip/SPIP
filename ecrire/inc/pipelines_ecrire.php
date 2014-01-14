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

/**
 * Fonctions déclarées dans des pipelines (espace privé)
 *
 * @package SPIP\Pipelines
**/
if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * Inserer jQuery et ses plugins pour l'espace privé
 *
 * La fonction ajoute les balises scripts dans le texte qui appelent
 * les scripts jQuery ainsi que certains de ses plugins. La liste
 * des js chargée peut être complété par le pipeline 'jquery_plugins'
 *
 * Cette fonction est appelée par le pipeline header_prive
 *
 * @see f_jQuery()
 * @link http://doc.spip.org/@f_jQuery
 * 
 * @param string $texte    Contenu qui sera inséré dans le head HTML
 * @return string          Contenu complété des scripts javascripts, dont jQuery
**/
function f_jQuery_prive ($texte) {
	$x = '';
	$jquery_plugins = pipeline('jquery_plugins',
		array(
			'prive/javascript/jquery.js',
			'prive/javascript/jquery.form.js',
			'prive/javascript/jquery.autosave.js',
			'prive/javascript/jquery.placeholder-label.js',
			'prive/javascript/ajaxCallback.js',
			'prive/javascript/jquery.colors.js',
			'prive/javascript/jquery.cookie.js',
			'prive/javascript/spip_barre.js',
		));
	foreach (array_unique($jquery_plugins) as $script)
		if ($script = find_in_path($script))
			$x .= "\n<script src=\"$script\" type=\"text/javascript\"></script>\n";
	// inserer avant le premier script externe ou a la fin
	if (preg_match(",<script[^><]*src=,",$texte,$match)
	  AND $p = strpos($texte,$match[0])){
	  $texte = substr_replace($texte,$x,$p,0);
	}
	else
		$texte .= $x;
	return $texte;
}


/**
 * Ajout automatique du title dans les pages du privé en squelette
 * 
 * Appellé dans le pipeline affichage_final_prive
 *
 * @param string $texte
 * @return string
 */
function affichage_final_prive_title_auto($texte){
	if (strpos($texte,'<title>')===false
	  AND
			(preg_match(",<h1[^>]*>(.+)</h1>,Uims", $texte, $match)
		   OR preg_match(",<h[23][^>]*>(.+)</h[23]>,Uims", $texte, $match))
		AND $match = textebrut(trim($match[1]))
		AND ($p = strpos($texte,'<head>'))!==FALSE) {
		if (!$nom_site_spip = textebrut(typo($GLOBALS['meta']["nom_site"])))
			$nom_site_spip=  _T('info_mon_site_spip');

		$titre = "<title>["
			. $nom_site_spip
			. "] ". $match
		  ."</title>";

		$texte = substr_replace($texte, $titre, $p+6,0);
	}
	return $texte;
}


// Fonction standard pour le pipeline 'boite_infos'
// http://doc.spip.org/@f_boite_infos
function f_boite_infos($flux) {
	$args = $flux['args'];
	$type = $args['type'];
	unset($args['row']);
	if (!trouver_fond($type,"prive/objets/infos/"))
		$type = 'objet';
	$flux['data'] .= recuperer_fond("prive/objets/infos/$type",$args);
	return $flux;
}


/**
 * pipeline recuperer_fond
 * Branchement automatise de affiche_gauche, affiche_droite, affiche_milieu
 * pour assurer la compat avec les versions precedentes des exec en php
 * Branche de affiche_objet
 * 
 * Les pipelines ne recevront plus exactement le meme contenu en entree,
 * mais la compat multi vertions pourra etre assuree
 * par une insertion au bon endroit quand le contenu de depart n'est pas vide
 * 
 * @param array $flux
 * @return array
 */
function f_afficher_blocs_ecrire($flux) {
	static $o=array();
	if (is_string($fond=$flux['args']['fond'])) {
		$exec = isset($flux['args']['contexte']['exec']) ? $flux['args']['contexte']['exec'] : _request('exec');
		if (!isset($o[$exec])){
			$o[$exec] = trouver_objet_exec($exec);
		}
		$typepage = (isset($flux['args']['contexte']['type-page'])?$flux['args']['contexte']['type-page']:$exec);
		if ($fond == "prive/squelettes/navigation/$typepage"){
			$flux['data']['texte'] = pipeline('affiche_gauche',array('args'=>$flux['args']['contexte'],'data'=>$flux['data']['texte']));
		}
		elseif ($fond=="prive/squelettes/extra/$typepage") {
			include_spip('inc/presentation_mini');
			$flux['data']['texte'] = pipeline('affiche_droite',array('args'=>$flux['args']['contexte'],'data'=>$flux['data']['texte'])).liste_objets_bloques($exec,$flux['args']['contexte']);
		}
		elseif ($fond=="prive/squelettes/hierarchie/$typepage" AND $o[$exec]) {
			// id non defini sur les formulaire de nouveaux objets
			$id = isset($flux['args']['contexte'][$o[$exec]['id_table_objet']]) ? intval($flux['args']['contexte'][$o[$exec]['id_table_objet']]) : 0;
			$flux['data']['texte'] = pipeline('affiche_hierarchie',array('args'=>array('objet'=>$o[$exec]['type'],'id_objet'=>$id),'data'=>$flux['data']['texte']));
		}
		elseif ($fond=="prive/squelettes/contenu/$typepage"){
			if (!strpos($flux['data']['texte'],"<!--affiche_milieu-->"))
				$flux['data']['texte'] = preg_replace(',<div id=["\']wysiwyg,',"<!--affiche_milieu-->\\0",$flux['data']['texte']);
			if ($o[$exec]
				AND $objet = $o[$exec]['type']
			  AND $o[$exec]['edition'] == false
			  AND $id = intval($flux['args']['contexte'][$o[$exec]['id_table_objet']])){
				// inserer le formulaire de traduction
				$flux['data']['texte'] = str_replace("<!--affiche_milieu-->",recuperer_fond('prive/objets/editer/traductions',array('objet'=>$objet,'id_objet'=>$id))."<!--affiche_milieu-->",$flux['data']['texte']);
				$flux['data']['texte'] = pipeline('afficher_fiche_objet',array(
																						'args'=>array(
																							'contexte'=>$flux['args']['contexte'],
																							'type'=>$objet,
																							'id'=>$id),
																						'data'=>$flux['data']['texte']));
			}
			$flux['data']['texte'] = pipeline('affiche_milieu',array('args'=>$flux['args']['contexte'],'data'=>$flux['data']['texte']));
		}
		elseif ($fond=="prive/squelettes/inclure/pied"){
			$flux['data']['texte'] = pipeline('affiche_pied',array('args'=>$flux['args']['contexte'],'data'=>$flux['data']['texte']));
		}
		elseif (strncmp($fond,"prive/objets/contenu/",21)==0
			AND $objet=basename($fond)
			AND $objet==substr($fond,21)
			AND isset($o[$objet]) 
			AND $o[$objet]) {
				$id = intval($flux['args']['contexte'][$o[$exec]['id_table_objet']]);
				$flux['data']['texte'] = pipeline('afficher_contenu_objet',array('args'=>array('type'=>$objet,'id_objet'=>$id,'contexte'=>$flux['args']['contexte']),'data'=>$flux['data']['texte']));
		}
	}

	return $flux;
}

/**
 * Afficher les taches en attente liees a un objet
 * @param string $flux
 * @return string
 */
function f_queue_affiche_milieu($flux){
	$args = $flux['args'];
	$res = "";
	foreach($args as $key=>$arg){
		if (preg_match(",^id_,",$key) AND is_numeric($arg) AND $arg=intval($arg)){
			$objet = preg_replace(',^id_,', '', $key);
			$res .= recuperer_fond('modeles/object_jobs_list',array('id_objet'=>$arg,'objet'=>$objet),array('ajax'=>true));
		}
	}
	if ($res)
		$flux['data'] = $res . $flux['data'];

	return $flux;
}

/**
 * Trouver l'objet qui correspond
 * a l'exec de l'espace prive passe en argument
 * renvoie false si pas d'objet en cours, ou un tableau associatif
 * contenant les informations table_objet_sql,table,type,id_table_objet,edition
 *
 * @param string $exec
 *   nom de la page testee
 * @return array|bool
 */
function trouver_objet_exec($exec){
	static $objet_exec=array();
	if (!$exec) return false;
	if (!isset($objet_exec[$exec])){
		$objet_exec[$exec]=false;
		$infos = lister_tables_objets_sql();
		foreach($infos as $t=>$info){
			if ($exec==$info['url_edit'] AND $info['editable']){
				return $objet_exec[$exec] = array('edition'=>$exec==$info['url_voir']?'':true,'table_objet_sql'=>$t,'table'=>$info['table_objet'],'type'=>$info['type'],'id_table_objet'=>id_table_objet($info['type']));
			}
			if ($exec==$info['url_voir']){
				return $objet_exec[$exec] = array('edition'=>false,'table_objet_sql'=>$t,'table'=>$info['table_objet'],'type'=>$info['type'],'id_table_objet'=>id_table_objet($info['type']));
			}
		}
	}
	return $objet_exec[$exec];
}
?>
