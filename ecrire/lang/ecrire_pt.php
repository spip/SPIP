<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://trad.spip.net/tradlang_module/ecrire_?lang_cible=pt
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'activer_plugin' => 'Activer le plugin', # NEW
	'affichage' => 'Affichage', # NEW
	'aide_non_disponible' => 'Esta parte da ajuda em linha ainda não está disponível nesta língua ',
	'annuler_recherche' => 'Annuler la recherche', # NEW
	'auteur' => 'Auteur :', # NEW
	'avis_acces_interdit' => 'Accesso proibido ',
	'avis_article_modifie' => 'Atenção, @nom_auteur_modif@  trabalhou neste artigo há @date_diff@ minutes',
	'avis_aucun_resultat' => 'Nenhum resultado ',
	'avis_base_inaccessible' => 'Impossible de se connecter à la base de données @base@.', # NEW
	'avis_chemin_invalide_1' => 'O caminho que escolheu ',
	'avis_chemin_invalide_2' => 'não parece válido. Favor volte à página anterior e verifique as informações fornecidas. ',
	'avis_connexion_echec_1' => 'A ligação ao servidor  SQL falhou.', # MODIF
	'avis_connexion_echec_2' => 'Volte à página anterior e verifique as informações que forneceu ',
	'avis_connexion_echec_3' => '<b>N.B.</b> Em diversos servidores, precisa <b>solicitar</b> a activação do seu acesso à base de dados SQL antes de poder utilizá-la. Se não consegue ligar-se, verifique se efectuou esse pedido.', # MODIF
	'avis_connexion_erreur_nom_base' => 'Le nom de la base ne peut contenir que des lettres, des chiffres et des tirets', # NEW
	'avis_connexion_ldap_echec_1' => 'A ligação ao servidor LDAP falhou',
	'avis_connexion_ldap_echec_2' => 'Volte à página anterior e verifique as informações que forneceu ',
	'avis_connexion_ldap_echec_3' => 'Alternativamente, não utilize o suporte LDAP para importar utilizadores.',
	'avis_deplacement_rubrique' => 'Atenção ! Esta rubrica contém @contient_breves@ informação@scb@ : se a deslocar, favor confirmá-lo nesta caixa',
	'avis_destinataire_obligatoire' => 'Deve indicar um destinatário antes de mandar esta mensagem.',
	'avis_erreur_connexion_mysql' => 'Erro de ligação SQL', # MODIF
	'avis_erreur_version_archive' => '<b>Atenção! O ficheiro @archive@ corresponde a
    uma versão de SPIP diferente da que
    tem instalada.</b> Enfrentará grandes
    dificuldades: risco de destruição da sua
    base de dados, mau funcionamento
    generalizado do site etc.
    Não valide este pedido de importação.
    <p>Para mais informações, leia
    <a href="@spipnet@">a documentação do SPIP</a>.</p>', # MODIF
	'avis_espace_interdit' => '<b>Espaço interdito</b><p>SPIP já está instalado.', # MODIF
	'avis_lecture_noms_bases_1' => 'O programa de instalação não conseguiu ler os nomes das bases de dados instaladas. ',
	'avis_lecture_noms_bases_2' => 'Ou nenhuma base está disponível, ou a função que permite listar as bases foi desactivada
 por razões de segurança( o que é o caso em muitos serviços de hospedagem).  ',
	'avis_lecture_noms_bases_3' => 'Na segunda alternativa, é provável que uma base tendo o  seu nome de login seja utilizável  :',
	'avis_non_acces_message' => 'Não tem acesso a esta mensagem. ',
	'avis_non_acces_page' => 'Não tem acesso a esta página. ',
	'avis_operation_echec' => 'A operação falhou. ',
	'avis_operation_impossible' => 'Opération impossible', # NEW
	'avis_probleme_archive' => 'Problema de leitura do ficheiro @archive@',
	'avis_suppression_base' => 'Atenção, a supressão dos dados é irreversível',
	'avis_version_mysql' => 'A sua versão de SQL (@version_mysql@) não permite a auto-reparação das tabelas da base.', # MODIF

	// B
	'bouton_acces_ldap' => 'Acrescentar o acesso a LDAP >>', # MODIF
	'bouton_ajouter' => 'Acrescentar ',
	'bouton_ajouter_participant' => 'ACRESCENTAR UM PARTICIPANTE :',
	'bouton_annonce' => 'ANÚNCIO',
	'bouton_annuler' => 'Annuler', # NEW
	'bouton_checkbox_envoi_message' => 'possibilidade de mandar uma mensagem',
	'bouton_checkbox_indiquer_site' => 'indicar obrigatoriamente um sítio Web',
	'bouton_checkbox_signature_unique_email' => 'uma única assinatura por endereço email',
	'bouton_checkbox_signature_unique_site' => 'uma única assinatura por sítio Web',
	'bouton_demande_publication' => 'Pedir a publicação deste artigo ',
	'bouton_desactive_tout' => 'Tout désactiver', # NEW
	'bouton_desinstaller' => 'Désinstaller', # NEW
	'bouton_effacer_index' => 'Apagar os índices',
	'bouton_effacer_tout' => 'Apagar TUDO',
	'bouton_envoi_message_02' => 'MANDAR UMA MENSAGEM',
	'bouton_envoyer_message' => 'Mensagem definitiva: mandar',
	'bouton_fermer' => 'Fermer', # NEW
	'bouton_mettre_a_jour_base' => 'Mettre à jour la base de données', # NEW
	'bouton_modifier' => 'Modificar',
	'bouton_pense_bete' => 'MEMORANDO DE USO PESSOAL',
	'bouton_radio_activer_messagerie' => 'Activar a caixa do correio interno',
	'bouton_radio_activer_messagerie_interne' => 'Activar a caixa de correio interno',
	'bouton_radio_activer_petition' => 'Activar o abaixo-assinado',
	'bouton_radio_afficher' => 'Exibir',
	'bouton_radio_apparaitre_liste_redacteurs_connectes' => 'Aparecer na lista dos redactores ligados',
	'bouton_radio_desactiver_messagerie' => 'Desactivar a caixa de correio',
	'bouton_radio_envoi_annonces_adresse' => 'Enviar os anúncios para o endereço:',
	'bouton_radio_envoi_liste_nouveautes' => 'Enviar a lista das novidades',
	'bouton_radio_non_apparaitre_liste_redacteurs_connectes' => 'Não aparecer na lista dos redactores',
	'bouton_radio_non_envoi_annonces_editoriales' => 'Não enviar anúncios editoriais',
	'bouton_radio_pas_petition' => 'Não há abaixo-assinado',
	'bouton_radio_petition_activee' => 'Abaixo-assinado activado',
	'bouton_radio_supprimer_petition' => 'Suprimir o abaixo-assinado',
	'bouton_redirection' => 'REDIRIGIR',
	'bouton_relancer_installation' => 'Lançar de novo a instalação',
	'bouton_suivant' => 'Seguinte',
	'bouton_tenter_recuperation' => 'Tentar uma reparação',
	'bouton_test_proxy' => 'Testar o proxy',
	'bouton_vider_cache' => 'Esvaziar a cache',
	'bouton_voir_message' => 'Ver esta mensagem antes de validar',

	// C
	'cache_mode_compresse' => 'Os ficheiros da cache são gravados em modo comprimido.',
	'cache_mode_non_compresse' => 'Os ficheiros da cache são gravados em modo não comprimido.',
	'cache_modifiable_webmestre' => 'Este parâmetro é modificável pelo webmaster do site.',
	'calendrier_synchro' => 'Se utilizar um software de agenda compatível <b>iCal</b>, pode sincronizá-lo com as informações deste sítio.',
	'config_activer_champs' => 'Activer les champs suivants', # NEW
	'config_choix_base_sup' => 'indiquer une base sur ce serveur', # NEW
	'config_erreur_base_sup' => 'SPIP n\'a pas accès à la liste des bases accessibles', # NEW
	'config_info_base_sup' => 'Si vous avez d\'autres bases de données à interroger à travers SPIP, avec son serveur SQL ou avec un autre, le formulaire ci-dessous, vous permet de les déclarer. Si vous laissez certains champs vides, les identifiants de connexion à la base principale seront utilisés.', # NEW
	'config_info_base_sup_disponibles' => 'Bases supplémentaires déjà interrogeables:', # NEW
	'config_info_enregistree' => 'La nouvelle configuration a été enregistrée', # NEW
	'config_info_logos' => 'Chaque élément du site peut avoir un logo, ainsi qu\'un « logo de survol »', # NEW
	'config_info_logos_utiliser' => 'Utiliser les logos', # NEW
	'config_info_logos_utiliser_non' => 'Ne pas utiliser les logos', # NEW
	'config_info_logos_utiliser_survol' => 'Utiliser les logos de survol', # NEW
	'config_info_logos_utiliser_survol_non' => 'Ne pas utiliser les logos de survol', # NEW
	'config_info_redirection' => 'En activant cette option, vous pourrez créer des articles virtuels, simples références d\'articles publiés sur d\'autres sites ou hors de SPIP.', # NEW
	'config_redirection' => 'Articles virtuels', # NEW
	'config_titre_base_sup' => 'Déclaration d\'une base supplémentaire', # NEW
	'config_titre_base_sup_choix' => 'Choisissez une base supplémentaire', # NEW
	'connexion_ldap' => 'Connexion :', # NEW
	'copier_en_local' => 'Copier en local', # NEW
	'creer_et_associer_un_auteur' => 'Créer et associer un auteur', # NEW
	'creer_et_associer_une_rubrique' => 'Créer et associer une rubrique', # NEW

	// D
	'date_mot_heures' => 'horas',

	// E
	'ecran_securite' => ' + écran de sécurité @version@', # NEW
	'email' => 'email',
	'email_2' => 'email :',
	'en_savoir_plus' => 'En savoir plus', # NEW
	'entree_adresse_annuaire' => 'Endereço do anuário',
	'entree_adresse_email' => 'O seu endereço email',
	'entree_adresse_email_2' => 'Adresse email', # NEW
	'entree_base_donnee_1' => 'Endereço da base de dados',
	'entree_base_donnee_2' => '(Muitas vezes, este endereço corresponde ao do seu sítio, outras vezes, corresponde à menção «localhost», às vezes, fica totalmente vazio.)',
	'entree_biographie' => 'Curta biografia, em poucas palavras',
	'entree_chemin_acces' => '<b>Introduzir</b> o caminho de acesso :', # MODIF
	'entree_cle_pgp' => 'A sua chave PGP',
	'entree_cle_pgp_2' => 'Clé PGP', # NEW
	'entree_contenu_rubrique' => '(Conteúdo da rubrica em poucas palavras.)',
	'entree_identifiants_connexion' => 'Os seus identificadores de ligação',
	'entree_identifiants_connexion_2' => 'Identifiants de connexion', # NEW
	'entree_informations_connexion_ldap' => 'Favor pôr neste formulário as informações de ligação ao seu anuário LDAP.
Essas informações devem poder ser fornecidas pelo administrador do sistema,
ou da rede',
	'entree_infos_perso' => 'Quem é você?',
	'entree_infos_perso_2' => 'Qui est l\'auteur ?', # NEW
	'entree_interieur_rubrique' => 'Dentro da rubrica :',
	'entree_liens_sites' => '<b>Laço hipertexto</b> (referência, sítio a visitar ...)', # MODIF
	'entree_login' => 'O seu login',
	'entree_login_connexion_1' => 'O login de ligação',
	'entree_login_connexion_2' => '(Corresponde às vezes ao seu login de acesso ao FTP; deixado vazio outrs vezes)',
	'entree_login_ldap' => 'Login LDAP inicial',
	'entree_mot_passe' => 'A sua palavra-passe',
	'entree_mot_passe_1' => 'A palavra-passe de ligação',
	'entree_mot_passe_2' => '(Corresponde, às vezes, à sua palavra-passe para o FTP; outras vezes, vazio)',
	'entree_nom_fichier' => 'Favor introduzir o nome do ficheiro @texte_compresse@:',
	'entree_nom_pseudo' => 'O seu nome ou o seu pseudónimo',
	'entree_nom_pseudo_1' => '(O seu nome ou o seu pseudónimo)',
	'entree_nom_pseudo_2' => 'Nom ou pseudo', # NEW
	'entree_nom_site' => 'O nome do seu sítio',
	'entree_nom_site_2' => 'Nom du site de l\'auteur', # NEW
	'entree_nouveau_passe' => 'Nova palavra-passe',
	'entree_passe_ldap' => 'Palavra-passe',
	'entree_port_annuaire' => 'O número de porta do anuário',
	'entree_signature' => 'Assinatura',
	'entree_titre_obligatoire' => '<b>Título</b> [Obrigatório]<br />', # MODIF
	'entree_url' => 'O endereço (URL) do seu sítio',
	'entree_url_2' => 'Adresse (URL) du site', # NEW
	'erreur_connect_deja_existant' => 'Un serveur existe déjà avec ce nom', # NEW
	'erreur_nom_connect_incorrect' => 'Ce nom de serveur n\'est pas autorisé', # NEW
	'erreur_plugin_desinstalation_echouee' => 'La désinstallation du plugin a echoué. Vous pouvez néanmoins le desactiver.', # NEW
	'erreur_plugin_fichier_absent' => 'Fichier absent', # NEW
	'erreur_plugin_fichier_def_absent' => 'Fichier de définition absent', # NEW
	'erreur_plugin_nom_fonction_interdit' => 'Nom de fonction interdit', # NEW
	'erreur_plugin_nom_manquant' => 'Nom du plugin manquant', # NEW
	'erreur_plugin_prefix_manquant' => 'Espace de nommage du plugin non défini', # NEW
	'erreur_plugin_tag_plugin_absent' => '&lt;plugin&gt; manquant dans le fichier de définition', # NEW
	'erreur_plugin_version_manquant' => 'Version du plugin manquante', # NEW

	// H
	'htaccess_a_simuler' => 'Avertissement: la configuration de votre serveur HTTP ne tient pas compte des fichiers @htaccess@. Pour pouvoir assurer une bonne sécurité, il faut que vous modifiiez cette configuration sur ce point, ou bien que les constantes @constantes@ (définissables dans le fichier mes_options.php) aient comme valeur des répertoires en dehors de @document_root@.', # NEW
	'htaccess_inoperant' => 'htaccess inopérant', # NEW

	// I
	'ical_info1' => 'Esta página apresenta muitos métodos para permanecer em contacto com a vida deste sítio.',
	'ical_info2' => 'Para mais informações sobre estas técnicas, consultar  <a href="@spipnet@">a documentação de SPIP</a>.', # MODIF
	'ical_info_calendrier' => 'Dois calendários estão à sua disposição. O primeiro é uma planta do sítio anunciando todos os artigos publicados. O segundo contém os anúncios editoriais assim como as últimas mensagens privadas : está reservado para si graças a uma chave pessoal, que pode modificar a qualquer momento com a renovação da sua palavra-passe.',
	'ical_methode_http' => 'Telecarregamento',
	'ical_methode_webcal' => 'Sincronização (webcal://)',
	'ical_texte_js' => 'Uma linha de javascript permite-lhe exibir, muito simplesmente, em qualquer sítio que lhe pertença, os artigos recentes publicados neste sítio.',
	'ical_texte_prive' => 'Este calendário, de uso estritamente pessoal, informa sobre a actividade editorial privada deste sítio (tarefas e encontros pessoais, artigos e notícias propostos...).',
	'ical_texte_public' => 'Este calendário permite acompanhar a actividade pública deste sítio (artigos e notícias publicados).',
	'ical_texte_rss' => 'Pode vincular as novidades deste sítio em qualquer leitor de ficheiros de formato XML/RSS (Rich Site Summary). É também o formato que permite a SPIP ler as novidades publicadas em outros sítios que utilizam um formato de troca compatível.',
	'ical_titre_js' => 'Javascript',
	'ical_titre_mailing' => 'Mailing-list',
	'ical_titre_rss' => 'Ficheiros « backend »', # MODIF
	'icone_accueil' => 'Accueil', # NEW
	'icone_activer_cookie' => 'Activar o cookie de correspondência',
	'icone_activite' => 'Activité', # NEW
	'icone_admin_plugin' => 'Gestion des plugins', # NEW
	'icone_administration' => 'Maintenance', # NEW
	'icone_afficher_auteurs' => 'Exibir os autores',
	'icone_afficher_visiteurs' => 'Exibir os visitantes',
	'icone_arret_discussion' => 'Não voltar a participar nesta discussão',
	'icone_calendrier' => 'Calendário',
	'icone_configuration' => 'Configuration', # NEW
	'icone_creer_auteur' => 'Criar um novo autor e associá-lo a este artigo',
	'icone_creer_mot_cle' => 'Criar uma nova palavra-chave e vinculá-la a este artigo',
	'icone_creer_mot_cle_rubrique' => 'Créer un nouveau mot-clé et le lier à cette rubrique', # NEW
	'icone_creer_mot_cle_site' => 'Créer un nouveau mot-clé et le lier à ce site', # NEW
	'icone_creer_rubrique_2' => 'Criar uma nova rubrica',
	'icone_edition' => 'Édition', # NEW
	'icone_envoyer_message' => 'Enviar esta mensagem',
	'icone_ma_langue' => 'Ma langue', # NEW
	'icone_mes_infos' => 'Mes informations', # NEW
	'icone_mes_preferences' => 'Mes préférences', # NEW
	'icone_modifier_article' => 'Modificar este artigo',
	'icone_modifier_message' => 'Modificar esta mensagem',
	'icone_modifier_rubrique' => 'Modificar esta rubrica',
	'icone_publication' => 'Publication', # NEW
	'icone_relancer_signataire' => 'Relancer le signataire', # NEW
	'icone_retour' => 'voltar',
	'icone_retour_article' => 'Voltar ao artigo',
	'icone_squelette' => 'Squelettes', # NEW
	'icone_suivi_publication' => 'Suivi de la publication', # NEW
	'icone_supprimer_cookie' => 'Suprimir o cookie de correspondência',
	'icone_supprimer_rubrique' => 'Suprimir esta rubrica',
	'icone_supprimer_signature' => 'Suprimir esta assinatura',
	'icone_valider_signature' => 'Validar esta assinatura',
	'image_administrer_rubrique' => 'Pode administrar esta rubrica',
	'impossible_modifier_login_auteur' => 'Impossible de modifier le login.', # NEW
	'impossible_modifier_pass_auteur' => 'Impossible de modifier le mot de passe.', # NEW
	'info_1_article' => '1 artigo',
	'info_1_article_syndique' => '1 article syndiqué', # NEW
	'info_1_auteur' => '1 auteur', # NEW
	'info_1_message' => '1 message', # NEW
	'info_1_mot_cle' => '1 mot-clé', # NEW
	'info_1_rubrique' => '1 rubrique', # NEW
	'info_1_site' => '1 sítio',
	'info_1_visiteur' => '1 visiteur', # NEW
	'info_activer_cookie' => 'Pode activar um <b>cookie de correspondência</b>, o que lhe
 permitirá passar facilmente do sítio público ao sítio privado ',
	'info_admin_etre_webmestre' => 'Me donner les droits de webmestre', # NEW
	'info_admin_gere_rubriques' => 'Este administrador gere as seguintes rubricas:',
	'info_admin_gere_toutes_rubriques' => 'Este administrador gere <b>todas as rubricas</b>.', # MODIF
	'info_admin_gere_toutes_rubriques_2' => 'Je gère <b>toutes les rubriques</b>', # NEW
	'info_admin_je_suis_webmestre' => 'Je suis <b>webmestre</b>', # NEW
	'info_admin_statuer_webmestre' => 'Donner à cet administrateur les droits de webmestre', # NEW
	'info_admin_webmestre' => 'Cet administrateur est <b>webmestre</b>', # NEW
	'info_administrateur' => 'Administrador',
	'info_administrateur_1' => 'Administrador',
	'info_administrateur_2' => 'do sítio (<i>utilize com precaução</i>)',
	'info_administrateur_site_01' => 'Se for o administrador do sítio,favor',
	'info_administrateur_site_02' => 'clicar sobre este vínculo',
	'info_administrateurs' => 'Administradores',
	'info_administrer_rubrique' => 'Pode administrar esta rubrica',
	'info_adresse' => 'ao endereço :',
	'info_adresse_url' => 'Endereço (URL) do sítio público',
	'info_afficher_par_nb' => 'Afficher par', # NEW
	'info_afficher_visites' => 'Exibir as visitas para :',
	'info_aide_en_ligne' => 'Ajuda em linha SPIP',
	'info_ajout_image' => 'Quando acrescentar imagens como documentos ligados a um artigo,
 SPIP pode criar para si, automaticamente, vinhetas (miniaturas) das
 imagens inseridas. Isso permite por exemplo criar
 automaticamente uma galeria ou um portfolio.',
	'info_ajout_participant' => 'O seguinte participante foi acrescentado :',
	'info_ajouter_rubrique' => 'Acrescentar uma rubrica a administrar :',
	'info_annonce_nouveautes' => 'Anúncio das novidades',
	'info_anterieur' => 'anterior',
	'info_article' => 'artigo',
	'info_article_2' => 'artigos',
	'info_article_a_paraitre' => 'Os artigos pós-datados a publicar',
	'info_articles_02' => 'artigos',
	'info_articles_2' => 'Artigos',
	'info_articles_auteur' => 'Os artigos deste autor',
	'info_articles_miens' => 'Mes articles', # NEW
	'info_articles_tous' => 'Tous les articles', # NEW
	'info_articles_trouves' => 'Artigos encontrados',
	'info_articles_trouves_dans_texte' => 'Artigos encontrados (no texto)',
	'info_attente_validation' => 'Os seus artigos à espera de validação',
	'info_aucun_article' => 'Aucun article', # NEW
	'info_aucun_article_syndique' => 'Aucun article syndiqué', # NEW
	'info_aucun_auteur' => 'Aucun auteur', # NEW
	'info_aucun_message' => 'Aucun message', # NEW
	'info_aucun_rubrique' => 'Aucune rubrique', # NEW
	'info_aucun_site' => 'Aucun site', # NEW
	'info_aucun_visiteur' => 'Aucun visiteur', # NEW
	'info_aujourdhui' => 'hoje :',
	'info_auteur_message' => 'AUTOR DA MENSAGEM',
	'info_auteurs' => 'Os autores',
	'info_auteurs_par_tri' => 'Autores@partri@',
	'info_auteurs_trouves' => 'Autores encontrados',
	'info_authentification_externe' => 'Autentificação externa',
	'info_avertissement' => 'Aviso',
	'info_barre_outils' => 'avec sa barre d\'outils ?', # NEW
	'info_base_installee' => 'A estrutura da sua base de dados está instalada',
	'info_bio' => 'Biographie', # NEW
	'info_chapeau' => 'Cabeçalho',
	'info_chapeau_2' => 'Cabeçalho :',
	'info_chemin_acces_1' => 'Opções : <b>Caminho de acesso no anuário</b>', # MODIF
	'info_chemin_acces_2' => 'Doravante, deve configurar o caminho de acesso às informações no anuário. Esta informação é indispensável para ler os perfis de utilizador armazenados no anuário.',
	'info_chemin_acces_annuaire' => 'Opções : <b>Caminho de acesso no anuário', # MODIF
	'info_choix_base' => 'Terceira etapa :',
	'info_classement_1' => '<sup>er</sup> no @liste@',
	'info_classement_2' => '<sup>e</sup> no @liste@',
	'info_code_acces' => 'Não se esqueça dos seus próprios códigos de acesso !',
	'info_compatibilite_html' => 'Norme HTML à suivre', # NEW
	'info_compresseur_gzip' => '<b>N. B. :</b> Il est recommandé de vérifier au préalable si l\'hébergeur compresse déjà systématiquement les scripts php ; pour cela, vous pouvez par exemple utiliser le service suivant : @testgzip@', # MODIF
	'info_compresseur_texte' => 'Si votre serveur ne comprime pas automatiquement les pages html pour les envoyer aux internautes, vous pouvez essayer de forcer cette compression pour diminuer le poids des pages téléchargées. <b>Attention</b> : cela peut ralentir considerablement certains serveurs.', # NEW
	'info_config_suivi' => 'Se este endereço corresponder a uma mailing-list, pode indicar, a seguir, o endereço no qual os participantes no sítio podem inscrever-se. Este endereço pode ser uma URL (por exemplo a página de inscrição na lista pela Web), ou um endereço email com um assunto específico (por exemplo: <tt>@adresse_suivi@?subject=subscribe</tt>):',
	'info_config_suivi_explication' => ' Pode assinar a  mailing-list deste sítio. Receberá então, por correio electrónico, os anúncios de artigos e de notícias propostos para publicação.',
	'info_confirmer_passe' => 'Confirmar esta nova palavra-passe',
	'info_conflit_edition_avis_non_sauvegarde' => 'Attention, les champs suivants ont été modifiés par ailleurs. Vos modifications sur ces champs n\'ont donc pas été enregistrées.', # NEW
	'info_conflit_edition_differences' => 'Différences :', # NEW
	'info_conflit_edition_version_enregistree' => 'La version enregistrée :', # NEW
	'info_conflit_edition_votre_version' => 'Votre version :', # NEW
	'info_connexion_base' => 'Segunda etapa: <b>Teste de ligação à base</b>', # MODIF
	'info_connexion_base_donnee' => 'Connexion à votre base de données', # NEW
	'info_connexion_ldap_ok' => 'A ligação LDAP foi bem sucedida.</b><p> Pode passar para a etapa seguinte.</p>', # MODIF
	'info_connexion_mysql' => 'Primeira etapa : <b>A sua ligação SQL<b>', # MODIF
	'info_connexion_ok' => 'A ligação foi bem sucedida.',
	'info_contact' => 'Contacto',
	'info_contenu_articles' => 'Conteúdo dos artigos',
	'info_contributions' => 'Contributions', # NEW
	'info_creation_paragraphe' => '(Para criar parágrafos, deixe simplesmente linhas vazias.)', # MODIF
	'info_creation_rubrique' => 'Antes de poder escrever artigos, <br />deve criar pelo menos uma rubrica.<br />', # MODIF
	'info_creation_tables' => 'Quarta etapa : <b>Criação das tabelas da base<b>', # MODIF
	'info_creer_base' => '<b>Criar</b> uma nova base de dados', # MODIF
	'info_dans_rubrique' => 'Na rubrica :',
	'info_date_publication_anterieure' => 'Data de publicação anterior:',
	'info_date_referencement' => 'DATA DE REFERENCIAMENTO DESTE SÍTIO :',
	'info_derniere_etape' => 'Última etapa : <b>Acabou !', # MODIF
	'info_derniers_articles_publies' => 'Os seus últimos artigos publicados em linha',
	'info_desactiver_messagerie_personnelle' => 'Pode activar ou desactivar o seu correio pessoal neste sítio.',
	'info_descriptif' => 'Descrição:',
	'info_desinstaller_plugin' => 'supprime les données et désactive le plugin', # NEW
	'info_discussion_cours' => 'Discussões em curso',
	'info_ecrire_article' => 'Antes de poder escrever artigos, deve criar pelo menos uma rubrica.',
	'info_email_envoi' => 'Endereço de email de envio (opcional)',
	'info_email_envoi_txt' => 'Indique aqui o endereço a utilizar para mandar os emails (se não for o caso, o endereço do destinatário será utilizado como endereço de envio) :',
	'info_email_webmestre' => 'Endereço e-mail do webmestre (opcional)', # MODIF
	'info_entrer_code_alphabet' => 'Insira o código do alfabeto a utilizar :',
	'info_envoi_email_automatique' => 'Envio automático de mails',
	'info_envoyer_maintenant' => 'Enviar agora',
	'info_etape_suivante' => 'Passar para a seguinte etapa',
	'info_etape_suivante_1' => 'Pode passar para a etapa seguinte',
	'info_etape_suivante_2' => 'Pode passar para a etapa seguinte',
	'info_exceptions_proxy' => 'Exceptions pour le proxy', # NEW
	'info_exportation_base' => 'exportação da base para @archive@',
	'info_facilite_suivi_activite' => 'A fim de facilitar o acompanhamento da actividade
 editorial do sítio, SPIP pode fazer chegar por email, por exemplo
 a uma <i>mailing-list</i> dos redactores, o anúncio dos pedidos de
 publicação e das validações de artigos.',
	'info_fichiers_authent' => 'Ficheiros de autenticação « .htpasswd »',
	'info_forums_abo_invites' => 'O seu site contém fóruns por assinatura; os visitantes são convidados a registar-se no site público.',
	'info_gauche_admin_effacer' => '<b>Esta página é acessível apenas aos responsáveis pelo site.</b><p> Ela dá acesso às diferentes funções de manutenção técnica. Algumas dessas funções possuem um processo específico de autenticação que exige acesso FTP ao website.</p>', # MODIF
	'info_gauche_admin_tech' => '<b>Esta página está acessível apenas aos responsáveis pelo site.</b><p> Ela dá acesso às diferentes funções de manutenção técnica. Algumas dessas funções possuem um processo específico de autenticação que exige acesso FTP ao website.</p>', # MODIF
	'info_gauche_admin_vider' => '<b>Esta página é acessível apenas aos responsáveis pelo site.</b><p> Ela dá acesso às diferentes funções de manutenção técnica. Algumas dessas funções possuem um processo específico de autenticação que exige acesso FTP ao website</p>', # MODIF
	'info_gauche_auteurs' => 'Encontrará aqui todos os autores do site.
Os estatuto dos autores é indicado pela cor dos ícones (administrador = verde; redactor = amarelo).',
	'info_gauche_auteurs_exterieurs' => 'Os autores externos, sem acesso ao sítio, são indicados por um ícone azul ;
 os autores apagados, por um caixote de lixo.', # MODIF
	'info_gauche_messagerie' => 'A caixa de correio permite-lhe trocar mensagens entre redactores, conservar memorandos (para o seu uso pessoal) ou exibir anúncios na página de abertura do espaço privado ( se for administrador).',
	'info_gauche_numero_auteur' => 'AUTOR NÚMERO',
	'info_gauche_statistiques_referers' => 'Esta página apresenta a lista dos  <i>referers</i>, ou seja, dos sites que contêm links para o seu site, unicamente para ontem e hoje; esta lista é actualizada a cada 24 horas.',
	'info_gauche_visiteurs_enregistres' => 'Encontrará aqui os visitantes registados
 no espaço público do sítio (fóruns por assinatura).',
	'info_generation_miniatures_images' => 'Geração de miniaturas das imagens',
	'info_gerer_trad' => 'Gerir os vínculos de tradução?',
	'info_gerer_trad_objets' => '@objets@ : gérer les liens de traduction', # NEW
	'info_hebergeur_desactiver_envoi_email' => 'Alguns serviços de hospedagem desactivam o envio automático de
 mails a partir dos seus  servidores. Nesse caso, as seguintes
 funcionalidades de SPIP não funcionarão.',
	'info_hier' => 'ontem :',
	'info_historique_activer' => 'Activar o acompanhamento das revisões',
	'info_historique_affiche' => 'Visualizar esta versão',
	'info_historique_comparaison' => 'comparação',
	'info_historique_desactiver' => 'Desactivar o acompanhamento das revisões',
	'info_historique_texte' => 'O acompanhamento das revisões permite conservar um histórico de todas as  alterações realizadas ao conteúdo dum artigo, e visualizar as diferenças entre as sucessivas versões',
	'info_identification_publique' => 'A sua identidade pública...',
	'info_image_process' => 'Seleccione o melhor método de criar as vinhetas clicando sobre a imagem correspondente.',
	'info_image_process2' => '<b>N.B.</b> <i> Se nenhuma imagem aparece, então o servidor que alberga o seu sítio não foi configurado para utilizar tais ferramentas. Se deseja utilizar essas funções, contacte o responsavel técnico e peça as extensões  «GD» ou «Imagick».</i>', # MODIF
	'info_images_auto' => 'Imagens calculadas automaticamente',
	'info_informations_personnelles' => 'Quinta etapa : <b>Informações pessoais<b>', # MODIF
	'info_inscription_automatique' => 'Inscrição automática de novos redactores',
	'info_jeu_caractere' => 'Jogo de carácteres do sítio',
	'info_jours' => 'dias',
	'info_laisser_champs_vides' => 'deixar estes campos vazios)',
	'info_langues' => 'Línguas do sítio',
	'info_ldap_ok' => 'A autenticação LDAP está instalada.',
	'info_lien_hypertexte' => 'Vínculo hipertexto :',
	'info_liste_nouveautes_envoyee' => 'La liste des nouveautés a été envoyée', # NEW
	'info_liste_redacteurs_connectes' => 'Lista dos redactores ligados',
	'info_login_existant' => 'Este login já existe.',
	'info_login_trop_court' => 'Login demasiado curto.',
	'info_login_trop_court_car_pluriel' => 'Le login doit contenir au moins @nb@ caractères.', # NEW
	'info_logos' => 'Les logos', # NEW
	'info_maximum' => 'máximo :',
	'info_meme_rubrique' => 'Na mesma rubrica',
	'info_message' => 'Mensagem do',
	'info_message_efface' => 'MENSAGEM APAGADA',
	'info_message_en_redaction' => 'As suas mensagens em curso de redacção',
	'info_message_technique' => 'Mensagem técnica:',
	'info_messagerie_interne' => 'Correio interno',
	'info_mise_a_niveau_base' => 'nivelação da sua base SQL', # MODIF
	'info_mise_a_niveau_base_2' => '{{Atenção!}} Instalou uma versão
 dos ficheiros SPIP {anterior} à que se encontrava
 antes neste sítio: a sua base de dados corre o risco de se perder
 e o seu sítio já não funcionará .<br />{{Re-instalar os
 ficheiros de SPIP.}}', # MODIF
	'info_modification_enregistree' => 'Votre modification a été enregistrée', # NEW
	'info_modifier_auteur' => 'Modifier l\'auteur :', # NEW
	'info_modifier_rubrique' => 'Modificar a rubrica :',
	'info_modifier_titre' => 'Modificar : @titre@',
	'info_mon_site_spip' => 'O meu sítio SPIP',
	'info_mot_sans_groupe' => '(Palavras sem grupo...)',
	'info_moteur_recherche' => 'Motor integrado de pesquisa',
	'info_moyenne' => 'média :',
	'info_multi_articles' => 'Activar o menu de língua sobre os artigos ?',
	'info_multi_cet_article' => 'Língua deste artigo :',
	'info_multi_langues_choisies' => 'Favor seleccionar a seguir as línguas à disposição dos redactores do seu sítio.
 As línguas já utilizadas no seu sítio (exibidas em primeiro lugar) não podem ser desactivadas.',
	'info_multi_objets' => '@objets@ : activer le menu de langue', # NEW
	'info_multi_rubriques' => 'Activar o menu de língua sobre as rubricas ? ',
	'info_multi_secteurs' => '... só para as rubricas situadas na raíz ?',
	'info_nb_articles' => '@nb@ articles', # NEW
	'info_nb_articles_syndiques' => '@nb@ articles syndiqués', # NEW
	'info_nb_auteurs' => '@nb@ auteurs', # NEW
	'info_nb_messages' => '@nb@ messages', # NEW
	'info_nb_mots_cles' => '@nb@ mots-clés', # NEW
	'info_nb_rubriques' => '@nb@ rubriques', # NEW
	'info_nb_sites' => '@nb@ sites', # NEW
	'info_nb_visiteurs' => '@nb@ visiteurs', # NEW
	'info_nom' => 'Nome',
	'info_nom_destinataire' => 'Nome do destinatário',
	'info_nom_site' => 'Nome do seu sítio',
	'info_nombre_articles' => '@nb_articles@ artigos,',
	'info_nombre_partcipants' => 'PARTICIPANTES NA DISCUSSÃO :',
	'info_nombre_rubriques' => '@nb_rubriques@ rubricas,',
	'info_nombre_sites' => '@nb_sites@ sítios,',
	'info_non_deplacer' => 'Não deslocar...',
	'info_non_envoi_annonce_dernieres_nouveautes' => 'SPIP pode enviar regularmente o anúncio das últimas novidades do sítio
 (artigos e notícias recentemente publicados).',
	'info_non_envoi_liste_nouveautes' => 'Não enviar a lista das novidades',
	'info_non_modifiable' => 'não pode ser modificado',
	'info_non_suppression_mot_cle' => 'não quero suprimir esta palavra-chave.',
	'info_note_numero' => 'Note @numero@', # NEW
	'info_notes' => 'Notas',
	'info_nouveaux_message' => 'Novas mensagens',
	'info_nouvel_article' => 'Novo artigo',
	'info_nouvelle_traduction' => 'Nova tradução :',
	'info_numero_article' => 'ARTIGO NÚMERO :',
	'info_obligatoire_02' => '[Obigatório]', # MODIF
	'info_option_accepter_visiteurs' => 'Aceitar a inscrição de visitantes do site público',
	'info_option_faire_suivre' => 'Fazer seguir as mensagens dos fóruns para os autores dos artigos',
	'info_option_ne_pas_accepter_visiteurs' => 'Recusar a inscrição dos visitantes',
	'info_options_avancees' => 'OPÇÕES AVANÇADAS',
	'info_ortho_activer' => 'Activar o corrector ortográfico',
	'info_ortho_desactiver' => 'Desactivar o corrector ortográfico',
	'info_ou' => 'ou...',
	'info_page_interdite' => 'Página proibida',
	'info_par_nom' => 'par nom', # NEW
	'info_par_nombre_article' => '(por número de artigos)', # MODIF
	'info_par_statut' => 'par statut', # NEW
	'info_par_tri' => '\'(par @tri@)\'', # NEW
	'info_passe_trop_court' => 'Palavra-passe demasiado curta',
	'info_passe_trop_court_car_pluriel' => 'Le mot de passe doit contenir au moins @nb@ caractères.', # NEW
	'info_passes_identiques' => 'As duas palavras-passe não são idênticas.',
	'info_pense_bete_ancien' => 'Os seus antigos memorandos', # MODIF
	'info_plus_cinq_car' => 'mais de 5 carácteres',
	'info_plus_cinq_car_2' => '(Mais de 5 carácteres)',
	'info_plus_trois_car' => '(Mais de 3 carácteres)',
	'info_popularite' => 'popularidade : @popularite@ ; visitas : @visites@',
	'info_popularite_4' => 'popularidade : @popularite@ ; visitas : @visites@',
	'info_post_scriptum' => 'Post-Scriptum',
	'info_post_scriptum_2' => 'Post-scriptum :',
	'info_pour' => 'para',
	'info_preview_admin' => 'Apenas os administradores podem visualizar o site',
	'info_preview_comite' => 'Todos os redactores podem visualizar o site',
	'info_preview_desactive' => 'A visualização está totalmente desactivada',
	'info_preview_texte' => 'É possível visualizar o site como se todas os artigos e notas (tendo pelo menos o estatuto de «proposta») estivessem publicados. Esta possibilidade deve estar disponível apenas para os administradores, para todos os redactores, ou para ninguém?',
	'info_principaux_correspondants' => 'Os seus principais correspondentes',
	'info_procedez_par_etape' => 'proceder etapa por etapa',
	'info_procedure_maj_version' => 'o procedimento de actualização deve ser lançado para adaptar
a base de dados à nova versão de SPIP.',
	'info_proxy_ok' => 'Test du proxy réussi.', # NEW
	'info_ps' => 'P.S', # MODIF
	'info_publier' => 'publicar',
	'info_publies' => 'Os seus artigos publicados em linha',
	'info_question_accepter_visiteurs' => 'Se os parâmetros do seu site prevêem o registo de visitantes sem acesso ao espaço privado, por favor, active a opção abaixo:',
	'info_question_inscription_nouveaux_redacteurs' => 'Aceita as inscrições de novos redactores a
 partir do sítio público ? Se aceitar, os visitantes poderão inscrever-se
 a partir de um formulário automatizado e acederão então ao espaço privado para
propor os seus próprios artigos. <blockquote><i>Durante a fase de inscrição,
 os utilizadores recebem um correio electrónico automático
fornecendo-lhes os seus códigos de acesso ao sítio privado.Alguns
serviços de hospedagem desactivam o envio de mails a partir dos seus
 servidores : nesse caso, a inscrição automática é
 impossível.', # MODIF
	'info_question_utilisation_moteur_recherche' => 'Deseja utilizar o motor integrado de pesquisa a SPIP ?
(desactivá-lo acelera o funcionamento do sistema)',
	'info_question_vignettes_referer_non' => 'Ne pas afficher les captures des sites d\'origine des visites', # NEW
	'info_qui_edite' => '@nom_auteur_modif@ a travaillé sur ce contenu il y a @date_diff@ minutes', # MODIF
	'info_racine_site' => 'Raiz do sítio',
	'info_recharger_page' => 'Favor voltar a carregar esta página daqui a pouco.',
	'info_recherche_auteur_a_affiner' => 'Demasiados resultados para "@cherche_auteur@" ; favor afinar a pesquisa.',
	'info_recherche_auteur_ok' => 'Muitos redactores encontrados para "@cherche_auteur@":',
	'info_recherche_auteur_zero' => 'Nenhum resultado para "@cherche_auteur@".',
	'info_recommencer' => 'Favor recomeçar',
	'info_redacteur_1' => 'Redactor',
	'info_redacteur_2' => 'tendo acesso ao espaço privado(<i>recomendado</i>)',
	'info_redacteurs' => 'Redactores',
	'info_redaction_en_cours' => 'EM CURSO DE REDACÇÃO',
	'info_redirection' => 'Redirigir',
	'info_redirection_activee' => 'La redirection est activée.', # NEW
	'info_redirection_desactivee' => 'La redirection a été supprimée.', # NEW
	'info_refuses' => 'Os seus artigos recusados',
	'info_reglage_ldap' => 'Opções : <b>Acerto da importação LDAP</b>', # MODIF
	'info_renvoi_article' => '<b>Redirigir.</b> Este artigo remete para a página:', # MODIF
	'info_reserve_admin' => 'Só os administradores podem modificar este endereço.',
	'info_restreindre_rubrique' => 'Limitar a gestão à rubrica : ',
	'info_resultat_recherche' => 'Resultados da pesquisa ;',
	'info_rubriques' => 'Rubricas',
	'info_rubriques_02' => 'rubricas',
	'info_rubriques_trouvees' => 'Rubricas encontradas',
	'info_rubriques_trouvees_dans_texte' => 'Rubricas encontradas (no texto)',
	'info_sans_titre' => 'Sem título',
	'info_selection_chemin_acces' => '<b>Seleccione</b> a seguir o caminho de acesso no anuário :',
	'info_signatures' => 'assinaturas',
	'info_site' => 'Sítio',
	'info_site_2' => 'sítio :',
	'info_site_min' => 'sítio',
	'info_site_reference_2' => 'Sítio referenciado',
	'info_site_web' => 'SÍTIO WEB :', # MODIF
	'info_sites' => 'sítios',
	'info_sites_lies_mot' => 'Os sítios referenciados ligados a esta palavra-chave',
	'info_sites_proxy' => 'Utilizar um proxy',
	'info_sites_trouves' => 'Sítios encontrados',
	'info_sites_trouves_dans_texte' => 'Sítios encontrados (no texto)',
	'info_sous_titre' => 'Sub-título :',
	'info_statut_administrateur' => 'Administrador',
	'info_statut_auteur' => 'Estatuto deste autor :', # MODIF
	'info_statut_auteur_2' => 'Je suis', # NEW
	'info_statut_auteur_a_confirmer' => 'Inscription à confirmer', # NEW
	'info_statut_auteur_autre' => 'Autre statut :', # NEW
	'info_statut_efface' => 'Apagado',
	'info_statut_redacteur' => 'Redactor',
	'info_statut_utilisateurs_1' => 'Estatuto por defeito dos utilizadores importados',
	'info_statut_utilisateurs_2' => 'Escolha o estatuto atribuído às pessoas presentes no anuário LDAP quando elas se ligam pela primeira vez. Poderá depois modificar este valor para cada autor, caso a caso.',
	'info_suivi_activite' => 'Acompanhamento da actividade editorial',
	'info_surtitre' => 'Antetítulo',
	'info_syndication_integrale_1' => 'Votre site propose des fichiers de syndication (voir « <a href="@url@">@titre@</a> »).', # NEW
	'info_syndication_integrale_2' => 'Souhaitez-vous transmettre les articles dans leur intégralité, ou ne diffuser qu\'un résumé de quelques centaines de caractères ?', # NEW
	'info_table_prefix' => 'Vous pouvez modifier le préfixe du nom des tables de données (ceci est indispensable lorsque l\'on souhaite installer plusieurs sites dans la même base de données). Ce préfixe s\'écrit en lettres minuscules, non accentuées, et sans espace.', # NEW
	'info_taille_maximale_images' => 'SPIP va tester la taille maximale des images qu\'il peut traiter (en millions de pixels).<br /> Les images plus grandes ne seront pas réduites.', # NEW
	'info_taille_maximale_vignette' => 'Tamanho máximo das vinhetas geradas pelo sistema :',
	'info_terminer_installation' => 'Pode agora acabar o procedimento de instalação tipo.',
	'info_texte' => 'Texto',
	'info_texte_explicatif' => 'Texto explicativo',
	'info_texte_long' => '(o texto é comprido : aparece, por isso, em muitas partes que serão coladas depois da validação.)',
	'info_texte_message' => 'Texto da sua mensagem :', # MODIF
	'info_texte_message_02' => 'Texto da mensagem',
	'info_titre' => 'Título :',
	'info_total' => 'total :',
	'info_tous_articles_en_redaction' => 'Todos os artgos em curso de redacção',
	'info_tous_articles_presents' => 'Todos os artigos publicados nesta rubrica',
	'info_tous_articles_refuses' => 'Tous les articles refusés', # NEW
	'info_tous_les' => 'todos os',
	'info_tous_redacteurs' => 'Anúncios a todos os redactores',
	'info_tout_site' => 'Todo o sítio',
	'info_tout_site2' => 'O artigo não está traduzido nesta língua.',
	'info_tout_site3' => 'O artigo foi traduzido nesta língua, mas foram feitas modificações ao artigo original. A tradução necessita ser actualizada.',
	'info_tout_site4' => 'O artigo foi traduzido nesta língua e a tradução está actual.',
	'info_tout_site5' => 'Artigo original.',
	'info_tout_site6' => '<b>Atenção:</b> só os artigos originais são mostrados.
As traduções estão associadas ao original,
numa cor que indica o seu estado:',
	'info_traductions' => 'Traductions', # NEW
	'info_travail_colaboratif' => 'Trabalho colaborativo sobre os artigos',
	'info_un_article' => 'um artigo',
	'info_un_site' => 'um sítio',
	'info_une_rubrique' => 'uma rubrica,',
	'info_une_rubrique_02' => '1 rubrica',
	'info_url' => 'URL :', # MODIF
	'info_url_proxy' => 'URL du proxy', # NEW
	'info_url_site' => 'URL DO SÍTIO :', # MODIF
	'info_url_test_proxy' => 'URL de test', # NEW
	'info_urlref' => 'Ligação hipertexto :',
	'info_utilisation_spip' => 'Pode começar agora a utilizar o sistema de publicação assistida...',
	'info_visites_par_mois' => 'Exibição por mês :',
	'info_visiteur_1' => 'Visitante',
	'info_visiteur_2' => 'do sítio público',
	'info_visiteurs' => 'Visitantes',
	'info_visiteurs_02' => 'Visitantes do sítio público',
	'info_webmestre_forces' => 'Les webmestres sont actuellement définis dans <tt>@file_options@</tt>.', # NEW
	'install_adresse_base_hebergeur' => 'Adresse de la base de données attribuée par l\'hébergeur', # NEW
	'install_base_ok' => 'La base @base@ a été reconnue', # NEW
	'install_connect_ok' => 'La nouvelle base a bien été déclarée sous le nom de serveur @connect@.', # NEW
	'install_echec_annonce' => 'A instalação vai provavelmente falhar,ou criar um sítio não funcional',
	'install_extension_mbstring' => 'O SPIP não funciona com :',
	'install_extension_php_obligatoire' => 'O SPIP exige a extensão php :',
	'install_login_base_hebergeur' => 'Login de connexion attribué par l\'hébergeur', # NEW
	'install_nom_base_hebergeur' => 'Nom de la base attribué par l\'hébergeur :', # NEW
	'install_pas_table' => 'Base actuellement sans tables', # NEW
	'install_pass_base_hebergeur' => 'Mot de passe de connexion attribué par l\'hébergeur', # NEW
	'install_php_version' => 'PHP version @version@ insuffisant (minimum = @minimum@)', # NEW
	'install_select_langue' => 'Seleccione uma língua e depois clique no botão " seguinte " para lançar o procedimento de instalação.',
	'install_select_type_db' => 'Indiquer le type de base de données :', # NEW
	'install_select_type_mysql' => 'MySQL', # NEW
	'install_select_type_pg' => 'PostgreSQL', # NEW
	'install_select_type_sqlite2' => 'SQLite 2', # NEW
	'install_select_type_sqlite3' => 'SQLite 3', # NEW
	'install_serveur_hebergeur' => 'Serveur de base de données attribué par l\'hébergeur', # NEW
	'install_table_prefix_hebergeur' => 'Préfixe de table attribué par l\'hébergeur :', # NEW
	'install_tables_base' => 'Tables de la base', # NEW
	'install_types_db_connus' => 'SPIP sait utiliser <b>MySQL</b> (le plus répandu), <b>PostgreSQL</b> et <b>SQLite</b>.', # NEW
	'install_types_db_connus_avertissement' => 'Attention : plusieurs plugins ne fonctionnent qu\'avec MySQL', # NEW
	'instituer_erreur_statut_a_change' => 'Le statut a déjà été modifié', # NEW
	'instituer_erreur_statut_non_autorise' => 'Vous ne pouvez pas choisir ce statut', # NEW
	'intem_redacteur' => 'redactor',
	'intitule_licence' => 'Licence', # NEW
	'item_accepter_inscriptions' => 'Aceitar as inscrições',
	'item_activer_messages_avertissement' => 'Activar as mensagens de aviso',
	'item_administrateur_2' => 'administrador',
	'item_afficher_calendrier' => 'Exibir o calendário',
	'item_autoriser_documents_joints' => 'Autorizar os documentos juntos aos artigos',
	'item_autoriser_documents_joints_rubriques' => 'Autorizar os documentos nas rubricas',
	'item_autoriser_syndication_integrale' => 'Diffuser l\'intégralité des articles dans les fichiers de syndication', # NEW
	'item_choix_administrateurs' => 'os administradores',
	'item_choix_generation_miniature' => 'Gerar automaticamente as miniaturas das imagens.',
	'item_choix_non_generation_miniature' => 'Não gerar miniaturas das imagens.',
	'item_choix_redacteurs' => 'os redactores',
	'item_choix_visiteurs' => 'os visitadores do sítio público',
	'item_creer_fichiers_authent' => 'Criar os ficheiros .htpasswd',
	'item_limiter_recherche' => 'Limitar a pesquisa às informações contidas no seu sítio',
	'item_login' => 'Login',
	'item_messagerie_agenda' => 'Activer la messagerie et l’agenda', # NEW
	'item_mots_cles_association_articles' => 'aos artigos',
	'item_mots_cles_association_rubriques' => 'às rubricas',
	'item_mots_cles_association_sites' => 'aos sítios referenciados ou vinculados.',
	'item_non' => 'Não',
	'item_non_accepter_inscriptions' => 'Não aceitar as inscrições',
	'item_non_activer_messages_avertissement' => 'Não há mensagens de aviso',
	'item_non_afficher_calendrier' => 'Não exibir no calendário',
	'item_non_autoriser_documents_joints' => 'Não autorizar os documentos nos artigos',
	'item_non_autoriser_documents_joints_rubriques' => 'Não autorizar os documentos nas rubricas',
	'item_non_autoriser_syndication_integrale' => 'Ne diffuser qu\'un résumé', # NEW
	'item_non_compresseur' => 'Désactiver la compression', # NEW
	'item_non_creer_fichiers_authent' => 'Não criar estes ficheiros',
	'item_non_gerer_statistiques' => 'Não gerir as estatísticas',
	'item_non_limiter_recherche' => 'Alargar a pesquisa ao conteúdo dos sítios referenciados',
	'item_non_messagerie_agenda' => 'Désactiver la messagerie et l’agenda', # NEW
	'item_non_publier_articles' => 'Não publicar os artigos antes da data de publicação fixada.',
	'item_non_utiliser_moteur_recherche' => 'Não utilizar o motor',
	'item_nouvel_auteur' => 'Novo autor',
	'item_nouvelle_rubrique' => 'Nova rubrica',
	'item_oui' => 'Sim',
	'item_publier_articles' => 'Publicar os artigos, seja qual for a sua data de publicação.',
	'item_reponse_article' => 'Resposta ao artigo',
	'item_utiliser_moteur_recherche' => 'Utilizar o motor de pesquisa',
	'item_version_html_max_html4' => 'Se limiter au HTML4 sur le site public', # NEW
	'item_version_html_max_html5' => 'Permettre le HTML5', # NEW
	'item_visiteur' => 'visitante',

	// J
	'jour_non_connu_nc' => 'n.c.',

	// L
	'label_bando_outils' => 'Barre d\'outils', # NEW
	'label_bando_outils_afficher' => 'Afficher les outils', # NEW
	'label_bando_outils_masquer' => 'Masquer les outils', # NEW
	'label_choix_langue' => 'Selectionnez votre langue', # NEW
	'label_nom_fichier_connect' => 'Indiquez le nom utilisé pour ce serveur', # NEW
	'label_slogan_site' => 'Slogan du site', # NEW
	'label_taille_ecran' => 'Largeur de l\'ecran', # NEW
	'label_texte_et_icones_navigation' => 'Menu de navigation', # NEW
	'label_texte_et_icones_page' => 'Affichage dans la page', # NEW
	'ldap_correspondance' => 'héritage du champ @champ@', # NEW
	'ldap_correspondance_1' => 'Héritage des champs LDAP', # NEW
	'ldap_correspondance_2' => 'Pour chacun des champs SPIP suivants, indiquer le nom du champ LDAP correspondant. Laisser vide pour ne pas le remplir, séparer par des espaces ou des virgules pour essayer plusieurs champs LDAP.', # NEW
	'lien_ajout_destinataire' => 'Acrescentar este destinatário',
	'lien_ajouter_auteur' => 'Acrescentar este autor',
	'lien_ajouter_participant' => 'Acrescentar um participante',
	'lien_ajouter_une_rubrique' => 'Ajouter cette rubrique', # NEW
	'lien_email' => 'email',
	'lien_nom_site' => 'NOME DO SÍTIO',
	'lien_retirer_auteur' => 'Tirar o autor',
	'lien_retirer_rubrique' => 'Retirer la rubrique', # NEW
	'lien_retirer_tous_auteurs' => 'Retirer tous les auteurs', # NEW
	'lien_retirer_toutes_rubriques' => 'Retirer toutes les rubriques', # NEW
	'lien_retrait_particpant' => 'tirar este participante',
	'lien_site' => 'sítio',
	'lien_supprimer_rubrique' => 'suprimir esta rubrica',
	'lien_tout_deplier' => 'Expandir tudo',
	'lien_tout_replier' => 'Recolher tudo',
	'lien_tout_supprimer' => 'Tout supprimer', # NEW
	'lien_trier_nom' => 'Seleccionar por nome',
	'lien_trier_nombre_articles' => 'seleccionar por número de artgos',
	'lien_trier_statut' => 'Seleccionar por estatuto',
	'lien_voir_en_ligne' => 'VER EM LINHA',
	'logo_article' => 'LOGOTIPO DO ARTIGO', # MODIF
	'logo_auteur' => 'LOGOTIPO DO AUTOR ', # MODIF
	'logo_rubrique' => 'LOGOTIPO DA RUBRICA', # MODIF
	'logo_site' => 'LOGOTIPO DESTE SÍTIO', # MODIF
	'logo_standard_rubrique' => 'LOGOTIPO MODELO DAS RUBRICAS ', # MODIF
	'logo_survol' => 'LOGOTIPO PARA  LEITURA  RÁPIDA', # MODIF

	// M
	'menu_aide_installation_choix_base' => 'Escolha da sua base',
	'module_fichier_langue' => 'Ficheiro de língua',
	'module_raccourci' => 'Atalhos',
	'module_texte_affiche' => 'Texto exibido',
	'module_texte_explicatif' => 'Pode inserir os seguintes atalhos nos esqueletos do seu sítio público. Serão automaticamente traduzidos para as várias línguas nas quais há um ficheiro de língua.',
	'module_texte_traduction' => 'O ficheiro de língua « @module@ » está disponível em :',
	'mois_non_connu' => 'não conhecido',

	// N
	'nouvelle_version_spip' => 'La version @version@ de SPIP est disponible', # NEW

	// O
	'onglet_contenu' => 'Contenu', # NEW
	'onglet_declarer_une_autre_base' => 'Déclarer une autre base', # NEW
	'onglet_discuter' => 'Discuter', # NEW
	'onglet_documents' => 'Documents', # NEW
	'onglet_interactivite' => 'Interactivité', # NEW
	'onglet_proprietes' => 'Propriétés', # NEW
	'onglet_repartition_actuelle' => 'actualmente',
	'onglet_sous_rubriques' => 'Sous-rubriques', # NEW

	// P
	'page_pas_proxy' => 'Cette page ne doit pas passer par le proxy', # NEW
	'pas_de_proxy_pour' => 'Au besoin, indiquez les machines ou domaines pour lesquels ce proxy ne doit pas s\'appliquer (par exemple : @exemple@)', # NEW
	'plugin_charge_paquet' => 'Chargement du paquet @name@', # NEW
	'plugin_charger' => 'Télécharger', # NEW
	'plugin_erreur_charger' => 'erreur : impossible de charger @zip@', # NEW
	'plugin_erreur_droit1' => 'Le répertoire <code>@dest@</code> n\'est pas accessible en écriture.', # NEW
	'plugin_erreur_droit2' => 'Veuillez vérifier les droits sur ce répertoire (et le créer le cas échéant), ou installer les fichiers par FTP.', # NEW
	'plugin_erreur_zip' => 'echec pclzip : erreur @status@', # NEW
	'plugin_etat_developpement' => 'en développement', # NEW
	'plugin_etat_experimental' => 'expérimental', # NEW
	'plugin_etat_stable' => 'stable', # NEW
	'plugin_etat_test' => 'en test', # NEW
	'plugin_impossible_activer' => 'Impossible d\'activer le plugin @plugin@', # NEW
	'plugin_info_automatique1' => 'Si vous souhaitez autoriser l\'installation automatique des plugins, veuillez :', # NEW
	'plugin_info_automatique1_lib' => 'Si vous souhaitez autoriser l\'installation automatique de cette librairie, veuillez :', # NEW
	'plugin_info_automatique2' => 'créer un répertoire <code>@rep@</code> ;', # NEW
	'plugin_info_automatique3' => 'vérifier que le serveur est autorisé à écrire dans ce répertoire.', # NEW
	'plugin_info_automatique_creer' => 'à créer à la racine du site.', # NEW
	'plugin_info_automatique_exemples' => 'exemples :', # NEW
	'plugin_info_automatique_ftp' => 'Vous pouvez installer des plugins, par FTP, dans le répertoire <tt>@rep@</tt>', # NEW
	'plugin_info_automatique_lib' => 'Certains plugins demandent aussi à pouvoir télécharger des fichiers dans le répertoire <code>lib/</code>, à créer le cas échéant à la racine du site.', # NEW
	'plugin_info_automatique_liste' => 'Vos listes de plugins :', # NEW
	'plugin_info_automatique_liste_officielle' => 'les plugins officiels', # NEW
	'plugin_info_automatique_liste_update' => 'Mettre à jour les listes', # NEW
	'plugin_info_automatique_ou' => 'ou...', # NEW
	'plugin_info_automatique_select' => 'Sélectionnez ci-dessous un plugin : SPIP le téléchargera et l\'installera dans le répertoire <code>@rep@</code> ; si ce plugin existe déjà, il sera mis à jour.', # NEW
	'plugin_info_credit' => 'Crédits', # NEW
	'plugin_info_erreur_xml' => 'La déclaration de ce plugin est incorrecte', # NEW
	'plugin_info_install_ok' => 'Installation réussie', # NEW
	'plugin_info_necessite' => 'Necessite :', # NEW
	'plugin_info_non_compatible_spip' => 'Ce plugin n\'est pas compatible avec cette version de SPIP', # NEW
	'plugin_info_plugins_dist_1' => 'Les extensions ci-dessous sont chargées et activées dans le répertoire @plugins_dist@.', # NEW
	'plugin_info_plugins_dist_2' => 'Elles ne sont pas désactivables.', # NEW
	'plugin_info_telecharger' => 'à télécharger depuis @url@ et à installer dans @rep@', # NEW
	'plugin_librairies_installees' => 'Librairies installées', # NEW
	'plugin_necessite_lib' => 'Ce plugin nécessite la librairie @lib@', # NEW
	'plugin_necessite_plugin' => 'Nécessite le plugin @plugin@ en version @version@ minimum.', # NEW
	'plugin_necessite_plugin_sans_version' => 'Nécessite le plugin @plugin@', # NEW
	'plugin_necessite_spip' => 'Nécessite SPIP en version @version@ minimum.', # NEW
	'plugin_source' => 'source: ', # NEW
	'plugin_titre_automatique' => 'Installation automatique', # NEW
	'plugin_titre_automatique_ajouter' => 'Ajouter des plugins', # NEW
	'plugin_titre_installation' => 'Installation du plugin @plugin@', # NEW
	'plugin_titre_modifier' => 'Mes plugins', # NEW
	'plugin_zip_active' => 'Continuez pour l\'activer', # NEW
	'plugin_zip_adresse' => 'indiquez ci-dessous l\'adresse d\'un fichier zip de plugin à télécharger, ou encore l\'adresse d\'une liste de plugins.', # NEW
	'plugin_zip_adresse_champ' => 'Adresse du plugin ou de la liste ', # NEW
	'plugin_zip_content' => 'Il contient les fichiers suivants (@taille@),<br />prêts à installer dans le répertoire <code>@rep@</code>', # NEW
	'plugin_zip_installe_finie' => 'Le fichier @zip@ a été décompacté et installé.', # NEW
	'plugin_zip_installe_rep_finie' => 'Le fichier @zip@ a été décompacté et installé dans le répertoire @rep@', # NEW
	'plugin_zip_installer' => 'Vous pouvez maintenant l\'installer.', # NEW
	'plugin_zip_telecharge' => 'Le fichier @zip@ a été téléchargé', # NEW
	'plugins_actif_aucun' => 'Aucun plugin activé.', # NEW
	'plugins_actif_un' => 'Un plugin activé.', # NEW
	'plugins_actifs' => '@count@ plugins activés.', # NEW
	'plugins_actifs_liste' => 'Plugins actifs', # MODIF
	'plugins_compte' => '@count@ plugins', # NEW
	'plugins_disponible_un' => 'Un plugin disponible.', # NEW
	'plugins_disponibles' => '@count@ plugins disponibles.', # NEW
	'plugins_erreur' => 'Erreur dans les plugins : @plugins@', # NEW
	'plugins_liste' => 'Liste des plugins', # NEW
	'plugins_liste_dist' => 'Extensions', # NEW
	'plugins_recents' => 'Plugins récents.', # NEW
	'plugins_tous_liste' => 'Tous', # NEW
	'plugins_vue_hierarchie' => 'Hiérarchie', # NEW
	'plugins_vue_liste' => 'Liste', # NEW
	'protocole_ldap' => 'Version du protocole :', # NEW

	// Q
	'queue_executer_maintenant' => 'Exécuter maintenant', # NEW
	'queue_info_purger' => 'Vous pouvez supprimer tous les travaux en attente et ré-inialiser la liste avec les travaux périodiques', # NEW
	'queue_nb_jobs_in_queue' => '@nb@ travaux en attente', # NEW
	'queue_next_job_in_nb_sec' => 'Prochain travail dans @nb@ s', # NEW
	'queue_no_job_in_queue' => 'Aucun travail en attente', # NEW
	'queue_one_job_in_queue' => '1 travail en attente', # NEW
	'queue_purger_queue' => 'Purger la liste des travaux', # NEW
	'queue_titre' => 'Liste des travaux', # NEW

	// R
	'repertoire_plugins' => 'Répertoire :', # NEW

	// S
	'sans_heure' => 'sans heure', # NEW
	'statut_admin_restreint' => '(admin restrito)',

	// T
	'tache_cron_asap' => 'Tache CRON @function@ (ASAP)', # NEW
	'tache_cron_secondes' => 'Tache CRON @function@ (toutes les @nb@ s)', # NEW
	'taille_cache_image' => 'As imagens calculadas automaticamente pelo SPIP (miniaturas dos documentos, títulos apresentados sob forma gráfica, funções matemáticas no formato TeX...) ocupam no directório @dir@ um total de @taille@.',
	'taille_cache_infinie' => 'Este site não prevê limitação de tamanho do diretório <code>CACHE/</code>.', # MODIF
	'taille_cache_maxi' => 'O SPIP tenta limitar o tamanho do diretório <code>CACHE/</code> deste site em cerca de <b>@octets@</b>.', # MODIF
	'taille_cache_moins_de' => 'La taille du cache est de moins de @octets@.', # NEW
	'taille_cache_octets' => 'O tamanho da cache é atualmente de  @octets@.', # MODIF
	'taille_cache_vide' => 'A cache está vazia.',
	'taille_repertoire_cache' => 'Tamanho do directório cache',
	'text_article_propose_publication' => 'Artigo proposto para publicação. Não hesite em dar a sua opinião graças ao fórum ligado a este artigo (no fundo da página).', # MODIF
	'texte_acces_ldap_anonyme_1' => 'Alguns servidores LDAP não aceitam nenhum acesso anónimo. Nesse caso, é preciso especificar um identificador de acesso inicial a fim de poder depois procurar informações no anuário. Na maior parte dos casos, porém, os seguintes campos poderão ser deixados vazios.',
	'texte_admin_effacer_01' => 'Este comando apaga <i>todo</i> o conteúdo da base de dados,
inclusive <i>todos</i> os acessos de redactores e administradores. Depois de o ter executado, deverá lançar a
reinstalação de SPIP para recriar uma nova base assim como um primeiro acesso administrador.',
	'texte_adresse_annuaire_1' => '(Se o seu anuário está instalado na mesma máquina que este sítio Web, trata-se de «localhost».)',
	'texte_ajout_auteur' => 'O seguinte autor foi acrescentado ao artigo :',
	'texte_annuaire_ldap_1' => 'Se tiver acesso a um anuário (LDAP), pode utilizá-lo para importar automaticamente utilizadores sob SPIP.',
	'texte_article_statut' => 'Este artigo está :',
	'texte_article_virtuel' => 'Artigo virtual',
	'texte_article_virtuel_reference' => '<b>Artigo virtual :</b> artigo referenciado no seu sítio SPIP, mas redirigido para uma outra URL. Para suprimir a redirecção, apague a URL acima.',
	'texte_aucun_resultat_auteur' => 'Nenhum resultado para "@cherche_auteur@"',
	'texte_auteur_messagerie' => 'Este site pode indicar permanentemente a lista dos redactores ligados, o que lhe permite trocar mensagens  em directo ( quando o correio está desactivado mais acima, a lista dos redactores está desactivada). Pode decidir não aparecer nesta lista (estando « invisível » para os outros utilizadores).',
	'texte_auteur_messagerie_1' => 'Este sítio permite a troca de mensagens e a constituição de fóruns de discussão privados entre os participantes do sítio. Pode decidir não participar nessas trocas.',
	'texte_auteurs' => 'OS AUTORES',
	'texte_choix_base_1' => 'Escolher a sua base',
	'texte_choix_base_2' => 'O servidor SQL contem muitas bases de dados.', # MODIF
	'texte_choix_base_3' => '<b>Seleccione</b> a seguir a que lhe foi atribuida pelo seu serviço de hospedagem.', # MODIF
	'texte_choix_table_prefix' => 'Préfixe des tables :', # NEW
	'texte_commande_vider_tables_indexation' => 'Utilize este comando para esvaziar as tabelas de indexação utilizadas
  pelo motor integrado de pesquisa em SPIP. Isso permitir-lhe-á ganhar espaço em disco',
	'texte_compatibilite_html' => 'Vous pouvez demander à SPIP de produire, sur le site public, du code compatible avec la norme <i>HTML4</i>, ou lui permettre d\'utiliser les possibilités plus modernes du <i>HTML5</i>.', # NEW
	'texte_compatibilite_html_attention' => 'Il n\'y a aucun risque à activer l\'option <i>HTML5</i>, mais si vous le faites, les pages de votre site devront commencer par la mention suivante pour rester valides : <code>&lt;!DOCTYPE html&gt;</code>.', # NEW
	'texte_compresse_ou_non' => '(este pode ser comprimido ou não)',
	'texte_compte_element' => '@count@ elemento',
	'texte_compte_elements' => '@count@ elementos',
	'texte_conflit_edition_correction' => 'Veuillez contrôler ci-dessous les différences entre les deux versions du texte ; vous pouvez aussi copier vos modifications, puis recommencer.', # NEW
	'texte_connexion_mysql' => 'Consulte as informações fornecidas pelo seu serviço de hospedagem : deve encontrar nelas, se o seu hospedeiro suporta SQL, os códigos de ligação ao servidoe SQL.', # MODIF
	'texte_contenu_article' => '(Conteúdo do artigo em poucas palavras.)',
	'texte_contenu_articles' => 'Segundo a maqueta adoptada para o seu sítio, pode decidir
que alguns elementos dos artigos não são utilizados.
   Utilize a lista a seguir para indicar quais são os elementos disponíveis.',
	'texte_crash_base' => 'Se a sua base de dados foi
 destruída, pode tentar uma reparação
 automática.',
	'texte_creer_rubrique' => 'Antes de poder escrever artigos, <br /> deve criar uma rubrica.', # MODIF
	'texte_date_creation_article' => 'DATA DE CRIAÇÃO DO ARTIGO:',
	'texte_date_creation_objet' => 'Date de création :', # on ajoute le ":" NEW
	'texte_date_publication_anterieure' => 'Data de redacção anterior :',
	'texte_date_publication_anterieure_nonaffichee' => 'Não exibir a data de redacção anterior',
	'texte_date_publication_article' => 'DATA DE PUBLICAÇÃO ONLINE:',
	'texte_date_publication_objet' => 'Date de publication en ligne :', # NEW
	'texte_descriptif_petition' => 'Descrição do abaixo-assinado',
	'texte_descriptif_rapide' => 'Descrição rápida',
	'texte_effacer_base' => 'Apagar a base de dados SPIP',
	'texte_effacer_donnees_indexation' => 'Apagar os dados de indexação',
	'texte_effacer_statistiques' => 'Effacer les statistiques', # NEW
	'texte_en_cours_validation' => 'Os artigos e notícias seguintes estão propostos para publicação. Não hesite em dar a sua opinião graças aos fóruns que lhes estão ligados.', # MODIF
	'texte_enrichir_mise_a_jour' => 'Pode enriquecer a paginação do seu texto, utilizando « atalhos tipográficos ».',
	'texte_fichier_authent' => '<b>SPIP deverá criar os ficheiros especiais<tt>.htpasswd-admin</tt> no repertório<tt>ecrire/data/<tt> ?</b><p>
  Estes ficheiros podem servir-lhe para restringir o acesso aos autores
e administradores em outros lugares do seu sites
(programa externo de estatísticas, por exemplo).<p>
 Se não utilizou, pode deixar esta opção
no seu valor por defeito (não há criação 
 dos ficheiros).', # MODIF
	'texte_informations_personnelles_1' => 'O sistema vai agora criar-lhe um acesso personalizado ao sítio',
	'texte_informations_personnelles_2' => '(Nota : se se tratar de uma reinstalação e se o seu acesso continua funcional, pode', # MODIF
	'texte_introductif_article' => '(Texto introdutório do artigo.)',
	'texte_jeu_caractere' => 'Esta opção é útil se o seu sítio precisa de exibir alfabetos
 diferentes do alfabeto romano ( ou  « ocidental ») e seus derivados.
 Nesse caso, pode ser preferível mudar a definição por defeito para utilizar
 um jogo de carácteres adequado ; aconselhamos, em todos os casos, a proceder a experiências
 a fim de encontrar uma solução satisfatória. Se modificar este parâmetro, não esqueça também de adaptar
o sítio público (balisa<tt>#CHARSET</tt>).', # MODIF
	'texte_jeu_caractere_2' => 'Esta definição não tem efeito retroactivo. Por
 conseguinte, os textos já introduzidos podem ser exibidos
 incorrectamente depois de uma modificação da definição. Em todo
 o caso, poderá voltar à definição anterior sem prejuízo.', # MODIF
	'texte_jeu_caractere_3' => 'Votre site est actuellement installé dans le jeu de caractères :', # NEW
	'texte_jeu_caractere_4' => 'Si cela ne correspond pas à la réalité de vos données (suite, par exemple, à une restauration de base de données), ou si <em>vous démarrez ce site</em> et souhaitez partir sur un autre jeu de caractères, veuillez indiquer ce dernier ici :', # NEW
	'texte_jeu_caractere_conversion' => 'Note : vous pouvez décider de convertir une fois pour toutes l\'ensemble des textes de votre site (articles, brèves, forums, etc.) vers l\'alphabet <tt>utf-8</tt>, en vous rendant sur <a href="@url@">la page de conversion vers l\'utf-8</a>.', # NEW
	'texte_lien_hypertexte' => '(Se a sua mensagem se refere a um artigo publicado na Web, ou a uma página que fornece mais informações, favor indicar a seguir o título da página e o endereço URL.)',
	'texte_login_ldap_1' => '(Deixar vazio para um acesso anónimo, ou introduzir o caminho completo, por exemplo, « <tt>uid=silva, ou=users, dc=meu-dominio, dc=com</tt> ».)',
	'texte_login_precaution' => 'Atenção ! Isto é o login sob o qual está ligado actualmente.
Utilize este formulário com precaução...',
	'texte_message_edit' => 'Atenção : esta mensagem pode ser modificada por todos os administradores do sítio, e é visível por todos os redactores. Utilizar os anúncios apenas para exibir acontecimentos importantes da vida do sítio.',
	'texte_messagerie_agenda' => 'Une messagerie permet aux rédacteurs du site de communiquer entre eux directement dans l’espace privé du site. Elle est associée à un agenda.', # NEW
	'texte_mise_a_niveau_base_1' => 'Acaba de actualizar os ficheiros SPIP.
é preciso agora pôr a nível a base de dados
 do sítio. ',
	'texte_modifier_article' => 'Modificar o artigo :',
	'texte_moteur_recherche_active' => '<b>O motor de pesquisa está activado.</b> Utilize este comando
 se desejar proceder a uma reindexação rápida (após restauro
de uma salvaguarda por exemplo). Note que os documentos modificados de
 maneira normal (a partir da interface SPIP) são automaticamente
 reindexados : este comando portanto só é útil de maneira excepcional',
	'texte_moteur_recherche_non_active' => 'O motor de pesquisa não está activado.',
	'texte_multilinguisme' => 'Se desejar gerir artigos em muitas línguas, com uma navegação complexa, pode acrescentar um menu de língua aos artigos e/ou nas rubricas, em função da organização do seu sítio.', # MODIF
	'texte_multilinguisme_trad' => 'Pode também activar um sistema de gestão de laços entre as diferentes traduções de um artigo.', # MODIF
	'texte_non_compresse' => '<i>não comprimido</i> ( o seu sezrvidor não suporta esta funcionalidade)',
	'texte_nouveau_message' => 'Nova mensagem',
	'texte_nouvelle_version_spip_1' => 'Instalou uma nova versão de SPIP.',
	'texte_nouvelle_version_spip_2' => 'Esta nova versão precisa de uma actualização mais completa do que o normal. Se é o webmaster do site, por favor, apague o ficheiro <tt>inc_connect.php3</tt> do directório <tt>ecrire</tt> e retome a instalação de forma a incluir os seus parâmetros de ligação à base de dados.<p> (NB.: se não se lembra dos seus parâmetros de ligação, consulte o arquivo <tt>inc_connect.php3</tt> antes de apagá-lo...)</p>', # MODIF
	'texte_operation_echec' => 'Volte à página anterior, seleccione uma outra base ou crie uma nova. Verifique as informações fornecidas pelo seu serviço de hospedagem.',
	'texte_plus_trois_car' => 'mais de 3 carácteres',
	'texte_plusieurs_articles' => 'Muitos autores encontrados para "@cherche_auteur@":',
	'texte_port_annuaire' => '(O valor indicado por defeito convém geralmente.)',
	'texte_presente_plugin' => 'Cette page liste les plugins disponibles sur le site. Vous pouvez activer les plugins nécessaires en cochant la case correspondante.', # NEW
	'texte_proposer_publication' => 'Quando acabar o seu artigo, <br /> pode propor a sua publicação.', # MODIF
	'texte_proxy' => 'Em alguns caso (intranet, redes protegidas...),
 pode ser necessário utilizar um <i>proxy HTTP</i>  para atingir os sítios vinculados.
 Se for o caso, indique a seguir o endereço, sob a forma
 <tt><html>http://proxy:8080</html></tt>. Em geral,
 deixará esta caixa vazia.', # MODIF
	'texte_publication_articles_post_dates' => 'Que comportamento SPIP deve adoptar perante os artigos cuja
 data de publicação foi fixada para um prazo futuro ?',
	'texte_rappel_selection_champs' => '[Não esquecer de seleccionar correctamente este campo.]',
	'texte_recalcul_page' => 'Se quiser
recompor uma única página, passe pelo espaço público e utilize o botão «recompor».',
	'texte_recapitiule_liste_documents' => 'Esta página recapitula a lista dos documentos que colocou nas rubricas. Para modificar as informações de cada documento, siga o link para a página da rubrica.',
	'texte_recuperer_base' => 'Reparar a base de dados',
	'texte_reference_mais_redirige' => 'artigo referenciado no seu sítio SPIP, mas redirigido para uma outra URL.',
	'texte_requetes_echouent' => '<b>Quando alguns pedidos SQL falharem
 sistematicamente e sem zazão aparente, é possível
 que seja por causa da  própria base de dados
.</b><p>
 SQL dispõe de uma funcionalidade de reparação das suas tabelas quando foram acidententalmente
 danificadas. Pode tentar aqui essa reparação : se falhar, conserve uma cópia da exibição que talvez contenha
 indícios daquilo que não funciona...<p>
 Se o problema persistir, contacte com o seu serviço de hospedagem.', # MODIF
	'texte_selection_langue_principale' => 'Pode seleccionar a seguir a  « língua principal » do sítio. Esta opção não o obriga - felizmente ! - a escrever os seus artigos na língua seleccionada, mas permite determinar :
 <u><li> o formato por defeito das datas no sítio público ;</li>
 <li> a natureza do motor tipográfico que SPIP deve utilizar para a restituição dos textos ;</li>
  <li> a língua utilizada nos formulários do sítio público ;</li>
  <li> a língua apresentada por defeito no espaço privado.</li></ul> ',
	'texte_sous_titre' => 'Sub-título',
	'texte_statistiques_visites' => '(barras escuras : domingo / curva escura : evolução da média)',
	'texte_statut_attente_validation' => 'à espera de validação',
	'texte_statut_publies' => 'publicados em linha',
	'texte_statut_refuses' => 'recusados',
	'texte_suppression_fichiers' => 'Utilize este comando para suprimir todos os ficheiros presentes
na cache SPIP. Isso permite, por exemplo, obrigar uma recomposição de todas as páginas se você
 fez modificaçõesimportantes de grafismo ou de estrutura do sítio.',
	'texte_sur_titre' => 'Supra-título',
	'texte_table_ok' => ': esta tabela está OK.',
	'texte_tables_indexation_vides' => 'As tabelas de indexação do motor estão vazias.',
	'texte_tentative_recuperation' => 'Tentativa de reparação',
	'texte_tenter_reparation' => 'Tentar uma reparação da base de dados',
	'texte_test_proxy' => 'Para experimentar este proxy, indique aqui o endereço de um sítio Web
  que deseje testar;',
	'texte_titre_02' => 'Título',
	'texte_titre_obligatoire' => '<b>Título</b> [Obrigatório]', # MODIF
	'texte_travail_article' => '@nom_auteur_modif@ trabalhou sobre este artigo há @date_diff@ minutes',
	'texte_travail_collaboratif' => 'Se é frequente muitos redactores
 trabalharem no mesmo artigo, o sistema
 pode exibir os artigos recentemente « abertos »
a fim de evitar as modificações simultâneas.
  Esta opção está desactivada por defeito
  a fim de evitar exibir mensagens de aviso
 intempestivas.',
	'texte_trop_resultats_auteurs' => 'Demasiado resultados para "@cherche_auteur@" ; favor afinar a pesquisa.',
	'texte_unpack' => 'telecarregamento da última versão',
	'texte_utilisation_moteur_syndiques' => 'Quando utilizar o motor integrado de pesquisa 
  no SPIP, pode efectuar as pesquisas nos sítios e
 nos artigos vinculados de duas maneiras
 diferentes. <br /><img src=\'puce.gif\'> A mais
 simples consiste em pesquisar unicamente nos
 títulos e descrições dos artigos. <br /><img src=\'puce.gif\'>
  Um segundo método, muito mais poderoso, permite
ao SPIP pesquisar igualmente no texto dos
 sítios referenciados . Se referenciar
 um sítio, SPIP vai então efectuar a
 pesquisa no texto do próprio sítio.', # MODIF
	'texte_utilisation_moteur_syndiques_2' => 'Este método obriga SPIP a visitar
  regularmente os sítios referenciados,
  o que pode provocar uma pequena desaceleração do seu próprio sítio.',
	'texte_vide' => 'vazio',
	'texte_vider_cache' => 'Esvaziar a cache',
	'titre_admin_effacer' => 'Manutenção técnica',
	'titre_admin_tech' => 'Manutenção técnica',
	'titre_admin_vider' => 'Manutenção técnica',
	'titre_ajouter_un_auteur' => 'Ajouter un auteur', # NEW
	'titre_ajouter_un_mot' => 'Ajouter un mot-clé', # NEW
	'titre_ajouter_une_rubrique' => 'Ajouter une rubrique', # NEW
	'titre_cadre_afficher_article' => 'Exibir os artigos',
	'titre_cadre_afficher_traductions' => 'Exibir o estado das traduções para estas línguas:',
	'titre_cadre_ajouter_auteur' => 'ACRESCENTAR UM AUTOR :',
	'titre_cadre_interieur_rubrique' => 'Dentro da rubrica',
	'titre_cadre_numero_auteur' => 'AUTOR NÚMERO',
	'titre_cadre_numero_objet' => '@objet@ NUMÉRO :', # NEW
	'titre_cadre_signature_obligatoire' => '<b>Assinatura</b> [Obrigatório]<br />', # MODIF
	'titre_config_contenu_notifications' => 'Notifications', # NEW
	'titre_config_contenu_prive' => 'Dans l’espace privé', # NEW
	'titre_config_contenu_public' => 'Sur le site public', # NEW
	'titre_config_fonctions' => 'Configuração do sítio',
	'titre_config_langage' => 'Configurer la langue', # NEW
	'titre_configuration' => 'Configuração do sítio',
	'titre_configurer_preferences' => 'Configurer vos préférences', # NEW
	'titre_conflit_edition' => 'Conflit lors de l\'édition', # NEW
	'titre_connexion_ldap' => 'Opções: <b>Sua ligação LDAP</b>',
	'titre_groupe_mots' => 'GRUPO DE PALAVRAS :',
	'titre_identite_site' => 'Identité du site', # NEW
	'titre_langue_article' => 'LÍNGUA DO ARTIGO ', # MODIF
	'titre_langue_rubrique' => 'LÍNGUA DA RUBRICA', # MODIF
	'titre_langue_trad_article' => 'LÍNGUA E TRADUÇÕES DO ARTIGO',
	'titre_les_articles' => 'OS ARTIGOS',
	'titre_messagerie_agenda' => 'Messagerie et agenda', # NEW
	'titre_naviguer_dans_le_site' => 'Navegar no sítio',
	'titre_nouvelle_rubrique' => 'Nova rubrica',
	'titre_numero_rubrique' => 'RUBRICA NÚMERO :',
	'titre_page_admin_effacer' => 'Manutenção técnica : apagar a base',
	'titre_page_articles_edit' => 'Modificar : @titre@',
	'titre_page_articles_page' => 'Os artigos',
	'titre_page_articles_tous' => 'Todo o sítio',
	'titre_page_auteurs' => 'Visitantes',
	'titre_page_calendrier' => 'Calendário @nom_mois@ @annee@',
	'titre_page_config_contenu' => 'Configuração do sítio',
	'titre_page_config_fonctions' => 'Configuração do sítio',
	'titre_page_configuration' => 'Configuração do sítio',
	'titre_page_controle_petition' => 'Acompanhamento dos abaixo-assinados',
	'titre_page_delete_all' => 'Supressão total e irreversível',
	'titre_page_documents_liste' => 'Os documentos das rubricas',
	'titre_page_index' => 'O seu espaço privado',
	'titre_page_message_edit' => 'Redigir uma mensagem',
	'titre_page_messagerie' => 'O seu correio',
	'titre_page_recherche' => 'Resultados da pesquisa @recherche@',
	'titre_page_statistiques_referers' => 'Estatísticas (ligações de entrada)',
	'titre_page_statistiques_signatures_jour' => 'Nombre de signatures par jour', # NEW
	'titre_page_statistiques_signatures_mois' => 'Nombre de signatures par mois', # NEW
	'titre_page_upgrade' => 'Reactualização de SPIP',
	'titre_publication_articles_post_dates' => 'Publicação dos artigos pós-datados',
	'titre_referencer_site' => 'Referenciar o sítio',
	'titre_rendez_vous' => 'ENCONTROS',
	'titre_reparation' => 'Reparação',
	'titre_suivi_petition' => 'Seguimento dos abaixo-assinados',
	'tls_ldap' => 'Transport Layer Security :', # NEW
	'trad_article_inexistant' => 'Não há artigo com este número',
	'trad_article_traduction' => 'Todas as versões deste artigo :',
	'trad_deja_traduit' => 'Este artigo é já uma tradução do presente artigo.', # MODIF
	'trad_delier' => 'Não voltar a ligar este artigo a estas traduções', # MODIF
	'trad_lier' => 'Este artigo é uma tradução do artigo número :',
	'trad_new' => 'Escrever uma nova tradução deste artigo', # MODIF

	// U
	'upload_info_mode_document' => 'Déposer cette image dans le portfolio', # NEW
	'upload_info_mode_image' => 'Retirer cette image du portfolio', # NEW
	'utf8_convert_attendez' => 'Attendez quelques instants et rechargez cette page.', # NEW
	'utf8_convert_avertissement' => 'Vous vous apprêtez à convertir le contenu de votre base de données (articles, brèves, etc) du jeu de caractères <b>@orig@</b> vers le jeu de caractères <b>@charset@</b>.', # NEW
	'utf8_convert_backup' => 'N\'oubliez pas de faire auparavant une sauvegarde complète de votre site. Vous devrez aussi vérifier que vos squelettes et fichiers de langue sont compatibles @charset@.', # NEW
	'utf8_convert_erreur_deja' => 'Votre site est déjà en @charset@, inutile de le convertir...', # NEW
	'utf8_convert_erreur_orig' => 'Erreur : le jeu de caractères @charset@ n\'est pas supporté.', # NEW
	'utf8_convert_termine' => 'C\'est terminé !', # NEW
	'utf8_convert_timeout' => '<b>Important :</b> en cas de <i>timeout</i> du serveur, veuillez recharger la page jusqu\'à ce qu\'elle indique « terminé ».', # NEW
	'utf8_convert_verifier' => 'Vous devez maintenant aller vider le cache, et vérifier que tout se passe bien sur les pages publiques du site. En cas de gros problème, une sauvegarde de vos données a été réalisée (au format SQL) dans le répertoire @rep@.', # NEW
	'utf8_convertir_votre_site' => 'Convertir votre site en utf-8', # NEW

	// V
	'version' => 'Version :' # NEW
);

?>
