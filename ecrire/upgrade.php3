<?php

include ("inc_version.php3");


// Si reinstallation necessaire, message ad hoc
if ($reinstall == 'oui') {
	if (!file_exists("inc_connect.php3")) {
		Header("Location: install.php3");
		exit;
	}

	@copy("inc_connect.php3", "inc_connect_install.php3");

	include_ecrire("inc_presentation.php3");
	install_debut_html("Mise &agrave; niveau de SPIP");
	echo "<p><b>Vous avez install&eacute; une nouvelle version de SPIP.</b><p> ";
	echo "Cette nouvelle version n&eacute;cessite une mise &agrave; jour plus ";
	echo "compl&egrave;te qu'&agrave; l'accoutum&eacute;e. ";
	echo "Si vous &ecirc;tes webmestre du site, veuillez effacer le fichier ";
	echo "<tt>inc_connect.php3</tt> du r&eacute;pertoire <tt>ecrire</tt> ";
	echo "et reprendre l'installation afin de mettre &agrave; jour vos ";
	echo "param&egrave;tres de connexion &agrave; la base de donn&eacute;es.";

	$link = new Link();
	echo "<p><div align='right'>";
	echo $link->getForm('GET');
	echo "<input type='submit' name='submit' value=\"Relancer l'installation\" class='fondl'>";
	echo "</form>\n";

	install_fin_html();
	exit;
}


include_ecrire ("inc_auth.php3");
include_ecrire ("inc_admin.php3");
include_ecrire ("inc_acces.php3");
include_ecrire ("inc_meta.php3");
include_ecrire ("inc_config.php3");
include_ecrire ("inc_texte.php3");
include_ecrire ("inc_filtres.php3");

$upgrade_titre = "mise &agrave; niveau de votre base MySQL";

// Commentaire standard upgrade
$commentaire = "Vous venez de mettre &agrave; jour les fichiers SPIP.
	Il faut maintenant mettre &agrave; niveau la base de donn&eacute;es
	du site.";

// Erreur downgrade (cas de double installation de fichiers SPIP sur une meme base)
if ($spip_version < (double) lire_meta('version_installee'))
	$commentaire = "{{Attention!}} Vous avez install&eacute; une version
		des fichiers SPIP {ant&eacute;rieure} &agrave; celle qui se trouvait
		auparavant sur ce site: votre base de donn&eacute;es risque d'&ecirc;tre
		perdue et votre site ne fonctionnera plus.<br>{{R&eacute;installez les
		fichiers de SPIP.}}";

// Qu'est-ce que tu fais ici?
if ($spip_version == (double) lire_meta('version_installee')) {
	@header("Location: index.php3");
	exit;
}

debut_admin($upgrade_titre, $commentaire);

include_ecrire ("inc_base.php3");

creer_base();
maj_base();
ecrire_acces();

init_config();

$hash = calculer_action_auteur("purger_cache");
$redirect = rawurlencode("index.php3");

fin_admin($upgrade_titre);

@header ("Location: ../spip_cache.php3?purger_cache=oui&id_auteur=$connect_id_auteur&hash=$hash&redirect=$redirect");

?>
