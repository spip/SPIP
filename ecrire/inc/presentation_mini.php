<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2014                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/**
 * Fonctions d'affichage pour l'espace privé (hors squelettes)
 *
 * @package SPIP\Core\Affichage
**/
if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Affiche un code html (echo) et log l'affichage car cet echo est anormal !
 * 
 * Signale une fonction qui devrait retourner un contenu mais effectue
 * un echo à la place pour compatibilité ascendante
 *
 * @deprecated
 *     Utiliser des squelettes pour l'affichage !
 * 
 * @param string $f
 *     Nom de la fonction
 * @param string $ret
 *     Code HTML à afficher
 * @return void
**/
function echo_log($f, $ret) {
	spip_log("Page " . self() . " function $f: echo ".substr($ret,0,50)."...",'echo');
	echo
	(_SIGNALER_ECHOS?"#Echo par $f#" :"")
		. $ret;
}

/**
 * Retourne le code HTML d'un début de cadre pour le centre de page (haut de page)
 * @return string Code HTML
 */
function debut_grand_cadre(){ return "\n<div class='table_page'>\n";}

/**
 * Retourne le code HTML d'une fin de cadre pour le centre de page (haut de page)
 * @return string Code HTML
 */
function fin_grand_cadre(){ return "\n</div>";}

// Debut de la colonne de gauche
// div navigation fermee par creer_colonne_droite qui ouvre
// div extra lui-meme ferme par debut_droite qui ouvre
// div contenu lui-meme ferme par fin_gauche() ainsi que
// div conteneur
// http://doc.spip.org/@debut_gauche
/**
 * Retourne le code HTML du début de la colonne gauche
 * @return string Code HTML
 */
function debut_gauche() {	return "<div id='conteneur' class=''>\n<div id='navigation' class='lat' role='contentinfo'>\n";}

/**
 * Retourne le code HTML de la fin de la colonne
 * @return string Code HTML
 */
function fin_gauche(){return "</div></div><br class='nettoyeur' />";}

/**
 * Retourne le code HTML du changement de colonne (passer de la gauche à la droite)
 * @return string Code HTML
 */
function creer_colonne_droite(){
	static $deja_colonne_droite;
	if ($GLOBALS['spip_ecran']!='large' OR $deja_colonne_droite) return '';
	$deja_colonne_droite = true;
	return "\n</div><div id='extra' class='lat' role='complementary'>";
}

/**
 * Retourne le code HTML de la colonne droite et du centre de page
 * @return string Code HTML
 */
function debut_droite() {
	return liste_objets_bloques(_request('exec'))
	  . creer_colonne_droite()
	  . "</div>"
	  . "\n<div id='contenu'>";
}

// http://doc.spip.org/@liste_articles_bloques
function liste_objets_bloques($exec,$contexte=array(),$auteur=null){
	$res = '';
	if ($GLOBALS['meta']["articles_modif"] != "non") {
		include_spip('inc/drapeau_edition');
		if (is_null($auteur))
			$auteur = $GLOBALS['visiteur_session'];
		if ($en_cours=trouver_objet_exec($exec)
			AND $en_cours['edition']
			AND $type = $en_cours['type']
		  AND ($id = $contexte[$en_cours['id_table_objet']] OR $id = _request($en_cours['id_table_objet']))) {
			// marquer le fait que l'objet est ouvert en edition par toto
			// a telle date ; une alerte sera donnee aux autres redacteurs
			signale_edition ($id,  $auteur, $type);
		}

		$objets_ouverts = liste_drapeau_edition($auteur['id_auteur']);
		if (count($objets_ouverts)) {
			$res .= recuperer_fond('prive/objets/liste/objets-en-edition',array(),array('ajax'=>true));
		}
	}
	return $res;
}

// Fin de page de l'interface privee.
// Elle comporte une image invisible declenchant une tache de fond
// http://doc.spip.org/@fin_page
function fin_page(){
	include_spip('inc/pipelines');
	// avec &var_profile=1 on a le tableau de mesures SQL
	$debug = ((_request('exec') !== 'valider_xml')
		AND ((_request('var_mode') == 'debug')
			OR (isset($GLOBALS['tableau_des_temps']) AND $GLOBALS['tableau_des_temps'])
			AND isset($_COOKIE['spip_admin'])));
	$t = '</div><div id="pied"><div class="largeur">'
	. recuperer_fond('prive/squelettes/inclure/pied')
	. "</div>"
	. "</div></div>" // cf. div#page et div.largeur ouvertes dans conmmencer_page()
	. ($debug?erreur_squelette():'')
	. "</body></html>\n";
	return f_queue($t);
}

function html_tests_js(){
	if (_SPIP_AJAX AND !defined('_TESTER_NOSCRIPT')) {
	  // pour le pied de page (deja defini si on est validation XML)
		define('_TESTER_NOSCRIPT',
			"<noscript>\n<div style='display:none;'><img src='"
		        . generer_url_ecrire('test_ajax', 'js=-1')
		        . "' width='1' height='1' alt='' /></div></noscript>\n");
	}
	return
		(defined('_SESSION_REJOUER') ? _SESSION_REJOUER : '')
	. (defined('_TESTER_NOSCRIPT') ? _TESTER_NOSCRIPT : '');
}

function info_maj_spip(){

	$maj = $GLOBALS['meta']['info_maj_spip'];
	if (!$maj)
		return "";

	$maj = explode('|',$maj);
	// c'est une ancienne notif, on a fait la maj depuis !
	if ($GLOBALS['spip_version_branche']!==reset($maj))
		return "";

	if (!autoriser('webmestre'))
		return "";

	array_shift($maj);
	$maj = implode('|',$maj);

	return "$maj<br />";
}

// http://doc.spip.org/@info_copyright
function info_copyright() {
	global $spip_version_affichee, $spip_lang;

	$version = $spip_version_affichee;

	//
	// Mention, le cas echeant, de la revision SVN courante
	//
	if ($svn_revision = version_svn_courante(_DIR_RACINE)) {
		$version .= ' ' . (($svn_revision < 0) ? 'SVN ':'')
		. "[<a href='http://core.spip.org/trac/spip/changeset/"
		. abs($svn_revision) . "' onclick=\"window.open(this.href); return false;\">"
		. abs($svn_revision) . "</a>]";
	}

	// et la version de l'ecran de securite
	$secu = defined('_ECRAN_SECURITE')
		? "<br />"._T('ecran_securite',array('version'=>_ECRAN_SECURITE))
		: '';

	return _T('info_copyright',
		   array('spip' => "<b>SPIP $version</b> ",
			 'lien_gpl' =>
			 "<a href='". generer_url_ecrire("aide", "aide=licence&var_lang=$spip_lang") . "' onclick=\"window.open(this.href, 'spip_aide', 'scrollbars=yes,resizable=yes,width=740,height=580'); return false;\">" . _T('info_copyright_gpl')."</a>"))
		. $secu;

}

// http://doc.spip.org/@formulaire_recherche
function formulaire_recherche($page, $complement=""){
	$recherche = _request('recherche');
	$recherche_aff = entites_html($recherche);
	if (!strlen($recherche)) {
		$recherche_aff = _T('info_rechercher');
		$onfocus = " onfocus=\"this.value='';\"";
	} else $onfocus = '';

	$form = '<input type="text" size="10" value="'.$recherche_aff.'" name="recherche" class="recherche" accesskey="r"' . $onfocus . ' />';
	$form .= "<input type='image' src='" . chemin_image('rechercher-20.png') . "' name='submit' class='submit' alt='"._T('info_rechercher')."' />";
	return "<div class='spip_recherche'>".generer_form_ecrire($page, $form . $complement, " method='get'")."</div>";
}

?>
