<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * une classe definissant un bouton dans la barre du haut de l'interface
 * privee ou dans un de ses sous menus
 */
// http://doc.spip.org/@Bouton
class Bouton
{
	var $icone;         /* l'icone a mettre dans le bouton */
	var $libelle;       /* le nom de l'entree d'i18n associe */
	var $url= null;     /* l'url de la page (null => ?exec=nom) */
	var $urlArg= null;  /* arguments supplementaires de l'url */
	var $url2= null;    /* url jscript */
	var $target= null;  /* pour ouvrir dans une fenetre a part */
	var $sousmenu= null;/* sous barre de boutons / onglets */

	// http://doc.spip.org/@Bouton
	function Bouton($icone, $libelle, $url=null, $urlArg=null,
		$url2=null, $target=null)
	{
		$this->icone  = $icone;
		$this->libelle= $libelle;
		$this->url    = $url;
		$this->urlArg = $urlArg;
		$this->url2   = $url2;
		$this->target = $target;
	}
}