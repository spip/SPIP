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

// http://doc.spip.org/@base_admin_repair_dist
function base_admin_repair_dist($titre='', $reprise='') {

	$f = sql_repair('repair', NULL, true);
	if ($f) {
		$res = admin_repair_tables();
	} else {
		if ($titre)
		  spip_log("Pas d'instruction REPAIR dans ce serveur SQL");
		$res = '     ';
	}

	if (!$res) {
		$res = "<br /><br /><span style='color: red; font-weight: bold;'><tt>"._T('avis_erreur_mysql').' '.sql_errno().': '.sql_error() ."</tt></span><br /><br /><br />\n";
	} else {
		include_spip('inc/rubriques');
		calculer_rubriques();
		propager_les_secteurs();
	}
	include_spip('inc/minipres');
	$res .= pipeline('base_admin_repair',$res);
	$res .= admin_repair_plat();
	echo minipres(_T('texte_tentative_recuperation'),
			$res . generer_form_ecrire('accueil', '','',_T('public:accueil_site')));
}

// http://doc.spip.org/@admin_repair_plat
function admin_repair_plat(){
	spip_log("verification des documents joints");
	$out = "";
	$repertoire = array();
	include_spip('inc/getdocument');
	$res = sql_select('*','spip_documents',"fichier REGEXP CONCAT('^',extension,'[^/\]') AND distant='non'");

	while ($row=sql_fetch($res)){
		$ext = $row['extension'];
		if (!$ext) {
			spip_log("document sans extension: " . $row['id_document']);
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
			if (deplacer_fichier_upload(_DIR_IMG . $src, _DIR_IMG . $dest)) {
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

	$connexion = $GLOBALS['connexions'][0];
	$prefixe = $connexion['prefixe'];
	$res1 = sql_showbase();
	$res = "";
	if ($res1) {
		while ($r = sql_fetch($res1)) {
			$tab = array_shift($r);
	
			$res .= "<br /><b>$tab</b> ";
			spip_log("Repare $tab");
			$result_repair = sql_repair($tab);
			if (!$result_repair) return false;
	
			$count = sql_countsel($tab);
	
			if ($count>1)
				$res .= "("._T('texte_compte_elements', array('count' => $count)).")\n";
			else if ($count==1)
				$res .= "("._T('texte_compte_element', array('count' => $count)).")\n";
			else
				$res .= "("._T('texte_vide').")\n";
	
			$msg = join(" ", sql_fetch($result_repair)) . ' ';
	
			$ok = strpos($msg, ' OK ');
	
			if (!$ok)
				$res .= "<pre><span style='color: red; font-weight: bold;'>".htmlentities($msg)."</span></pre>\n";
			else
				$res .= " "._T('texte_table_ok')."<br />\n";
	  }
	}
	return $res;
}
?>
