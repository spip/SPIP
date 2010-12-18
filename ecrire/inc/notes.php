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

if (!defined('_ECRIRE_INC_VERSION')) return;

//
// Notes de bas de page
//

// argument = true: empiler l'etat courant, initialiser un nouvel etat
// argument = false: restaurer l'etat precedent, denonce un etat courant perdu
// argument chaine, on y recherche les notes et on les renvoie en tableau
// argument tableau, texte de notes a rajouter dans ce qu'on a deja
// le dernier cas retourne la composition totale
// en particulier, envoyer un tableau vide permet de tout recuperer
// C'est stocke dans la globale $les_notes, mais pas besoin de le savoir

function inc_notes_dist($arg,$operation='traiter')
{
	static $pile = array();
	static $next_marqueur = 1;
	static $marqueur = 1;
	global $les_notes, $compt_note, $notes_vues;
	switch ($operation){
		case 'traiter':
			if (is_array($arg)) return traiter_les_notes($arg);
			else
				return traiter_raccourci_notes($arg, $marqueur>1?$marqueur:'');
			break;
		case 'empiler':
			#var_dump(">$compt_note:$marqueur");
			if ($compt_note==0)
				// si le marqueur n'a pas encore ete utilise, on le recycle dans la pile courante
				array_push($pile, array(@$les_notes, @$compt_note, $notes_vues,0));
			else {
				// sinon on le stocke au chaud, et on en cree un nouveau
				array_push($pile, array(@$les_notes, @$compt_note, $notes_vues,$marqueur));
				$next_marqueur++; // chaque fois qu'on rempile on incremente le marqueur general
				$marqueur = $next_marqueur; // et on le prend comme marqueur courant
			}
			$les_notes = '';
			$compt_note = 0;
			break;
		case 'depiler':
			#$prev_notes = $les_notes;
			if (strlen($les_notes)) spip_log("notes perdues");
			// si le marqueur n'a pas servi, le liberer
			if (!strlen($les_notes) AND $marqueur==$next_marqueur)
				$next_marqueur--;
			// on redepile tout suite a une fin d'inclusion ou d'un affichage des notes
			list($les_notes, $compt_note, $notes_vues, $marqueur) = array_pop($pile);
			#$les_notes .= $prev_notes;
			#var_dump("<$compt_note:$marqueur");
			// si pas de marqueur attribue, on le fait
			if (!$marqueur){
				$next_marqueur++; // chaque fois qu'on rempile on incremente le marqueur general
				$marqueur = $next_marqueur; // et on le prend comme marqueur courant
			}
			break;
		case 'sauver_etat':
			if ($compt_note OR $marqueur>1 OR $next_marqueur>1)
				return array($les_notes, $compt_note, $notes_vues, $marqueur,$next_marqueur);
			else
				return ''; // rien a sauver
			break;
		case 'restaurer_etat':
			if ($arg AND is_array($arg)) // si qqchose a restaurer
				list($les_notes, $compt_note, $notes_vues, $marqueur,$next_marqueur) = $arg;
			break;
		case 'contexter_cache':
			if ($compt_note OR $marqueur>1 OR $next_marqueur>1)
				return array("$compt_note:$marqueur:$next_marqueur");
			else
				return '';
			break;
		case 'reset_all': // a n'utiliser qu'a fins de test
			if (strlen($les_notes)) spip_log("notes perdues [reset_all]");
			$pile = array();
			$next_marqueur = 1;
			$marqueur = 1;
			$les_notes = '';
			$compt_note = 0;
			$notes_vues = array();
			break;
	}
}

define('_RACCOURCI_NOTES', ', *\[\[(\s*(<([^>\'"]*)>)?(.*?))\]\],msS');

function traiter_raccourci_notes($letexte, $marqueur_notes)
{
	global $compt_note,   $les_notes, $notes_vues;
	global $ouvre_ref, $ferme_ref;

	if (!preg_match_all(_RACCOURCI_NOTES, $letexte, $m, PREG_SET_ORDER))
		return array($letexte, array());

	// quand il y a plusieurs series de notes sur une meme page
	$mn =  !$marqueur_notes ? '' : ($marqueur_notes.'-');
	$mes_notes = array();
	foreach ($m as $r) {
		list($note_source, $note_all, $ref, $nom, $note_texte) = $r;

		// reperer une note nommee, i.e. entre chevrons
		// On leve la Confusion avec une balise en regardant
		// si la balise fermante correspondante existe
		// Cas pathologique:   [[ <a> <a href="x">x</a>]]

		if (!(isset($nom) AND $ref
		AND ((strpos($note_texte, '</' . $nom .'>') === false)
		     OR preg_match(",<$nom\W.*</$nom>,", $note_texte)))) {
			$nom = ++$compt_note;
			$note_texte = $note_all;
		}

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
		if ($nom) $nom = "$ouvre_ref<a href='#nb$ancre' class='spip_note' rel='footnote'$title$att>$nom</a>$ferme_ref";

		$pos = strpos($letexte, $note_source);
		$letexte = substr($letexte, 0, $pos)
		. code_echappement($nom)
		. substr($letexte, $pos + strlen($note_source));

	}
	return array($letexte, $mes_notes);
}


// http://doc.spip.org/@traiter_les_notes
function traiter_les_notes($notes) {
	global $ouvre_note, $ferme_note;

	$mes_notes = '';
	if ($notes) {
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
		$mes_notes = propre('<p>' . $mes_notes);
		if ($GLOBALS['class_spip'])
			$mes_notes = str_replace('<p class="spip">', '<p class="spip_note">', $mes_notes);
	}
	return ($GLOBALS['les_notes'] .= $mes_notes);
}

?>
