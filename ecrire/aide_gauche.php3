<?php

include ("inc_version.php3");
include_ecrire("inc_lang.php3");
utiliser_langue_visiteur();
gerer_menu_langues();

if (file_exists($flag_ecrire ? "inc_connect.php3" : "ecrire/inc_connect.php3")) {
	include_ecrire("inc_auth.php3");
	$aide_statut = ($connect_statut == '1comite') ? 'redac' : 'admin';
}
else $aide_statut = 'admin';

?>
<HTML>
<HEAD>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">
<style>
<!--
	a {text-decoration: none; }
	A:Hover {text-decoration: underline;}

-->
</style>
</HEAD>

<body bgcolor="#FFFFFF" text="#000000" link="#E86519" vlink="#6E003A" alink="#FF9900" TOPMARGIN="0" LEFTMARGIN="0" MARGINWIDTH="0" MARGINHEIGHT="0"<?php
	if ($spip_lang_rtl)
		echo " dir='rtl'";
echo ">";

function rubrique($titre, $statut = "redac") {
	global $aide;
	global $ligne;
	global $larubrique;
	global $texte;
	global $afficher;
	global $aff_ligne;
	global $rubrique;
	global $les_rub;

	global $aide_statut;

	if (($statut == "admin" AND $aide_statut == "admin") OR ($statut == "redac")) {
		$larubrique++;
		$ligne++;

		$texte[$ligne]="<TR><TD><IMG SRC='img_pack/rien.gif' BORDER=0 WIDTH=10 HEIGHT=1></TD></TR><TD BGCOLOR='#044476' COLSPAN=2><A HREF='#LIEN'>#IMG</A>	<B><A HREF='#LIEN'><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#FFFFFF'>$titre</FONT></A></B></TD></TR>";
		$rubrique[$ligne]=$larubrique;

		if (ereg(",$larubrique,","$les_rub")){
			$afficher[$larubrique]=1;
		}else{
			$afficher[$larubrique]=0;
		}
		
		$aff_ligne[$ligne]=1;
	}
}

function article($titre, $lien, $statut = "redac") {
	global $aide;
	global $ligne;
	global $larubrique;
	global $rubrique;
	global $texte;
	global $afficher;
	global $les_rub;
	global $aide_statut;
	global $spip_lang_rtl;

	if (($statut == "admin" AND $aide_statut == "admin") OR ($statut == "redac")) {
		$ligne++;

		$rubrique[$ligne]=$larubrique;
		
		if ($aide==$lien) {
			$afficher[$larubrique]=1;
			$texte[$ligne]= "<TR><TD BGCOLOR='#DDDDDD' ALIGN='right' COLSPAN=2><FONT FACE='Arial,Helvetica,sans-serif' SIZE=2>$titre</font> <IMG SRC='img_pack/triangle$spip_lang_rtl.gif' BORDER=0 ALIGN='middle'></TD></TR>";
		}
		else {
			$texte[$ligne]= "<TR><TD><FONT FACE='Arial,Helvetica,sans-serif' SIZE=2><B><A HREF='aide_index.php3?aide=$lien&les_rub=$les_rub' TARGET='_top'><IMG SRC='img_pack/triangle$spip_lang_rtl.gif' BORDER=0></A></B></font></TD><TD BGCOLOR='#FFFFFF'><FONT FACE='Arial,Helvetica,sans-serif' SIZE=2><A HREF='aide_index.php3?aide=$lien&les_rub=$les_rub' TARGET='_top'>$titre</A></font></TD></TR>";
		}
	}
}



?>

<TABLE WIDTH="100%" BORDER=0 CELLPADDING=2 CELLSPACING=0>

<?php

if ($supp_rub) $les_rub=ereg_replace(",$supp_rub,","",$les_rub);
if ($addrub) $les_rub.=",$addrub,";

rubrique(_T('menu_aide_installation_spip'),"admin");
article(_T('menu_aide_installation_reactuliser_droits'), "install0", "admin");
article(_T('menu_aide_installation_connexion_mysql'), "install1", "admin");
article(_T('menu_aide_installation_choix_base'), "install2", "admin");
article(_T('menu_aide_installation_informations_personnelles'), "install5", "admin");
article(_T('menu_aide_installation_ftp'), "ftp_auth", "admin");
article(_T('menu_aide_installation_probleme_squelette'), "erreur_mysql", "admin");

rubrique(_T('menu_aide_articles'));
article(_T('menu_aide_articles_raccourcis_typo'),"raccourcis");
article(_T('menu_aide_articles_titres'),"arttitre");
article(_T('menu_aide_articles_choix_rubrique'),"artrub");
article(_T('menu_aide_articles_descriptif_rapide'),"artdesc");
article(_T('menu_aide_articles_chapeau'),"artchap");
article(_T('menu_aide_articles_redirection'),"artvirt","admin");
article(_T('menu_aide_articles_texte'),"arttexte");
article(_T('menu_aide_articles_date'),"artdate");
article(_T('menu_aide_articles_date_anterieure'),"artdate_redac");
article(_T('menu_aide_articles_auteurs'),"artauteurs");
article(_T('menu_aide_articles_logos'),"logoart","admin");
article(_T('menu_aide_articles_statut'),"artstatut");
article(_T('menu_aide_articles_proposer'),"artprop");
article(_T('menu_aide_articles_en_cours_modification'),"artmodif");


rubrique(_T('menu_aide_rubriques'));
article(_T('menu_aide_rubriques_structure'),"rubhier");
article(_T('menu_aide_rubriques_choix'),"rubrub","admin");
article(_T('menu_aide_rubriques_logo'),"rublogo","admin");

rubrique(_T('menu_aide_breves'));
article(_T('menu_aide_breves_breves'),"breves");
article(_T('menu_aide_breves_choix'),"brevesrub");
article(_T('menu_aide_breves_lien'),"breveslien");
article(_T('menu_aide_breves_statut'),"brevesstatut","admin");
article(_T('menu_aide_breves_logo'),"breveslogo","admin");

rubrique(_T('menu_aide_images_doc'));
article(_T('menu_aide_images_doc_inserer'),"ins_img");
article(_T('menu_aide_images_doc_joindre'),"ins_doc");
article(_T('menu_aide_images_doc_ftp'),"ins_upload","admin");

rubrique(_T('menu_aide_mots_cles'));
article(_T('menu_aide_mots_cles_principe'),"mots");
article(_T('menu_aide_mots_cles_mots_cles'),"artmots");
article(_T('menu_aide_mots_cles_groupes'),"motsgroupes","admin");


rubrique(_T('menu_aide_sites'));
article(_T('menu_aide_sites_referencer'),"reference");
article(_T('menu_aide_sites_syndiquer'),"rubsyn");
article(_T('menu_aide_sites_articles_syndiques'),"artsyn");
article(_T('menu_aide_sites_proxy'),"confhttpproxy","admin");

rubrique(_T('menu_aide_messagerie'));
article("<img src='img_pack/m_envoi.gif' align='left' border=0> "._T('menu_aide_messagerie_utilisateurs'),"messut");
article("<img src='img_pack/m_envoi_bleu.gif' align='left' border=0> "._T('menu_aide_messagerie_pense_bete'),"messpense");
article(_T('menu_aide_messagerie_calendrier'),"messcalen");
article(_T('menu_aide_messagerie_configuration_perso'),"messconf");


rubrique(_T('menu_aide_suivi_forum'),"admin");
article(_T('menu_aide_suivi_forum_suivi'),"suiviforum","admin");

rubrique(_T('menu_aide_suivi_forum_configuration'),"admin");
article(_T('menu_aide_suivi_forum_nom_adresse'),"confnom","admin");
article(_T('menu_aide_suivi_forum_contenu_articles'),"confart","admin");
article(_T('menu_aide_suivi_forum_articles_postes'),"confdates","admin");
article(_T('menu_aide_suivi_forum_fonctionnement'),"confforums","admin");
article(_T('menu_aide_suivi_forum_systeme_breves'),"confbreves","admin");
article(_T('menu_aide_suivi_forum_messagerie_interne'),"confmessagerie","admin");
article(_T('menu_aide_suivi_forum_statistiques'),"confstat","admin");
article(_T('menu_aide_suivi_forum_envoi_emails'),"confmails","admin");
article(_T('menu_aide_suivi_forum_moteur_recherche'),"confmoteur","admin");

rubrique(_T('menu_aide_interface_perso'));
article(_T('menu_aide_interface_perso_simplifiee'),"intersimple");
article(_T('menu_aide_interface_perso_cookie'),"cookie");
article(_T('menu_aide_interface_perso_deconnecter'),"deconnect");

for ($i=0; $i<=count($texte); $i++) {
	
	$larubrique=$rubrique[$i];
	$aff=$afficher[$larubrique];
	
	if ($aff == 1 OR $aff_ligne[$i] == 1) {
		if ($aff == 1) {
			$supp_rub="$larubrique";
			
			$texte[$i]=ereg_replace("#IMG","<img src='img_pack/triangle-bleu-bas.gif' alt='' width='14' height='14' border='0'>",$texte[$i]);
			$texte[$i]=ereg_replace("#LIEN","aide_gauche.php3?les_rub=$les_rub&supp_rub=$supp_rub&aide=$aide",$texte[$i]);
		}
		else {
			$ajouter_rub="$larubrique";
			$texte[$i]=ereg_replace("#IMG","<img src='img_pack/triangle-bleu.gif' alt='' width='14' height='14' border='0'>",$texte[$i]);
			$texte[$i]=ereg_replace("#LIEN","aide_gauche.php3?les_rub=$les_rub&addrub=$ajouter_rub&aide=$aide",$texte[$i]);

		}
		echo $texte[$i]."\n";
	}
}

echo '</TABLE>';

echo "<br><div align='center'>". menu_langues()."</div>";

?>
</BODY>
</HTML>
