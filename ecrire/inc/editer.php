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


// Produit la liste des md5 d'un tableau de donnees, sous forme
// de inputs html
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

// Controle la liste des md5 envoyes, supprime les inchanges,
// signale les modifies depuis telle date
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

function display_conflit_champ($x) {
	if (strstr($x, "\n") OR strlen($x)>80)
		return "<textarea style='width:99%; height:10em;'>".entites_html($x)."</textarea>\n";
	else
		return "<input type='text' size='40' style='width:99%' value=\"".entites_html($x)."\" />\n";
}

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
		if (_request('var_ajaxcharset'))
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
