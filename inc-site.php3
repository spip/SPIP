<?php

global $site_array;
$site_array = array();

function site_stat($args, $filtres)
{
  return ((lire_meta("proposer_sites") != 2) ? '' :
	  array("'" . $args[0] . "'"));
}

function site_dyn($la_rubrique) {
  include_ecrire("inc_mail.php3");

	global $nom_site;
	global $url_site;
	global $description_site;
	global $spip_lang_rtl;
	$puce_ligne = "<br /><img src='puce$spip_lang_rtl.gif' border='0' alt='-' /> ";

	if ($nom_site) {
		// Tester le nom du site
		if (strlen ($nom_site) < 2){
			$reponse_signature .= $puce_ligne . (_T('form_prop_indiquer_nom_site'));
			$refus = "oui";
		}

		// Tester l'URL du site
		include_ecrire("inc_sites.php3");
		if (!recuperer_page($url_site)) {
			$reponse_signature .=  $puce_ligne . (_T('form_pet_url_invalide'));
			$refus = "oui";
		}

		// Integrer a la base de donnees
		
		if ($refus !="oui"){
			$nom_site = addslashes($nom_site);
			$url_site = addslashes($url_site);
			$description_site = addslashes($description_site);
			
			spip_query("INSERT INTO spip_syndic (nom_site, url_site, id_rubrique, descriptif, date, date_syndic, statut, syndication) ".
				   "VALUES ('$nom_site', '$url_site', $la_rubrique, '$description_site', NOW(), NOW(), 'prop', 'non')");
			$res =  _T('form_prop_enregistre');
		}
		else {
			$res = $reponse_signature .
			  "<p> "._T('form_prop_non_enregistre') . "</p>";
		}
		
		$res = "<div class='reponse_formulaire'>$res</div>";
	}
	else {
		$link = $GLOBALS['clean_link'];
		$res = $link->getForm('POST') .
		  "<p><div class='spip_encadrer'><b>"._T('form_prop_nom_site')."</b><br />" .
		  "<input type=\"text\" class=\"forml\" name=\"nom_site\" value=\"\" size=\"30\">" .
		  "</p><p><b>"._T('form_prop_url_site')."</b></p><br />" .
		  "<input type=\"text\" class=\"forml\" name=\"url_site\" value=\"\" size=\"30\"></div>" .
		  "<p><b>"._T('form_prop_description')."</b></p><br />" .
		  "<textarea name='description_site' rows='5' class='forml' cols='40' wrap=soft></textarea>" .
		  "<div align=\"right\"><input type=\"submit\" name=\"valider\" class=\"spip_bouton\" value=\""._t('bouton_valider')."\">" .
		  "</div></form>";
		}
	return $res;
}
?>
