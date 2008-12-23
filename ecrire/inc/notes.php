<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2009                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;

//
// Notes de bas de page
//

// argument chaine, on y recherche les notes et on les renvoie en tableau
// argument tableau,c'est les notes qu'on met en page dans $GLOBALS[les_notes]

function inc_notes_dist($arg)
{
	if (is_string($arg))
		return traiter_raccourci_notes($arg);
	else return traiter_les_notes($arg);
}

define('_RACCOURCI_NOTES', ', *\[\[(\s*(<([^>\'"]*)>)?.*?)\]\],msS');

function traiter_raccourci_notes($letexte)
{
	global $compt_note,  $marqueur_notes, $les_notes, $notes_vues;
	global $ouvre_ref, $ferme_ref;

	if (!preg_match_all(_RACCOURCI_NOTES, $letexte, $m, PREG_SET_ORDER))
		return array($letexte, array());

	// quand il y a plusieurs series de notes sur une meme page
	$mn =  !$marqueur_notes ? '' : ($marqueur_notes.'-');
	$mes_notes = array();
	foreach ($m as $r) {
		list($note_source, $note_texte, $ref, $nom) = $r;

		// note nommee ou pas ?
		if (isset($nom) AND strpos($note_texte, '</' . $nom .'>') === false) {
			$note_texte = preg_replace(",^\s*".preg_quote($ref,",").",ms",'',$note_texte);
		} 
		else
			$nom = ++$compt_note;

		// eliminer '%' pour l'attribut id
		$ancre = $mn . str_replace('%','_', rawurlencode($nom));

		// ne mettre qu'une ancre par appel de note (XHTML)
		$att = ($notes_vues[$ancre]++) ? '' : " id='nh$ancre'";

		// creer le popup 'title' sur l'appel de note
		if ($title = supprimer_tags(propre($note_texte))) {
			$title = " title='" . couper($title,80) . "'";
		}

		// ajouter la note aux notes precedentes
		if ($note_texte) {
			$mes_notes[]= array($ancre, $nom, $note_texte);
		}

		// dans le texte, mettre l'appel de note a la place de la note
		$pos = strpos($letexte, $note_source);
		$letexte = substr($letexte, 0, $pos) .
			code_echappement($nom
				? "$ouvre_ref<a href='#nb$ancre' class='spip_note' rel='footnote'$title$att>$nom</a>$ferme_ref"
				: '') .
			substr($letexte, $pos + strlen($note_source));
	}
	return array($letexte, $mes_notes);
}


// http://doc.spip.org/@traiter_les_notes
function traiter_les_notes($notes) {
	global $ouvre_note, $ferme_note;

	$mes_notes = '<p>';
	$title =  _T('info_notes');
	foreach ($notes as $r) {
		list($ancre, $nom, $texte) = $r;
		$atts = " href='#nh$ancre' id='nb$ancre' class='spip_note' title='$title $ancre' rev='footnote'";
		$mes_notes .= "\n\n"
		. code_echappement($nom
			? "$ouvre_note<a$atts>$nom</a>$ferme_note"
			: '')
		. $texte;
	}
	$mes_notes= propre($mes_notes);
	if ($GLOBALS['class_spip'])
		$mes_notes = str_replace('<p class="spip">', '<p class="spip_note">', $mes_notes);

	return ($GLOBALS['les_notes'] .= $mes_notes);
}

?>
