<?php

// Ce fichier ne sera execute qu'une fois
if (defined("_INC_DIR")) return;
define("_INC_DIR", "1");

# Retourne le re'pertoire accessible en e'criture 
# et verifie la presence de son .htaccess (sinon le genere)
# Force le statcache de PHP par la me^me occasion.

# NE PAS REFERENCER CE REPERTOIRE AUTREMENT QUE PAR CET APPEL

function dir_var()
{
  $dir = 'CACHE/';
  if ($flag_ecrire) $dir = '../' . $dir;
  $file = $dir . '.htaccess';
  clearstatcache();
  if (!@file_exists($file)) {
    if ($hebergeur == 'nexenservices'){
      echo "<font color=\"#FF0000\">IMPORTANT : </font>";
      echo "Votre h&eacute;bergeur est Nexen Services.<br />";
      echo "La protection du r&eacute;pertoire <i>CACHE/</i> doit se faire par l
'interm&eacute;diaire de ";
      echo "<a href=\"http://www.nexenservices.com/webmestres/htlocal.php\" targ
et=\"_blank\">l'espace webmestres</a>.";
      echo "Veuillez cr&eacute;er manuellement la protection pour ce r&eacute;pe
rtoire (un couple login/mot de passe est n&eacute;cessaire).<br />";
    }
    else{
      $f = fopen($file, "wb");
      fputs($f, "deny from all\n");
      fclose($f);
    }
  }
  return($dir);
}

# retourne un sous-re'petoire du pre'ce'dent
# le cre'e avec les bons droits au besoin

function subdir_var($dir, $subdir)
{
  $dir .=  $subdir;
  if (!@is_writable($dir))
    { 
      if (!@mkdir ($dir, 0777))
	{
	  flock($lock, LOCK_UN);
	  header("Location: spip_test_dirs.php3");
	  exit;
	}
    }
  return $dir  . '/';
}

# retourne le sous-re'pertoire des squelettes. 

function subdir_skel()
{
  return subdir_var(dir_var(), 's');
}

# retourne un sous-sous-re'pertoire de cache.

function subdir_cache($h, $delai)
{
  return subdir_var(subdir_var(dir_var(), $h), $delai);
}

# retourne tous les sous-re'pertoires de cache

function alldir_cache()
{
  $listdir = "0123456789abcdef";
  $dir = dir_var();
  $tous = array();
  for($i=0;$i<16;$i++)
  { 
    $tous[] = subdir_var($dir,$listdir[$i]);
  }
  return $tous;
}

# Retourne un fichier de cache suppose' e^tre du html
# Le nom du cache ne doit pas de'passer 64 caracte`res
# (sinon rede'finir les tables de caches type's et incluants 
# dans inc_base et inc_auxbase)

function file_cache($cle, $delai)
{
  $hache = md5($cle);
  return subdir_cache($hache[16],$delai) . $hache . '.html';
}

# retourne le re'pertoire d'un fichier de cache

function dir_of_file_cache($cle, $delai)
{
  $hache = md5($cle);
  return subdir_cache($hache[16],$delai);
}

?>
