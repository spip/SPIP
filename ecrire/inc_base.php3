<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


//
// Ce fichier ne sera execute qu'une fois
if (defined("_ECRIRE_INC_BASE")) return;
define("_ECRIRE_INC_BASE", "1");

include_ecrire("inc_acces.php3");
include_ecrire("inc_serialbase.php3");
include_ecrire("inc_auxbase.php3");
include_ecrire("inc_majbase.php3");

// Fonction de creation d'une table SQL nommee $nom
// a partir de 2 tableaux PHP :
// champs: champ => type
// cles: type-de-cle => champ(s)
// si $autoinc, c'est une auto-increment (i.e. serial) sur la Primary Key
// Le nom des caches doit etre inferieur a 64 caracteres

function spip_create_table($nom, $champs, $cles, $autoinc=false) {
	$query = ''; $keys = ''; $s = '';

	foreach($cles as $k => $v) {
		$keys .= "$s\n\t\t$k ($v)";
		if ($k == "PRIMARY KEY")
			$p = $v;
		$s = ",";
	}
	$s = '';

	foreach($champs as $k => $v) {
		$query .= "$s\n\t\t$k $v" .
		(($autoinc && ($p == $k)) ? " auto_increment" : '');
		$s = ",";
	}

	$query = "CREATE TABLE IF NOT EXISTS $nom ($query" .
		($keys ? ",$keys" : '') .
		")\n";
	spip_query_db($query);

}


function creer_base() {
	global $tables_principales, $tables_auxiliaires;

	// ne pas revenir plusieurs fois (si, au contraire, il faut pouvoir
	// le faire car certaines mises a jour le demandent explicitement)
	# static $vu = false;
	# if ($vu) return; else $vu = true;

	foreach($tables_principales as $k => $v)
		spip_create_table($k, $v['field'], $v['key'], true);
	foreach($tables_auxiliaires as $k => $v)
		spip_create_table($k, $v['field'], $v['key'], false);

	remplir_table_type_documents();
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

function remplir_table_type_documents() {
	// Images reconnues par PHP
	$query = "INSERT IGNORE spip_types_documents (id_type, extension, titre, inclus) VALUES ".
		"(1, 'jpg', 'JPEG', 'image'), ".
		"(2, 'png', 'PNG', 'image'), ".
		"(3, 'gif', 'GIF', 'image')";
	spip_query_db($query);

	// Autres images (peuvent utiliser le tag <img>)
	$query = "INSERT IGNORE spip_types_documents (extension, titre, inclus) VALUES ".
		"('bmp', 'BMP', 'image'), ".
		"('psd', 'Photoshop', 'image'), ".
		"('tif', 'TIFF', 'image')";
	spip_query_db($query);

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
	spip_query_db($query);

	// Documents varies
	$query = "INSERT IGNORE spip_types_documents (extension, titre, inclus) VALUES ".
		"('ai', 'Adobe Illustrator', 'non'), ".
		"('bz2', 'BZip', 'non'), ".
		"('c', 'C source', 'non'), ".
		"('css', 'Cascading Style Sheet', 'non'), ".
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
	spip_query_db($query);


	// Mettre a jour les types MIME
	$types = array(
		// Images reconnues par PHP
		'jpg'=>'image/jpeg',
		'png'=>'image/png',
		'gif'=>'image/gif',

		// Autres images (peuvent utiliser le tag <img>)
		'bmp'=>'image/x-ms-bmp', // pas enregistre par IANA, variante: image/bmp
		'psd'=>'image/x-photoshop',	// pas IANA
		'tif'=>'image/tiff',

		// Multimedia (peuvent utiliser le tag <embed>)
		'aiff'=>'audio/x-aiff',
		'asf'=>'video/x-ms-asf',
		'avi'=>'video/x-msvideo',
		'mid'=>'audio/midi',
		'mng'=>'video/x-mng',
		'mov'=>'video/quicktime',
		'mp3'=>'audio/mpeg',
		'mpg'=>'video/mpeg',
		'ogg'=>'application/ogg',
		'qt' =>'video/quicktime',
		'ra' =>'audio/x-pn-realaudio',
		'ram'=>'audio/x-pn-realaudio',
		'rm' =>'audio/x-pn-realaudio',
		'swf'=>'application/x-shockwave-flash',
		'wav'=>'audio/x-wav',
		'wmv'=>'video/x-ms-wmv',

		// Documents varies
		'ai' =>'application/illustrator',
		'bz2'=>'application/x-bzip2',
		'c'  =>'text/x-csrc',
		'css'=>'text/css',
		'deb'=>'application/x-debian-package',
		'doc'=>'application/msword',
		'djvu'=>'image/vnd.djvu',
		'dvi'=>'application/x-dvi',
		'eps'=>'application/postscript',
		'gz' =>'application/x-gzip',
		'h'  =>'text/x-chdr',
		'html'=>'text/html',
		'pas'=>'text/x-pascal',
		'pdf'=>'application/pdf',
		'ppt'=>'application/vnd.ms-powerpoint',
		'ps' =>'application/postscript',
		'rpm'=>'application/x-redhat-package-manager',
		'rtf'=>'application/rtf',
		'sdd'=>'application/vnd.stardivision.impress',
		'sdw'=>'application/vnd.stardivision.writer',
		'sit'=>'application/x-stuffit',
		'sxc'=>'application/vnd.sun.xml.calc',
		'sxi'=>'application/vnd.sun.xml.impress',
		'sxw'=>'application/vnd.sun.xml.writer',
		'tex'=>'text/x-tex',
		'tgz'=>'application/x-gtar',
		'txt'=>'text/plain',
		'xcf'=>'application/x-xcf',
		'xls'=>'application/vnd.ms-excel',
		'xml'=>'application/xml',
		'zip'=>'application/zip'
	);

	foreach ($types as $extension => $type_mime)
		spip_query_db("UPDATE spip_types_documents
		SET mime_type='$type_mime' WHERE extension='$extension'");
}

?>
