<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/forum?lang_cible=ca
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'aucun_message_forum' => 'Aucun message de forum', # NEW

	// B
	'bouton_radio_articles_futurs' => 'només als futurs articles (no afecta a la base de dades).',
	'bouton_radio_articles_tous' => 'a tots els articles sense cap excepció.',
	'bouton_radio_articles_tous_sauf_forum_desactive' => 'a tots els articles, excepte aquells que tenen el fòrum desactivat. ',
	'bouton_radio_enregistrement_obligatoire' => 'Enregistrament obligatori (els usuaris
  s\'han d\'inscriure facilitant la seva adreça electrònica abans de poder
  afegir les seves contribucions).',
	'bouton_radio_moderation_priori' => 'Moderat a priori (les
 contribucions no apareixen públicament fins que no han estat
 validades des de l\'adminsitració del
 lloc).', # MODIF
	'bouton_radio_modere_abonnement' => 'per subscripció',
	'bouton_radio_modere_posteriori' => 'moderació a posteriori', # MODIF
	'bouton_radio_modere_priori' => 'moderació a priori', # MODIF
	'bouton_radio_publication_immediate' => 'Publicació immediata dels missatges (es poden suprimir posteriorment des de l\'administració). ',

	// D
	'documents_interdits_forum' => 'Documents prohibits al fórum',

	// E
	'erreur_enregistrement_message' => 'Votre message n\'a pas pu être enregistré en raison d\'un problème technique', # NEW

	// F
	'form_pet_message_commentaire' => 'Un missatge, un comentari?',
	'forum' => 'Fòrum',
	'forum_acces_refuse' => 'Ja no teniu accés a aquests fòrums.',
	'forum_attention_dix_caracteres' => '<b>Atenció!</b> el vostre missatge ha de tenir almenys deu caràcters.',
	'forum_attention_message_non_poste' => 'Attention, vous n\'avez pas posté votre message !', # NEW
	'forum_attention_nb_caracteres_mini' => '<b>Attention !</b> votre message doit contenir au moins @min@ caractères.', # NEW
	'forum_attention_trois_caracteres' => '<b>Atenció!</b> el vostre títol ha de tenir almenys tres caràcters.',
	'forum_attention_trop_caracteres' => '<b>Atenció!</b> el vostre missatge és massa llarg (@compte@ caràcters) : per a poder-vos enregistrar no pot sobrepassar els @max@ caràcters.', # MODIF
	'forum_avez_selectionne' => 'Heu seleccionat:',
	'forum_cliquer_retour' => 'Premeu <a href=\'@retour_forum@\'>ici</a> per continuar.',
	'forum_forum' => 'fòrum',
	'forum_info_modere' => 'Aquest fòrum és moderat a priori: la seva contribució no es mostrarà fins que no hagi estat validada per l\'administrador del lloc. ', # MODIF
	'forum_lien_hyper' => '<b>Enllaç hipertext</b> (opcional)', # MODIF
	'forum_message' => 'Votre message', # NEW
	'forum_message_definitif' => 'Missatge definitiu: enviar al lloc',
	'forum_message_trop_long' => 'El vostre missatge és massa llarg. La mida màxima són 20000 caràcters.', # MODIF
	'forum_ne_repondez_pas' => 'No respongueu a aquest correu electrònic, feu-ho al fòrum a la següent adreça: ',
	'forum_page_url' => '(Si el vostre missatge fa referència a un article publicat a la Web, o a una pàgina que conté més informacions, podeu indicar a continuació el títol de la pàgina i la seva adreça URL.)',
	'forum_permalink' => 'Lien permanent vers le commentaire', # NEW
	'forum_poste_par' => 'Missatge enviat@parauteur@ a continuació de l\'article « @titre@ ».', # MODIF
	'forum_poste_par_court' => 'Message posté@parauteur@.', # NEW
	'forum_poste_par_generique' => 'Message posté@parauteur@ (@objet@ « @titre@ »).', # NEW
	'forum_qui_etes_vous' => '<b>Qui sóu?</b> (opcional)', # MODIF
	'forum_saisie_texte_info' => 'Ce formulaire accepte les raccourcis SPIP <code>[-&gt;url] {{gras}} {italique} &lt;quote&gt; &lt;code&gt;</code> et le code HTML <code>&lt;q&gt; &lt;del&gt; &lt;ins&gt;</code>. Pour créer des paragraphes, laissez simplement des lignes vides.', # NEW
	'forum_texte' => 'Text del missatge:', # MODIF
	'forum_titre' => 'Titol:', # MODIF
	'forum_url' => 'URL:', # MODIF
	'forum_valider' => 'Validar l\'elecció',
	'forum_voir_avant' => 'Veure el missatge abans d\'enviar-lo', # MODIF
	'forum_votre_email' => 'La seva adreça electrònica:', # MODIF
	'forum_votre_nom' => 'El vostre nom (o pseudònim):', # MODIF
	'forum_vous_enregistrer' => 'Per participar al fòrum, us heu de registrar prèviament.
Si ja n\'esteu, escriviu a continuació l\'identifcador que us ha estat proporcionat. Si encara no ho heu fet, heu d\'',
	'forum_vous_inscrire' => 'inscriure\'s.',

	// I
	'icone_bruler_message' => 'Signaler comme Spam', # NEW
	'icone_bruler_messages' => 'Signaler comme Spam', # NEW
	'icone_legitimer_message' => 'Signaler comme licite', # NEW
	'icone_poster_message' => 'Enviar un missatge',
	'icone_suivi_forum' => 'Seguiment del fòrum públic: @nb_forums@ contribution(s)',
	'icone_suivi_forums' => 'Seguir/gestionar els fòrums',
	'icone_supprimer_message' => 'Suprimir aquest missatge',
	'icone_supprimer_messages' => 'Supprimer ces messages', # NEW
	'icone_valider_message' => 'Validar aquest missatge',
	'icone_valider_messages' => 'Valider ces messages', # NEW
	'icone_valider_repondre_message' => 'Valider & Répondre à ce message', # NEW
	'info_1_message_forum' => '1 message de forum', # NEW
	'info_activer_forum_public' => '<i>Per activar els fòrums públics, escolliu el seu mode
 de moderació per defecte:</i>', # MODIF
	'info_appliquer_choix_moderation' => 'Aplicar aquesta opció de moderació:',
	'info_config_forums_prive' => 'A l\'espai privat del lloc Web, podeu activar diversos models de fòrums:', # MODIF
	'info_config_forums_prive_admin' => 'Un fòrum reservat als administradors del lloc:',
	'info_config_forums_prive_global' => 'Un fòrum global, obert a tots els redactors:',
	'info_config_forums_prive_objets' => 'Un fòrum a sota de cada article, breu, lloc referenciat, etc.:',
	'info_desactiver_forum_public' => 'Inhabilitar l\'ús dels fòrums públics.
  Els fòrums públics es podran permetre, cas per cas, en els articles;
  estaran prohibits a les seccions, breus, etc.',
	'info_envoi_forum' => 'Enviar els fòrums als autors dels articles',
	'info_fonctionnement_forum' => 'Funcionament del fòrum :',
	'info_forums_liees_mot' => 'Les messages de forum liés à ce mot', # NEW
	'info_gauche_suivi_forum_2' => 'La pàgina de <i>seguiment dels fòrums</i> és una eina de gestió del vostre lloc Web (i no un espai de discussió o de redacció). Mostra totes les contribucions dels fòrums del lloc, tant les de l\'espai públic com les de l\'espai privat i us permet gestionar aquestes contribucions.', # MODIF
	'info_liens_syndiques_3' => 'fòrums',
	'info_liens_syndiques_4' => 'són',
	'info_liens_syndiques_5' => 'fòrum',
	'info_liens_syndiques_6' => 'és',
	'info_liens_syndiques_7' => 'pendent de validació',
	'info_liens_texte' => 'Lien(s) contenu(s) dans le texte du message', # NEW
	'info_liens_titre' => 'Lien(s) contenu(s) dans le titre du message', # NEW
	'info_mode_fonctionnement_defaut_forum_public' => 'Mode de funcionament per defecte dels fòrums públics',
	'info_nb_messages_forum' => '@nb@ messages de forum', # NEW
	'info_option_email' => 'Quan un visitant del lloc envia un nou missatge al fòrum associat a un article, els autors de l\'article poden ser informats d\'aquest missatge per correu electrònic. Indiqueu per cada tipus de fòrum si és necessari utilitzar aquesta opció.',
	'info_pas_de_forum' => 'sense fòrum',
	'info_question_visiteur_ajout_document_forum' => 'Si voleu permetre que els visitants puguin adjuntar documents (imatges, sons...) als seus missatges dels fòrums, indiqueu, més avall, la llista de documents permesos pels fòrums (ex. gif, jpg, png, mp3).', # MODIF
	'info_question_visiteur_ajout_document_forum_format' => 'Si voleu autoritzar tots els tipus de documents considerats com a fiables per SPIP, poseu una estrella. No indiqueu res, si no voleu autoritzar res.', # MODIF
	'info_selectionner_message' => 'Sélectionner les messages :', # NEW
	'interface_formulaire' => 'Interface formulaire', # NEW
	'interface_onglets' => 'Interface avec onglets', # NEW
	'item_activer_forum_administrateur' => 'Activar el fòrum dels administradors',
	'item_config_forums_prive_global' => 'Activar el fòrum dels redactors',
	'item_config_forums_prive_objets' => 'Activar aquests fòrums',
	'item_desactiver_forum_administrateur' => 'Desactivar el fòrum dels administradors',
	'item_non_config_forums_prive_global' => 'Desactivar el fòrum dels redactors',
	'item_non_config_forums_prive_objets' => 'Desactivar aquests fòrums',

	// L
	'label_selectionner' => 'Sélectionner :', # NEW
	'lien_reponse_article' => 'Resposta a l\'article',
	'lien_reponse_breve_2' => 'Resposta a la breu',
	'lien_reponse_message' => 'Réponse au message', # NEW
	'lien_reponse_rubrique' => 'Resposta a la secció',
	'lien_reponse_site_reference' => 'Resposta al lloc referenciat:', # MODIF
	'lien_vider_selection' => 'Vider la selection', # NEW

	// M
	'messages_aucun' => 'Aucun', # NEW
	'messages_meme_auteur' => 'Tous les messages de cet auteur', # NEW
	'messages_meme_email' => 'Tous les messages de cet email', # NEW
	'messages_meme_ip' => 'Tous les messages de cette IP', # NEW
	'messages_off' => 'Supprimés', # NEW
	'messages_perso' => 'Personnels', # NEW
	'messages_privadm' => 'Administrateurs', # NEW
	'messages_prive' => 'Privés', # NEW
	'messages_privoff' => 'Supprimés', # NEW
	'messages_privrac' => 'Généraux', # NEW
	'messages_prop' => 'Proposés', # NEW
	'messages_publie' => 'Publiés', # NEW
	'messages_spam' => 'Spam', # NEW
	'messages_tous' => 'Tous', # NEW

	// O
	'onglet_messages_internes' => 'Missatges interns',
	'onglet_messages_publics' => 'Missatges públics',
	'onglet_messages_vide' => 'Missatges sense text',

	// R
	'repondre_message' => 'Respondre a aquest missatge',

	// S
	'statut_off' => 'Supprimé', # NEW
	'statut_original' => 'original',
	'statut_prop' => 'Proposé', # NEW
	'statut_publie' => 'Publié', # NEW
	'statut_spam' => 'Spam', # NEW

	// T
	'text_article_propose_publication_forum' => 'No dubteu a donar la vostra opinió per mitjà del fòrum agregat a aquest article (al capdavall de la pàgina).',
	'texte_en_cours_validation' => 'Les articles, brèves, forums ci dessous sont proposés à la publication.', # NEW
	'texte_en_cours_validation_forum' => 'No dubteu a donar la vostra opinió per mitjà dels fòrums que porten agregats. ',
	'texte_messages_publics' => 'Messages publics sur :', # NEW
	'titre_cadre_forum_administrateur' => 'Fòrum privat dels administradors',
	'titre_cadre_forum_interne' => 'Fòrum intern',
	'titre_config_forums_prive' => 'Fòrums de l\'espai privat',
	'titre_forum' => 'Fòrum',
	'titre_forum_suivi' => 'Seguiment dels fòrums',
	'titre_page_forum_suivi' => 'Seguiment dels fòrums',
	'titre_selection_action' => 'Sélection', # NEW
	'tout_voir' => 'Voir tous les messages', # NEW

	// V
	'voir_messages_objet' => 'voir les messages' # NEW
);

?>
