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


//
if (!defined('_ECRIRE_INC_VERSION')) return;

global $tables_images, $tables_sequences, $tables_documents, $tables_mime, $mime_alias;

$tables_images = array(
		       // Images reconnues par PHP
		       'jpg' => 'JPEG',
		       'png' => 'PNG',
		       'gif' => 'GIF',

		       // Autres images (peuvent utiliser le tag <img>)
		       'bmp' => 'BMP',
		       'tif' => 'TIFF'
		       );

// Multimedia (peuvent utiliser le tag <embed>)

$tables_sequences = array(
			'aiff' => 'AIFF',
			'anx' => 'Annodex',
			'axa' => 'Annodex Audio',
			'axv' => 'Annodex Video',
			'asf' => 'Windows Media',
			'avi' => 'AVI',
			'dv'	=> 'Digital Video',
			'f4a' => 'Audio for Adobe Flash Player',
			'f4b' => 'Audio Book for Adobe Flash Player',
			'f4p' => 'Protected Video for Adobe Flash Player',
			'f4v' => 'Video for Adobe Flash Player',
			'flac' => 'Free Lossless Audio Codec',
			'flv' => 'Flash Video',
			'm4v' => 'MPEG4 Video',
			'm4a' => 'MPEG4 Audio',
			'm4b' => 'MPEG4 Audio',
			'm4p' => 'MPEG4 Audio',
			'mid' => 'Midi',
			'mng' => 'MNG',
			'mka' => 'Matroska Audio',
			'mkv' => 'Matroska Video',
			'mov' => 'QuickTime',
			'mp3' => 'MP3',
			'mp4' => 'MPEG4',
			'mpc' => 'Musepack',
			'mpg' => 'MPEG',
			'oga' => 'Ogg Audio',
			'ogg' => 'Ogg Vorbis',
			'ogv' => 'Ogg Video',
			'ogx' => 'Ogg Multiplex',
			'qt' => 'QuickTime',
			'ra' => 'RealAudio',
			'ram' => 'RealAudio',
			'rm' => 'RealAudio',
			'spx' => 'Ogg Speex',
			'svg' => 'Scalable Vector Graphics',
			'swf' => 'Flash',
			'wav' => 'WAV',
			'webm' => 'WebM',
			'wmv' => 'Windows Media',
			'3gp' => '3rd Generation Partnership Project'
		);

// Documents varies
$tables_documents = array(
			'abw' => 'Abiword',
			'ai' => 'Adobe Illustrator',
			'bz2' => 'BZip',
			'bin' => 'Binary Data',
			'blend' => 'Blender',
			'c' => 'C source',
			'cls' => 'LaTeX Class',
			'css' => 'Cascading Style Sheet',
			'csv' => 'Comma Separated Values',
			'deb' => 'Debian',
			'doc' => 'Word',
			'djvu' => 'DjVu',
			'dvi' => 'LaTeX DVI',
			'eps' => 'PostScript',
			'gz' => 'GZ',
			'h' => 'C header',
			'html' => 'HTML',
			'kml' => 'Keyhole Markup Language',
			'kmz' => 'Google Earth Placemark File',
			'm4u' => 'MPEG4 playliste',
			'pas' => 'Pascal',
			'pdf' => 'PDF',
			'pgn' => 'Portable Game Notation',
			'ppt' => 'PowerPoint',
			'ps' => 'PostScript',
			'psd' => 'Photoshop',
			'rpm' => 'RedHat/Mandrake/SuSE',
			'rtf' => 'RTF',
			'sdd' => 'StarOffice',
			'sdw' => 'StarOffice',
			'sit' => 'Stuffit',
			'sty' => 'LaTeX Style Sheet',
			'sxc' => 'OpenOffice.org Calc',
			'sxi' => 'OpenOffice.org Impress',
			'sxw' => 'OpenOffice.org',
			'tex' => 'LaTeX',
			'tgz' => 'TGZ',
			'torrent' => 'BitTorrent',
			'ttf' => 'TTF Font',
			'txt' => 'texte',
			'xcf' => 'GIMP multi-layer',
			'xspf' => 'XSPF',
			'xls' => 'Excel',
			'xml' => 'XML',
			'zip' => 'Zip',

			// open document format
			'odt' => 'opendocument text',
			'ods' => 'opendocument spreadsheet',
			'odp' => 'opendocument presentation',
			'odg' => 'opendocument graphics',
			'odc' => 'opendocument chart',
			'odf' => 'opendocument formula',
			'odb' => 'opendocument database',
			'odi' => 'opendocument image',
			'odm' => 'opendocument text-master',
			'ott' => 'opendocument text-template',
			'ots' => 'opendocument spreadsheet-template',
			'otp' => 'opendocument presentation-template',
			'otg' => 'opendocument graphics-template',

			// Open XML File Formats
			'docm' => 'Word',
			'docx' => 'Word',
			'dotm' => 'Word template',
			'dotx' => 'Word template',

			'potm' => 'Powerpoint template',
			'potx' => 'Powerpoint template',
			'ppam' => 'Powerpoint addin',
			'ppsm' => 'Powerpoint slideshow',
			'ppsx' => 'Powerpoint slideshow',
			'pptm' => 'Powerpoint',
			'pptx' => 'Powerpoint',

			'xlam' => 'Excel',
			'xlsb' => 'Excel binary',
			'xlsm' => 'Excel',
			'xlsx' => 'Excel',
			'xltm' => 'Excel template',
			'xltx' => 'Excel template'
		);

$tables_mime = array(
		// Images reconnues par PHP
		'jpg' => 'image/jpeg',
		'png' => 'image/png',
		'gif' => 'image/gif',

		// Autres images (peuvent utiliser le tag <img>)
		'bmp' => 'image/x-ms-bmp', // pas enregistre par IANA, variante: image/bmp
		'tif' => 'image/tiff',

		// Multimedia (peuvent utiliser le tag <embed>)
		'aiff' => 'audio/x-aiff',
		'asf' => 'video/x-ms-asf',
		'avi' => 'video/x-msvideo',
		'anx' => 'application/annodex',
		'axa' => 'audio/annodex',
		'axv' => 'video/annodex',
		'dv' => 'video/x-dv',
		'f4a' => 'audio/mp4',
		'f4b' => 'audio/mp4',
		'f4p' => 'video/mp4',
		'f4v' => 'video/mp4',
		'flac' => 'audio/x-flac',
		'flv' => 'video/x-flv',
		'm4a' => 'audio/mp4a-latm',
		'm4b' => 'audio/mp4a-latm',
		'm4p' => 'audio/mp4a-latm',
		'm4u' => 'video/vnd.mpegurl',
		'm4v' => 'video/x-m4v',
		'mid' => 'audio/midi',
		'mka' => 'audio/mka',
		'mkv' => 'video/mkv',
		'mng' => 'video/x-mng',
		'mov' => 'video/quicktime',
		'mp3' => 'audio/mpeg',
		'mp4' => 'application/mp4',
		'mpc' => 'audio/x-musepack',
		'mpg' => 'video/mpeg',
		'oga' => 'audio/ogg',
		'ogg' => 'audio/ogg',
		'ogv' => 'video/ogg',
		'ogx' => 'application/ogg',
		'qt' => 'video/quicktime',
		'ra' => 'audio/x-pn-realaudio',
		'ram' => 'audio/x-pn-realaudio',
		'rm' => 'audio/x-pn-realaudio',
		'spx' => 'audio/ogg',
		'svg' => 'image/svg+xml',
		'swf' => 'application/x-shockwave-flash',
		'wav' => 'audio/x-wav',
		'webm' => 'video/webm',
		'wmv' => 'video/x-ms-wmv',
		'3gp' => 'video/3gpp',

		// Documents varies
		'ai' => 'application/illustrator',
		'abw' => 'application/abiword',
		'bin' => 'application/octet-stream', # le tout-venant
		'blend' => 'application/x-blender',
		'bz2' => 'application/x-bzip2',
		'c'  => 'text/x-csrc',
		'css' => 'text/css',
		'csv' => 'text/csv',
		'deb' => 'application/x-debian-package',
		'doc' => 'application/msword',
		'djvu' => 'image/vnd.djvu',
		'dvi' => 'application/x-dvi',
		'eps' => 'application/postscript',
		'gz' => 'application/x-gzip',
		'h'  => 'text/x-chdr',
		'html' => 'text/html',
		'kml' => 'application/vnd.google-earth.kml+xml',
		'kmz' => 'application/vnd.google-earth.kmz',
		'pas' => 'text/x-pascal',
		'pdf' => 'application/pdf',
		'pgn' => 'application/x-chess-pgn',
		'ppt' => 'application/vnd.ms-powerpoint',
		'ps' => 'application/postscript',
		'psd' => 'image/x-photoshop', // pas enregistre par IANA
		'rpm' => 'application/x-redhat-package-manager',
		'rtf' => 'application/rtf',
		'sdd' => 'application/vnd.stardivision.impress',
		'sdw' => 'application/vnd.stardivision.writer',
		'sit' => 'application/x-stuffit',
		'sxc' => 'application/vnd.sun.xml.calc',
		'sxi' => 'application/vnd.sun.xml.impress',
		'sxw' => 'application/vnd.sun.xml.writer',
		'tex' => 'text/x-tex',
		'tgz' => 'application/x-gtar',
		'torrent' => 'application/x-bittorrent',
		'ttf' => 'application/x-font-ttf',
		'txt' => 'text/plain',
		'xcf' => 'application/x-xcf',
		'xls' => 'application/vnd.ms-excel',
		'xspf' => 'application/xspf+xml',
		'xml' => 'application/xml',
		'zip' => 'application/zip',

		// Open Document format
		'odt' => 'application/vnd.oasis.opendocument.text',
		'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		'odp' => 'application/vnd.oasis.opendocument.presentation',
		'odg' => 'application/vnd.oasis.opendocument.graphics',
		'odc' => 'application/vnd.oasis.opendocument.chart',
		'odf' => 'application/vnd.oasis.opendocument.formula',
		'odb' => 'application/vnd.oasis.opendocument.database',
		'odi' => 'application/vnd.oasis.opendocument.image',
		'odm' => 'application/vnd.oasis.opendocument.text-master',
		'ott' => 'application/vnd.oasis.opendocument.text-template',
		'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
		'otp' => 'application/vnd.oasis.opendocument.presentation-template',
		'otg' => 'application/vnd.oasis.opendocument.graphics-template',

		'cls' => 'text/x-tex',
		'sty' => 'text/x-tex',

		// Open XML File Formats
		'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
		'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',

		'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
		'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
		'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
		'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
		'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
		'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
		'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',

		'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
		'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
		'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
		'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template'
	);


	$mime_alias = array (
		'audio/x-mpeg' => 'audio/mpeg',
		'audio/webm' => 'video/webm',
		'audio/x-musepack' => 'audio/musepack',
		'application/x-ogg' => 'application/ogg',
		'video/mp4' => 'application/mp4',
		'video/flv' => 'video/x-flv',
		'audio/3gpp' => 'video/3gpp'
	);

?>
