<?php
// Ce fichier ne sera execute qu'une fois
if (defined("_INVALIDEUR")) return;
define("_INVALIDEUR", "1");

include($GLOBALS['flag_ecrire'] ? '../inc-cache.php3' : 'inc-cache.php3');
include($GLOBALS['flag_ecrire'] ? 'inc_serialbase.php3' : 'ecrire/inc_serialbase.php3');

function  supprime_invalideurs()
{
  global $tables_principales;

  foreach($tables_principales as $a)
	{
	$p = $a['key']["PRIMARY KEY"];
	if (!strpos($p, ","))
	  spip_query("
DELETE FROM spip_" . $p . _SUFFIXE_DES_CACHES
);
	}
  supprime_invalideurs_inclus();
}

function  supprime_invalideurs_inclus($cond='')
{
  spip_query("
DELETE FROM spip_inclure"  . _SUFFIXE_DES_CACHES . ($cond ? " WHERE $cond" :'')
);
}

function maj_invalideurs($hache, $infosurpage)
{
    // pour l'instant on ne sait traiter que ces infos-la`:
  insere_invalideur($infosurpage['id_article'],'id_article', $hache);
  insere_invalideur($infosurpage['id_breve'],   'id_breve', $hache);
  insere_invalideur($infosurpage['id_rubrique'],'id_rubrique', $hache);
}

function insere_invalideur($a, $type, $hache) {
  if (is_array($a))
    {
      $values = array();
      foreach($a as $k => $v)
	{ $m = "('$hache', '$k')"; $values[] = $m; $l .= " $k";}
      spip_query("
INSERT IGNORE INTO spip_" . $type . _SUFFIXE_DES_CACHES . "
(hache, " . $type . ")
VALUES " . join(", ", $values));
#      spip_log("De'pendances $type: " . join(", ", $values));
    }
}

// Regarde dans une table de nom de caches ceux ve'rifiant une condition donne'e
// Les retire de cette table et de la table ge'ne'rale des caches
// Si la condition est vide, c'est une simple purge ge'ne'rale

function suivre_invalideur($cond, $table)
{
  $result = spip_query("
SELECT  DISTINCT hache 
FROM    $table
WHERE   $cond
");
  $tous = array();
  while ($row = spip_fetch_array($result)) 
    { $tous[] = $row['hache'];
    }
  spip_log("suivre: " . join(' ' , $tous));
  applique_invalideur($tous);
}

function applique_invalideur($depart)
{
  global $tables_principales;

  if ($depart)
    {
      $tous = join("', '", $depart);
      $tous = "'$tous'";
      $niveau = $tous;
      while ($niveau)
	{
# le NOT IN est the'oriquement superflu, mais prote`ge des tables endommage'es
	  $result = spip_query("
SELECT  DISTINCT hache
FROM    spip_inclure" . _SUFFIXE_DES_CACHES . "
WHERE   inclure IN ($niveau) 
AND	hache NOT IN ($tous)
");
	  $niveau = array();
	  while ($row = spip_fetch_array($result))
	    { $niveau[] = "'" . $row['hache'] . "'"; 
	      $depart[] = $row['hache'];
	      $tous .= ", '" . $row['hache'] . "'";}
	  $niveau = join(', ', $niveau);
	}
      spip_query("
DELETE FROM spip_inclure"  . _SUFFIXE_DES_CACHES . "
WHERE hache IN ($tous)
");
      
      foreach($tables_principales as $a)
	{
 
		$p = $a['key']["PRIMARY KEY"];
		if (!strpos($p, ","))
	    spip_query("
DELETE FROM spip_" . $p  . _SUFFIXE_DES_CACHES ."
WHERE hache IN ($tous)
");
	}
      retire_caches($depart);
    }
}

// Une petite fonction de mise au point qui devrait etre dans inc_db_mysql

function spip_query_log($r)
{
  $l = spip_query($r); 
  $e = mysql_info(); # absent de certaines versions de MySQL
  spip_log($r .  $e);
  return $l;
}
?>
