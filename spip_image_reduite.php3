<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/


include ("ecrire/inc_version.php3");
include_ecrire("inc_admin.php3");
include_ecrire("inc_logos.php3");

if (!$taille_y)
	$taille_y = $taille_x;

// Chercher l'image dans le repertoire IMG/
if (eregi("(\.\./)?(.*)\.(jpg|gif|png)$", $img, $regs)
AND $i = cherche_image_nommee($regs[2], array($regs[3])) # hu ?
) {
	$img = $i[0].$i[1].'.'.$i[2];
	// si on a deja la bonne taille, pas la peine de se fatiguer
	$taille = @getimagesize($img);
	if ($taille_x == $taille[0] AND $taille_y == $taille[1])
		$stop = true;
}

if (lire_meta('creer_preview') <> 'oui')
	$stop = true;

if (!$stop
AND verifier_action_auteur("reduire $taille_x $taille_y", $hash, $hash_id_auteur))
	{
		list($dir,$nom,$format) = $i;
		$logo = $dir . $nom . '.' . $format;
		
		include_ecrire("inc_logos.php3");
		$suffixe = '-'.$taille_x.'x'.$taille_y;
		$preview = creer_vignette($logo, $taille_x, $taille_y, $format,('cache'.$suffixe), $nom.$suffixe);
		if ($preview)
			$img = $preview['fichier'];
}

// Envoie le navigateur vers l'image cible
redirige_par_entete($img);


?>