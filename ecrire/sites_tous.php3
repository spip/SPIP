<?php

include ("inc.php3");


if ($connect_statut == '0minirezo' AND $supp_syndic) {
	$query="DELETE FROM spip_syndic WHERE id_syndic=$supp_syndic";
	$result=spip_query($query);
}


debut_page("Les sites r&eacute;f&eacute;renc&eacute;s","documents","sites");
debut_gauche();



debut_droite();



$proposer_sites=lire_meta("proposer_sites");




afficher_sites("Les sites r&eacute;f&eacute;renc&eacute;s", "SELECT * FROM spip_syndic WHERE syndication='non' AND statut='publie' ORDER BY nom_site");


afficher_sites("Les sites syndiqu&eacute;s", "SELECT * FROM spip_syndic WHERE syndication='oui' OR syndication='sus' AND statut='publie' ORDER BY nom_site");


afficher_sites("Les sites propos&eacute;s", "SELECT * FROM spip_syndic WHERE statut='prop' ORDER BY nom_site");

if ($connect_statut == '0minirezo' OR $proposer_sites > 0) {
	echo "<div align='right'>";
	$link = new Link('sites_edit.php3');
	$link->addVar('target', 'sites.php3');
	$link->addVar('redirect', $this_link->getUrl());
	icone("R&eacute;f&eacute;rencer un nouveau site", $link->getUrl(), "site-24.png", "creer.gif");
	echo "</div>";
}



afficher_sites("Ces sites ont rencontr&eacute; un probl&egrave;me de syndication", "SELECT * FROM spip_syndic WHERE syndication='off' AND statut='publie' ORDER BY nom_site");

if ($options == 'avancees' AND $connect_statut == '0minirezo') {
	afficher_sites("Les sites refus&eacute;s", "SELECT * FROM spip_syndic WHERE statut='refuse' ORDER BY nom_site");
}


fin_page();

?>

