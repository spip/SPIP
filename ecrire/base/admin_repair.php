<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2007                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

// http://doc.spip.org/@base_admin_repair_dist
function base_admin_repair_dist() {
	$res1= spip_query("SHOW TABLES");

	$res = "";
	/*if ($res1) { while ($tab = spip_fetch_array($res1,SPIP_NUM)) {
		$res .= "<p><b>".$tab[0]."</b> ";

		$result_repair = spip_query("REPAIR TABLE ".$tab[0]);
		if (!$result_repair) return false;

		$result = spip_fetch_array(spip_query("SELECT COUNT(*) AS n FROM ".$tab[0]));
		if (!$result) return false;

		$count = $result['n'];
		if ($count>1)
			$res .= "("._T('texte_compte_elements', array('count' => $count)).")\n";
		else if ($count==1)
			$res .= "("._T('texte_compte_element', array('count' => $count)).")\n";
		else
			$res .= "("._T('texte_vide').")\n";

		$row = spip_fetch_array($result_repair,SPIP_NUM);
		$ok = ($row[3] == 'OK');

		if (!$ok)
			$res .= "<pre><span style='color: red; font-weight: bold;'>".htmlentities(join("\n", $row))."</span></pre>\n";
		else
			$res .= " "._T('texte_table_ok')."<br />\n";
	  }
	}*/

	reparer_base();
	
	if (!$res) {
		$res = "<br /><br /><span style='color: red; font-weight: bold;'><tt>"._T('avis_erreur_mysql').' '.spip_sql_errno().': '.spip_sql_error() ."</tt></span><br /><br /><br />\n";
	}
	include_spip('inc/minipres');
	echo minipres(_T('texte_tentative_recuperation'), $res);
	exit;
}

include_spip('base/serial');
include_spip('base/auxiliaires');
function reparer_base() {
	// retablir les declarations par defaut des tables
  global $tables_principales, $tables_auxiliaires, $tables_images, $tables_sequences, $tables_documents, $tables_mime;

	// ne pas revenir plusieurs fois (si, au contraire, il faut pouvoir
	// le faire car certaines mises a jour le demandent explicitement)
	# static $vu = false;
	# if ($vu) return; else $vu = true;
	$done = array();
	if (lire_fichier(_DIR_TMP."repair.txt",$contenu))
		$done = unserialize($contenu);

	foreach($tables_principales as $k => $v)
		if (!in_array($k,$done)){
			spip_mysql_repair($k, $v['field'], $v['key'], true);
			$done[] = $k;
			ecrire_fichier(_DIR_TMP."repair.txt",serialize($done));
		}

	foreach($tables_auxiliaires as $k => $v)
		if (!in_array($k,$done)){
			spip_mysql_repair($k, $v['field'], $v['key'], false);
			$done[] = $k;
			ecrire_fichier(_DIR_TMP."repair.txt",serialize($done));
		}
	@unlink(_DIR_TMP."repair.txt");
}

function spip_mysql_repair($nom, $champs, $cles, $autoinc=false, $temporary=false) {
	$query = ''; $keys = ''; $s = ''; $p='';

	// certains plugins declarent les tables  (permet leur inclusion dans le dump)
	// sans les renseigner (laisse le compilo recuperer la description)
	if (!is_array($champs) || !is_array($cles)) 
		return;

	foreach($cles as $k => $v) {
		$keys .= "$s\n\t\t$k ($v)";
		if ($k == "PRIMARY KEY")
			$p = $v;
		$s = ",";
	}
	$s = '';
	
	$character_set = "";
	if (isset($GLOBALS['meta']['charset_sql_base']))
		$character_set .= " CHARACTER SET ".$GLOBALS['meta']['charset_sql_base'];
	if (isset($GLOBALS['meta']['charset_collation_sql_base']))
		$character_set .= " COLLATE ".$GLOBALS['meta']['charset_collation_sql_base'];

	foreach($champs as $k => $v) {
		if (preg_match(',(null|default),i',$v)){
			if (preg_match(',([a-z]*\s*(\(\s*[0-9]*\s*\))?),i',$v,$defs)){
				if (preg_match(',(char|text),i',$defs[1])){
					$v = $defs[1] . $character_set . ' ' . substr($v,strlen($defs[1]));
				}
			}
			spip_query("ALTER TABLE $nom CHANGE $k $k $v" . (($autoinc && ($p == $k)) ? " auto_increment" : ''));
		}
	}
}
?>
