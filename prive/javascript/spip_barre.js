// Barre de raccourcis
function barre_inserer(text, el = document.activeElement) {
	// sur un élément qui gère la sélection
	if (el.setRangeText) {
		const [start, end] = [el.selectionStart, el.selectionEnd];
		// remplace la sélection (ou curseur) par le contenu demandé
		el.setRangeText(text, start, end, 'select');
		// place le curseur à la fin (désélectionne)
		const new_caret_pos = start + text.length;
		el.setSelectionRange(new_caret_pos, new_caret_pos);
		el.focus();
		// trigger une saisie clavier
		el.dispatchEvent(new Event('input'));
	}
}