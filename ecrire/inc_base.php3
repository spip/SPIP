<?php

//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_BASE")) return;
define("_ECRIRE_INC_BASE", "1");

include_ecrire("inc_acces.php3");
include_ecrire("inc_serialbase.php3");
include_ecrire("inc_auxbase.php3");
include_ecrire("inc_majbase.php3");

// Fonction de cre'ation d'une table SQL nomme'e $nom
// a` partir de 2 tableaux PHP:
// champs: champ => type
// cles: type-de-cle' => champ(s)
// si $f est vrai, c'est une auto-increment (i.e. serial) sur la Primary Key
// si en  plus la Primary Key re'fe'rence un champ unique,
// on cre'e aussi une table des de'pendances de caches selon ce champ.
// Le nom des caches doit e^tre infe'rieur a` 64 caracte`res

function spip_create($nom, $champs, $cles, $f=false)
{
  # en fait c'est table_prefix, pas forcement 'spip_' faudra finaliser
  $nom = 'spip_' . $nom;
  $query = ''; $keys = ""; $s = '';
  foreach($cles as $k => $v)
    {
      $keys .= "$s\n\t\t$k ($v)";
      if ($k == "PRIMARY KEY") $p = $v;
      $s = ",";
    }
  $s = '';
  foreach($champs as $k => $v)
    {
      $query .= "$s\n\t\t$k $v" .
		(($f && ($p == $k)) ? " auto_increment" : '');
      $s = ",";
    }
  if (!$f)  spip_query ("DROP TABLE IF EXISTS $nom;");
  $query = "CREATE TABLE IF NOT EXISTS $nom ($query" .
    	($keys ? ",$keys" : '') .
    	")\n";
  spip_query($query);  
#  spip_log($query);
  if (($f && !strpos($p, ",")))
    {
      $t = "spip_" . $p . _SUFFIXE_DES_CACHES;
      spip_query("DROP TABLE IF EXISTS $t");
      spip_log("Create $t");
      spip_query("
CREATE TABLE $t (
hache char (64) NOT NULL, $p char (64) NOT NULL,
KEY hache (hache),
KEY $p ($p))
");
    }
}

function creer_base() {
	global $tables_principales, $tables_auxiliaires;
	foreach($tables_principales as $k => $v)
	  {spip_create($k, $v['field'], $v['key'], true);}
	foreach($tables_auxiliaires as $k => $v)
	  {spip_create($k, $v['field'], $v['key'], false);}
	// Images reconnues par PHP
	$query = "INSERT IGNORE spip_types_documents (id_type, extension, titre, inclus) VALUES ".
		"(1, 'jpg', 'JPEG', 'image'), ".
		"(2, 'png', 'PNG', 'image'), ".
		"(3, 'gif', 'GIF', 'image')";
	spip_query($query);

	// Autres images (peuvent utiliser le tag <img>)
	$query = "INSERT IGNORE spip_types_documents (extension, titre, inclus) VALUES ".
		"('bmp', 'BMP', 'image'), ".
		"('psd', 'Photoshop', 'image'), ".
		"('tif', 'TIFF', 'image')";
	spip_query($query);

	// Multimedia (peuvent utiliser le tag <embed>)
	$query = "INSERT IGNORE spip_types_documents (extension, titre, inclus) VALUES ".
		"('aiff', 'AIFF', 'embed'), ".
		"('asf', 'Windows Media', 'embed'), ".
		"('avi', 'Windows Media', 'embed'), ".
		"('mid', 'Midi', 'embed'), ".
		"('mng', 'MNG', 'embed'), ".
		"('mov', 'QuickTime', 'embed'), ".
		"('mp3', 'MP3', 'embed'), ".
		"('mpg', 'MPEG', 'embed'), ".
		"('ogg', 'Ogg', 'embed'), ".
		"('qt', 'QuickTime', 'embed'), ".
		"('ra', 'RealAudio', 'embed'), ".
		"('ram', 'RealAudio', 'embed'), ".
		"('rm', 'RealAudio', 'embed'), ".
		"('swf', 'Flash', 'embed'), ".
		"('wav', 'WAV', 'embed'), ".
		"('wmv', 'Windows Media', 'embed')";
	spip_query($query);

	// Documents varies
	$query = "INSERT IGNORE spip_types_documents (extension, titre, inclus) VALUES ".
		"('ai', 'Adobe Illustrator', 'non'), ".
		"('bz2', 'BZip', 'non'), ".
		"('c', 'C source', 'non'), ".
		"('deb', 'Debian', 'non'), ".
		"('doc', 'Word', 'non'), ".
		"('djvu', 'DjVu', 'non'), ".
		"('dvi', 'LaTeX DVI', 'non'), ".
		"('eps', 'PostScript', 'non'), ".
		"('gz', 'GZ', 'non'), ".
		"('h', 'C header', 'non'), ".
		"('html', 'HTML', 'non'), ".
		"('pas', 'Pascal', 'non'), ".
		"('pdf', 'PDF', 'non'), ".
		"('ppt', 'PowerPoint', 'non'), ".
		"('ps', 'PostScript', 'non'), ".
		"('rpm', 'RedHat/Mandrake/SuSE', 'non'), ".
		"('rtf', 'RTF', 'non'), ".
		"('sdd', 'StarOffice', 'non'), ".
		"('sdw', 'StarOffice', 'non'), ".
		"('sit', 'Stuffit', 'non'), ".
		"('sxc', 'OpenOffice Calc', 'non'), ".
		"('sxi', 'OpenOffice Impress', 'non'), ".
		"('sxw', 'OpenOffice', 'non'), ".
		"('tex', 'LaTeX', 'non'), ".
		"('tgz', 'TGZ', 'non'), ".
		"('txt', 'texte', 'non'), ".
		"('xcf', 'GIMP multi-layer', 'non'), ".
		"('xls', 'Excel', 'non'), ".
		"('xml', 'XML', 'non'), ".
		"('zip', 'Zip', 'non')";
	spip_query($query);
}

function stripslashes_base($table, $champs) {
	$modifs = '';
	reset($champs);
	while (list(, $champ) = each($champs)) {
		$modifs[] = $champ . '=REPLACE(REPLACE(' .$champ. ',"\\\\\'", "\'"), \'\\\\"\', \'"\')';
	}
	$query = "UPDATE $table SET ".join(',', $modifs);
	spip_query($query);
}

?>
