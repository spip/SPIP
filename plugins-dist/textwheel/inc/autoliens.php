<?php


// Les URLs brutes sont converties en <a href='url'>url</a>
// http://doc.spip.org/@traiter_raccourci_liens
function tw_autoliens($t) {

	defined('_EXTRAIRE_LIENS') || define('_EXTRAIRE_LIENS', ',' . '\[[^\[\]]*(?:<-|->).*?\]' . '|<a\b.*?</a\b' . '|<\w.*?>' . '|((?:https?:/|www\.)[^"\'\s\[\]\}\)<>]*)' .',imsS');

	$t = preg_replace_callback(_EXTRAIRE_LIENS, 'tw_traiter_autoliens', $t);

	include_spip('inc/ressource');
	if (defined('_EXTRAIRE_RESSOURCES'))
	$t = preg_replace_callback(_EXTRAIRE_RESSOURCES, 'traiter_ressources', $t);

	// echapper les autoliens eventuellement inseres (en une seule fois)
	if (strpos($t,"<html>")!==false)
		$t = echappe_html($t);
	return $t;
}


// callback pour la fonction autoliens()
// http://doc.spip.org/@autoliens_callback
function tw_traiter_autoliens($r) {
	if (count($r)<2) return reset($r);
	list($tout, $l) = $r;
	if (!$l) return $tout;
	// reperer le protocole
	if (preg_match(',^(https?):/*,S', $l, $m)) {
		$l = substr($l, strlen($m[0]));
		$protocol = $m[1];
	} else 	$protocol = 'http';
	// valider le nom de domaine
	if (!preg_match(_EXTRAIRE_DOMAINE, $l)) return $tout;
	// supprimer les ponctuations a la fin d'une URL
	preg_match('/^(.*?)([,.;?]?)$/', $l, $k);
	$url = $protocol.'://'.$k[1];
	$lien = charger_fonction('lien', 'inc');
	// deux fois <html> car inc_lien echappe un coup et restaure ensuite
	// => un perd 1 <html>
	$r = $lien($url,"<html><html>$url</html></html>",'','','','nofollow') . $k[2];

	// ajouter la class auto
	$r = inserer_attribut($r, 'class', trim(extraire_attribut($r,'class').' auto'));

	// si l'original ne contenait pas le 'http:' on le supprime du clic
	return ($m ? $r : str_replace('>http://', '>', $r));
}



?>