<?php

// Ce plug-in ajoute le raccourci ancre [#ancre<-]

function avant_propre_ancres($texte) {
	$regexp = "|\[#?([^][]*)<-\]|";
	if (preg_match_all($regexp, $texte, $matches, PREG_SET_ORDER))
	foreach ($matches as $regs)
		$texte = str_replace($regs[0],
		'<a name="'.entites_html($regs[1]).'"></a>', $texte);

	return $texte;
}

completer_fonction("propre", "avant_propre_ancres", "");

?>