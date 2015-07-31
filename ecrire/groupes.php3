<?php
	include ("inc.php3");
	include_ecrire ("inc_connect.php3");
	include_ecrire ("inc_auth.php3");
	include_ecrire ("inc_meta.php3");

	if ((lire_meta("mailing_list_manager") == $connect_id_auteur)
	OR ($connect_statut == '0minirezo'))
	{
		if ($show_list) {
			@header("Content-Type: text/plain");

			// la liste est donnee par son numero ou par son titre
			if (! ereg("^[0-9]+$", $show_list)) {
				$res = spip_query("SELECT id_liste FROM spip_listes WHERE titre='".addslashes($show_list)."' LIMIT 0,1");
				if ($row = spip_fetch_array($res))
					$show_list = $row['id_liste'];
				else {
					@Header("Content-Type: text/plain");
					echo "\t\t// No list by that name : $show_list\n";
					exit;
				}
			}

			$query = "SELECT auteurs.* FROM spip_auteurs AS auteurs, spip_listes AS listes WHERE FIND_IN_SET(auteurs.statut, listes.droits) AND FIND_IN_SET($show_list,auteurs.abonne) AND id_liste=$show_list";
			$res = spip_query($query);
			if ($res AND (spip_num_rows($res) > 0)) {
				if ($format=='sendmail')
					@header("Content-Type: text/plain");

				while ($row = spip_fetch_array($res))
					if (email_valide($row['email'])) {
						$nom = $row['nom']; // a sanitizer !
						$passliste = $row['abonne_pass']; // idem !
						if ($format == 'sendmail')
							echo "$nom <".$row['email'].">";
						else
							echo $row['email']."\t$passliste\t<a href='auteur_messagerie.php3?id_auteur=".$row['id_auteur']."'>$nom</a><br>";

						echo "\n";
						$subs ++;
					}
				if ($format == 'sendmail') {}
				else
					echo "\t\t // $subs subscriber(s)\n";
			} else
				echo "\t\t// 0 subscriber, empty list\n";
			exit;
		}
		else {
			// traiter les elements POSTes
			$lien = $clean_link;
			if ($modif_liste == 'oui') {
				modifier_liste($id_liste, $titre, $descriptif, $droits, $statut);
				$lien->addVar('modif_liste', 'non');
			}

			if ($creer_liste == 'oui') {
				$query = "INSERT INTO spip_listes
					(titre, descriptif, droits, statut) VALUES
					('Nouvelle liste', '', '0minirezo,1comite', 'prepa')";
				spip_query($query);
				$lien->addVar('creer_liste', 'non');
			}

			if ($HTTP_POST_VARS) {
				@Header("Location: ".$lien->getUrl());
				exit;
			}

			// afficher la liste des listes connues de spip
			debut_page("Gestion de listes de diffusion");
			debut_gauche();
			debut_droite();
			gros_titre("Listes de diffusion");

			echo "<p>" . propre("{{Ceci est une fonctionnalit&eacute;
			exp&eacute;rimentale:}} SPIP peut fabriquer des listes de
			diffusion pr&ecirc;tes &agrave; injecter dans un serveur de
			listes.") . aide("liste") . "<p>" . propre("Il vous faut pour
			cela:\n-# Cr&eacute;er un administrateur d&eacute;di&eacute;
			disposant d'un login et d'un mot de passe (on peut r&eacute;gler
			sa messagerie sur &laquo;ne pas appara&icirc;tre sur la liste
			des auteurs connect&eacute;s&raquo;...)\n-# Indiquer ci-dessous
			la configuration des listes;\n-# Tester la fabrication, par
			SPIP, des fichiers contenant les listes d'abonn&eacute;s;\n-#
			Configurer votre gestionnaire de listes pour qu'il accepte de
			lire ces fichiers.");

			// liste des listes
			$result = spip_query("SELECT * FROM spip_listes");
			if (spip_num_rows($result) == 0)
				echo "\n<p>". propre("{{Ce site ne propose pas de listes de diffusion.}}");
			else while ($row = spip_fetch_array($result)) {
				if ($row['statut'] == 'prepa')
					debut_cadre_enfonce();
				else
					debut_cadre_relief();
				$lien = $GLOBALS['clean_link'];
				$lien->addVar('id_liste', $row['id_liste']);
				$lien->addVar('modif_liste', 'oui');
				echo "\n".$lien->getForm('post');
				echo "<table width='100%'><tr><td valign='top'>\n";
				echo "<b>".$row['id_liste']."</b> - adresse de la liste :\n";
				echo "<br><input type='text' name='titre' value='".entites_html($row['titre'])."'>\n";
				list($selected_tous,$selected_admin) = ($row['droits']<>'0minirezo') ? array("SELECTED","") : array("","SELECTED");
				echo "<br>Qui peut s'inscrire :<br><select name='droits'><OPTION VALUE='0minirezo,1comite' $selected_tous>Tous les auteurs\n<OPTION VALUE='0minirezo' $selected_admin>Les administrateurs uniquement\n</select>";
				list($selected_prepa,$selected_publie) = ($row['statut']<>'publie') ? array("SELECTED","") : array("","SELECTED");
				echo "<br>Statut :<br><select name='statut'><OPTION VALUE='prepa' $selected_prepa>en pr&eacute;paration\n<OPTION VALUE='publie' $selected_publie>active\n</select>";
				if (!email_valide($row['titre']))
					echo "<p><font color='red' size='-1'>> Cette liste ne pourra &ecirc;tre active que lorsque que vous aurez indiqu&eacute; une adresse email valide.</font>\n";
				echo "</td><td valign='top'>Descriptif : ".propre($row['descriptif']);
				echo "<br><input type='text' name='descriptif' value='".entites_html($row['descriptif'])."'>\n";

				$lien = $clean_link;
				$lien->addVar('show_list', $row['id_liste']);
				
				echo "<p><small><a href='".$lien->getUrl()."'>Voir les abonn&eacute;s.</a></small>";

				echo "</td></tr><tr><td valign='top'></td>\n";
				echo "<td valign='top'>";

				echo "<div align='right'><input type='submit' name='Modifier' value='Modifier' class='fondo'></div></td></tr></table>\n";
				echo "</form>\n";

				if ($row['statut'] == 'prepa')
					fin_cadre_enfonce();
				else
					fin_cadre_relief();
			}

			// bouton creation nouvelle liste
			$lien = $GLOBALS['clean_link'];
			$lien->addVar('creer_liste', 'oui');
			echo "\n".$lien->getForm('post');
			echo "<div align='right'><input type='submit' name='creer'
				value='Ajouter une liste' class='fondo'></div></form>\n";
			fin_page();			
		}
	}
	else {
		echo "Vous n'avez pas acc&egrave;s &agrave; ces donn&eacute;es.";
	}

	function modifier_liste($id_liste, $titre, $descriptif, $droits, $statut) {
		if (!email_valide($titre))
			$statut = 'prepa';
		$titre = addslashes(corriger_caracteres($titre));
		$descriptif = addslashes(corriger_caracteres($descriptif));
		if ($droits <> '0minirezo') $droits = '0minirezo,1comite';
		if ($statut <> 'publie') $statut = 'prepa';
		settype($id_liste, 'integer');
		spip_query("UPDATE spip_listes SET titre='$titre', descriptif='$descriptif', droits='$droits', statut='$statut' WHERE id_liste=$id_liste");
	}

?>