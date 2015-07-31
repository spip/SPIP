<?php

# Fonction de traduction des champs Spip basiques
# A terme, elle devrait etre remplace'e par autant de fonctions que de 'case'

function calculer_champ_divers($fonctions, $nom_champ, $id_boucle, &$boucles, $id_mere)
{
  global  $flag_pcre;

  switch($nom_champ) {

	// Introduction (d'un article, d'une breve ou d'un message de forum)

  case 'INTRODUCTION':
		$code = 'calcul_introduction(\'' .
		  $boucles[$id_boucle]->type_requete . "',\n" .
		  index_pile($id_boucle,  "texte", $boucles) . ",\n" .
		  index_pile($id_boucle,  "chapo", $boucles) . ",\n" .
		  index_pile($id_boucle,  "descriptif", $boucles) . ")\n"; 
		break;

  case 'NOM_SITE_SPIP':
		$code = "lire_meta('nom_site')";
		break;

  case 'EMAIL_WEBMASTER':
		$code = "lire_meta('email_webmaster')";
		break;

  case 'CHARSET':
		$code = "lire_meta('charset')";
		break;


  case 'LANG_LEFT':
		$code = "lang_dir(\$GLOBALS['spip_lang'],'left','right')";
		break;

  case 'LANG_RIGHT':
		$code = "lang_dir(\$GLOBALS['spip_lang'],'right','left')";
		break;

  case 'LANG_DIR':
		$code = "lang_dir(\$GLOBALS['spip_lang'],'ltr','rtl')";
		break;

  case 'PUCE':
		$code = "propre('- ')";
		break;


  case 'DATE_NOUVEAUTES':
		$code = "((lire_meta('quoi_de_neuf') == 'oui' AND lire_meta('majnouv')) ? normaliser_date(lire_meta('majnouv')) : \"'0000-00-00'\")";
		break;

  case 'URL_SITE_SPIP':
		$code = "lire_meta('adresse_site')";
		break;

  case 'URL_ARTICLE':
		$code = "generer_url_article(" . 
		  index_pile($id_boucle,  'id_article', $boucles) . 
		  ")" ;
		  if ($boucles[$id_boucle]->hash)
		    $code = "url_var_recherche(" . $code . ")";
		break;

  case 'URL_RUBRIQUE':
		$code = "generer_url_rubrique(" . 
		  index_pile($id_boucle,  'id_rubrique', $boucles) . 
		  ")" ;
		  if ($boucles[$id_boucle]->hash)
		    $code = "url_var_recherche(" . $code . ")";
		break;

  case 'URL_BREVE':
		$code = "generer_url_breve(" .
		  index_pile($id_boucle,  'id_breve', $boucles) . 
		  ")";
		  if ($boucles[$id_boucle]->hash)
		    $code = "url_var_recherche(" . $code . ")";
		break;

  case 'URL_MOT':
		$code = "generer_url_mot(" .
		  index_pile($id_boucle,  'id_mot', $boucles) .
		  ")";
		$code = "url_var_recherche(" . $code . ")";
		break;

  case 'URL_FORUM':
		$code = "generer_url_forum(" .
		  index_pile($id_boucle,  'id_forum', $boucles) .")";
		break;

  case 'URL_DOCUMENT':
		$code = "generer_url_document(" .
		  index_pile($id_boucle,  'id_document', $boucles) . ")";
		break;

  case 'URL_AUTEUR': # 1.7.2
               $code = "generer_url_auteur(" .
                 index_pile($id_boucle,  'id_forum', $boucles) .")";
               if ($boucles[$id_boucle]->hash)
                   $code = "url_var_recherche(" . $code . ")";
               break;
 
  case 'NOTES':
		$milieu = '$lacible = $GLOBALS["les_notes"];
			$GLOBALS["les_notes"] = "";
			$GLOBALS["compt_note"] = 0;
			$GLOBALS["marqueur_notes"] ++;
			';
		$code = '$lacible';
		break;

  case 'RECHERCHE':
		$code = 'htmlspecialchars($GLOBALS["recherche"])';
		break;

  case 'COMPTEUR_BOUCLE':
		$code = '$compteur_boucle';
		break;

  case 'TOTAL_BOUCLE':
	  $n = 0;
	  $b = $id_boucle;
	  // si $id_boucle est vide, c'est la racine 
	  // il faudrait dire a` l'auteur du squelette que ca n'a pas de sens
	  while ($b != $id_mere)
	    {
	      $n++;
	      $b = $boucles[$b]->id_parent;
	      // c~a ne devrait pas arriver si id_mere != '',
	      // mais je ne prends pas le risque
	      if (!$b) break;
	    }
	$code = '$PileNum[$SP' . (($n==0) ? "" : "+$n") . ']';
	$boucles[$id_boucle]->numrows = true;
#	spip_log("TOTAL_BOUCLE: $id_boucle dans $id_mere");
		break;

  case 'POPULARITE_ABSOLUE':
		$code = 'ceil(' .
		  index_pile($id_boucle,  "popularite", $boucles) .
		  ')';
		break;

  case 'POPULARITE_SITE':
                $code = 'ceil(lire_meta(\'popularite_total\'))';
                break;

  case 'POPULARITE_MAX':
                $code = 'ceil(lire_meta(\'popularite_max\'))';
                break;


  case 'EXPOSER':
          break;
                $on = 'on';
                $off='';
                if ($fonctions) {
                        // Gerer la notation [(#EXPOSER|on,off)]
                        reset($fonctions);
                        list(, $onoff) = each($fonctions);
                        ereg("([^,]*)(,(.*))?", $onoff, $regs);
                        $on = addslashes($regs[1]);
                        $off = addslashes($regs[3]);

                        // autres filtres
                        $filtres=Array();
                        while (list(, $nom) = each($fonctions)) {
                                $filtres[] = $nom;
                        }
                        $fonctions = $filtres;
                }
                $id_on_off = $table_primary[$boucles[$id_boucle]->type_requete];
                if ($id_on_off) 
                        $code = "(\$PileRow[0]['$id_on_off'] == \$PileRow[\$SP]['$id_on_off']) ?
 '$on' : '$off'";
                else 
                        $code = "'$off'";
                break;
	//
	// Inserer directement un document dans le squelette
	//
	case 'EMBED_DOCUMENT':
		$milieu = "
		include_ecrire('inc_documents.php3');";
		$code = "embed_document(" .
		  index_pile($id_boucle,  'id_document', $boucles) . ", '" .
			($fonctions) ? join($fonctions, "|") : "" .
			"', false)";
		$fonctions = "";
		break;

	// Debut et fin de surlignage auto des mots de la recherche
	// on inse`re une balise Span avec une classe sans spec:
	// c'est transparent s'il n'y a pas de recherche,
	// sinon elles seront remplace'es par les fontions de inc_surligne
	// flag_pcre est juste une flag signalant que preg_match est dispo.

  case 'DEBUT_SURLIGNE':
	  $code = ($flag_pcre ? ('\'<span class="spip_surligneconditionnel">\'') : '');
	  break;
  case 'FIN_SURLIGNE':
	  $code = ($flag_pcre ? ('\'</span class="spip_surligneconditionnel">\'') : '');
	  break;

  case 'MENU_LANG':
                $code = '"<"."?php
                        include_ecrire(\"inc_lang.php3\");
                        echo menu_langues(\"var_lang\", \$menu_lang);
                        ?".">"';
                break;

        //
        // Formulaire de changement de langue / page de login
  case 'MENU_LANG_ECRIRE':
                $code = '"<"."?php
                        include_ecrire(\"inc_lang.php3\");
                        echo menu_langues(\"var_lang_ecrire\", \$menu_lang);
                        ?".">"';

                break;

	//
	// Formulaires de login
	//
  case 'LOGIN_PRIVE':
	  $code = '"<"."?php include(\'inc-login.php3\'); login(\'\', \'prive\'); ?".">"'; 
		break;

  case 'LOGIN_PUBLIC':
    $lacible = '\$GLOBALS[\'clean_link\']';
    if ($fonctions) {
      $filtres = array();
      while (list(, $nom) = each($fonctions))
	$lacible = "new Link('".$nom."')";
      $fonctions = $filtres;
    }
    $code = '"<"."?php include(\'inc-login.php3\'); login(' . $lacible . ', false); ?".">"';
    break;

  case 'URL_LOGOUT':
                if ($fonctions) {
                        $url = "&url=".$fonctions[0];
                        $fonctions = array();
                } else {
                        $url = '&url=\'.urlencode(\$clean_link->getUrl()).\'';
                }
                $code = '"<"."?php if (\$GLOBALS[\'auteur_session\'][\'login\'])
 { echo \'spip_cookie.php3?logout_public=\'.\$GLOBALS[\'auteur_session\'][\'login\'].\'' . $url . '\'; } ?".">"';
		break;

  case 'LOGO_ARTICLE':
  case 'LOGO_ARTICLE_NORMAL':
  case 'LOGO_ARTICLE_RUBRIQUE':
  case 'LOGO_ARTICLE_SURVOL':
  case 'LOGO_AUTEUR':
  case 'LOGO_AUTEUR_NORMAL':
  case 'LOGO_AUTEUR_SURVOL':
  case 'LOGO_SITE':
  case 'LOGO_BREVE':
  case 'LOGO_BREVE_RUBRIQUE':
  case 'LOGO_MOT':
  case 'LOGO_RUBRIQUE':
  case 'LOGO_RUBRIQUE_NORMAL':
  case 'LOGO_RUBRIQUE_SURVOL':
  case 'LOGO_DOCUMENT' :
    // retour imme'diat: filtres de'rogatoires traite's dans la fonction
    return calculer_champ_LOGO($fonctions, $nom_champ, $id_boucle, $boucles, $id_mere);
    break; 

  default:
	  // champ inconnu. Il s'auto-de'note.
	    $code = "'#$nom_champ'";
	  break;
	} // switch

  list($c,$m) = applique_filtres($fonctions, $code, $id_boucle, $boucles, $id_mere);
  return array($c,$milieu . $m);
}


?>
