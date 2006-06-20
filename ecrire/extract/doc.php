<?php

//
// Lit un document 'doc' et extrait son contenu en texte brut
//

// NOTE : l'extracteur n'est pas oblige de convertir le contenu dans
// le charset du site, mais il *doit* signaler le charset dans lequel
// il envoie le contenu, de facon a ce qu'il soit converti au moment
// voulu ; dans le cas contraire le document sera lu comme s'il etait
// dans le charset iso-8859-1

function extracteur_doc($fichier, &$charset) {

	$charset = 'iso-8859-1';

	@exec('metamail -d -q -b -c application/msword '.escapeshellarg($fichier), $r, $e);
	if (!$e) return @join(' ', $r);

	# wvText
	# http://wvware.sourceforge.net/
	$temp = tempnam(_DIR_CACHE, 'doc');
	@exec('wvText '.escapeshellarg($fichier).'> '.$temp, $r, $e);
	lire_fichier($temp, $contenu);
	@unlink($temp);
	if (!$e) return $contenu;

	# antiword
	# http://www.winfield.demon.nl/
	@exec('antiword '.escapeshellarg($fichier), $r, $e);
	if (!$e) return @join(' ', $r);

	# catdoc
	# http://www.45.free.net/~vitus/ice/catdoc/
	@exec('catdoc '.escapeshellarg($fichier), $r, $e);
	if (!$e) return @join(' ', $r);

}

// Sait-on extraire ce format ?
// TODO: ici tester si les binaires fonctionnent
$GLOBALS['extracteur']['doc'] = 'extracteur_doc';

?>
