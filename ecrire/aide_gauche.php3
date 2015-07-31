<?php

include ("inc_version.php3");

$lastmodified = filemtime("aide_gauche.php3");
$headers_only = http_last_modified($lastmodified, time() + 24 * 3600);
if ($headers_only) exit;

if (file_exists($flag_ecrire ? "inc_connect.php3" : "ecrire/inc_connect.php3")) {
	include_ecrire("inc_auth.php3");
	$aide_statut = ($connect_statut == '1comite') ? 'redac' : 'admin';
}
else $aide_statut = 'admin';

include_ecrire("inc_lang.php3");
utiliser_langue_visiteur();
if ($var_lang) changer_langue($var_lang);

include_ecrire("inc_layer.php3");

?>
<HTML>
<HEAD>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">
<style>
<!--
	a {text-decoration: none; }
	A:Hover {text-decoration: underline;}

	.article-inactif {
		float: <?php echo $spip_lang_left; ?>;
		text-align: <?php echo $spip_lang_left; ?>;
		width: 80%;
		background: url(img_pack/triangle<?php echo $spip_lang_rtl; ?>.gif) <?php echo $spip_lang_left; ?> center no-repeat;
		margin: 2px;
		padding: 0px;
		padding-<?php echo $spip_lang_left; ?>: 20px;
		font-family: Arial, Sans, sans-serif;
		font-size: 12px;
	}
	.article-actif {
		float: <?php echo $spip_lang_right; ?>;
		text-align: <?php echo $spip_lang_right; ?>;
		width: 80%;
		background: url(img_pack/triangle<?php echo $spip_lang_rtl; ?>.gif) <?php echo $spip_lang_right; ?> center no-repeat;
		margin: 4px;
		padding: 0px;
		padding-<?php echo $spip_lang_right; ?>: 20px;
		font-family: Arial, Sans, sans-serif;
		font-size: 12px;
		font-weight: bold;
		color: black;
	}
	.article-actif:hover {
		text-decoration: none;
	}
	.rubrique {
		width: 90%;
		margin: 0px;
		margin-top: 6px;
		margin-bottom: 4px;
		padding: 4px;
		font-family: Trebuchet MS, Arial, Sans, sans-serif;
		font-size: 13px;
		font-weight: bold;
		color: black;
		background-color: #EEEECC;
		-moz-border-radius: 4px;
	}
-->
</style>
<script type='text/javascript'><!--
var curr_article;
function activer_article(id) {
	if (curr_article)
		document.getElementById(curr_article).className = 'article-inactif';
	if (id) {
		document.getElementById(id).className = 'article-actif';
		curr_article = id;
	}
}
//--></script>
<?php afficher_script_layer(); ?>
</HEAD>

<body bgcolor="#FFFFFF" text="#000000" link='#E86519' vlink='#6E003A' alink='#FF9900' TOPMARGIN="5" LEFTMARGIN="5" MARGINWIDTH="5" MARGINHEIGHT="5"<?php
	if ($spip_lang_rtl)
		echo " dir='rtl'";
echo ">";

function rubrique($titre, $statut = "redac") {
	global $ligne_rubrique;
	global $block_rubrique;
	global $titre_rubrique;
	global $afficher_rubrique, $ouvrir_rubrique;
	global $larubrique;

	global $aide_statut;

	$afficher_rubrique = 0;

	if (($statut == "admin" AND $aide_statut == "admin") OR ($statut == "redac")) {
		$larubrique++;
		$titre_rubrique = $titre;
		$ligne_rubrique = array();
		$block_rubrique = "block$larubrique";
		$afficher_rubrique = 1;
		$ouvrir_rubrique = 0;
	}
}

function fin_rubrique() {
	global $ligne_rubrique;
	global $block_rubrique;
	global $titre_rubrique;
	global $afficher_rubrique, $ouvrir_rubrique;
	global $texte;

	if ($afficher_rubrique && count($ligne_rubrique)) {
		echo "<div class='rubrique'>";
		if ($ouvrir_rubrique)
			echo bouton_block_visible($block_rubrique);
		else 
			echo bouton_block_invisible($block_rubrique);
		echo $titre_rubrique;
		echo "</div>\n";
		if ($ouvrir_rubrique)
			echo debut_block_visible($block_rubrique);
		else
			echo debut_block_invisible($block_rubrique);
		echo "\n";
		reset($ligne_rubrique);
		while (list(, $ligne) = each($ligne_rubrique)) {
			echo $texte[$ligne];
		}
		echo fin_block();
		echo "\n\n";
	}
}

function article($titre, $lien, $statut = "redac") {
	global $aide;
	global $ligne;
	global $ligne_rubrique;
	global $rubrique;
	global $texte;
	global $afficher_rubrique, $ouvrir_rubrique;
	global $aide_statut;
	global $spip_lang;

	if ($afficher_rubrique AND (($statut == "admin" AND $aide_statut == "admin") OR ($statut == "redac"))) {
		$ligne_rubrique[] = ++$ligne;
		
		$texte[$ligne] = '';
		$id = "ligne$ligne";
		$url = "aide_droite.php3?aide=$lien&var_lang=$spip_lang";
		if ($aide == $lien) {
			$ouvrir_rubrique = 1;
			$class = "article-actif";
			$texte[$ligne] .= "<script type='text/javascript'><!--\ncurr_article = '$id';\n// --></script>\n";
		}
		else {
			$class = "article-inactif";
		}
		$texte[$ligne] .= "<a class='$class' id='$id' href='$url' target='droite' ".
			"onClick=\"activer_article('$id');return true;\">$titre</a><br style='clear:both;'>\n";
	}
}


rubrique(_T('menu_aide_installation_spip'),"admin");
article(_T('menu_aide_installation_reactuliser_droits'), "install0", "admin");
article(_T('menu_aide_installation_connexion_mysql'), "install1", "admin");
article(_T('menu_aide_installation_choix_base'), "install2", "admin");
article(_T('menu_aide_installation_informations_personnelles'), "install5", "admin");
article(_T('menu_aide_installation_ftp'), "ftp_auth", "admin");
article(_T('menu_aide_installation_probleme_squelette'), "erreur_mysql", "admin");
fin_rubrique();

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
fin_rubrique();

rubrique(_T('menu_aide_rubriques'));
article(_T('menu_aide_rubriques_structure'),"rubhier");
article(_T('menu_aide_rubriques_choix'),"rubrub","admin");
article(_T('menu_aide_rubriques_logo'),"rublogo","admin");
fin_rubrique();

rubrique(_T('menu_aide_breves'));
article(_T('menu_aide_breves_breves'),"breves");
article(_T('menu_aide_breves_choix'),"brevesrub");
article(_T('menu_aide_breves_lien'),"breveslien");
article(_T('menu_aide_breves_statut'),"brevesstatut","admin");
article(_T('menu_aide_breves_logo'),"breveslogo","admin");
fin_rubrique();

rubrique(_T('menu_aide_images_doc'));
article(_T('menu_aide_images_doc_inserer'),"ins_img");
article(_T('menu_aide_images_doc_joindre'),"ins_doc");
article(_T('menu_aide_images_doc_ftp'),"ins_upload","admin");
fin_rubrique();

rubrique(_T('menu_aide_mots_cles'));
article(_T('menu_aide_mots_cles_principe'),"mots");
article(_T('menu_aide_mots_cles_mots_cles'),"artmots");
article(_T('menu_aide_mots_cles_groupes'),"motsgroupes","admin");
fin_rubrique();

rubrique(_T('menu_aide_sites'));
article(_T('menu_aide_sites_referencer'),"reference");
article(_T('menu_aide_sites_syndiquer'),"rubsyn");
article(_T('menu_aide_sites_articles_syndiques'),"artsyn");
article(_T('menu_aide_sites_proxy'),"confhttpproxy","admin");
fin_rubrique();

rubrique(_T('menu_aide_messagerie'));
article("<img src='img_pack/m_envoi$spip_lang_rtl.gif' border=0> "._T('menu_aide_messagerie_utilisateurs'),"messut");
article("<img src='img_pack/m_envoi_bleu$spip_lang_rtl.gif' border=0> "._T('menu_aide_messagerie_pense_bete'),"messpense");
article(_T('menu_aide_messagerie_calendrier'),"messcalen");
article(_T('menu_aide_messagerie_configuration_perso'),"messconf");
fin_rubrique();

rubrique(_T('menu_aide_suivi_forum'),"admin");
article(_T('menu_aide_suivi_forum_suivi'),"suiviforum","admin");
fin_rubrique();

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
fin_rubrique();

rubrique(_T('menu_aide_interface_perso'));
article(_T('menu_aide_interface_perso_simplifiee'),"intersimple");
article(_T('menu_aide_interface_perso_cookie'),"cookie");
article(_T('menu_aide_interface_perso_deconnecter'),"deconnect");
fin_rubrique();

?>
</BODY>
</HTML>
