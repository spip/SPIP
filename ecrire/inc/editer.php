<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2008                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined("_ECRIRE_INC_VERSION")) return;
include_spip('base/abstract_sql');

// http://doc.spip.org/@formulaires_editer_objet_traiter
function formulaires_editer_objet_traiter($type, $id='new', $id_parent=0, $lier_trad=0, $retour='', $config_fonc='articles_edit_config', $row=array(), $hidden=''){

	$message = "";
	$action_editer = charger_fonction("editer_$type",'action');
	list($id,$err) = $action_editer();
	if ($err){
		$message .= $err;
	}
	elseif ($retour) {
		include_spip('inc/headers');
		$id_table_objet = id_table_objet($type);
		$message .= redirige_formulaire(parametre_url($retour,$id_table_objet,$id));
	}
	return $message;
}

// http://doc.spip.org/@formulaires_editer_objet_verifier
function formulaires_editer_objet_verifier($type,$id='new', $oblis = array()){
	$erreurs = array();
	if (intval($id)) {
		$conflits = controler_contenu($type,$id);
		if (count($conflits)) {
			foreach($conflits as $champ=>$conflit){
				$erreurs[$champ] .= _T("alerte_modif_info_concourante")."<br /><textarea readonly='readonly' class='forml'>".$conflit['base']."</textarea>";
			}
		}
	}
	foreach($oblis as $obli){
		if (!_request($obli))
			$erreurs[$obli] .= _T("info_obligatoire");
	}
	return $erreurs;
}

// http://doc.spip.org/@formulaires_editer_objet_charger
function formulaires_editer_objet_charger($type, $id='new', $id_parent=0, $lier_trad=0, $retour='', $config_fonc='articles_edit_config', $row=array(), $hidden=''){
	$table_objet = table_objet($type);
	$table_objet_sql = table_objet_sql($type);
	$id_table_objet = id_table_objet($type);
	$new = $id;
	// Appel direct dans un squelette
	if (!$row) {
		if ($select = charger_fonction($type."_select",'inc',true)){
			$row = $select($id, $id_parent, $lier_trad);
		}
		else {
			$row = sql_fetsel('*',$table_objet_sql,$id_table_objet."=".intval($id));
		}
		if (is_numeric($id)) $new = '';
		else $new = $id;
		if (!$row) {
			$trouver_table = charger_fonction('trouver_table','base');
			if ($desc = base_trouver_table_dist($table_objet))
				foreach($desc['field'] as $k=>$v)
					$row[$k]='';
		}
	}
	// Gaffe: sans ceci, on ecrase systematiquement l'article d'origine
	// (et donc: pas de lien de traduction)
	$id = ($new OR $lier_trad) ? 'oui' : $row[$id_table_objet];
	

	$contexte = $row;
	if ($id_parent && (!isset($contexte['id_parent']) OR $new))
		$contexte['id_parent']=$id_parent;
	$contexte['config'] = $config = $config_fonc($row);
	$att_text = " class='formo' "
	. $GLOBALS['browser_caret']
	. " rows='"
	. ($config['lignes'] +15)
	. "' cols='40'";
	list($contexte['texte'],$contexte['_texte_trop_long']) = editer_texte_recolle($contexte['texte'],$att_text);

	// on veut conserver la langue de l'interface ;
	// on passe cette donnee sous un autre nom, au cas ou le squelette
	// voudrait l'exploiter
	if (isset($contexte['lang'])) {
		$contexte['langue'] = $contexte['lang'];
		unset($contexte['lang']);
	}

	$contexte['browser_caret']=$GLOBALS['browser_caret'];

	$contexte['_hidden'] = "<input type='hidden' name='editer_$type' value='oui' />\n" .
		 (!$lier_trad ? '' :
		 ("\n<input type='hidden' name='lier_trad' value='" .
		  $lier_trad .
		  "' />" .
		  "\n<input type='hidden' name='changer_lang' value='" .
		  $config['langue'] .
		  "' />")) 
		  . $hidden;

	// Ajouter le controles md5
	if (intval($id)) {
		$contexte['_hidden'] .= controles_md5($row);
	}
	if (isset($contexte['extra']))
		$contexte['extra'] = unserialize($contexte['extra']);

	// preciser que le formulaire doit passer dans un pipeline
	$contexte['_pipeline'] = array('editer_contenu_objet','args'=>array('type'=>$type,'id'=>$id,'contexte'=>$contexte));
	// preciser que le formulaire doit etre securise auteur/action
	$contexte['_action'] = array("editer_$type",$id);

	return $contexte;
}

//
// Gestion des textes trop longs (limitation brouteurs)
// utile pour les textes > 32ko

// http://doc.spip.org/@coupe_trop_long
function coupe_trop_long($texte){
	$aider = charger_fonction('aider', 'inc');
	if (strlen($texte) > 28*1024) {
		$texte = str_replace("\r\n","\n",$texte);
		$pos = strpos($texte, "\n\n", 28*1024);	// coupe para > 28 ko
		if ($pos > 0 and $pos < 32 * 1024) {
			$debut = substr($texte, 0, $pos)."\n\n<!--SPIP-->\n";
			$suite = substr($texte, $pos + 2);
		} else {
			$pos = strpos($texte, " ", 28*1024);	// sinon coupe espace
			if (!($pos > 0 and $pos < 32 * 1024)) {
				$pos = 28*1024;	// au pire (pas d'espace trouv'e)
				$decalage = 0; // si y'a pas d'espace, il ne faut pas perdre le caract`ere
			} else {
				$decalage = 1;
			}
			$debut = substr($texte,0,$pos + $decalage); // Il faut conserver l'espace s'il y en a un
			$suite = substr($texte,$pos + $decalage);
		}
		return (array($debut,$suite));
	}
	else
		return (array($texte,''));
}

// http://doc.spip.org/@editer_texte_recolle
function editer_texte_recolle($texte, $att_text)
{
	if ((strlen($texte)<29*1024)
	 OR (include_spip('inc/layer') AND ($GLOBALS['browser_name']!="MSIE")) )
	 return array($texte,"");
	 
	include_spip('inc/barre');
	$textes_supplement = "<br /><span style='color: red'>"._T('info_texte_long')."</span>\n";
	$nombre = 0;

	while (strlen($texte)>29*1024) {
		$nombre ++;
		list($texte1,$texte) = coupe_trop_long($texte);
		$id = "document.getElementById('texte$nombre')";
		$textes_supplement .= "<br />" . afficher_barre($id) .
			"<textarea id='texte$nombre' name='texte_plus[$nombre]'$att_text>$texte1</textarea>\n";
		}
	return array($texte,$textes_supplement);
}


// Produit la liste des md5 d'un tableau de donnees, sous forme
// de inputs html
// http://doc.spip.org/@controles_md5
function controles_md5($data, $prefixe='ctr_', $format='html'){
	if (!is_array($data))
		return false;

	$ctr = array();
	foreach ($data as $key => $val) {
		$m = md5($val);
		$k = $prefixe.$key;

		switch ($format) {
			case 'html':
				$ctr[$k] = "<input type='hidden' value='$m' name='$k' />";
				break;
			default:
				$ctr[$k] = $m;
				break;
		}
	}

	if ($format == 'html')
		return "\n\n<!-- controles md5 -->\n".join("\n", $ctr)."\n\n";
	else
		return $ctr;
}

// http://doc.spip.org/@controler_contenu
function controler_contenu($type, $id, $options=array(), $c=false, $serveur='') {
	include_spip('inc/filtres');

	$table_objet = table_objet($type);
	$spip_table_objet = table_objet_sql($type);
	$id_table_objet = id_table_objet($type);
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($table_objet, $serveur);

	// Appels incomplets (sans $c)
	if (!is_array($c)) {
		foreach($desc['field'] as $champ=>$ignore)
			if(_request($champ))
				$c[$champ] = _request($champ);
	}

	// Securite : certaines variables ne sont jamais acceptees ici
	// car elles ne relevent pas de autoriser(article, modifier) ;
	// il faut passer par instituer_XX()
	// TODO: faut-il passer ces variables interdites
	// dans un fichier de description separe ?
	unset($c['statut']);
	unset($c['id_parent']);
	unset($c['id_rubrique']);
	unset($c['id_secteur']);

	// Gerer les champs non vides
	if (is_array($options['nonvide']))
	foreach ($options['nonvide'] as $champ => $sinon)
		if ($c[$champ] === '')
			$c[$champ] = $sinon;

	// N'accepter que les champs qui existent
	// TODO: ici aussi on peut valider les contenus
	// en fonction du type
	$champs = array();
	foreach($desc['field'] as $champ => $ignore)
		if (isset($c[$champ]))
			$champs[$champ] = $c[$champ];

	// Nettoyer les valeurs
	$champs = array_map('corriger_caracteres', $champs);

	// recuperer les extras (utilise $_POST, un peu sale...
	// a voir pour le faire marcher avec les crayons)
	if (isset($desc['field']['extra'])
	AND isset($_POST['extra'])
	AND $GLOBALS['champs_extra']) {
		include_spip('inc/extra');
		$extra = extra_update($table_objet, $id, $_POST);
		if ($extra !== false)
			$champs['extra'] = $extra;
	}

	// Envoyer aux plugins
	$champs = pipeline('pre_edition',
		array(
			'args' => array(
				'table' => $spip_table_objet,
				'id_objet' => $id,
				'champs' => $options['champs']
			),
			'data' => $champs
		)
	);

	if (!$champs) return false;

	// Verifier si les mises a jour sont pertinentes, datees, en conflit etc
	$conflits = controler_md5($champs, $_POST, $type, $id, $serveur);
	return $conflits;
}

// Controle la liste des md5 envoyes, supprime les inchanges,
// signale les modifies depuis telle date
// http://doc.spip.org/@controler_md5
function controler_md5(&$champs, $ctr, $type, $id, $serveur) {
	$table_objet = table_objet($type);
	$spip_table_objet = table_objet_sql($type);
	$id_table_objet = id_table_objet($type);

	// Controle des MD5 envoyes
	// On elimine les donnees non modifiees par le formulaire (mais
	// potentiellement modifiees entre temps par un autre utilisateur)
	foreach ($champs as $key => $val) {
		if ($m = _request('ctr_'.$key)) {
			if ($m == md5($val))
				unset ($champs[$key]);
		}
	}
	if (!$champs) return;

	// On veut savoir si notre modif va avoir un impact ; en mysql
	// on pourrait employer mysql_affected_rows() mais pas en multi-base
	// donc on fait autrement, avec verification prealable
	// On utilise md5 pour eviter la casse (en SQL: 'SPIP'='spip')
	$verifier = array();
	foreach ($champs as $ch => $val)
		$verifier[] = "($ch IS NULL OR MD5($ch)!=".sql_quote(md5($val)).")";
	if (!sql_countsel($spip_table_objet, "($id_table_objet=$id) AND (" . join(' OR ',$verifier). ")",
	null,null,null,$serveur))
		return;

	// Detection de conflits :
	// On verifie si notre modif ne provient pas d'un formulaire
	// genere a partir de donnees modifiees dans l'intervalle ; ici
	// on compare a ce qui est dans la base, et on bloque en cas
	// de conflit.
	$ctr = $ctrq = $conflits = array();
	foreach (array_keys($champs) as $key) {
		if ($m = _request('ctr_'.$key)) {
			$ctr[$key] = $m;
			$ctrq[] = $key;
			$ctrq[] = "md5($key) AS ctrq_$key";
		}
	}
	if ($ctrq) {
		$ctrq = sql_fetsel($ctrq, $spip_table_objet, "$id_table_objet=$id", $serveur);
		foreach ($ctr as $key => $m) {
			if ($m != $ctrq['ctrq_'.$key]
			AND $champs[$key] !== $ctrq[$key]
			AND $ctrq['ctrq_'.$key] !== null) {
				$conflits[$key] = array(
					'base' => $ctrq[$key],
					'post' => $champs[$key]
				);
				unset($champs[$key]); # stocker quand meme les modifs ?
			}
		}
	}

	return $conflits;
}

// http://doc.spip.org/@display_conflit_champ
function display_conflit_champ($x) {
	if (strstr($x, "\n") OR strlen($x)>80)
		return "<textarea style='width:99%; height:10em;'>".entites_html($x)."</textarea>\n";
	else
		return "<input type='text' size='40' style='width:99%' value=\"".entites_html($x)."\" />\n";
}

// http://doc.spip.org/@signaler_conflits_edition
function signaler_conflits_edition($conflits, $redirect='') {
	include_spip('inc/minipres');
	include_spip('inc/revisions');
	include_spip('inc/suivi_versions');
	include_spip('inc/diff');
	foreach ($conflits as $champ=>$a) {
		$diff = new Diff(new DiffTexte);
		$n = preparer_diff($a['post']);
		$o = preparer_diff($a['base']);
		$d = propre_diff(
			afficher_para_modifies(afficher_diff($diff->comparer($n,$o))));
		$diffs[] = "<h2>$champ</h2>\n"
			. "<h3>"._L('Diff&#233;rences&nbsp;:')."</h3>\n"
			. "<div style='max-height:8em; overflow: auto; width:99%;'>".$d."</div>\n"
			. "<h4>"._L('Votre version&nbsp;:')."</h4>"
			. display_conflit_champ($a['post'])
			. "<h4>"._L('La version enregistr&#233;e&nbsp;:')."</h4>"
			. display_conflit_champ($a['base']);
	}

	if ($redirect) {
		$id = uniqid();
		$redirect = "<form action='$redirect' method='get'
			id='$id'
			style='float:".$GLOBALS['spip_lang_right']."; margin-top:2em;'>\n"
		.form_hidden($redirect)
		."<input type='submit' value='"._T('icone_retour')."' />
		</form>\n";

		// pour les documents, on est probablement en ajax : il faut ajaxer
		if (_AJAX)
			$redirect .= '<script type="text/javascript">'
			.'setTimeout(function(){$("#'.$id.'")
			.ajaxForm({target:$("#'.$id.'").parent()});
			}, 200);'
			."</script>\n";

	}

	echo minipres(
		_L('Conflit lors de l\'&#233;dition'),

		'<style>
.diff-para-deplace { background: #e8e8ff; }
.diff-para-ajoute { background: #d0ffc0; color: #000; }
.diff-para-supprime { background: #ffd0c0; color: #904040; text-decoration: line-through; }
.diff-deplace { background: #e8e8ff; }
.diff-ajoute { background: #d0ffc0; }
.diff-supprime { background: #ffd0c0; color: #802020; text-decoration: line-through; }
.diff-para-deplace .diff-ajoute { background: #b8ffb8; border: 1px solid #808080; }
.diff-para-deplace .diff-supprime { background: #ffb8b8; border: 1px solid #808080; }
.diff-para-deplace .diff-deplace { background: #b8b8ff; border: 1px solid #808080; }
</style>'
		.'<p>'._L('Attention, les champs suivants ont &#233;t&#233; modifi&#233;s par ailleurs. Vos modifications sur ces champs n\'ont donc pas &#233;t&#233; enregistr&#233;es.').'</p>'
		.'<p>'._L('Veuillez contr&#244;ler ci-dessous les diff&#233;rences entre les deux versions du texte&nbsp;; vous pouvez aussi copier vos modifications, puis recommencer.').'</p>'
		."<div style='text-align:".$GLOBALS['spip_lang_left'].";'>"
		. join("\n",$diffs)
		."</div>\n"
		
		. $redirect
	);
}

?>
