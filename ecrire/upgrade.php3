<?php

include ("inc_version.php3");

include_ecrire ("inc_connect.php3");
include_ecrire ("inc_auth.php3");
include_ecrire ("inc_admin.php3");
include_ecrire ("inc_acces.php3");
include_ecrire ("inc_meta.php3");
include_ecrire("inc_texte.php3");
include_ecrire("inc_filtres.php3");

$upgrade_titre = "mise &agrave; niveau de votre base MySQL";

// commentaire standard upgrade
$commentaire = "Vous venez de mettre &agrave; jour les fichiers SPIP.
	Il faut maintenant mettre &agrave; niveau la base de donn&eacute;es
	du site.";

// erreur downgrade (cas de double installation de fichiers SPIP sur une meme base)
if ($spip_version < (double) lire_meta('version_installee'))
	$commentaire = "{{Attention!}} Vous avez install&eacute; une version
		des fichiers SPIP {ant&eacute;rieure} &agrave; celle qui se trouvait
		auparavant sur ce site: votre base de donn&eacute;es risque d'&ecirc;tre
		perdue et votre site ne fonctionnera plus.<br>{{R&eacute;installez les
		fichiers de SPIP.}}";

// qu'est-ce que tu fais ici?
if ($spip_version == (double) lire_meta('version_installee')) {
	@header("Location: index.php3");
	exit;
}

debut_admin($upgrade_titre, $commentaire);

include_ecrire ("inc_base.php3");

creer_base();
maj_base();
ecrire_acces();

$hash = calculer_action_auteur("purger_squelettes");
$redirect = rawurlencode("index.php3");

fin_admin($upgrade_titre);

@header ("Location: ../spip_cache.php3?purger_squelettes=oui&id_auteur=$connect_id_auteur&hash=$hash&redirect=$redirect");

?>
