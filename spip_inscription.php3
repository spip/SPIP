<?php
include ("ecrire/inc_version.php3");
include_ecrire("inc_presentation.php3");
include_local(find_in_path("inc-formulaire_inscription.php3"));
include_local("inc-public-global.php3"); 
include_local ("inc-cache.php3");

install_debut_html(_T('pass_vousinscrire'));
inclure_balise_dynamique(balise_formulaire_inscription_dyn($mode, $mail_inscription, $nom_inscription, $focus, $target));
# echo http_script_window_close(); # fait dans le squelette a present
install_fin_html();
?>
