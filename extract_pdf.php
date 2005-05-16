<?php

//
// Lit un document 'pdf' et extrait son contenu en texte brut
//

// NOTE : l'extracteur n'est pas oblige de convertir le contenu dans
// le charset du site, mais il *doit* signaler le charset dans lequel
// il envoie le contenu, de facon a ce qu'il soit converti au moment
// voulu ; dans le cas contraire le document sera lu comme s'il etait
// dans le charset iso-8859-1

function extracteur_pdf($fichier, &$charset) {

	$use_metamail = true;
	$use_pdftotext = true;

	$charset = 'iso-8859-1';

	if ($use_metamail) {
		exec('metamail -d -q -b -c application/pdf '.escapeshellarg($fichier), $r, $e);
		if (!$e) return join(' ', $r);
	}

	# pdftotext
	# http://www.glyphandcog.com/Xpdf.html
	if ($use_pdftotext) {
		# l'option "-enc utf-8" peut echouer ... dommage !
		exec('pdftotext '.escapeshellarg($fichier).' -', $r, $e);
		if (!$e) return join(' ', $r);
	}

/*
 * methode php (crado) inspiree de
 * http://www.livejournal.com/community/php/295413.html
	function ps2txt($data) {
		$c = $data;
		$c = str_replace('\\(', '*', $c);
		$c = str_replace('\\)', '*', $c);
		$c = str_replace(')(', ' ', $c);
		$c = preg_replace(',[)][^()]*?[(],ms', '', $c);
		if (preg_match(',[(]([^()]*)[)],ms', $c, $reg))
			$d .= $reg[1];
		return $d;
	}

	lire_fichier($fichier, $contenu);
	if (preg_match_all(",obj.*?stream\r\n(.*?)\rendstream\r,ms", $contenu, $regs, PREG_SET_ORDER)) {
		foreach ($regs as $r) {
			$s = gzuncompress($r[1]);
			$b .= ps2txt($s);
		}
		return $b;
	}
 *
 */

}

// Sait-on extraire ce format ?
// TODO: ici tester si les binaires fonctionnent
$GLOBALS['extracteur']['pdf'] = 'extracteur_pdf';

?>