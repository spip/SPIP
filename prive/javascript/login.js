function affiche_login_secure() {
	if (alea_actuel)
		jQuery('#pass_securise').show();
	else
		jQuery('#pass_securise').hide();
}

function informe_auteur(c){
	informe_auteur_en_cours = false;
	eval('c = '+c); // JSON envoye par informer_auteur.html
	if (c) {
		alea_actuel = c.alea_actuel;
		alea_futur = c.alea_futur;
		// indiquer le cnx si on n'y a pas touche
		jQuery('input#session_remember:not(.modifie)')
		.attr('checked',(c.cnx=='1')?'checked':'');
	} else {
		alea_actuel = '';
	}
	if (c.logo)
		jQuery('#spip_logo_auteur').html(c.logo);
	else
		jQuery('#spip_logo_auteur').html('');
	affiche_login_secure();
}

function calcule_hash_pass(pass){
	if ((alea_actuel || alea_futur)
		&& !pass.match(/^\{([0-9a-f]{32});([0-9a-f]{32})\}$/i)
		&& !pass.match(/^\{([0-9a-f]{64});([0-9a-f]{64});([0-9a-f]{32});([0-9a-f]{32})\}$/i)
		&& sha256_self_test() // verifions que le hash sha est operationnel
	) {
		var hash = "";
		hash = hex_sha256(alea_actuel + pass);

		hash = hash+';'+hex_sha256(alea_futur + pass);
		// envoyer aussi le md5 si demande (compatibilite)
		if (window.calcMD5){
			hash = hash+';'+calcMD5(alea_actuel + pass);
			hash = hash+';'+calcMD5(alea_futur + pass);
		}

		jQuery('input[name=password]').attr('value','{'+hash+'}');
	}
}

function actualise_auteur(){
	if (login != jQuery('#var_login').attr('value')) {
		informe_auteur_en_cours = true;
		login = jQuery('#var_login').attr('value');
		var currentTime = new Date();// on passe la date en var pour empecher la mise en cache de cette requete (bug avec FF3 & IE7)
		jQuery.get(page_auteur, {var_login:login,var_compteur:currentTime.getTime()},informe_auteur);
	}
}

function login_submit(){
	actualise_auteur();
	var inputpass = jQuery('input[name=password]');
	pass = inputpass.attr('value');
	// ne pas laisser le pass d'un auteur "auth=spip" circuler en clair
	if (pass) {
		// si l'information est en cours, retenter sa chance
		// pas plus de 5 fois (si profondeur_url fausse, la requete d'information echoue et ne repond jamais)
		if (informe_auteur_en_cours && (attente_informe<5)) { 
			attente_informe++;
			jQuery('form#formulaire_login').animeajax().find('p.boutons input').before('.'); // montrer qu'il se passe quelque chose
			setTimeout(function(){
				jQuery('form#formulaire_login').submit();
			}, 1000);
			return false;
		}

		// Si on a l'alea, on peut lancer le submit apres avoir hashe le pass
		if (alea_actuel || alea_futur) {
			// il ne faut pas injecter le pass hashe directement dans l'input password visible car
			// - cela est perturbant
			// - certains navigateurs memorisent le hash au lieu du pass ...
			// on cree un input hidden a cote, on lui met le name="password"
			// et on vide le champ visible
			inputpass.after('<input name="password" type="hidden" value="" />').attr('value',pass);
			inputpass.attr('name','nothing').attr('value','');
			calcule_hash_pass(pass);
		}
		// si on arrive pas a avoir une reponse ajax, vider le pass pour forcer un passage en 2 fois
		else if(informe_auteur_en_cours)
			jQuery('input[name=password]').attr('value','');
		// sinon c'est que l'auteur n'existe pas
		// OU qu'il sera accepte par LDAP ou autre auth avec mot de passe en clair
	}
}
