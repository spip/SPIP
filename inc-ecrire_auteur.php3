<?php

global $ecrire_auteur_array ;
$ecrire_auteur_array = array('id_auteur',
			     'email',
			     'sujet_message_auteur',
			     'texte_message_auteur',
			     'email_message_auteur'
);

function ecrire_auteur_stat($args, $filtres)
{
  list($id_auteur, $mail, $sujet, $texte, $adres) = $args;
  return (!email_valide($mail) ? '' :
	  array("$id_auteur",
		"'$mail'",
		"'" . addslashes($sujet) ."'",
		"'" . addslashes($texte) ."'",
		"'" . addslashes($adres) ."'"));
}

function ecrire_auteur_dyn($id_auteur, $mail, $sujet, $texte, $adres) {
	global $flag_wordwrap, $spip_lang_rtl;
	$puce_ligne = "<br /><img src='puce$spip_lang_rtl.gif' border='0' alt='-' /> ";

	include_ecrire("inc_filtres.php3");

	if ($texte) {
		if ($sujet == "")
			$erreur = _T('form_prop_indiquer_sujet');
		else if (! email_valide($adres) )
			$erreur = _T('form_prop_indiquer_email');
		else if ($GLOBALS['valide_message_auteur']) {  // verifier hash ?
			$texte .= "\n\n-- "._T('envoi_via_le_site')." ".lire_meta('nom_site')." (".lire_meta('adresse_site')."/) --\n";
			include_ecrire("inc_mail.php3");
			envoyer_mail($mail,
				     $sujet, $texte, $adres,
				"X-Originating-IP: ".$GLOBALS['REMOTE_ADDR']);
			return $puce_ligne . _T('form_prop_message_envoye');
		} else { //preview
			if ($flag_wordwrap) $texte = wordwrap($texte);
			$link = $GLOBALS['clean_link'];
			$link->addVar('email_message_auteur', $adres);
			$link->addVar('sujet_message_auteur', $sujet);
			$link->addVar('texte_message_auteur', $texte);
			$link->addVar('valide_message_auteur', 'oui');
			return 
			  "<br />"
			  .  _T('form_prop_sujet')	
			  .  " <b>"
			  . entites_html($sujet)
			  . "</b></div>" 
			  .  "<pre>"
			  . entites_html($texte)
			  . "</pre>" 
			  . $link->getForm('post') 
			  .  "<div align=\"right\"><input type=\"submit\" class=\"spip_bouton\" value=\""
			  . _T('form_prop_confirmer_envoi')
			  . "\" />" 
			  . "</form>";
		}
	}

	if ($erreur) $res = $puce_ligne . $erreur . '<br /><br />&nbsp;';

	$retour = $GLOBALS['REQUEST_URI'];
	$link = $GLOBALS['clean_link'];
	return $res
	  . $link->getForm('post')
	  ._T('form_pet_votre_email')."<br />" .
	  "<input type=\"text\" class=\"forml\" name=\"email_message_auteur\" value=\"".entites_html($adres)."\" SIZE=\"30\" />\n" .
	  "<p>"._T('form_prop_sujet')."<br />" .
	  "<input type=\"text\" class=\"forml\" name=\"sujet_message_auteur\" value=\"".entites_html($sujet)."\" SIZE=\"30\" /></p>\n" .
	  "<p><textarea name='texte_message_auteur' rows='10' class='forml' cols='40' wrap=soft>"
	  .entites_html($texte)."</textarea></p>\n" .
	  "<div align=\"right\"><input type=\"submit\"  class=\"spip_bouton\" value=\""._T('form_prop_envoyer')."\" /></div>" .
	  "</form>";

}

?>
