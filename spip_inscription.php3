<?php
include ("ecrire/inc_version.php3");
include_ecrire("inc_presentation.php3");
include_local("inc-formulaire_inscription.php3");
include_local("inc-public-global.php3"); 
include_ecrire("inc_lang.php3"); 
utiliser_langue_site();
utiliser_langue_visiteur();
install_debut_html(_T('pass_vousinscrire'));
inclure_balise_dynamique(balise_formulaire_inscription_dyn($mode, $mail_inscription, $nom_inscription, $focus));
install_fin_html();
?>
