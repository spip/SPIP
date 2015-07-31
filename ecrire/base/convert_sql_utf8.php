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


// http://doc.spip.org/@base_convert_sql_utf8_dist
function base_convert_sql_utf8_dist($titre, $reprise=false)
{
	// poser un verrou (et abandonner si l'action est en cours)

	if (!spip_get_lock('conversion_charset_sql')) {
		echo minipres(_T('utf8_convert_attendez'));
		exit;
	}

	include_spip('inc/meta');
	ecrire_meta('convert_sql_utf8','oui','non');
	echo install_debut_html($titre);
	
	echo "<p>", _T('utf8_convert_timeout'), "</p><hr />\n";
	
	convert_sql_utf8();

	echo "<p><b>"._T('utf8_convert_termine')."</b></p>";
	effacer_meta('convert_sql_utf8');

	// bouton "retour au site" + redirige_par_entete
	echo "<p style='text-align: right'>",
	  "<a href='", generer_url_ecrire("config_lang"), "'> &gt;&gt; ",
	  _T('icone_retour'),"</a></p>",
	  install_fin_html();
}

// http://doc.spip.org/@convert_sql_utf8
function convert_sql_utf8(){
	include_spip('base/db_mysql');

	define(_DEBUG_CONVERT, false);
	$charset_spip = $GLOBALS['meta']['charset'];
	$charset_supporte = false;
	$utf8_supporte = false;	
	// verifier que mysql gere le charset courant pour effectuer les conversions 
	if ($c = spip_mysql_character_set($charset_spip)){
		$sql_charset = $c['charset'];
		$sql_collation = $c['collation'];
		$charset_supporte = true;
	}
	if (!$charset_supporte){
		$res = spip_query("SHOW CHARACTER SET");
		while ($row = spip_fetch_array($res)){
			if ($row['Charset']=='utf8') $utf8_supporte = true;
		}
		echo install_debut_html();
		echo _L("Le charset SPIP actuel $charset_spip n'est pas supporte par votre serveur MySQL<br/>");  # non traduit car complexe & obsolete
		if ($utf8_supporte)
			echo _L("Votre serveur supporte utf-8, vous devriez convertir votre site en utf-8 avant de recommencer cette operation");
		echo install_fin_html();
		exit;
	}
	echo _L("Charset Actuel du site SPIP : $charset_spip<br/>");
	echo _L("Conversion des champs des tables spip de type latin1 vers <b>$sql_charset</b> (collation $sql_collation) <br/>");
	// lister les collations et leur charset correspondant
	$res = spip_query("SHOW COLLATION");
	$charset2collations = array();
	while ($row = spip_fetch_array($res)){
		$charset2collations[$row['Collation']] = $row['Charset'];
	}
	
	$count = 0;
	// lister les tables spip
	include_spip('base/serial');
	include_spip('base/auxiliaires');

	$res = spip_query("SHOW TABLES");
	while (($row = spip_fetch_array($res,SPIP_NUM)) /*&& ($count<1)*/){
		$nom = $row[0];
		if (preg_match(',^'.$GLOBALS['table_prefix'].'_(.*)$,',$nom,$regs)){
			$count++;
			$nom = $regs[1];
			echo "<hr /><h2>$nom</h2>";
			// lister les champs de la table
			$res2 = spip_query("SHOW FULL COLUMNS FROM spip_$nom");
			while ($row2 = spip_fetch_array($res2)){
				$collation = $row2['Collation'];
				$champ = $row2['Field'];
				if ($collation!="NULL" 
				&& isset($charset2collations[$collation]) 
				&& $charset2collations[$collation]=='latin1'){
					echo "Conversion de '$champ' depuis $collation (".$charset2collations[$collation]."):";
					// conversion de latin1 vers le charset reel du contenu
					$type_texte= $row2['Type'];
					$type_blob = "blob";
					if (strpos($type_texte,"text")!==FALSE)
						$type_blob = str_replace("text","blob",$type_texte);

					// sauf si blob expressement demande dans la description !
					if ((
					$a = $GLOBALS['tables_principales']['spip_'.$nom]['field'][$champ]
					OR $a = $GLOBALS['tables_auxiliaires']['spip_'.$nom]['field'][$champ]
					) AND preg_match(',blob,i', $a)) {
						echo "On ignore le champ blob $nom.$champ <hr />\n";
					} else {

						$default = $row2['Default']?(" DEFAULT "._q($row2['Default'])):"";
						$notnull = ($row2['Null']=='YES')?"":" NOT NULL";
						$q = "ALTER TABLE spip_$nom CHANGE $champ $champ $type_blob $default $notnull";
						if (!_DEBUG_CONVERT)
							$b = spip_query($q);
						echo "<pre>$q</pre>$b\n";
						$q = "ALTER TABLE spip_$nom CHANGE $champ $champ $type_texte CHARACTER SET $sql_charset COLLATE $sql_collation  $default $notnull";
						if (!_DEBUG_CONVERT)
							$b = spip_query($q);
						echo "<pre>$q</pre>\n";
					}
				}
			}
			// on ne change le charset par defaut de la table que quand tous ses champs sont convertis
			$q = "ALTER TABLE spip_$nom DEFAULT CHARACTER SET $sql_charset COLLATE $sql_collation";
			if (!_DEBUG_CONVERT)
				$b = spip_query($q);
			echo "<pre>$q</pre>$b\n";
		}
	}
	ecrire_meta('charset_sql_base',$sql_charset,'non');
	ecrire_meta('charset_sql_connexion',$sql_charset,'non');
	ecrire_metas();
	spip_release_lock('conversion_charset_sql');
}
?>