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

// http://doc.spip.org/@base_admin_repair_dist
function base_repair_dist($titre='', $reprise='') {

	$res = admin_repair_tables();
	if (!$res) {
		$res = "<div class='error'>"._T('avis_erreur_mysql').' '.sql_errno().': '.sql_error() ."</div>\n";
	} else {
		include_spip('inc/rubriques');
		calculer_rubriques();
		propager_les_secteurs();
	}
	include_spip('inc/minipres');
	$res .= pipeline('base_admin_repair',$res);
	echo minipres(_T('texte_tentative_recuperation'),
	$res . generer_form_ecrire('accueil', '','',_T('public:accueil_site')));
}

// http://doc.spip.org/@admin_repair_plat
function admin_repair_plat(){
	spip_log( "verification des documents joints", _LOG_INFO_IMPORTANTE);
	$out = "";
	$repertoire = array();
	include_spip('inc/getdocument');
	$res = sql_select('*','spip_documents',"fichier REGEXP CONCAT('^',extension,'[^/\]') AND distant='non'");

	while ($row=sql_fetch($res)){
		$ext = $row['extension'];
		if (!$ext) {
			spip_log("document sans extension: " . $row['id_document'], _LOG_INFO_IMPORTANTE);
			continue;
		}
		if (!isset($repertoire[$ext])){
			if (@file_exists($plat = _DIR_IMG. $ext .".plat"))
				spip_unlink($plat);
			$repertoire[$ext] = creer_repertoire_documents($ext);
			if (preg_match(',_$,',$repertoire[$ext]))
				$repertoire[$ext] = false;
		}
		if ($d=$repertoire[$ext]){
			$d = substr($d,strlen(_DIR_IMG));
			$src = $row['fichier'];
			$dest = $d . substr($src,strlen($d));
			if (@copy(_DIR_IMG . $src, _DIR_IMG . $dest)
			  AND file_exists(_DIR_IMG . $dest)) {
				sql_updateq('spip_documents',array('fichier'=>$dest),'id_document='.intval($row['id_document']));
				spip_unlink(_DIR_IMG . $src);
				$out .= "$src => $dest<br />";
			}
		}
	}

	return $out;
}

// http://doc.spip.org/@admin_repair_tables
function admin_repair_tables() {

	$repair = sql_repair('repair', NULL, 'continue');

	// recreer les tables manquantes eventuelles
	include_spip('base/create');
	creer_base();

	$connexion = $GLOBALS['connexions'][0];
	$prefixe = $connexion['prefixe'];
	$res1 = sql_showbase();
	$res = "";
	if ($res1) {
		while ($r = sql_fetch($res1)) {
			$tab = array_shift($r);

			$class = "";
			$m = "<strong>$tab</strong> ";
			spip_log("Repare $tab", _LOG_INFO_IMPORTANTE);
			// supprimer la meta avant de lancer la reparation
			// car le repair peut etre long ; on ne veut pas boucler
			effacer_meta('admin_repair');
			if ($repair){
				$result_repair = sql_repair($tab);
				if (!$result_repair) return false;
			}

			// essayer de maj la table (creation de champs manquants)
			maj_tables($tab);

			$count = sql_countsel($tab);
	
			if ($count>1)
				$m .= "("._T('texte_compte_elements', array('count' => $count)).")\n";
			else if ($count==1)
				$m .= "("._T('texte_compte_element', array('count' => $count)).")\n";
			else
				$m .= "("._T('texte_vide').")\n";
	
			if ($result_repair
			  AND $msg = join(" ", sql_fetch($result_repair)) . ' '
				AND strpos($msg, ' OK ')==FALSE){
				$class = " class='notice'";
				$m .= "<br /><tt>".spip_htmlentities($msg)."</tt>\n";
			}
			else
				$m .= " "._T('texte_table_ok');

			$res .="<div$class>$m</div>";
	  }
	}
	return $res;
}
?>
