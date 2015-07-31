<?php
// Ce fichier ne sera execute qu'une fois
if (defined("_INVALIDEUR")) return;
define("_INVALIDEUR", "1");

include_ecrire('inc_serialbase.php3');
include_local('inc-cache.php3');
include_local('inc-calcul_mysql3.php'); # pour mysql_in

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
  // en attendant de réécrire les 3 scripts dans ecrire
#  insere_invalideur($infosurpage['id_article'],'id_article', $hache);
#  insere_invalideur($infosurpage['id_breve'],   'id_breve', $hache);
#  insere_invalideur($infosurpage['id_rubrique'],'id_rubrique', $hache);
  insere_invalideur($infosurpage['id_forum'],'id_forum', $hache);
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
      spip_log("Dépendances $type: " . join(", ", $values));
    }
}

// Regarde dans une table de nom de caches ceux vérifiant une condition donnée
// Les retire de cette table et de la table générale des caches
// Si la condition est vide, c'est une simple purge générale

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
  spip_log("suivre $cond");
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
      spip_log("applique $tous");
      while ($niveau)
	{
# le NOT est théoriquement superflu, mais protège des tables endommagées
	  $result = spip_query("
SELECT  DISTINCT hache
FROM    spip_inclure" . _SUFFIXE_DES_CACHES . '
WHERE	' .
			       calcul_mysql_in('inclure', $niveau, '') . '
AND	' .
			       calcul_mysql_in('hache', $tous, 'NOT')
);
	  $niveau = array();
	  while ($row = spip_fetch_array($result))
	    { $niveau[] = "'" . $row['hache'] . "'"; 
	      $depart[] = $row['hache'];
	      $tous .= ", '" . $row['hache'] . "'";}
	  $niveau = join(', ', $niveau);
	}
      spip_query("
DELETE FROM spip_inclure"  . _SUFFIXE_DES_CACHES . '
WHERE	' .
			       calcul_mysql_in('hache', $tous, 'NOT')
);
      
      foreach($tables_principales as $a)
	{
 
		$p = $a['key']["PRIMARY KEY"];
		if (!strpos($p, ","))
	    spip_query("
DELETE FROM spip_" . $p  . _SUFFIXE_DES_CACHES . '
WHERE	' .
			       calcul_mysql_in('hache', $tous, 'NOT')
);
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
