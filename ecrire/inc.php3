<?

if (!file_exists("inc_connect.php3")) {
	@header("Location: install.php3");
	exit;
}

//
// -- Attention --
// Cette fonction DOIT etre avant les includes
// car elle est necessitee par inc_texte.php3
//

function integre_image($id_document, $align, $affichage_detaille = false) {
	$query = "SELECT * FROM spip_documents WHERE id_document = $id_document";
	$result = mysql_query($query);
	if ($row = mysql_fetch_array($result)) {
		$id_document = $row['id_document'];
		$id_type = $row['id_type'];
		$titre = propre($row ['titre']);
		$descriptif = propre($row['descriptif']);
		$fichier = $row['fichier'];
		$largeur = $row['largeur'];
		$hauteur = $row['hauteur'];
		$taille = $row['taille'];
		$mode = $row['mode'];
		$id_vignette = $row['id_vignette'];

		if ($id_vignette) {
			$query_vignette = "SELECT * FROM spip_documents WHERE id_document = $id_vignette";
			$result_vignette = mysql_query($query_vignette);
			if ($row_vignette = @mysql_fetch_array($result_vignette)) {
				$fichier_vignette = $row_vignette['fichier'];
				$largeur_vignette = $row_vignette['largeur'];
				$hauteur_vignette = $row_vignette['hauteur'];
			}
		}
		else if ($mode == 'vignette') {
			$fichier_vignette = $fichier;
			$largeur_vignette = $largeur;
			$hauteur_vignette = $hauteur;
		}

		if ($fichier_vignette) {
			$vignette = "<img src='../$fichier_vignette' border=0";
			if ($largeur_vignette && $hauteur_vignette) {
				$vignette .= " width='$largeur_vignette' height='$hauteur_vignette'";
			}
			if ($titre) {
				$vignette .= " alt=\"$titre\" title=\"$titre\"";
			}
			if ($affichage_detaille)
				$vignette .= ">";
			else
				$vignette .= " hspace='5' vspace='3'>";
		}
		else {
			$vignette = "pas de pr&eacute;visualisation";
		}

		if ($mode == 'document' OR $affichage_detaille) {
			$vignette = "<a href='../$fichier'>$vignette</a>";
		}
		if ($affichage_detaille) {
			$query_type = "SELECT * FROM spip_types_documents WHERE id_type=$id_type";
			$result_type = mysql_query($query_type);
			if ($row_type = @mysql_fetch_array($result_type)) {
				$type = $row_type['titre'];
			}
			else $type = 'fichier';

			$taille_ko = floor($taille / 1024);

			$retour = "<table cellpadding=5 cellspacing=0 border=0 align='$align'>\n";
			$retour .= "<tr><td align='center'>\n<div class='spip_documents'>\n";
			$retour .= $vignette;

			if ($titre) $retour .= "<br><b>$titre</b>";
			if ($descriptif) $retour .= "<br>$descriptif";
			if ($fichier) $retour .= "<br>$type - $taille_ko&nbsp;ko";
			if ($largeur && $hauteur) $retour .= "<br>$largeur x $hauteur pixels";
			
			$retour .= "</div>\n</td></tr>\n</table>\n";
		}
		else $retour = $vignette;
	}
	return $retour;
}


include ("inc_version.php3");

include_local ("inc_connect.php3");
include_local ("inc_meta.php3");
include_local ("inc_auth.php3");
include_local ("inc_texte.php3");
include_local ("inc_urls.php3");
include_local ("inc_mail.php3");
include_local ("inc_admin.php3");
include_local ("inc_layer.php3");
include_local ("inc_sites.php3");
include_local ("inc_index.php3");

if (!file_exists("inc_meta_cache.php3")) ecrire_metas();


//
// Cookies de presentation
//

$options = $HTTP_COOKIE_VARS['spip_options'];
$graphisme = $HTTP_COOKIE_VARS['spip_graphisme'];

if (!$graphisme) $graphisme="0";

$fond = substr($graphisme,0,1);


if ($set_fond) {
	$fond = floor($set_fond);
	setcookie('spip_graphisme', $fond, time()+(3600*24*365));
}

if ($set_survol) {
	setcookie('spip_survol', $set_survol, time()+(3600*24*365));
	$spip_survol=$set_survol;
}

if ($set_couleur) {
	$couleur= floor($set_couleur);
	setcookie('spip_couleur', $couleur, time()+(3600*24*365));
	$spip_couleur=$couleur;
}

if ($set_options == 'avancees') {
	setcookie('spip_options', 'avancees', time()+(3600*24*365));
	$options = 'avancees';
}
if ($set_options == 'basiques') {
	setcookie('spip_options', 'basiques', time()+(3600*24*365));
	$options = 'basiques';
}


//
// Gestion de la configuration globale du site
//

if ($envoi_now) {
	effacer_meta('majnouv');
}

if (!$adresse_site) {
	$nom_site_spip = lire_meta("nom_site");
	$adresse_site = lire_meta("adresse_site");
	$activer_breves = lire_meta("activer_breves");
	$activer_statistiques = lire_meta("activer_statistiques");
	$articles_mots = lire_meta("articles_mots");
}

if (!$nom_site_spip) {
	$nom_site_spip = "Mon site SPIP";
	ecrire_meta("nom_site", $nom_site_spip);
	ecrire_metas();
}

if (!$adresse_site) {
	$adresse_site = "http://$HTTP_HOST".substr($REQUEST_URI, 0, strpos($REQUEST_URI, "/ecrire"));
	ecrire_meta("adresse_site", $adresse_site);
	ecrire_metas();
}


function tester_rubrique_vide($id_rubrique) {
	$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent='$id_rubrique' LIMIT 0,1";
	list($n) = mysql_fetch_row(mysql_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_article FROM spip_articles WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prepa' OR statut='prop') LIMIT 0,1";
	list($n) = mysql_fetch_row(mysql_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_breve FROM spip_breves WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prop') LIMIT 0,1";
	list($n) = mysql_fetch_row(mysql_query($query));
	if ($n > 0) return false;

	$query = "SELECT id_syndic FROM spip_syndic WHERE id_rubrique='$id_rubrique' AND (statut='publie' OR statut='prop') LIMIT 0,1";
	list($n) = mysql_fetch_row(mysql_query($query));
	if ($n > 0) return false;

	return true;
}


function aide ($aide) {
	return " <FONT SIZE=1>[<B><A HREF='#' onMouseDown=\"window.open('aide_index.php3?aide=$aide','myWindow','scrollbars=yes,resizable=yes,width=550')\">AIDE</A></B>]</FONT>";
}

switch ($spip_couleur){

	case 1:
		/// Vert
		$couleur_foncee="#02531B";
		$couleur_claire="#CFFEDE";
		break;
	case 2:
		/// Rouge
		$couleur_foncee="#640707";
		$couleur_claire="#FFE0E0";
		break;
	case 3:
		/// Jaune
		$couleur_foncee="#666500";
		$couleur_claire="#FFFFE0";
		break;
	case 4:
		/// Violet
		$couleur_foncee="#340049";
		$couleur_claire="#F9EBFF";
		break;
	case 5:
		/// Gris
		$couleur_foncee="#3F3F3F";
		$couleur_claire="#F2F2F2";
		break;
	case 6:
		/// Bleu
		$couleur_foncee="#044476";
		$couleur_claire="#EDF3FE";
		break;
	default:
		/// Bleu
		$couleur_foncee="#044476";
		$couleur_claire="#EDF3FE";
}

// affiche un bouton imessage
function bouton_imessage($destinataire, $row = '') {
	// si on passe "force" au lieu de $row, on affiche l'icone sans verification
	global $connect_id_auteur;

	$url = "message_edit.php3?";

	// verifier que ce n'est pas un auto-message
	if ($destinataire == $connect_id_auteur)
		return;
	// verifier que le destinataire a un login

	if ($row != "force") {
		$login_req = "select login, messagerie from spip_auteurs where id_auteur=$destinataire AND en_ligne>DATE_SUB(NOW(),INTERVAL 15 DAY)";
		$row = mysql_fetch_array(mysql_query($login_req));
		
		if (($row['login'] == "") OR ($row['messagerie'] == "non")) {
			return;
		}
	}
	$url .= "dest=$destinataire&";
	$url .= "new=oui&type=normal";
	
	$texte_bouton = "<IMG SRC='IMG2/m_envoi.gif' WIDTH='14' HEIGHT='7' BORDER='0'>";
	return "<a href='$url'>$texte_bouton</a>";
}

function debut_page($titre = "") {
	global $couleur_foncee;
	global $couleur_claire;
	$nom_site_spip=htmlspecialchars(lire_meta("nom_site"));
	$titre = textebrut(typo($titre));

	if ($nom_site_spip == "")
		$nom_site_spip="SPIP";
	
	?>
	<html>
	<head>
	<title>[<? echo $nom_site_spip; ?>] <? echo $titre; ?></TITLE>
	<meta http-equiv="Expires" content="0">
	<meta http-equiv="cache-control" content="no-cache,no-store">
	<meta http-equiv="pragma" content="no-cache">
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style><!--
	.forml {width: 100%; background-color: #E4E4E4; background-position: center bottom; float: none; color: #000000}
	.formo {width: 100%; background-color: <? echo $couleur_claire; ?>; background-position: center bottom; float: none;}
	.fondl {background-color: <? echo $couleur_claire; ?>; background-position: center bottom; float: none; color: #000000}
	.fondo {background-color: <? echo $couleur_foncee; ?>; background-position: center bottom; float: none; color: #FFFFFF}
	.fondf {background-color: #FFFFFF; border-style: solid ; border-width: 1; border-color: #E86519; color: #E86519}
	.profondeur {border-right-color:white; border-top-color:#666666; border-left-color:#666666; border-bottom-color:white; border-style:solid}
	.hauteur {border-right-color:#666666; border-top-color:white; border-left-color:white; border-bottom-color:#666666; border-style:solid}
	label {cursor: pointer;}
	.arial1 {font-family: Arial, Helvetica, sans-serif; font-size: 10px;}
	.arial2 {font-family: Arial, Helvetica, sans-serif; font-size: 12px;}

	a {text-decoration: none;}
	a:hover {color:#FF9900; text-decoration: underline;}
	a.spip_in  {background-color:#eeeeee;}
	a.spip_out {}
	a.spip_note {}
	.spip_recherche {width : 100%}
	.spip_cadre { 
		width : 100%;
		background-color: #FFFFFF; 
		padding: 5px; 
	}


	.boutonlien {
		font-family: Verdana,Arial,Helvetica,sans-serif;
		font-weight: bold;
		font-size: 9px;
	}
	a.boutonlien:hover {color:#454545; text-decoration: none;}
	a.boutonlien {color:#808080; text-decoration: none;}


	h3.spip {
		font-family: Verdana,Arial,Helvetica,sans-serif;
		font-weight: bold;
		font-size: 115%;
		text-align: center;
	}
	.spip_documents{
		font-family: Verdana,Arial,Helvetica,sans-serif;
		font-size : 70%;
	}
	table.spip {
	}
	table.spip tr.row_first {
		background-color: #FCF4D0;
	}
	table.spip tr.row_odd {
		background-color: #C0C0C0;
	}
	table.spip tr.row_even {
		background-color: #F0F0F0;
	}
	table.spip td {
		padding: 1px;
		text-align: left;
		vertical-align: center;
	}

--></style>
<?
afficher_script_layer();
?>
<script language="JavaScript">
<!--
	function MM_preloadImages() { //v3.0
		var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
		var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
		if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
	}

//-->
</script>
</head>
<body bgcolor="#E4E4E4" <?
	global $fond;
	if ($fond==1) $img='IMG2/rayures.gif';
	if ($fond==2) $img='IMG2/blob.gif';
	if ($fond==3) $img='IMG2/carreaux.gif';
	if ($fond==4) $img='IMG2/fond-trame.gif';
	if ($fond==5) $img='IMG2/degrade.jpg';
	if (!$img) $img='IMG2/rayures.gif';

	echo "background='$img'";
	
	?> text="#000000" link="#E86519" vlink="#6E003A" alink="#FF9900" >
	
	<center>
	
<?
	global $spip_survol;
	if ($spip_survol=="off"){

	?>	
		
		<table cellpadding=0 cellspacing=0 border=0>
		<td valign="top">
		<table cellpadding=0 cellspacing=0 border=0>
		<tr><td width=179>
		<a href="naviguer.php3" onMouseOver="texte.src='IMG2/naviguer-texte.gif'" onMouseOut="texte.src='IMG2/rien.gif'"><img src="IMG2/naviguer-off.gif" name="naviguer" alt="Naviguer" width="56" height="79" border="0"></A><A HREF="index.php3" onMouseOver="texte.src='IMG2/asuivre-texte.gif'" onMouseOut="texte.src='IMG2/rien.gif'"><img src="IMG2/asuivre-off.gif" name="asuivre" alt="A suivre" width="69" height="79" border="0"></A><A HREF="articles_tous.php3" onMouseOver="texte.src='IMG2/tout-texte.gif'" onMouseOut="texte.src='IMG2/rien.gif'"><img src="IMG2/tout-off.gif" name="tout" alt="Tout le site" width="54" height="79" border="0"></A>
		</td></tr>
		<tr><td><img src="IMG2/rien.gif" name="texte" alt="" width="179" height="24" border="0"></td>
		</tr></table>
		</td>
		<td valign="top">
		<table cellpadding="0" cellspacing="0" border="0">
		<tr><td>
		<?
		global $articles_mots;
		if ($articles_mots != "non"){
		?>
		<a href="mots_tous.php3" onMouseOver="fond.src='IMG2/cles-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/cles-off.gif" name="cles" alt="Les mots-cl&eacute;s" width="78" height="58" border="0"></A>
		<?
	}else{
	?><img src="IMG2/cles-non.gif" name="cles" alt="Les mots-cl&eacute;s" width="78" height="58" border="0"><?
	}
	?></td></tr>
		<tr><td><? global $activer_breves;
		 if ($activer_breves!="non"){ ?><A HREF="breves.php3" onMouseOver="fond.src='IMG2/breves-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/breves-off.gif" name="breves" alt="Les br&egrave;ves" width="78" height="45" border="0"></A><?}else{ ?><img src="IMG2/breves-non.gif" name="breves" alt="Les br&egrave;ves" width="78" height="45" border="0"><?} ?></TD></TR>
		</table></td>
		<td valign="top">
		<table cellpadding=0 cellspacing=0 border=0>
		<tr><td><a href="auteurs.php3" onMouseOver="fond.src='IMG2/redacteurs-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/redacteurs-off.gif" name="redacteurs" alt="Les r&eacute;dacteurs" width="52"  height="58" border="0"></A><?
		global $options;
		global $connect_statut;
		if ($options=='avancees' AND $connect_statut == '0minirezo'){
		?><a href="controle_forum.php3" onMouseOver="fond.src='IMG2/suivre-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/suivre-off.gif" name="suivre" alt="Suivre les forums" width="58" height="58" border="0"></A><?

	global $activer_statistiques;

	if ($activer_statistiques=="non"){
		echo "<img src='IMG2/statistiques-non.gif' name='statistiques' alt='Statistiques' width='43' height='58' border='0'>";
	}else{

	?><A HREF="articles_class.php3" onMouseOver="fond.src='IMG2/statistiques-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/statistiques-off.gif" name="statistiques" alt="Statistiques" width="43" height="58" border="0"></A><?
	}
	?><?
	global $connect_toutes_rubriques;
	
	if ($connect_toutes_rubriques){
	?><A HREF="configuration.php3" onMouseOver="fond.src='IMG2/config-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/config-off.gif" name="config" alt="Configurer" width="44" height="58" border="0"></A><A HREF="admin_tech.php3" onMouseOver="fond.src='IMG2/sauvegarde-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/sauvegarde-off.gif" name="sauvegarde" alt="Sauvegarde de la base" width="53" height="58" border="0"></A><?
	}else{
		echo "<img src='IMG2/haut-vide.gif' alt='' width='97' height='58' border='0'>";
	}?>
	<?
		}else{
		?><img src="IMG2/haut-vide.gif" alt="" width="198" height="58" border="0"><?
		}
		?><a href="forum.php3" onMouseOver="fond.src='IMG2/forum-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/forum-off.gif" name="forum" alt="Forum interne" width="57" height="58" border="0"></A><A HREF="#" onMouseDown="window.open('aide_index.php3','myWindow','scrollbars=yes,resizable=yes,width=550')" onMouseOver="fond.src='IMG2/aide-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'"><img src="IMG2/aide-off.gif" name="aide" alt="A l'aide" width="50" height="58" border="0"></A></TD></TR>
		<tr><?
		if ($options != 'avancees'){
			echo "<td background='IMG2/rond-vert-simp.gif'>";
		}else{
			echo "<td background='IMG2/rond-vert-comp.gif'>";
		
		}
		?><img src="IMG2/rien.gif" name="fond" alt="" width="357" height="45" border="0" usemap="#map-interface"></td>
		</tr>
		</table>
		

		</td>
		<td valign="top">
		
		<table cellpadding=0 cellspacing=0 border=0>
		<tr><td><?
		global $adresse_site;
		if (strlen($adresse_site)<10) $adresse_site="../";

		echo "<a href='$adresse_site' onMouseOver=\"visiter2.src='IMG2/visiter-texte.gif'\" onMouseOut=\"visiter2.src='IMG2/rien.gif'\"><img src=\"IMG2/visiter-off.gif\" name=\"visiter\" alt=\"Visiter le site\" width=\"86\" height=\"79\" border=\"0\"></A>";
		?></td></tr>
		<tr><td><img src="IMG2/rien.gif" name="visiter2" alt="" width="86" height="24" border="0">
		</td>
		</tr></table>
		</td>
		</tr></table>
<?

}else{

?>		
		<table cellpadding=0 cellspacing=0 border=0>
		<td valign="top">
		<table cellpadding=0 cellspacing=0 border=0>
		<tr><td width=179>
		<a href="naviguer.php3" onMouseOver="naviguer.src='IMG2/naviguer-on.gif'; texte.src='IMG2/naviguer-texte.gif'" onMouseOut="naviguer.src='IMG2/naviguer-off.gif'; texte.src='IMG2/rien.gif'"><img src="IMG2/naviguer-off.gif" name="naviguer" alt="Naviguer" width="56" height="79" border="0"></A><A HREF="index.php3" onMouseOver="asuivre.src='IMG2/asuivre-on.gif'; texte.src='IMG2/asuivre-texte.gif'" onMouseOut="asuivre.src='IMG2/asuivre-off.gif'; texte.src='IMG2/rien.gif'"><img src="IMG2/asuivre-off.gif" name="asuivre" alt="A suivre" width="69" height="79" border="0"></A><A HREF="articles_tous.php3" onMouseOver="tout.src='IMG2/tout-on.gif'; texte.src='IMG2/tout-texte.gif'" onMouseOut="tout.src='IMG2/tout-off.gif'; texte.src='IMG2/rien.gif'"><img src="IMG2/tout-off.gif" name="tout" alt="Tout le site" width="54" height="79" border="0"></A>
		</td></tr>
		<tr><td><img src="IMG2/rien.gif" name="texte" alt="" width="179" height="24" border="0"></TD>
		</tr></table>
		</td>
		<td valign="top">
		<table cellpadding=0 cellspacing=0 border=0>
		<tr><td><?
		global $articles_mots;
		if ($articles_mots!="non"){
		?><A HREF="mots_tous.php3" onMouseOver="cles.src='IMG2/cles-on.gif'; fond.src='IMG2/cles-texte.gif'" onMouseOut="cles.src='IMG2/cles-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/cles-off.gif" name="cles" alt="Les mots-cl&eacute;s" width="78" height="58" border="0"></A><?
	}else{
	?><img src="IMG2/cles-non.gif" name="cles" alt="Les mots-cl&eacute;s" width="78" height="58" border="0"><?
	}
	?></td></tr>
		<tr><td><? global $activer_breves;
		 if ($activer_breves!="non"){ ?><A HREF="breves.php3" onMouseOver="breves.src='IMG2/breves-on.gif'; fond.src='IMG2/breves-texte.gif'" onMouseOut="breves.src='IMG2/breves-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/breves-off.gif" name="breves" alt="Les br&egrave;ves" width="78" height="45" border="0"></A><?}else{ ?><img src="IMG2/breves-non.gif" name="breves" alt="Les br&egrave;ves" width="78" height="45" border="0"><?} ?></TD></TR>
		</table></td>
		<td valign="top">
		<table cellpadding=0 cellspacing=0 border=0>
		<tr><td><a href="auteurs.php3" onMouseOver="redacteurs.src='IMG2/redacteurs-on.gif'; fond.src='IMG2/redacteurs-texte.gif'" onMouseOut="redacteurs.src='IMG2/redacteurs-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/redacteurs-off.gif" name="redacteurs" alt="Les r&eacute;dacteurs" width="52"  height="58" border="0"></A><?
		global $options;
		global $connect_statut;
		if ($options=='avancees' AND $connect_statut == '0minirezo'){
		?><a href="controle_forum.php3" onMouseOver="suivre.src='IMG2/suivre-on.gif'; fond.src='IMG2/suivre-texte.gif'" onMouseOut="suivre.src='IMG2/suivre-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/suivre-off.gif" name="suivre" alt="Suivre les forums" width="58" height="58" border="0"></A><?

	global $activer_statistiques;

	if ($activer_statistiques=="non"){
		echo "<img src='IMG2/statistiques-non.gif' name='statistiques' alt='Statistiques' width='43' height='58' border='0'>";
	}else{

	?><a href="articles_class.php3" onMouseOver="statistiques.src='IMG2/statistiques-on.gif'; fond.src='IMG2/statistiques-texte.gif'" onMouseOut="statistiques.src='IMG2/statistiques-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/statistiques-off.gif" name="statistiques" alt="Statistiques" width="43" height="58" border="0"></A><?
	}
	?><?
	 global $connect_toutes_rubriques;

	if ($connect_toutes_rubriques){	
	?><a href="configuration.php3" onMouseOver="config.src='IMG2/config-on.gif'; fond.src='IMG2/config-texte.gif'" onMouseOut="config.src='IMG2/config-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/config-off.gif" name="config" alt="Configurer" width="44" height="58" border="0"></A><A HREF="admin_tech.php3" onMouseOver="sauvegarde.src='IMG2/sauvegarde-on.gif'; fond.src='IMG2/sauvegarde-texte.gif'" onMouseOut="sauvegarde.src='IMG2/sauvegarde-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/sauvegarde-off.gif" name="sauvegarde" alt="Sauvegarde de la base" width="53" height="58" border="0"></A><?
	}else{
		echo "<img src='IMG2/haut-vide.gif' alt='' width='97' height='58' border='0'>";
	}
	
	?><?
		}else{
		?><img src="IMG2/haut-vide.gif" alt="" width="198" height="58" border="0"><?
		}
		?><a href="forum.php3" onMouseOver="forum.src='IMG2/forum-on.gif'; fond.src='IMG2/forum-texte.gif'" onMouseOut="forum.src='IMG2/forum-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/forum-off.gif" name="forum" alt="Forum interne" width="57" height="58" border="0"></A><A HREF="#" onMouseDown="window.open('aide_index.php3','myWindow','scrollbars=yes,resizable=yes,width=550')" onMouseOver="aide.src='IMG2/aide-on.gif'; fond.src='IMG2/aide-texte.gif'" onMouseOut="aide.src='IMG2/aide-off.gif'; fond.src='IMG2/rien.gif'"><img src="IMG2/aide-off.gif" name="aide" alt="A l'aide" width="50" height="58" border="0"></A></TD></TR>
		<TR><?
		if ($options != 'avancees'){
			echo "<td background='IMG2/rond-vert-simp.gif'>";
		}else{
			echo "<td background='IMG2/rond-vert-comp.gif'>";
		
		}
		?><img src="IMG2/rien.gif" name="fond" alt="" width="357" height="45" border="0" usemap="#map-interface"></td>
		</tr>
		</table>
		

		</td>
		<td valign="top">
		
		<table cellpadding=0 cellspacing=0 border=0>
		<tr><td><?
		global $adresse_site;
		if (strlen($adresse_site)<10) $adresse_site="../";

		echo "<A HREF='$adresse_site' onMouseOver=\"visiter.src='IMG2/visiter-on.gif'; visiter2.src='IMG2/visiter-texte.gif'\" onMouseOut=\"visiter.src='IMG2/visiter-off.gif'; visiter2.src='IMG2/rien.gif'\"><img src=\"IMG2/visiter-off.gif\" name=\"visiter\" alt=\"Visiter le site\" width=\"86\" height=\"79\" border=\"0\"></A>";
		?></td></tr>
		<tr><td><img src="IMG2/rien.gif" name="visiter2" alt="" width="86" height="24" border="0">
		</td>
		</tr></table>
		</td>
		</tr></table>
<?
}

?>


	<map name="map-interface">
	<area shape='rect' coords='19,29,31,44' href='interface.php3' onMouseOver="fond.src='IMG2/modifier-interface-texte.gif'" onMouseOut="fond.src='IMG2/rien.gif'">
	<?

	global $REQUEST_URI;
	global $requete_fichier;
	global $connect_id_auteur;
	
	if (!$requete_fichier) {
		$requete_fichier = substr($REQUEST_URI, strrpos($REQUEST_URI, '/') + 1);
	}
	$lien = ereg_replace("\&set_options=(basiques|avancees)", "", $requete_fichier);
	if (!ereg('\?', $lien)) $lien .= '?';

	if ($options=="avancees"){
		echo "<area shape='rect' coords='56,30,156,44' href='$lien&set_options=basiques' onMouseOver=\"fond.src='IMG2/interface-texte.gif'\" onMouseOut=\"fond.src='IMG2/rien.gif'\">";
	}else{
		echo "<area shape='rect' coords='163,30,268,44' href='$lien&set_options=avancees' onMouseOver=\"fond.src='IMG2/interface-texte.gif'\" onMouseOut=\"fond.src='IMG2/rien.gif'\">";
	}

	?>

	</map>

	<?


	/// Messagerie...
	
	global $changer_config;
	global $activer_messagerie;
	global $activer_imessage;
	global $connect_activer_messagerie;
	
	
	if ($changer_config != "oui") {
		$activer_messagerie = lire_meta("activer_messagerie");
		$activer_imessage = lire_meta("activer_imessage");
	}
	
	echo "<font face='verdana,arial,helvetica,sans-serif' size=1><b><a href='sites_tous.php3'><font color='#666666'><img src='IMG2/tous-sites.gif' align='middle' alt='' width='16' height='15' border='0'> TOUS LES SITES R&Eacute;F&Eacute;RENC&Eacute;S</font></a></b></font>";

	if ($activer_messagerie != 'non' AND $connect_activer_messagerie != 'non') {
		echo "<font face='verdana,arial,helvetica,sans-serif' size=1><b>";
		
		echo " &nbsp; &nbsp; <a href='messagerie.php3'><font color='#666666'><img src='IMG2/tous-messages.gif' align='middle' alt='' width='17' height='15' border='0'> TOUS VOS MESSAGES</font></a>";

		$result_messages = mysql_query("SELECT * FROM spip_messages, spip_auteurs_messages AS lien WHERE lien.id_auteur=$connect_id_auteur AND vu='non' AND statut='publie' AND type='normal' AND lien.id_message=spip_messages.id_message");
		$total_messages = @mysql_num_rows($result_messages);
		if ($total_messages == 1) {
			while($row = @mysql_fetch_array($result_messages)) {
				$ze_message=$row[0];
				echo " | <a href='message.php3?id_message=$ze_message'><font color='red'>VOUS AVEZ UN NOUVEAU MESSAGE</font></a>";
			}
		}
		if ($total_messages > 1) echo " | <a href='messagerie.php3'><font color='red'>VOUS AVEZ $total_messages NOUVEAUX MESSAGES</font></a>";
		

		$result_messages = mysql_query("SELECT messages.* FROM spip_messages AS messages, spip_auteurs_messages AS lien WHERE lien.id_auteur='$connect_id_auteur' AND messages.statut='publie' AND lien.id_message=messages.id_message AND messages.rv='oui' AND messages.date_heure>DATE_SUB(NOW(),INTERVAL 1 DAY) GROUP BY messages.id_message");
		$total_messages = @mysql_num_rows($result_messages);
		
		if ($total_messages == 1) {
			while ($row = @mysql_fetch_array($result_messages)) {
				$ze_message = $row[0];
				echo " | <a href='message.php3?id_message=$ze_message'><font color='red'>UN RENDEZ-VOUS</font></a> ";
			}
		}
		if ($total_messages > 1) echo " | <a href='calendrier.php3'><font color='red'>$total_messages RENDEZ-VOUS</font></a> ";

		echo "</b></font>";
	}
	
	echo " &nbsp; &nbsp; <font face='verdana,arial,helvetica,sans-serif' size=1><b><a href='calendrier.php3'><font color='#666666'><img src='IMG2/calendrier.gif' align='middle' alt='' width='14' height='18' border='0'> CALENDRIER</font></a></b></font>";
	 
}


function debut_gauche() {
	global $connect_statut, $cookie_admin;
	global $REQUEST_URI;
	global $options;
	global $requete_fichier;
	global $connect_id_auteur;

	if (!$requete_fichier) {
		$requete_fichier = substr($REQUEST_URI, strrpos($REQUEST_URI, '/') + 1);
	}
	$lien = $requete_fichier;
	if (!ereg('\?', $lien)) $lien .= '?';

	$lapage=$lien;
	if ($lapage=="?") $lapage="index.php3?";
	if (ereg("&",$lapage)) $lapage=substr($lapage,0,strpos($lapage,"&"));
	
	?>
	<br><br>
	
	<table width=700 cellpadding=0 cellspacing=0 border=0>
	
	<tr>
	<td width=180 valign="top">
	<font face='Georgia,Garamond,Times,serif' size=2>
	<?
	
	
	// Afficher les auteurs recemment connectes
	
	global $changer_config;
	global $activer_messagerie;
	global $activer_imessage;
	global $connect_activer_messagerie;
	global $connect_activer_imessage;
	
	if ($changer_config!="oui"){
		$activer_messagerie=lire_meta("activer_messagerie");
		$activer_imessage=lire_meta("activer_imessage");
	}

	if ($activer_messagerie!="non" AND $connect_activer_messagerie!="non"){
	
		debut_cadre_relief();

		echo "<a href='message_edit.php3?new=oui&type=normal'><img src='IMG2/m_envoi.gif' width='14' height='7' border='0'>";
		echo "<font color='#169249' face='verdana,arial,helvetica,sans-serif' size=1><b>&nbsp;NOUVEAU MESSAGE</b></font></a>";
		echo "\n<br><a href='message_edit.php3?new=oui&type=pb'><img src='IMG2/m_envoi_bleu.gif' width='14' height='7' border='0'>";
		echo "<font color='#044476' face='verdana,arial,helvetica,sans-serif' size=1><b>&nbsp;NOUVEAU PENSE-B&Ecirc;TE</b></font></a>";

		if ($activer_imessage != "non" AND ($connect_activer_imessage != "non" OR $connect_statut == "0minirezo")) {
		 	$query2 = "SELECT * FROM spip_auteurs WHERE spip_auteurs.id_auteur!=$connect_id_auteur AND spip_auteurs.imessage!='non' AND spip_auteurs.messagerie!='non' AND spip_auteurs.en_ligne>DATE_SUB(NOW(),INTERVAL 5 MINUTE)";
			$result_auteurs = mysql_query($query2);

			if (mysql_num_rows($result_auteurs) > 0) {
				echo "<font face='verdana,arial,helvetica,sans-serif' size=2>";
				echo "<p><b>Actuellement en ligne&nbsp;:</b>";
			
				while($row = mysql_fetch_array($result_auteurs)){
					$id_auteur = $row["id_auteur"];
					$nom_auteur = typo($row["nom"]);
					echo "<br>".bouton_imessage($id_auteur,$row)." $nom_auteur";
				}	
				echo "</font>";
			}
		}
		fin_cadre_relief();
	}	
}


function debut_droite() {
	//
	// Boite de recherche
	//

	echo '<p><form method="get" action="recherche.php3">';
	debut_cadre_relief();
	echo '<font face="verdana,arial,helvetica,sans-serif" size="2">';
	echo 'Recherche sur les titres des articles et des br&egrave;ves&nbsp;:<br>';
	echo '<input type="text" size="18" name="recherche" class="spip_recherche">';
	echo "</font>\n";
	fin_cadre_relief();
	echo "</form>";

	?>
	<br></font>
	&nbsp;
	</td>
	<td width=40 rowspan=1>&nbsp;</td>
	<td width=480 valign="top" rowspan=2>
	<font face="Georgia,Garamond,Times,serif" size=3>
	<?
}


function fin_page() {
	global $spip_version_affichee;

?>
<p align='right'><font size='2'><a href='http://www.uzine.net/spip'>SPIP
<? echo $spip_version_affichee; ?></a> est distribu&eacute;
<a href='gpl.txt'>sous licence GPL</a>.</p></td></tr>
<tr><td width="180" valign="bottom"></td></tr></table></center></body></html>
<?
	flush();
}


function debut_cadre_relief(){
	echo "<TABLE CELLPADDING=0 CELLSPACING=0 BORDER=0 WIDTH=\"100%\">";
	echo "<TR><TD WIDTH=\"100%\">";
	echo "<TABLE CELLPADDING=1 CELLSPACING=0 BORDER=0 WIDTH=\"100%\"><TR><TD BGCOLOR='#000000' WIDTH=\"100%\">";
	echo "<TABLE CELLPADDING=8 CELLSPACING=0 BORDER=0 WIDTH=\"100%\"><TR><TD BACKGROUND='IMG2/rayures.gif' BGCOLOR='#FFFFFF' WIDTH=\"100%\">";
}

function fin_cadre_relief(){
	echo "</TD></TR></TABLE>";
	echo "</TD></TR></TABLE>";
	echo "</TD>";
	echo "<TD VALIGN='top' BACKGROUND='IMG2/ombre-d.gif' WIDTH=5><img src='IMG2/ombre-hd.gif' width='5' height='9' border=0><TD></TR>";
	echo "<TR><TD BACKGROUND='IMG2/ombre-b.gif' ALIGN='left'><img src='IMG2/ombre-bg.gif' width='8' height='5' border='0'></TD><TD><img src='IMG2/ombre-bd.gif' width='5' height='5' border='0'></TD></TR></TABLE>";
}


function debut_boite_alerte() {
	echo "<P><TABLE CELLPADDING=6 BORDER=0><TR><TD WIDTH='100%' BGCOLOR='red'>";
	echo "<TABLE WIDTH='100%' CELLPADDING=12 BORDER=0><TR><TD WIDTH='100%' bgcolor='white'>";
}

function fin_boite_alerte() {
	echo "</TD></TR></TABLE>";
	echo "</TD></TR></TABLE>";
}


function debut_boite_info() {
	echo "<P><TABLE CELLPADDING=5 CELLSPACING=0 BORDER=1 WIDTH='100%' CLASS='profondeur' BACKGROUND=''>";
	echo "<TR><TD BGCOLOR='#DBE1C5' WIDTH='100%'>";
	echo "<FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=2 COLOR='#333333'>";
}

function fin_boite_info() {
	echo "</FONT></TD></TR></TABLE>";
}


function bandeau_titre_boite($titre, $afficher_auteurs, $boite_importante = true) {
	global $couleur_foncee;
	if ($boite_importante) {
		$couleur_fond = $couleur_foncee;
		$couleur_texte = '#FFFFFF';
	}
	else {
		$couleur_fond = '#EEEECC';
		$couleur_texte = '#000000';
	}
	echo "<TR BGCOLOR='$couleur_fond'><TD WIDTH=\"100%\"><FONT FACE='Verdana,Arial,Helvetica,sans-serif' SIZE=3 COLOR='$couleur_texte'>";
	echo "<B>$titre</B></FONT></TD>";
	if ($afficher_auteurs){
		echo "<TD WIDTH='100'>";
		echo "<img src='IMG2/rien.gif' width='100' height='12' border='0'>";
		echo "</TD>";
	}
	echo "<TD WIDTH='90'>";
	echo "<img src='IMG2/rien.gif' width='90' height='12' border='0'>";
	echo "</TD>";
	echo "</TR>";
}


//
// Recuperation du cookie
//

$cookie_admin = $HTTP_COOKIE_VARS["spip_admin"];


//
// Gestion de version
//

$version_installee = (double) lire_meta("version_installee");
if ($version_installee < $spip_version) {
	debut_page();
	if (!$version_installee) $version_installee = "ant&eacute;rieure";
	echo "<h4>Message technique : la proc&eacute;dure de mise &agrave; jour doit &ecirc;tre lanc&eacute;e afin d'adapter
	la base de donn&eacute;es &agrave; la nouvelle version de SPIP.</h4>
	Si vous &ecirc;tes administrateur du site, veuillez <a href='upgrade.php3'>cliquer sur ce lien</a>.<p>";
	fin_page();
	exit;
}


//
// Ajouter un message de forum
//

if ($ajout_forum AND strlen($texte)>10 AND strlen($titre)>2) {
	$titre = addslashes($titre);
	$texte = addslashes($texte);
	$nom_site = addslashes($nom_site);
	$auteur = addslashes($auteur);
	$query_forum = "INSERT INTO spip_forum (id_parent, id_rubrique, id_article, id_breve, id_message, id_syndic, date_heure, titre, texte, nom_site, url_site, auteur, email_auteur, statut, id_auteur) VALUES ('$forum_id_parent','$forum_id_rubrique','$forum_id_article','$forum_id_breve','$forum_id_message', '$forum_id_syndic', NOW(),\"$titre\",\"$texte\",\"$nom_site\",\"$url_site\",\"$auteur\",\"$email_auteur\",\"$forum_statut\",\"$connect_id_auteur\")";
	$result_forum = mysql_query($query_forum);
		
}


//
// Fonctions d'affichage
//

function afficher_liste($largeurs, $table, $styles = '') {
	global $couleur_claire;

	if (!is_array($table)) return;
	reset($table);
	echo "\n";
	while (list(, $t) = each($table)) {
		$couleur_fond = ($ifond ^= 1) ? '#FFFFFF' : $couleur_claire;
		echo "<tr bgcolor=\"$couleur_fond\">";
		reset($largeurs);
		if ($styles) reset($styles);
		while (list(, $texte) = each($t)) {
			$style = $largeur = "";
			list(, $largeur) = each($largeurs);
			if ($styles) list(, $style) = each($styles);
			if (!trim($texte)) $texte .= "&nbsp;";
			echo "<td";
			if ($largeur) echo " width=\"$largeur\"";
			if ($style) echo " class=\"$style\"";
			echo ">$texte</td>";
		}
		echo "</tr>\n";
	}
	echo "\n";
}

function afficher_tranches_requete(&$query, $colspan) {
	$query = trim($query);
	$query_count = eregi_replace('^(SELECT)[[:space:]].*[[:space:]](FROM)[[:space:]]', '\\1 COUNT(*) \\2 ', $query);
	list($num_rows) = mysql_fetch_row(mysql_query($query_count));
	if (!$num_rows) return;

	$nb_aff = 10;
	// Ne pas couper pour trop peu
	if ($num_rows <= 1.5 * $nb_aff) $nb_aff = $num_rows;
	if (ereg('LIMIT .*,([0-9]+)', $query, $regs)) {
		if ($num_rows > $regs[1]) $num_rows = $regs[1];
	}

	$texte = "\n";

	if ($num_rows > $nb_aff) {
		$tmp_var = $query;
		$deb_aff = intval(getTmpVar($tmp_var));

		$texte .= "<tr><td background=\"\" class=\"arial2\" colspan=\"".($colspan - 1)."\">";

		for ($i = 0; $i < $num_rows; $i += $nb_aff){
			$deb = $i + 1;
			$fin = $i + $nb_aff;
			if ($fin > $num_rows) $fin = $num_rows;
			if ($deb_aff + 1 >= $deb AND $deb_aff + 1 <= $fin) {
				$texte .= "[<B>$deb-$fin</B>] ";
			}
			else {
				$link = new Link;
				$link->addTmpVar($tmp_var, strval($deb - 1));
				$texte .= "[<A HREF=\"".$link->getUrl()."\">$deb-$fin</A>] ";
			}
		}
		$texte .= "</td>\n";
		$texte .= "<td background=\"\" class=\"arial2\" colspan=\"1\" align=\"right\" valign=\"top\">";
		if ($deb_aff == -1) {
			$texte .= "[<B>Tout afficher</B>]";
		} else {
			$link = new Link;
			$link->addTmpVar($tmp_var, -1);
			$texte .= "[<A HREF=\"".$link->getUrl()."\">Tout afficher</A>]";
		}		
	
		$texte .= "</td>\n";
		$texte .= "</tr>\n";
		
		
		if ($deb_aff != -1) {
			$query = eregi_replace('LIMIT[[:space:]].*$', '', $query);
			$query .= " LIMIT $deb_aff, $nb_aff";
		}
	}

	return $texte;
}


//
// Afficher tableau d'articles
//

function afficher_articles($titre_table, $requete, $afficher_visites = false, $afficher_auteurs = true, $toujours_afficher = false) {
	global $connect_id_auteur;

	$activer_messagerie = lire_meta("activer_messagerie");
	$activer_statistiques = lire_meta("activer_statistiques");

	$tranches = afficher_tranches_requete($requete, $afficher_auteurs ? 3 : 2);

	if (strlen($tranches) OR $toujours_afficher) {
	 	$result = mysql_query($requete);
	 	$num_rows = mysql_num_rows($result);

		echo "<p><table width=100% cellpadding=0 cellspacing=0 border=0><tr><td width=100% background=''>";
		echo "<table width=100% cellpadding=3 cellspacing=0 border=0>";

		bandeau_titre_boite($titre_table, $afficher_auteurs);

		echo $tranches;

		$compteur_liste = 0;
		$table = '';

		while ($row = mysql_fetch_array($result)) {
			$vals = '';

			$id_article = $row['id_article'];
			$titre = $row['titre'];
			$id_rubrique = $row['id_rubrique'];
			$date = $row['date'];
			$statut = $row['statut'];
			$visites = $row['visites'];

			$query_petition = "SELECT COUNT(*) FROM spip_petitions WHERE id_article=$id_article";
			$row_petition = mysql_fetch_array(mysql_query($query_petition));
			$petition = ($row_petition[0] > 0);

			if ($afficher_auteurs) {
				$les_auteurs = "";
			 	$query2 = "SELECT spip_auteurs.id_auteur, nom, messagerie, login, en_ligne FROM spip_auteurs, spip_auteurs_articles AS lien WHERE lien.id_article=$id_article AND spip_auteurs.id_auteur=lien.id_auteur";
				$result_auteurs = mysql_query($query2);

				while ($row = mysql_fetch_array($result_auteurs)) {
					$id_auteur = $row['id_auteur'];
					$nom_auteur = typo($row['nom']);
					$auteur_messagerie = $row['messagerie'];
					
					$les_auteurs .= ", $nom_auteur";
					if ($id_auteur != $connect_id_auteur AND $auteur_messagerie != "non" AND $activer_messagerie != "non") {
						$les_auteurs .= "&nbsp;".bouton_imessage($id_auteur, $row);
					}
				}
				$les_auteurs = substr($les_auteurs, 2);
			}

			$s = "<A HREF=\"articles.php3?id_article=$id_article\">";
			if ($statut=='publie') $puce = 'verte';
			else if ($statut == 'prepa') $puce = 'blanche';
			else if ($statut == 'prop') $puce = 'orange';
			else if ($statut == 'refuse') $puce = 'rouge';
			else if ($statut == 'poubelle') $puce = 'poubelle';
			if (acces_restreint_rubrique($id_rubrique))
				$puce = "puce-$puce-anim.gif";
			else
				$puce = "puce-$puce.gif";

			$s .= "<img src=\"IMG2/$puce\" width=\"13\" height=\"14\" border=\"0\">";
			$s .= "&nbsp;&nbsp;".typo($titre)."</A>";
			if ($petition) $s .= " <Font size=1 color='red'>P&Eacute;TITION</font>";

			$vals[] = $s;
		
			if ($afficher_auteurs) $vals[] = $les_auteurs;

			$s = affdate($date);
			if ($activer_statistiques == "oui" AND $afficher_visites AND $visites > 0) {
				$s .= "<br><font size=\"1\">($visites&nbsp;visites)</font>";
			}
			$vals[] = $s;

			$table[] = $vals;
		}
		mysql_free_result($result);

		if ($afficher_auteurs) {
			$largeurs = array('', 100, 90);
			$styles = array('arial2', 'arial1', 'arial1');
		}
		else {
			$largeurs = array('', 90);
			$styles = array('arial2', 'arial1');
		}
		afficher_liste($largeurs, $table, $styles);
		
		echo "</table></td></tr></table>";
	}
	return $num_rows;
}


//
// Afficher tableau de breves
//

function afficher_breves($titre_table, $requete) {
	global $connect_id_auteur;

	$tranches = afficher_tranches_requete($requete, 2);

	if (strlen($tranches)) {

		if ($titre_table) {
			echo "<p><table width=100% cellpadding=0 cellspacing=0 border=0 background=''>";
			echo "<tr><td width=100% background=''>";
			echo "<table width=100% cellpadding=3 cellspacing=0 border=0>";
			echo "<tr bgcolor='#EEEECC'><td width=100% colspan=2><font face='Verdana,Arial,Helvetica,sans-serif' size=3 color='#000000'>";
			echo "<b>$titre_table</b></font></td></tr>";
		}
		else {
			echo "<p><table width=100% cellpadding=3 cellspacing=0 border=0 background=''>";
		}

		echo $tranches;

	 	$result = mysql_query($requete);
		$num_rows = mysql_num_rows($result);

		$table = '';
		while ($row = mysql_fetch_array($result)) {
			$vals = '';

			$id_breve = $row['id_breve'];
			$date_heure = $row['date_heure'];
			$titre = $row['titre'];
			$statut = $row['statut'];

			$s = "<a href=\"breves_voir.php3?id_breve=$id_breve\">";
			$puce = "IMG2/breve-$statut.gif";
			$s .= "<img src=\"$puce\" alt=\"o\" width=\"8\" height=\"9\" border=\"0\"> ";
			$s .= typo($titre);
			$s .= "</A>";
			$vals[] = $s;

			$s = "<div align=\"right\">";
			if ($statut == "prop") $s .= "[<font color=\"red\">&agrave; valider</font>]";
			else $s .= affdate($date_heure);
			$s .= "</div>";
			$vals[] = $s;
			$table[] = $vals;
		}
		mysql_free_result($result);

		$largeurs = array('', '');
		$styles = array('arial2', 'arial2');
		afficher_liste($largeurs, $table, $styles);

		if ($titre_table) echo "</TABLE></TD></TR>";
		echo "</TABLE>";
	}
	return $num_rows;
}


//
// Afficher tableau de rubriques
//

function afficher_rubriques($titre_table, $requete) {
	global $connect_id_auteur;

	$tranches = afficher_tranches_requete($requete, 2);

	if (strlen($tranches)) {

		if ($titre_table) {
			echo "<p><table width=100% cellpadding=0 cellspacing=0 border=0 background=''>";
			echo "<tr><td width=100% background=''>";
			echo "<table width=100% cellpadding=3 cellspacing=0 border=0>";
			echo "<tr bgcolor='#333333'><td width=100% colspan=2><font face='Verdana,Arial,Helvetica,sans-serif' size=3 color='#FFFFFF'>";
			echo "<b>$titre_table</b></font></td></tr>";
		}
		else {
			echo "<p><table width=100% cellpadding=3 cellspacing=0 border=0 background=''>";
		}

		echo $tranches;

	 	$result = mysql_query($requete);
		$num_rows = mysql_num_rows($result);

		$table = '';
		while ($row = mysql_fetch_array($result)) {
			$vals = '';

			$id_rubrique = $row['id_rubrique'];
			$titre = $row['titre'];

			$s = "<b><a href=\"naviguer.php3?coll=$id_rubrique\">";
			$puce = "puce.gif";
			$s .= "<img src=\"$puce\" alt=\">\" border=\"0\"> ";
			$s .= typo($titre);
			$s .= "</A></b>";
			$vals[] = $s;

			$s = "<div align=\"right\">";
			$s .= "</div>";
			$vals[] = $s;
			$table[] = $vals;
		}
		mysql_free_result($result);

		$largeurs = array('', '');
		$styles = array('arial2', 'arial2');
		afficher_liste($largeurs, $table, $styles);

		if ($titre_table) echo "</TABLE></TD></TR>";
		echo "</TABLE>";
	}
	return $num_rows;
}


//
// Supprimer forum
//

if ($supp_forum AND $connect_statut == "0minirezo") {

	$query_forum = "SELECT * FROM spip_forum WHERE id_forum=\"$supp_forum\"";
 	$result_forum = mysql_query($query_forum);

 	while($row=mysql_fetch_array($result_forum)){
		$id_forum=$row[0];
		$forum_id_parent=$row[1];
		$forum_id_rubrique=$row[2];
		$forum_id_article=$row[3];
		$forum_id_breve=$row[4];
		$forum_id_syndic=$row["id_syndic"];
		$forum_date_heure=$row[5];
		$forum_titre=$row[6];
		$forum_texte=$row[7];
		$forum_auteur=$row[8];
		$forum_email_auteur=$row[9];
		$forum_nom_site=$row[10];
		$forum_url_site=$row[11];
		$forum_stat=$row[12];
		$forum_ip=$row[13];
	}
	$query_forum = "UPDATE spip_forum SET id_parent='$forum_id_parent', id_rubrique='$forum_id_rubrique', id_article='$forum_id_article', id_breve='$forum_id_breve', id_syndic='$forum_id_syndic' WHERE id_parent=$supp_forum AND statut!='off'";
	$result_forum = mysql_query($query_forum);

	unset($where);
	if ($forum_id_article) $where[] = "id_article=$forum_id_article";
	if ($forum_id_rubrique) $where[] = "id_rubrique=$forum_id_rubrique";
	if ($forum_id_breve) $where[] = "id_breve=$forum_id_breve";
	if ($forum_id_syndic) $where[] = "id_syndic=$forum_id_syndic";
	if ($forum_id_parent) $where[] = "id_forum=$forum_id_parent";
	if ($where) {
		$query = "SELECT fichier FROM spip_forum_cache WHERE ".join(' OR ', $where);
		$result = mysql_query($query);
		unset($fichiers);
		if ($result) while ($row = mysql_fetch_array($result)) {
			$fichier = $row[0];
			@unlink("../CACHE/$fichier");
			$fichiers[] = $fichier;
		}
		if ($fichiers) {
			$fichiers = join(',', $fichiers);
			$query = "DELETE FROM spip_forum_cache WHERE fichier IN ($fichiers)";
			mysql_query($query);
		}
	}
	$query_forum = "UPDATE spip_forum SET statut='off' WHERE id_forum=$supp_forum";
	$result_forum = mysql_query($query_forum);
}


//
// Valider un forum
//

if ($valid_forum AND $connect_statut == "0minirezo") {

	$query_forum = "SELECT * FROM spip_forum WHERE id_forum=\"$valid_forum\"";
 	$result_forum = mysql_query($query_forum);

 	while($row=mysql_fetch_array($result_forum)){
		$id_forum=$row[0];
		$forum_id_parent=$row[1];
		$forum_id_rubrique=$row[2];
		$forum_id_article=$row[3];
		$forum_id_breve=$row[4];
		$forum_id_syndic=$row["id_syndic"];
		$forum_date_heure=$row[5];
		$forum_titre=$row[6];
		$forum_texte=$row[7];
		$forum_auteur=$row[8];
		$forum_email_auteur=$row[9];
		$forum_nom_site=$row[10];
		$forum_url_site=$row[11];
		$forum_stat=$row[12];
		$forum_ip=$row[13];
	}

	unset($where);
	if ($forum_id_article) $where[] = "id_article=$forum_id_article";
	if ($forum_id_rubrique) $where[] = "id_rubrique=$forum_id_rubrique";
	if ($forum_id_breve) $where[] = "id_breve=$forum_id_breve";
	if ($forum_id_syndic) $where[] = "id_syndic=$forum_id_syndic";
	if ($forum_id_parent) $where[] = "id_forum=$forum_id_parent";
	if ($where) {
		$query = "SELECT fichier FROM spip_forum_cache WHERE ".join(' OR ', $where);
		$result = mysql_query($query);
		unset($fichiers);
		if ($result) while ($row = mysql_fetch_array($result)) {
			$fichier = $row[0];
			@unlink("../CACHE/$fichier");
			$fichiers[] = $fichier;
		}
		if ($fichiers) {
			$fichiers = join(',', $fichiers);
			$query = "DELETE FROM spip_forum_cache WHERE fichier IN ($fichiers)";
			mysql_query($query);
		}
	}
	$query_forum = "UPDATE spip_forum SET statut='publie' WHERE id_forum=$valid_forum";
	$result_forum = mysql_query($query_forum);
}


//
// Afficher les forums
//
 
function afficher_forum($request, $adresse_retour, $controle = "non", $recurrence = "oui") {
	global $debut;
	static $compteur_forum;
	static $nb_forum;
	static $i;
	global $couleur_foncee;
	global $connect_id_auteur;
	global $connect_activer_messagerie;	
	global $mots_cles_forums;


	$activer_messagerie = lire_meta("activer_messagerie");
	
	$compteur_forum++; 

	$nb_forum[$compteur_forum] = mysql_num_rows($request);
	$i[$compteur_forum] = 1;
 	while($row = mysql_fetch_array($request)) {
		$id_forum=$row[0];
		$id_parent=$row[1];
		$id_rubrique=$row[2];
		$id_article=$row[3];
		$id_breve=$row[4];
		$id_message=$row['id_message'];
		$id_syndic=$row['id_syndic'];
		$date_heure=$row[5];
		$titre=$row[6];
		$texte=$row[7];
		$auteur=$row[8];
		$email_auteur=$row[9];
		$nom_site=$row[10];
		$url_site=$row[11];
		$statut=$row[12];
		$ip=$row["ip"];
		$id_auteur=$row["id_auteur"];

		if ($compteur_forum==1){echo "<BR><BR>\n";}

		$afficher = ($controle=="oui") ? ($statut!="perso") :
			(($statut=="prive" OR $statut=="privrac" OR $statut=="privadm" OR $statut=="perso")
			OR ($statut=="publie" AND $id_parent > 0));

		if ($afficher) {
			echo "<table width=100% cellpadding=0 cellspacing=0 border=0><tr>";
			for ($count=2;$count<=$compteur_forum AND $count<11;$count++){
				$fond[$count]='IMG2/rien.gif';
				if ($i[$count]!=$nb_forum[$count]){		
					$fond[$count]='IMG2/forum-vert.gif';
				}		
				$fleche='IMG2/rien.gif';
				if ($count==$compteur_forum){		
					$fleche='IMG2/forum-droite.gif';
				}		
				echo "<td width=10 valign='top' background=$fond[$count]><img src=$fleche alt='' width=10 height=13 border=0></td>\n";
			}
			
			echo "\n<td width=100% bgcolor='#eeeeee' valign='top'>";

			// Si refuse, cadre rouge
			if ($statut=="off") {
				echo "<table width=100% cellpadding=2 cellspacing=0 border=0><tr><td bgcolor='#FF0000'>";
			}
			// Si propose, cadre jaune
			else if ($statut=="prop") {
				echo "<table width=100% cellpadding=2 cellspacing=0 border=0><tr><td bgcolor='#FFFF00'>";
			}

			echo "<table width=100% cellpadding=3 cellspacing=0><tr><td bgcolor='$couleur_foncee'><font face='Verdana,Arial,Helvetica,sans-serif' size=2 color='#FFFFFF'><b>".typo($titre)."</b></font></td></tr>";
			echo "<tr><td bgcolor='#EEEEEE'>";
			echo "<font size=2 face='Georgia,Garamond,Times,serif'>";
			echo "<font face='arial,helvetica'>$date_heure</font>";

			if ($email_auteur) {
				echo " <a href=\"mailto:$email_auteur?subject=".rawurlencode($titre)."\">$auteur</a>";
			}
			else {
				echo " $auteur";
			}

			if ($id_auteur AND $activer_messagerie != "non" AND $connect_activer_messagerie != "non") {
				$bouton = bouton_imessage($id_auteur,$row_auteur);
				if ($bouton) echo "&nbsp;".$bouton;
			}

			if ($controle == "oui") {
				if ($statut != "off") {
					echo "<a href='articles_forum.php3?id_article=$id_article&supp_forum=$id_forum&debut=$debut' ".
						"onMouseOver=\"message$id_forum.src='IMG2/supprimer-message-on.gif'\" onMouseOut=\"message$id_forum.src='IMG2/supprimer-message-off.gif'\">";
					echo "<img src='IMG2/supprimer-message-off.gif' width=64 height=52 name='message$id_forum' align='right' border=0></a>";
				}
				else {
					echo "<br><font color='red'><b>MESSAGE SUPPRIM&Eacute; $ip</b></font>";
					if ($id_auteur) {
						echo " - <a href='auteurs_edit.php3?id_auteur=$id_auteur'>Voir cet auteur</A>";
					}
				}
				if ($statut == "prop" OR $statut == "off") {
					echo "<a href='articles_forum.php3?id_article=$id_article&valid_forum=$id_forum&debut=$debut' onMouseOver=\"valider_message$id_forum.src='IMG2/valider-message-on.gif'\" onMouseOut=\"valider_message$id_forum.src='IMG2/valider-message-off.gif'\"><img src='IMG2/valider-message-off.gif' width=60 height=52 name='valider_message$id_forum' align='right' border=0></a>";
				}
			}
			echo justifier(propre($texte));
			
			if (strlen($url_site) > 10 AND $nom_site) {
				echo "<p align='left'><font face='Verdana,Arial,Helvetica,sans-serif'><b><a href='$url_site'>$nom_site</a></b></font>";
			}
				
			if ($controle != "oui") {
				echo "<p align='right'><font face='Verdana,Arial,Helvetica,sans-serif' size=1>";
				$url = "forum_envoi.php3?id_parent=$id_forum&adresse_retour=".rawurlencode($adresse_retour)
					."&titre_message=".rawurlencode($titre);
				echo "<b><a href=\"$url\">R&eacute;pondre &agrave; ce message</a></b></font>";
			}
			
			if ($mots_cles_forums == "oui"){
				$query_mots = "SELECT * FROM spip_mots AS mots, spip_mots_forum AS lien WHERE lien.id_forum = '$id_forum' AND lien.id_mot = mots.id_mot";
				$result_mots = mysql_query($query_mots);
				while ($row_mots = mysql_fetch_array($result_mots)) {
					$id_mot = $row_mots['id_mot'];
					$titre_mot = propre($row_mots['titre']);
					$type_mot = propre($row_mots['type']);
					echo "<li> <b>$type_mot :</b> $titre_mot";
				}
			}
			
			echo "</font>";
			echo "</td></tr></table>";
			if ($statut == "off" OR $statut == "prop") {
				echo "</td></tr></table>";
			}			
			echo "</td></tr></table>\n";

			if ($recurrence == "oui") forum($id_forum,$adresse_retour,$controle);
		}
		$i[$compteur_forum]++;
	}
	mysql_free_result($request);
	$compteur_forum--;
}
  
  
function forum($le_forum, $adresse_retour, $controle = "non") {
	global $id_breve;
      	echo "<font size=2 face='Georgia,Garamond,Times,serif'>";
	
	if ($controle == "oui") {
		$query_forum2 = "SELECT * FROM spip_forum WHERE id_parent='$le_forum' ORDER BY date_heure";
	}
	else {
		$query_forum2 = "SELECT * FROM spip_forum WHERE id_parent='$le_forum' AND statut<>'off' ORDER BY date_heure";
	}
 	$result_forum2 = mysql_query($query_forum2);
	afficher_forum($result_forum2, $adresse_retour, $controle);
}


function bouton($titre,$lien) {
	$lapage=substr($lien,0,strpos($lien,"?"));
	$lesvars=substr($lien,strpos($lien,"?")+1,strlen($lien));

	echo "\n<form action='$lapage' method='get'>\n";
	$lesvars=explode("&",$lesvars);
	
	for($i=0;$i<count($lesvars);$i++){
		$var_loc=explode("=",$lesvars[$i]);
		echo "<input type='Hidden' name='$var_loc[0]' value=\"$var_loc[1]\">\n";
	}
	echo "<input type='submit' name='Submit' class='fondo' value=\"$titre\">\n";
	echo "</form>";
}


//
// Recalculer les secteurs de chaque article, rubrique, syndication
//

function calculer_secteurs() {
	$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent=0";
	$result = mysql_query($query);

	while ($row = mysql_fetch_array($result)) $secteurs[] = $row[0];
	if (!$secteurs) return;

	while (list(, $id_secteur) = each($secteurs)) {
		$rubriques = "$id_secteur";
		$rubriques_totales = $rubriques;
		while ($rubriques) {
			$query = "SELECT id_rubrique FROM spip_rubriques WHERE id_parent IN ($rubriques)";
			$result = mysql_query($query);

			unset($rubriques);
			while ($row = mysql_fetch_array($result)) $rubriques[] = $row[0];
			if ($rubriques) {
				$rubriques = join(',', $rubriques);
				$rubriques_totales .= ",".$rubriques;
			}
		}
		$query = "UPDATE spip_articles SET id_secteur=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = mysql_query($query);
		$query = "UPDATE spip_breves SET id_rubrique=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = mysql_query($query);
		$query = "UPDATE spip_rubriques SET id_secteur=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = mysql_query($query);
		$query = "UPDATE spip_syndic SET id_secteur=$id_secteur WHERE id_rubrique IN ($rubriques_totales)";
		$result = mysql_query($query);
	}
}


function calculer_dates_rubriques($id_parent="0", $date_parent="0000-00-00"){

	
	$query = "SELECT MAX(date_heure) FROM spip_breves WHERE id_rubrique = '$id_parent' GROUP BY id_rubrique";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$date_breves = $row[0];
		if ($date_breves > $date_parent) $date_parent = $date_breves;
	}
	
	$query = "SELECT MAX(date) FROM spip_syndic WHERE id_rubrique = '$id_parent' GROUP BY id_rubrique";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		$date_syndic = $row[0];
		if ($date_syndic > $date_parent) $date_parent = $date_syndic;
	}
	
	
	
	if ($post_dates != "non") {
		$query = "SELECT rubrique.id_rubrique,  MAX(articles.date) FROM spip_rubriques AS rubrique, spip_articles AS articles WHERE rubrique.id_parent='$id_parent' AND articles.id_rubrique=rubrique.id_rubrique AND articles.statut = 'publie' GROUP BY rubrique.id_rubrique";
	}
	else {
		$query = "SELECT rubrique.id_rubrique,  MAX(articles.date) FROM spip_rubriques AS rubrique, spip_articles AS articles WHERE rubrique.id_parent='$id_parent' AND articles.id_rubrique=rubrique.id_rubrique AND articles.statut = 'publie' AND articles.date < NOW() GROUP BY rubrique.id_rubrique";
	}
	$result = mysql_query($query);
	
	while ($row = mysql_fetch_array($result)) {
		$id_rubrique = $row[0];
		$date_rubrique = $row[1];
		
		$date_rubrique = calculer_dates_rubriques($id_rubrique,$date_rubrique);
		
		if ($date_rubrique > $date_parent) $date_parent = $date_rubrique;
	}


	mysql_query("UPDATE spip_rubriques SET date='$date_parent' WHERE id_rubrique='$id_parent'");

	return $date_parent;


}

//calculer_dates_rubriques();

function calculer_rubriques_publiques()
{
	$post_dates = lire_meta("post_dates");

	if ($post_dates != "non") {
		$query = "SELECT DISTINCT id_rubrique FROM spip_articles WHERE statut = 'publie'";
	}
	else {
		$query = "SELECT DISTINCT id_rubrique FROM spip_articles WHERE statut = 'publie' AND date < NOW()";
	}
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		if ($row[0]) $rubriques[] = $row[0];
	}
	$query = "SELECT DISTINCT id_rubrique FROM spip_breves WHERE statut = 'publie'";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		if ($row[0]) $rubriques[] = $row[0];
	}
	$query = "SELECT DISTINCT id_rubrique FROM spip_syndic WHERE statut = 'publie'";
	$result = mysql_query($query);
	while ($row = mysql_fetch_array($result)) {
		if ($row[0]) $rubriques[] = $row[0];
	}

	while ($rubriques) {
		$rubriques = join(",", $rubriques);
		if ($rubriques_publiques) $rubriques_publiques .= ",$rubriques";
		else $rubriques_publiques = $rubriques;
		$query = "SELECT DISTINCT id_parent FROM spip_rubriques WHERE (id_rubrique IN ($rubriques)) AND (id_parent NOT IN ($rubriques_publiques))";
		$result = mysql_query($query);
		unset($rubriques);
		while ($row = mysql_fetch_array($result)) {
			if ($row[0]) $rubriques[] = $row[0];
		}
	}
	$query = "UPDATE spip_rubriques SET statut='prive' WHERE id_rubrique NOT IN ($rubriques_publiques)";
	mysql_query($query);
	$query = "UPDATE spip_rubriques SET statut='publie' WHERE id_rubrique IN ($rubriques_publiques)";
	mysql_query($query);

	calculer_dates_rubriques();

}


//
// Recalculer l'ensemble des donnees associees a l'arborescence des rubriques
// (cette fonction est a appeler a chaque modification sur les rubriques)
//

function calculer_rubriques()
{
	calculer_secteurs();
	calculer_rubriques_publiques();
}

// Supprimer rubrique
if ($supp_rubrique = intval($supp_rubrique) AND $connect_statut == '0minirezo' AND acces_rubrique($supp_rubrique)) {
	$query = "DELETE FROM spip_rubriques WHERE id_rubrique=$supp_rubrique";
	$result = mysql_query($query);

	calculer_rubriques();
}

// fonction d'affichage
function tableau($texte,$lien,$image){
	echo "<td width=15>&nbsp;</td>\n";
	echo "<td width=80 valign='top' align='center'><a href='$lien'><img src='$image' border='0'></a><br><font size=1 face='arial,helvetica' color='#e86519'><b>$texte</b></font></td>";
}


?>
